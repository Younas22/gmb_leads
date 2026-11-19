<?php

namespace App\Jobs;

use App\Models\ConnectedEmailAccount;
use App\Models\EmailAuditLog;
use App\Models\EmailSendAttempt;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Models\User;
use App\Models\UserSalesProfile;
use App\Services\Gmail\GmailApi;
use App\Services\Gmail\GmailException;
use App\Services\Gmail\GmailMessageBuilder;
use App\Services\Gmail\GmailTokenManager;
use App\Services\EmailTracking\EventRecorder;
use App\Services\EmailTracking\LeadSync;
use App\Services\PlanFeatures;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Sends ONE user-confirmed email through the sender's own Gmail.
 *
 * Safety rules:
 *  - the row is claimed atomically (pending -> sending), so a duplicate job or a
 *    second worker can never send it twice;
 *  - only failures where Gmail clearly did NOT send are retried automatically;
 *  - an ambiguous outcome (timeout after sending, 5xx) is marked failed with
 *    outcome_unknown and is never retried without the user's confirmation.
 */
class SendGmailMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries;
    public int $timeout;
    public bool $failOnTimeout = true;

    public function __construct(public readonly int $outboundEmailId)
    {
        $this->tries = (int) config('gmail.queue.tries', 3);
        $this->timeout = (int) config('gmail.queue.timeout', 60);
    }

    /** @return array<int> */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(GmailTokenManager $tokens, GmailApi $api, GmailMessageBuilder $builder): void
    {
        // Atomic claim: only one worker can move pending -> sending
        $claimed = OutboundEmail::whereKey($this->outboundEmailId)
            ->where('provider', OutboundEmail::PROVIDER_GMAIL) // Phase 5: never touch Resend rows
            ->where('status', OutboundEmail::STATUS_PENDING)
            ->update(['status' => OutboundEmail::STATUS_SENDING, 'updated_at' => now()]);
        if (!$claimed) {
            return; // already sent, cancelled, failed or being sent
        }

        $email = OutboundEmail::findOrFail($this->outboundEmailId);
        $account = ConnectedEmailAccount::find($email->connected_account_id);
        $sender = User::find($email->user_id);

        // Re-check at send time
        if (!$account || !$sender || (int) $account->user_id !== (int) $sender->id) {
            $this->fail_($email, 'sender_unavailable', 'The sending Gmail account is no longer connected.', null);
            return;
        }
        if (mb_strtolower((string) $account->email) !== mb_strtolower((string) $email->from_email)) {
            $this->fail_($email, 'sender_changed', 'A different Gmail account is connected now. Review and send again.', null);
            return;
        }
        if ((int) $sender->getAccountOwner()?->id !== (int) $email->owner_user_id) {
            $this->fail_($email, 'sender_unavailable', 'The sender is no longer part of this account.', null);
            return;
        }
        if (!PlanFeatures::enabled($sender, 'gmail_sending_enabled')) {
            $this->fail_($email, 'plan', 'Your plan no longer includes sending through Gmail.', null);
            return;
        }
        if (EmailSuppression::isSuppressed($email->owner_user_id, $email->to_email)) {
            $this->fail_($email, 'suppressed', 'The recipient unsubscribed before the email was sent.', null);
            return;
        }

        // Gentle pacing per Gmail account
        if (!RateLimiter::attempt('gmail-send:' . $account->id, (int) config('gmail.per_minute_per_account', 5), fn () => true, 60)) {
            $email->forceFill(['status' => OutboundEmail::STATUS_PENDING])->save();
            $this->release(30);
            return;
        }

        $profile = UserSalesProfile::where('owner_user_id', $email->owner_user_id)->first();
        $message = $builder->build(
            $profile?->sender_name ?: $sender->name,
            $account->email,
            $email->to_email,
            $email->subject,
            $email->bodyToSend(), // fixed when queued (tracked links included when enabled)
            app(\App\Services\Gmail\OutboundEmailService::class)->unsubscribeUrl($email),
            $account->email,
        );

        $attemptNo = $email->attempts()->count() + 1;
        $started = microtime(true);

        try {
            try {
                $result = $api->send($tokens->accessToken($account), $message['raw']);
            } catch (GmailException $e) {
                // 401 = token rejected before sending: refresh once and try again (safe)
                if ($e->reason !== GmailException::AUTH) {
                    throw $e;
                }
                $result = $api->send($tokens->accessToken($account->fresh(), true), $message['raw']);
            }
        } catch (GmailException $e) {
            $this->recordAttempt($email, $attemptNo, $e->isAmbiguous() ? 'unknown' : 'failed', $e->httpStatus, $e->reason, $started);

            if ($e->needsReconnect()) {
                $tokens->markNeedsReconnect($account, $e->getMessage());
            }

            if ($e->isRetryable() && $this->attempts() < $this->tries) {
                $email->forceFill(['status' => OutboundEmail::STATUS_PENDING, 'error_code' => $e->reason, 'error_message' => $e->getMessage()])->save();
                $this->release($this->backoff()[min($this->attempts() - 1, 1)]);
                return;
            }

            $this->fail_($email, $e->reason, $e->getMessage(), $e->isAmbiguous());
            return;
        } catch (Throwable $e) {
            // Unexpected error after claiming: we cannot be sure nothing was sent
            $this->recordAttempt($email, $attemptNo, 'unknown', null, 'exception', $started);
            Log::error('Gmail send crashed', ['outbound_email_id' => $email->id, 'exception' => get_class($e)]);
            $this->fail_($email, GmailException::AMBIGUOUS, (new GmailException(GmailException::AMBIGUOUS))->getMessage(), true);
            return;
        }

        $this->recordAttempt($email, $attemptNo, 'success', 200, null, $started);
        $email->forceFill([
            'status' => OutboundEmail::STATUS_SENT,
            'gmail_message_id' => mb_substr($result['id'], 0, 64),
            'gmail_thread_id' => $result['threadId'] ? mb_substr($result['threadId'], 0, 64) : null,
            'rfc_message_id' => $message['message_id'],
            'mime_sha256' => hash('sha256', $message['raw']),
            'sent_at' => now(),
            'error_code' => null,
            'error_message' => null,
            'outcome_unknown' => false,
        ])->save();
        $account->forceFill(['last_used_at' => now()])->save();

        EmailAuditLog::record($email->owner_user_id, $email->user_id, 'sent', $email->id, ['gmail_message_id' => $email->gmail_message_id]);

        // Phase 4 bookkeeping. Best-effort only: the email is already sent, so nothing
        // here may throw (an exception would make the queue treat the job as failed).
        try {
            EventRecorder::forEmail($email, EmailTrackingEvent::ACCEPTED, EmailTrackingEvent::SOURCE_GMAIL, 'accepted:' . $email->id, $email->user_id, [], $email->sent_at);
            LeadSync::markContacted($email->lead);
        } catch (Throwable $e) {
            Log::warning('Post-send bookkeeping failed', ['outbound_email_id' => $email->id, 'exception' => get_class($e)]);
        }
    }

    /** Queue gave up (crash / timeout). If it was mid-send we cannot know the outcome. */
    public function failed(?Throwable $e): void
    {
        $email = OutboundEmail::find($this->outboundEmailId);
        if (!$email) {
            return;
        }
        if ($email->status === OutboundEmail::STATUS_SENDING) {
            $this->fail_($email, GmailException::AMBIGUOUS, (new GmailException(GmailException::AMBIGUOUS))->getMessage(), true);
        } elseif ($email->status === OutboundEmail::STATUS_PENDING) {
            $this->fail_($email, 'job_failed', 'The email could not be sent. You can retry it.', false);
        }
    }

    /** @param bool|null $unknown true = may have been sent (keeps the draft lock) */
    private function fail_(OutboundEmail $email, string $code, string $message, ?bool $unknown): void
    {
        $email->forceFill([
            'status' => OutboundEmail::STATUS_FAILED,
            'outcome_unknown' => (bool) $unknown,
            // Known failure: release the draft so it can be sent again; unknown: keep it locked
            'draft_lock' => $unknown ? $email->draft_lock : null,
            'error_code' => mb_substr($code, 0, 40),
            'error_message' => mb_substr($message, 0, 255),
        ])->save();

        EmailAuditLog::record($email->owner_user_id, $email->user_id, 'send_failed', $email->id, ['code' => $code, 'outcome_unknown' => (bool) $unknown]);

        // Gmail answered (or failed to answer) the request; checks we made ourselves are "system"
        $fromGmail = $unknown !== null;
        EventRecorder::forEmail($email, $unknown ? EmailTrackingEvent::UNKNOWN : EmailTrackingEvent::FAILED,
            $fromGmail ? EmailTrackingEvent::SOURCE_GMAIL : EmailTrackingEvent::SOURCE_SYSTEM,
            EventRecorder::failureKey($email), $email->user_id, ['code' => $code]);
    }

    private function recordAttempt(OutboundEmail $email, int $no, string $result, ?int $status, ?string $code, float $started): void
    {
        EmailSendAttempt::create([
            'outbound_email_id' => $email->id,
            'attempt_no' => $no,
            'result' => $result,
            'http_status' => $status,
            'error_code' => $code,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ]);
    }
}
