<?php

namespace App\Services\Gmail;

use App\Jobs\SendGmailMessage;
use App\Models\AiEmailDraft;
use App\Models\ConnectedEmailAccount;
use App\Models\EmailAuditLog;
use App\Models\EmailSuppression;
use App\Models\OutboundEmail;
use App\Models\User;
use App\Models\UserSalesProfile;
use App\Services\EmailTracking\EventRecorder;
use App\Services\EmailTracking\LinkTracker;
use App\Services\PlanFeatures;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Prepares, validates and queues user-confirmed sends of approved AI drafts.
 * Nothing here sends automatically: every send starts from an explicit
 * confirmation by the user, for one draft and one recipient.
 */
class OutboundEmailService
{
    public function __construct(private readonly GmailOAuth $oauth, private readonly LinkTracker $tracker)
    {
    }

    public function account(User $user): ?ConnectedEmailAccount
    {
        return ConnectedEmailAccount::where('user_id', $user->id)->first();
    }

    public function dailyLimit(User $user): int
    {
        return PlanFeatures::limit($user, 'email_sends_daily');
    }

    /** Account-wide: a company and its team members share one daily allowance. */
    public function sentToday(User $user): int
    {
        return OutboundEmail::where('owner_user_id', $user->getAccountOwner()->id)
            ->where('provider', OutboundEmail::PROVIDER_GMAIL) // Resend has its own quotas
            ->whereIn('status', OutboundEmail::ACTIVE_STATUSES)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    public static function validRecipient(?string $email): bool
    {
        $email = trim((string) $email);

        return $email !== ''
            && strlen($email) <= 254
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && !preg_match('/[\r\n,;<>"\s]/', $email)
            && str_contains(substr(strrchr($email, '@'), 1), '.');
    }

    /**
     * Everything the confirmation screen shows, plus blocking problems.
     *
     * @return array{account: ?ConnectedEmailAccount, recipient: string, subject: string, body: string, problems: list<string>, sent_today: int, daily_limit: int, tracking: array{enabled: bool, links: list<string>}}
     */
    public function preview(User $user, AiEmailDraft $draft, ?string $recipient = null): array
    {
        $owner = $user->getAccountOwner();
        $account = $this->account($user);
        $profile = UserSalesProfile::where('owner_user_id', $owner->id)->first();
        $recipient = trim((string) ($recipient ?? $draft->lead?->email ?? ''));
        $limit = $this->dailyLimit($user);
        $sentToday = $this->sentToday($user);
        $problems = [];

        if (!$this->oauth->isConfigured()) {
            $problems[] = 'Gmail sending is not configured on this server.';
        }
        if (!PlanFeatures::enabled($user, 'gmail_sending_enabled')) {
            $problems[] = 'Your plan does not include sending through Gmail.';
        }
        if (!$account || $account->status === ConnectedEmailAccount::STATUS_DISCONNECTED) {
            $problems[] = 'Connect your Gmail account first.';
        } elseif (!$account->canSend()) {
            $problems[] = 'Your Gmail connection needs to be renewed. Please reconnect Gmail.';
        }
        if ((int) $draft->owner_user_id !== (int) $owner->id) {
            $problems[] = 'This draft does not belong to your account.';
        }
        if ($draft->generation_status !== AiEmailDraft::GENERATION_GENERATED || $draft->review_status !== AiEmailDraft::REVIEW_APPROVED) {
            $problems[] = 'Only approved drafts can be sent. Approve the draft first.';
        }
        if (OutboundEmail::where('draft_lock', (string) $draft->id)->exists()) {
            $problems[] = 'This draft has already been sent or is being sent.';
        }
        if (!$profile || blank($profile->sender_name) || blank($profile->sender_postal_address)) {
            $problems[] = 'Add your sender name and business postal address in your AI Sales Profile (required in outreach emails).';
        }
        if (!self::validRecipient($recipient)) {
            $problems[] = $recipient === '' ? 'This lead has no email address.' : 'The recipient email address is not valid.';
        } elseif (EmailSuppression::isSuppressed($owner->id, $recipient)) {
            $problems[] = 'This address has unsubscribed or is blocked. It cannot be emailed.';
        } elseif ($account && EmailSuppression::normalize($recipient) === EmailSuppression::normalize((string) $account->email)) {
            $problems[] = 'You cannot send an outreach email to your own Gmail address.';
        }
        if ($limit === 0) {
            $problems[] = 'Your plan does not include email sending.';
        } elseif ($limit !== -1 && $sentToday >= $limit) {
            $problems[] = "You have reached today's sending limit ({$limit}).";
        }

        $body = $this->finalBody($draft, $profile, '[unsubscribe link added when sent]');
        $tracking = $this->tracker->enabledFor($user);

        return [
            'account' => $account,
            'recipient' => $recipient,
            'subject' => (string) $draft->subject,
            'body' => $body,
            'problems' => $problems,
            'sent_today' => $sentToday,
            'daily_limit' => $limit,
            // Phase 4: what link tracking will do to this email (shown on the review screen)
            'tracking' => ['enabled' => $tracking, 'links' => $tracking ? $this->tracker->eligibleLinks($body) : []],
        ];
    }

    /**
     * Queue one confirmed send. Idempotent per $idempotencyKey; a draft is sent at most once.
     *
     * @return array{email: ?OutboundEmail, problems: list<string>}
     */
    public function queue(User $user, AiEmailDraft $draft, string $recipient, string $idempotencyKey, ?bool $trackingShown = null): array
    {
        if ($existing = OutboundEmail::where('idempotency_key', $idempotencyKey)->first()) {
            // Same confirmation submitted twice (double click / refresh): no second send
            return ['email' => (int) $existing->user_id === (int) $user->id ? $existing : null, 'problems' => []];
        }

        return Cache::lock('email-send:' . $user->id, 10)->block(5, function () use ($user, $draft, $recipient, $idempotencyKey, $trackingShown) {
            $preview = $this->preview($user, $draft, $recipient);
            if ($preview['problems'] !== []) {
                return ['email' => null, 'problems' => $preview['problems']];
            }

            // The user must have seen the link-tracking behaviour that will actually apply
            $tracking = $preview['tracking']['enabled'];
            if (($trackingShown === null && $tracking) || ($trackingShown !== null && $trackingShown !== $tracking)) {
                return ['email' => null, 'problems' => ['Your link tracking setting changed. Please review the email again before sending.']];
            }

            $owner = $user->getAccountOwner();
            $profile = UserSalesProfile::where('owner_user_id', $owner->id)->first();

            try {
                $email = DB::transaction(function () use ($user, $owner, $draft, $recipient, $idempotencyKey, $preview, $profile, $tracking) {
                    $email = OutboundEmail::create([
                        'owner_user_id' => $owner->id,
                        'user_id' => $user->id,
                        'connected_account_id' => $preview['account']->id,
                        'saved_lead_id' => $draft->saved_lead_id,
                        'draft_id' => $draft->id,
                        'idempotency_key' => $idempotencyKey,
                        'draft_lock' => (string) $draft->id,
                        'from_email' => $preview['account']->email,
                        'to_email' => EmailSuppression::normalize($recipient),
                        'subject' => mb_substr($draft->subject, 0, 200),
                        'body' => '',
                        'status' => OutboundEmail::STATUS_PENDING,
                        'queued_at' => now(),
                    ]);

                    $unsubscribeUrl = $this->unsubscribeUrl($email);
                    $body = $this->finalBody($draft, $profile, $unsubscribeUrl);

                    // Tracked links are created here, before any Gmail call and inside this
                    // transaction: if this fails nothing is queued, so nothing can be sent twice.
                    $sentBody = $body;
                    $trackedCount = 0;
                    if ($tracking) {
                        try {
                            ['body' => $sentBody, 'count' => $trackedCount] = $this->tracker->prepare($email, $body, [$unsubscribeUrl]);
                        } catch (\Throwable $e) {
                            throw new TrackingSetupFailed('Link tracking could not be prepared.', 0, $e);
                        }
                    }

                    $email->forceFill(['body' => $body, 'sent_body' => $sentBody, 'link_tracking' => $trackedCount > 0])->save();

                    return $email;
                });
            } catch (UniqueConstraintViolationException) {
                return ['email' => null, 'problems' => ['This draft has already been sent or is being sent.']];
            } catch (TrackingSetupFailed $e) {
                Log::warning('Link tracking setup failed', ['exception' => get_class($e->getPrevious() ?? $e)]);

                return ['email' => null, 'problems' => ['Tracked links could not be prepared, so nothing was sent. Try again, or turn off link tracking in Email Sending settings.']];
            }

            EmailAuditLog::record($owner->id, $user->id, 'send_queued', $email->id, ['to_domain' => substr(strrchr($email->to_email, '@'), 1)]);
            EventRecorder::queued($email, $user->id);
            self::dispatch($email);

            return ['email' => $email, 'problems' => []];
        });
    }

    /** Cancel a send that has not started yet. */
    public function cancel(User $user, OutboundEmail $email): bool
    {
        $updated = OutboundEmail::whereKey($email->id)
            ->whereNull('campaign_id') // campaign emails are cancelled with their campaign
            ->where('status', OutboundEmail::STATUS_PENDING)
            ->update(['status' => OutboundEmail::STATUS_CANCELLED, 'cancelled_at' => now(), 'draft_lock' => null, 'updated_at' => now()]);

        if ($updated) {
            EmailAuditLog::record($email->owner_user_id, $user->id, 'send_cancelled', $email->id);
        }

        return (bool) $updated;
    }

    /**
     * Retry a failed send. When the earlier outcome is unknown (it may have been
     * sent), the user must confirm they checked their Gmail "Sent" folder.
     *
     * @return list<string> problems (empty on success)
     */
    public function retry(User $user, OutboundEmail $email, bool $confirmedNotSent): array
    {
        if ($email->provider === OutboundEmail::PROVIDER_RESEND) {
            return app(\App\Services\Resend\ResendSendService::class)->retry($user, $email, $confirmedNotSent);
        }
        if ($email->status !== OutboundEmail::STATUS_FAILED) {
            return ['Only failed emails can be retried.'];
        }
        if ($email->outcome_unknown && !$confirmedNotSent) {
            return ['Check your Gmail "Sent" folder first, then confirm the email was not sent.'];
        }
        if ((int) $email->user_id !== (int) $user->id) {
            return ['Only the person who sent this email can retry it.'];
        }

        $account = $this->account($user);
        if (!$account || !$account->canSend()) {
            return ['Reconnect your Gmail account first.'];
        }
        if (EmailSuppression::isSuppressed($email->owner_user_id, $email->to_email)) {
            return ['This address has unsubscribed or is blocked.'];
        }
        $limit = $this->dailyLimit($user);
        if ($limit !== -1 && $this->sentToday($user) >= $limit) {
            return ["You have reached today's sending limit ({$limit})."];
        }

        try {
            $email->forceFill([
                'status' => OutboundEmail::STATUS_PENDING,
                'draft_lock' => $email->draft_id ? (string) $email->draft_id : null,
                'connected_account_id' => $account->id,
                'from_email' => $account->email,
                'outcome_unknown' => false,
                'error_code' => null,
                'error_message' => null,
                'queued_at' => now(),
                'created_at' => now(), // counts toward today's limit
            ])->save();
        } catch (UniqueConstraintViolationException) {
            return ['This draft has already been sent or is being sent.'];
        }

        EmailAuditLog::record($email->owner_user_id, $user->id, 'send_retried', $email->id, ['confirmed_not_sent' => $confirmedNotSent]);
        EventRecorder::queued($email->fresh(), $user->id, true);
        self::dispatch($email);

        return [];
    }

    public function unsubscribeUrl(OutboundEmail $email): string
    {
        // Always on the public APP_URL host (the link outlives this request)
        return \App\Support\CanonicalUrl::make(fn () => URL::signedRoute('email.unsubscribe', ['email' => $email->id]));
    }

    private function finalBody(AiEmailDraft $draft, ?UserSalesProfile $profile, string $unsubscribeUrl): string
    {
        $sender = trim(implode(', ', array_filter([$profile?->sender_name, $profile?->company_name])));

        $footer = strtr((string) config('gmail.footer_template'), [
            ':sender' => $sender !== '' ? $sender : 'Sender',
            ':address' => (string) ($profile?->sender_postal_address ?? ''),
            ':url' => $unsubscribeUrl,
        ]);

        return rtrim((string) $draft->body) . "\n\n" . $footer;
    }

    public static function dispatch(OutboundEmail $email): void
    {
        dispatch(new SendGmailMessage($email->id))
            ->onConnection(config('gmail.queue.connection', 'mail_database'))
            ->onQueue(config('gmail.queue.name', 'mail'));
    }
}
