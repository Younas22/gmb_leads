<?php

namespace App\Services\Resend;

use App\Jobs\SendResendEmail;
use App\Models\AiEmailDraft;
use App\Models\EmailAuditLog;
use App\Models\EmailSuppression;
use App\Models\MarketingContact;
use App\Models\OutboundEmail;
use App\Models\ResendIntegration;
use App\Models\ResendSender;
use App\Models\User;
use App\Models\UserSalesProfile;
use App\Services\EmailTracking\EventRecorder;
use App\Services\Gmail\OutboundEmailService;
use App\Services\PlanFeatures;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * One user-confirmed email from an approved AI draft, sent through the customer's
 * own Resend account.
 *
 * Resend's Acceptable Use Policy forbids cold outreach to scraped contacts, so the
 * recipient MUST be an opted-in contact with recorded consent evidence. Having a
 * lead's address from Google Maps is not consent. The draft lock is shared with
 * Gmail, so a draft is still sent at most once whichever provider is used.
 */
class ResendSendService
{
    public function __construct(private readonly ResendQuota $quota)
    {
    }

    public function sender(User $user, ?int $senderId): ?ResendSender
    {
        $owner = $user->getAccountOwner();
        $query = ResendSender::where('owner_user_id', $owner->id)->with('domain');

        return $senderId ? $query->find($senderId) : $query->orderByDesc('is_default')->first();
    }

    public function contactFor(User $user, string $recipient): ?MarketingContact
    {
        return MarketingContact::ownedBy($user->getAccountOwner())->where('email_hash', MarketingContact::hashFor($recipient))->first();
    }

    /** @return array{integration: ?ResendIntegration, sender: ?ResendSender, recipient: string, contact: ?MarketingContact, subject: string, body: string, problems: list<string>, tracking: array{open: bool, click: bool}} */
    public function preview(User $user, AiEmailDraft $draft, ?int $senderId = null, ?string $recipient = null): array
    {
        $owner = $user->getAccountOwner();
        $integration = ResendIntegration::forOwner($owner);
        $sender = $this->sender($user, $senderId);
        $recipient = trim((string) ($recipient ?? $draft->lead?->email ?? ''));
        $contact = $recipient !== '' ? $this->contactFor($user, $recipient) : null;
        $profile = UserSalesProfile::where('owner_user_id', $owner->id)->first();
        $problems = [];

        if (!PlanFeatures::enabled($user, 'resend_single_sending_enabled')) {
            $problems[] = 'Your plan does not include sending through Resend.';
        }
        if (!$integration || !$integration->isUsable()) {
            $problems[] = 'Connect a working Resend account first.';
        }
        if (!$sender || (int) $sender->owner_user_id !== (int) $owner->id) {
            $problems[] = 'Add a sender on a verified Resend domain first.';
        } elseif (!$sender->canSend()) {
            $problems[] = 'The sender\'s domain is not verified in Resend.';
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
            $problems[] = 'Add your sender name and business postal address in your AI Sales Profile (required in emails).';
        }
        if (!OutboundEmailService::validRecipient($recipient)) {
            $problems[] = $recipient === '' ? 'This lead has no email address.' : 'The recipient email address is not valid.';
        } elseif (EmailSuppression::isSuppressed($owner->id, $recipient)) {
            $problems[] = 'This address has unsubscribed, bounced or is blocked. It cannot be emailed.';
        } elseif ($contact && !$contact->isSendable()) {
            // A contact who withdrew / unsubscribed / bounced is never emailed, in any mode
            $problems[] = 'This contact cannot receive email (' . str_replace('_', ' ', (string) $contact->blockReason()) . ').';
        } elseif (!$contact && !$this->isLeadOutreach($integration, $draft, $recipient)) {
            $problems[] = $integration?->allowsLeadOutreach()
                ? 'Without consent, Resend can only email the saved lead\'s own address (' . ($draft->lead?->email ?: 'none') . ').'
                : 'Resend can only be used for people who opted in to your emails. ' . $recipient . ' is not an opted-in contact. '
                    . 'A lead\'s address found on Google Maps is not consent: use Gmail for one-to-one outreach instead, '
                    . 'or allow one-to-one Resend emails to leads in Email Sending → Resend.';
        }

        return [
            'integration' => $integration,
            'sender' => $sender,
            'recipient' => $recipient,
            'contact' => $contact,
            // "contact": opted-in contact (consent recorded) · "lead": saved lead, owner allowed lead outreach
            'mode' => !$contact && $this->isLeadOutreach($integration, $draft, $recipient) ? 'lead' : 'contact',
            'subject' => (string) $draft->subject,
            'body' => $this->finalBody($draft, $profile, '[unsubscribe link added when sent]'),
            'problems' => $problems,
            'tracking' => ['open' => (bool) $sender?->domain?->open_tracking, 'click' => (bool) $sender?->domain?->click_tracking],
        ];
    }

