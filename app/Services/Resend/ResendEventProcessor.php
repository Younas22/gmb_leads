<?php

namespace App\Services\Resend;

use App\Models\EmailCampaign;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\MarketingConsentLog;
use App\Models\MarketingContact;
use App\Models\OutboundEmail;
use App\Models\ResendDomain;
use App\Models\ResendWebhookReceipt;
use App\Models\User;
use App\Services\EmailTracking\EventRecorder;
use App\Services\EmailTracking\FollowUpService;
use App\Services\EmailTracking\LeadSync;
use App\Services\PlanFeatures;
use Illuminate\Support\Carbon;

/**
 * Turns verified Resend webhook receipts into the shared Phase 4 event log and
 * message state. Everything is scoped to the receipt's owner (taken from the
 * endpoint that verified the signature, never from the payload).
 *
 * Resend type        -> normalized event        delivery_status
 * email.sent         -> provider_sent           sent
 * email.delivered    -> delivered               delivered
 * email.delivery_delayed -> delivery_delayed     delivery_delayed
 * email.bounced      -> bounced                 bounced     (Permanent => suppression)
 * email.complained   -> complained              (complained_at + suppression)
 * email.failed       -> provider_failed         failed
 * email.suppressed   -> provider_suppressed     suppressed  (+ suppression)
 * email.opened       -> provider_opened         (needs email_tracking_enabled)
 * email.clicked      -> provider_clicked        (needs email_tracking_enabled; never unsubscribe links)
 */
class ResendEventProcessor
{
    private const MAP = [
        'email.sent' => [EmailTrackingEvent::PROVIDER_SENT, 'sent'],
        'email.delivered' => [EmailTrackingEvent::DELIVERED, 'delivered'],
        'email.delivery_delayed' => [EmailTrackingEvent::DELIVERY_DELAYED, 'delivery_delayed'],
        'email.bounced' => [EmailTrackingEvent::BOUNCED, 'bounced'],
        'email.complained' => [EmailTrackingEvent::COMPLAINED, null],
        'email.failed' => [EmailTrackingEvent::PROVIDER_FAILED, 'failed'],
        'email.suppressed' => [EmailTrackingEvent::PROVIDER_SUPPRESSED, 'suppressed'],
        'email.opened' => [EmailTrackingEvent::PROVIDER_OPENED, null],
        'email.clicked' => [EmailTrackingEvent::PROVIDER_CLICKED, null],
    ];

    public function __construct(private readonly FollowUpService $followUps)
    {
    }

    /** @return string final receipt status */
    public function process(ResendWebhookReceipt $receipt): string
    {
        $data = (array) $receipt->payload;
        $type = $receipt->event_type;

        $status = match (true) {
            isset(self::MAP[$type]) => $this->emailEvent($receipt, $data),
            $type === 'contact.updated' => $this->contactUpdated($receipt, $data),
            $type === 'domain.updated' => $this->domainUpdated($receipt, $data),
            default => 'ignored', // e.g. email.received: inbound handling is not enabled
        };

        $receipt->forceFill([
            'status' => $status,
            'attempts' => $receipt->attempts + 1,
            'processed_at' => in_array($status, ['processed', 'ignored'], true) ? now() : null,
        ])->save();

        return $status;
    }

    private function emailEvent(ResendWebhookReceipt $receipt, array $data): string
    {
        $email = $this->findEmail($receipt->owner_user_id, $data);
        if (!$email) {
            // Webhooks can arrive before our row has the Resend id (or for mail sent outside
            // CustomerNearMe). Retried for a while, then ignored.
            return $receipt->created_at->gt(now()->subMinutes((int) config('resend_user.unmatched_retry_minutes', 60))) ? 'unmatched' : 'ignored';
        }

        [$eventType, $delivery] = self::MAP[$receipt->event_type];
        $occurred = $receipt->event_created_at ?? $receipt->created_at;

        if (in_array($eventType, [EmailTrackingEvent::PROVIDER_OPENED, EmailTrackingEvent::PROVIDER_CLICKED], true)) {
            $sender = User::find($email->user_id);
            if (!$sender || !PlanFeatures::enabled($sender, 'email_tracking_enabled')) {
                return 'ignored'; // engagement data is not collected without the tracking feature
            }
            if ($eventType === EmailTrackingEvent::PROVIDER_CLICKED && str_contains(mb_strtolower((string) ($data['click_link'] ?? '')), 'unsubscribe')) {
                return 'ignored';
            }
        }

        $meta = array_filter([
            'bounce_type' => $data['bounce_type'] ?? null,
            'bounce_sub_type' => $data['bounce_sub_type'] ?? null,
            'link' => $eventType === EmailTrackingEvent::PROVIDER_CLICKED ? ($data['click_link'] ?? null) : null,
        ]);

        EventRecorder::record([
            'owner_user_id' => $email->owner_user_id,
            'outbound_email_id' => $email->id,
            'campaign_id' => $email->campaign_id,
            'saved_lead_id' => $email->saved_lead_id,
            'marketing_contact_id' => $email->marketing_contact_id,
            'event_type' => $eventType,
            'event_source' => EmailTrackingEvent::SOURCE_RESEND_WEBHOOK,
            'provider' => OutboundEmail::PROVIDER_RESEND,
            'dedupe_key' => 'resend:' . $receipt->webhook_config_id . ':' . $receipt->svix_id,
            'metadata' => $meta ?: null,
            'occurred_at' => $occurred,
        ]);

        $this->applyState($email, $eventType, $delivery, $occurred, $data);

        return 'processed';
    }

