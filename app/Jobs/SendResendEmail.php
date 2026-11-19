<?php

namespace App\Jobs;

use App\Models\EmailAuditLog;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Models\ResendIntegration;
use App\Models\User;
use App\Services\EmailTracking\EventRecorder;
use App\Services\EmailTracking\LeadSync;
use App\Services\PlanFeatures;
use App\Services\Resend\ResendConnectionService;
use App\Services\Resend\ResendException;
use App\Services\Resend\ResendSendService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends ONE confirmed single email through the customer's Resend account.
 * Same safety model as Gmail: atomic pending->sending claim, every check repeated
 * right before the API call, ambiguous outcomes never retried automatically.
 * The Idempotency-Key header (24 h at Resend) adds a second guard against duplicates.
 */
class SendResendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries;
    public int $timeout;
    public bool $failOnTimeout = true;

    public function __construct(public readonly int $outboundEmailId)
    {
        $this->tries = (int) config('resend_user.queue.tries', 3);
        $this->timeout = (int) config('resend_user.queue.timeout', 75);
    }

    public function handle(ResendSendService $service): void
    {
        $claimed = OutboundEmail::whereKey($this->outboundEmailId)
            ->where('provider', OutboundEmail::PROVIDER_RESEND)->whereNull('campaign_id')
            ->where('status', OutboundEmail::STATUS_PENDING)
            ->update(['status' => OutboundEmail::STATUS_SENDING, 'updated_at' => now()]);
        if (!$claimed) {
            return;
        }

        $email = OutboundEmail::findOrFail($this->outboundEmailId);
        $integration = ResendIntegration::find($email->resend_integration_id);
        $sender = $email->resendSender()->with('domain')->first();
        $user = User::find($email->user_id);

        if (!$integration || !$integration->isUsable() || (int) $integration->owner_user_id !== (int) $email->owner_user_id) {
            $this->fail_($email, 'resend_unavailable', 'Resend is no longer connected.', null, $service);
            return;
        }
        if (!$sender || !$sender->canSend() || (int) $sender->owner_user_id !== (int) $email->owner_user_id || mb_strtolower($sender->from_email) !== mb_strtolower($email->from_email)) {
            $this->fail_($email, 'sender_unavailable', 'The sender or its domain is no longer verified.', null, $service);
            return;
        }
        if (!$user || (int) $user->getAccountOwner()?->id !== (int) $email->owner_user_id || !PlanFeatures::enabled($user, 'resend_single_sending_enabled')) {
            $this->fail_($email, 'plan', 'Sending through Resend is no longer available for this account.', null, $service);
            return;
        }
        // Opted-in contact still sendable, or (no contact) an allowed one-to-one email to the saved lead;
        // the do-not-email list always wins.
        if ($problem = $service->recipientProblem($email)) {
            $this->fail_($email, $problem[0], $problem[1], null, $service);
            return;
        }

        $unsubscribe = $service->unsubscribeUrl($email);
        $payload = array_filter([
            'from' => $sender->fromHeader(),
            'to' => [$email->to_email],
            'subject' => $email->subject,
            'text' => $email->bodyToSend(),
            'reply_to' => $sender->reply_to ?: null,
            'headers' => ['List-Unsubscribe' => '<' . $unsubscribe . '>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'],
            'tags' => [['name' => 'cnm_email', 'value' => (string) $email->id]],
        ]);

        try {
            $result = ResendConnectionService::client($integration)->sendEmail($payload, 'cnm-' . $email->idempotency_key);
        } catch (ResendException $e) {
            if ($e->reason === ResendException::INVALID_KEY) {
                app(ResendConnectionService::class)->markProblem($integration, $e);
            }
            if ($e->isRetryable() && $this->attempts() < $this->tries) {
                $email->forceFill(['status' => OutboundEmail::STATUS_PENDING, 'error_code' => $e->reason])->save();
                $this->release(60);
                return;
            }
            $unknown = $e->isAmbiguous() || $e->reason === ResendException::CONFLICT;
            $this->fail_($email, $e->reason, $unknown ? 'Resend did not confirm the result. Check Emails in your Resend dashboard before retrying.' : $e->getMessage(), $unknown, $service);
            return;
        } catch (Throwable $e) {
            Log::error('Resend send crashed', ['outbound_email_id' => $email->id, 'exception' => get_class($e)]);
            $this->fail_($email, 'exception', 'Resend did not confirm the result. Check Emails in your Resend dashboard before retrying.', true, $service);
            return;
        }

        $email->forceFill([
            'status' => OutboundEmail::STATUS_SENT,
            'provider_message_id' => mb_substr((string) ($result['id'] ?? ''), 0, 64) ?: null,
            'sent_at' => now(),
            'error_code' => null,
            'error_message' => null,
            'outcome_unknown' => false,
        ])->save();
        EmailAuditLog::record($email->owner_user_id, $email->user_id, 'resend_sent', $email->id, ['provider_message_id' => $email->provider_message_id]);

        try {
            EventRecorder::forEmail($email, EmailTrackingEvent::ACCEPTED, EmailTrackingEvent::SOURCE_RESEND_API, 'accepted:' . $email->id, $email->user_id, [], $email->sent_at);
            LeadSync::markContacted($email->lead);
        } catch (Throwable $e) {
            Log::warning('Post-send bookkeeping failed', ['outbound_email_id' => $email->id, 'exception' => get_class($e)]);
        }
    }

    public function failed(?Throwable $e): void
    {
        $email = OutboundEmail::find($this->outboundEmailId);
        if (!$email) {
            return;
        }
        $service = app(ResendSendService::class);
        if ($email->status === OutboundEmail::STATUS_SENDING) {
            $this->fail_($email, 'outcome_unknown', 'Resend did not confirm the result. Check Emails in your Resend dashboard before retrying.', true, $service);
        } elseif ($email->status === OutboundEmail::STATUS_PENDING) {
            $this->fail_($email, 'job_failed', 'The email could not be sent. You can retry it.', false, $service);
        }
    }

    /** @param bool|null $unknown true = may have been sent (keeps draft lock and quota) */
    private function fail_(OutboundEmail $email, string $code, string $message, ?bool $unknown, ResendSendService $service): void
    {
        $email->forceFill([
            'status' => OutboundEmail::STATUS_FAILED,
            'outcome_unknown' => (bool) $unknown,
            'draft_lock' => $unknown ? $email->draft_lock : null,
            'error_code' => mb_substr($code, 0, 40),
            'error_message' => mb_substr($message, 0, 255),
        ])->save();

        if (!$unknown) {
            $service->releaseFor($email->owner_user_id);
        }

        EmailAuditLog::record($email->owner_user_id, $email->user_id, 'resend_send_failed', $email->id, ['code' => $code, 'outcome_unknown' => (bool) $unknown]);
        EventRecorder::forEmail($email, $unknown ? EmailTrackingEvent::UNKNOWN : EmailTrackingEvent::FAILED,
            $unknown === null ? EmailTrackingEvent::SOURCE_SYSTEM : EmailTrackingEvent::SOURCE_RESEND_API,
            EventRecorder::failureKey($email), $email->user_id, ['code' => $code]);
    }
}