    /**
     * One-to-one email to a saved lead without a consent record: only when the owner switched
     * lead outreach on, and only to the lead's own stored address (never a typed-in one).
     */
    public function isLeadOutreach(?ResendIntegration $integration, AiEmailDraft $draft, string $recipient): bool
    {
        $leadEmail = (string) ($draft->lead?->email ?? '');

        return $integration?->allowsLeadOutreach() === true
            && $leadEmail !== ''
            && EmailSuppression::normalize($leadEmail) === EmailSuppression::normalize($recipient);
    }

    /** @return array{email: ?OutboundEmail, problems: list<string>} */
    public function queue(User $user, AiEmailDraft $draft, ?int $senderId, string $recipient, string $idempotencyKey): array
    {
        if ($existing = OutboundEmail::where('idempotency_key', $idempotencyKey)->first()) {
            return ['email' => (int) $existing->user_id === (int) $user->id ? $existing : null, 'problems' => []];
        }

        return Cache::lock('email-send:' . $user->id, 10)->block(5, function () use ($user, $draft, $senderId, $recipient, $idempotencyKey) {
            $preview = $this->preview($user, $draft, $senderId, $recipient);
            if ($preview['problems'] !== []) {
                return ['email' => null, 'problems' => $preview['problems']];
            }

            if ($problem = $this->quota->reserve($user, [ResendQuota::DAILY => 1, ResendQuota::MONTHLY => 1])) {
                return ['email' => null, 'problems' => [$problem]];
            }

            $owner = $user->getAccountOwner();
            $profile = UserSalesProfile::where('owner_user_id', $owner->id)->first();

            try {
                $email = DB::transaction(function () use ($user, $owner, $draft, $preview, $idempotencyKey, $profile) {
                    $email = OutboundEmail::create([
                        'owner_user_id' => $owner->id,
                        'provider' => OutboundEmail::PROVIDER_RESEND,
                        'user_id' => $user->id,
                        'resend_integration_id' => $preview['integration']->id,
                        'resend_sender_id' => $preview['sender']->id,
                        'saved_lead_id' => $draft->saved_lead_id,
                        'draft_id' => $draft->id,
                        'marketing_contact_id' => $preview['contact']?->id, // null = one-to-one email to a saved lead
                        'idempotency_key' => $idempotencyKey,
                        'draft_lock' => (string) $draft->id,
                        'from_email' => $preview['sender']->from_email,
                        'to_email' => EmailSuppression::normalize($preview['recipient']),
                        'subject' => mb_substr($draft->subject, 0, 200),
                        'body' => '',
                        'link_tracking' => false, // Resend emails never use app redirects (no double wrapping)
                        'status' => OutboundEmail::STATUS_PENDING,
                        'queued_at' => now(),
                    ]);
                    $body = $this->finalBody($draft, $profile, app(OutboundEmailService::class)->unsubscribeUrl($email));
                    $email->forceFill(['body' => $body, 'sent_body' => $body])->save();

                    return $email;
                });
            } catch (UniqueConstraintViolationException) {
                $this->releaseFor($owner->id);

                return ['email' => null, 'problems' => ['This draft has already been sent or is being sent.']];
            }

            EmailAuditLog::record($owner->id, $user->id, 'resend_send_queued', $email->id, ['to_domain' => substr(strrchr($email->to_email, '@'), 1)]);
            EventRecorder::queued($email, $user->id);
            self::dispatch($email);

            return ['email' => $email, 'problems' => []];
        });
    }