    /** Move delivery state forward only; late or out-of-order events never undo a later state. */
    private function applyState(OutboundEmail $email, string $eventType, ?string $delivery, Carbon $at, array $data): void
    {
        $changes = [];

        if ($delivery) {
            $current = OutboundEmail::DELIVERY_RANK[$email->delivery_status] ?? 0;
            if (OutboundEmail::DELIVERY_RANK[$delivery] > $current) {
                $changes['delivery_status'] = $delivery;
            }
        }
        if ($eventType === EmailTrackingEvent::DELIVERED && !$email->delivered_at) {
            $changes['delivered_at'] = $at;
        }
        if ($eventType === EmailTrackingEvent::BOUNCED && !$email->bounced_at) {
            $changes['bounced_at'] = $at;
        }
        if ($eventType === EmailTrackingEvent::COMPLAINED && !$email->complained_at) {
            $changes['complained_at'] = $at;
        }
        // A scheduled campaign email is "sent" once Resend reports it
        if ($email->status === OutboundEmail::STATUS_PENDING && in_array($eventType, [EmailTrackingEvent::PROVIDER_SENT, EmailTrackingEvent::DELIVERED, EmailTrackingEvent::DELIVERY_DELAYED, EmailTrackingEvent::BOUNCED], true)) {
            $changes['status'] = OutboundEmail::STATUS_SENT;
            $changes['sent_at'] = $email->sent_at ?? $at;
        }
        if ($changes) {
            $email->forceFill($changes)->save();
        }

        $permanentBounce = $eventType === EmailTrackingEvent::BOUNCED && strcasecmp((string) ($data['bounce_type'] ?? ''), 'Permanent') === 0;
        $reason = match (true) {
            $permanentBounce => EmailSuppression::REASON_BOUNCED,
            $eventType === EmailTrackingEvent::COMPLAINED => EmailSuppression::REASON_COMPLAINT,
            $eventType === EmailTrackingEvent::PROVIDER_SUPPRESSED => EmailSuppression::REASON_PROVIDER_SUPPRESSED,
            default => null,
        };
        if ($reason) {
            EmailSuppression::suppress($email->owner_user_id, $email->to_email, $reason, $email->id);
            $this->followUps->cancelAllForEmailRecipient(null, $email, $reason);
            if ($reason !== EmailSuppression::REASON_PROVIDER_SUPPRESSED) {
                LeadSync::applyOptOut($email->lead);
            }
        }
    }

    private function findEmail(int $ownerId, array $data): ?OutboundEmail
    {
        if (!empty($data['email_id'])) {
            $email = OutboundEmail::where('owner_user_id', $ownerId)->where('provider', OutboundEmail::PROVIDER_RESEND)
                ->where('provider_message_id', $data['email_id'])->first();
            if ($email) {
                return $email;
            }
        }

        // Broadcast recipients: matched by broadcast + recipient address, then the id is stored
        if (!empty($data['broadcast_id']) && !empty($data['to'])) {
            $campaign = EmailCampaign::where('owner_user_id', $ownerId)->where('provider_broadcast_id', $data['broadcast_id'])->first();
            $email = $campaign
                ? OutboundEmail::where('campaign_id', $campaign->id)->where('to_email', EmailSuppression::normalize($data['to']))->first()
                : null;
            if ($email && !empty($data['email_id']) && !$email->provider_message_id) {
                $email->forceFill(['provider_message_id' => $data['email_id']])->save();
            }

            return $email;
        }

        return null;
    }

    /** Contact unsubscribed through Resend (e.g. the broadcast unsubscribe link). */
    private function contactUpdated(ResendWebhookReceipt $receipt, array $data): string
    {
        if (($data['unsubscribed'] ?? null) !== true || empty($data['email'])) {
            return 'ignored';
        }

        $already = EmailSuppression::isSuppressed($receipt->owner_user_id, $data['email']);
        EmailSuppression::suppress($receipt->owner_user_id, $data['email'], EmailSuppression::REASON_UNSUBSCRIBED);

        // Same bookkeeping as our own unsubscribe link: count it in Email Analytics
        // ("Unsubscribes"), stop planned follow-ups and mark the lead as opted out.
        $latest = OutboundEmail::where('owner_user_id', $receipt->owner_user_id)
            ->where('to_email', EmailSuppression::normalize($data['email']))
            ->where('status', OutboundEmail::STATUS_SENT)->latest('id')->first();
        if ($latest && !$already) {
            EventRecorder::forEmail($latest, EmailTrackingEvent::OPTED_OUT, EmailTrackingEvent::SOURCE_RESEND_WEBHOOK, 'opted_out:' . $latest->id);
            $this->followUps->cancelAllForEmailRecipient(null, $latest, 'unsubscribed');
            LeadSync::applyOptOut($latest->lead);
        }

        $contact =MarketingContact::where('owner_user_id', $receipt->owner_user_id)->where('email_hash', MarketingContact::hashFor($data['email']))->first();
        if ($contact) {
            MarketingConsentLog::record($contact, null, 'unsubscribed_via_resend', $contact->consent_status, MarketingContact::WITHDRAWN);
            $contact->forceFill(['consent_status' => MarketingContact::WITHDRAWN, 'unsubscribed_at' => $contact->unsubscribed_at ?? now()])->save();
        }

        return 'processed';
    }

    private function domainUpdated(ResendWebhookReceipt $receipt, array $data): string
    {
        if (empty($data['id']) || empty($data['status'])) {
            return 'ignored';
        }

        $updated = ResendDomain::where('owner_user_id', $receipt->owner_user_id)->where('provider_domain_id', $data['id'])
            ->update(['status' => $data['status'], 'last_synced_at' => now(), 'updated_at' => now()]);

        return $updated ? 'processed' : 'ignored';
    }
}