    /**
     * Checked again right before every API call (and on retry).
     * @return array{0: string, 1: string}|null [error_code, message] when the email must not be sent
     */
    public function recipientProblem(OutboundEmail $email): ?array
    {
        if (EmailSuppression::isSuppressed($email->owner_user_id, $email->to_email)) {
            return ['suppressed', 'The recipient unsubscribed or is blocked.'];
        }
        $contact = $email->contact;
        if ($contact) {
            return $contact->isSendable() ? null : ['no_consent', 'The recipient is no longer an opted-in contact.'];
        }
        // No consent record: only a one-to-one email to the saved lead, while the owner still allows it
        $integration = ResendIntegration::find($email->resend_integration_id);
        $lead = $email->lead;
        $ok = !$email->campaign_id && $lead && $integration?->allowsLeadOutreach()
            && EmailSuppression::normalize((string) $lead->email) === EmailSuppression::normalize($email->to_email);

        return $ok ? null : ['no_consent', 'Resend emails to leads are switched off, and the recipient is not an opted-in contact.'];
    }

    /** @return list<string> */
    public function retry(User $user, OutboundEmail $email, bool $confirmedNotSent): array
    {
        if ($email->status !== OutboundEmail::STATUS_FAILED || $email->campaign_id) {
            return ['Only failed single emails can be retried.'];
        }
        if ($email->outcome_unknown && !$confirmedNotSent) {
            return ['Check the email in your Resend dashboard (Emails) first, then confirm it was not sent.'];
        }
        if ((int) $email->user_id !== (int) $user->id) {
            return ['Only the person who sent this email can retry it.'];
        }
        if ($this->recipientProblem($email) !== null) {
            return ['This recipient can no longer be emailed (consent withdrawn, unsubscribed, blocked, or Resend emails to leads were switched off).'];
        }
        if ($problem = $this->quota->reserve($user, [ResendQuota::DAILY => 1, ResendQuota::MONTHLY => 1])) {
            return [$problem];
        }

        try {
            // The same Idempotency-Key is reused: within 24 hours Resend will not send twice
            $email->forceFill([
                'status' => OutboundEmail::STATUS_PENDING,
                'draft_lock' => $email->draft_id ? (string) $email->draft_id : null,
                'outcome_unknown' => false,
                'error_code' => null,
                'error_message' => null,
                'queued_at' => now(),
            ])->save();
        } catch (UniqueConstraintViolationException) {
            $this->releaseFor($email->owner_user_id);

            return ['This draft has already been sent or is being sent.'];
        }

        EmailAuditLog::record($email->owner_user_id, $user->id, 'resend_send_retried', $email->id, ['confirmed_not_sent' => $confirmedNotSent]);
        EventRecorder::queued($email->fresh(), $user->id, true);
        self::dispatch($email);

        return [];
    }

    /** Same signed unsubscribe link as Gmail emails (shared suppression list). */
    public function unsubscribeUrl(OutboundEmail $email): string
    {
        return app(OutboundEmailService::class)->unsubscribeUrl($email);
    }

    public function releaseFor(int $ownerId): void
    {
        $this->quota->release($ownerId, ResendQuota::DAILY, 1);
        $this->quota->release($ownerId, ResendQuota::MONTHLY, 1);
    }

    private function finalBody(AiEmailDraft $draft, ?UserSalesProfile $profile, string $unsubscribeUrl): string
    {
        $sender = trim(implode(', ', array_filter([$profile?->sender_name, $profile?->company_name])));
        $footer = "--\n" . ($sender !== '' ? $sender : 'Sender') . "\n" . ($profile?->sender_postal_address ?? '')
            . "\nTo stop receiving these emails, unsubscribe here: " . $unsubscribeUrl;

        return rtrim((string) $draft->body) . "\n\n" . $footer;
    }

    public static function dispatch(OutboundEmail $email): void
    {
        dispatch(new SendResendEmail($email->id))
            ->onConnection(config('resend_user.queue.connection'))
            ->onQueue(config('resend_user.queue.send'));
    }
}
