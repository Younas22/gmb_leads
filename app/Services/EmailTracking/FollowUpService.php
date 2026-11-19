<?php

namespace App\Services\EmailTracking;

use App\Models\EmailFollowUp;
use App\Models\EmailReplyRecord;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\MarketingContact;
use App\Models\OutboundEmail;
use App\Models\SavedLead;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Follow-up reminders. Reminders only: nothing is ever sent from here. */
class FollowUpService
{
    /** Why a new follow-up would be inappropriate, or null when it is fine. */
    public function blockedReason(SavedLead $lead): ?string
    {
        $ownerId = self::ownerIdOf($lead);
        if ($lead->email && EmailSuppression::isSuppressed($ownerId, $lead->email)) {
            return 'This lead unsubscribed or is on your do-not-email list.';
        }
        $negative = EmailReplyRecord::where('saved_lead_id', $lead->id)->where('reply_status', 'not_interested')->exists();
        if ($negative) {
            return 'This lead replied that they are not interested.';
        }

        return null;
    }

    /** @return array{follow_up: ?EmailFollowUp, problem: ?string} */
    public function schedule(User $user, SavedLead $lead, Carbon $due, ?string $notes, ?OutboundEmail $email = null): array
    {
        if ($problem = $this->blockedReason($lead)) {
            return ['follow_up' => null, 'problem' => $problem];
        }

        $followUp = DB::transaction(function () use ($user, $lead, $due, $notes, $email) {
            $followUp = EmailFollowUp::create([
                'owner_user_id' => $user->getAccountOwner()->id,
                'saved_lead_id' => $lead->id,
                'outbound_email_id' => $email?->id,
                'created_by_user_id' => $user->id,
                'due_date' => $due->toDateString(),
                'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
                'status' => EmailFollowUp::OPEN,
            ]);
            LeadSync::syncFollowUpDate($lead);

            return $followUp;
        });

        $this->event($followUp, EmailTrackingEvent::FOLLOW_UP_SCHEDULED, $user->id, ['due_date' => $followUp->due_date->toDateString()]);

        return ['follow_up' => $followUp, 'problem' => null];
    }

    /** Phase 5: opted-in contacts (Resend) that are not saved leads. */
    public function blockedReasonForContact(MarketingContact $contact): ?string
    {
        if (EmailSuppression::isSuppressed($contact->owner_user_id, $contact->email) || $contact->unsubscribed_at || $contact->complained_at || $contact->bounced_at) {
            return 'This contact unsubscribed, bounced or complained.';
        }
        $negative = EmailReplyRecord::whereIn('outbound_email_id', OutboundEmail::where('marketing_contact_id', $contact->id)->select('id'))
            ->where('reply_status', 'not_interested')->exists();

        return $negative ? 'This contact replied that they are not interested.' : null;
    }

    /** @return array{follow_up: ?EmailFollowUp, problem: ?string} */
    public function scheduleForContact(User $user, MarketingContact $contact, Carbon $due, ?string $notes, ?OutboundEmail $email = null): array
    {
        if ($contact->saved_lead_id && $contact->lead) {
            return $this->schedule($user, $contact->lead, $due, $notes, $email);
        }
        if ($problem = $this->blockedReasonForContact($contact)) {
            return ['follow_up' => null, 'problem' => $problem];
        }

        $followUp = EmailFollowUp::create([
            'owner_user_id' => $user->getAccountOwner()->id,
            'saved_lead_id' => null,
            'marketing_contact_id' => $contact->id,
            'outbound_email_id' => $email?->id,
            'created_by_user_id' => $user->id,
            'due_date' => $due->toDateString(),
            'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            'status' => EmailFollowUp::OPEN,
        ]);
        $this->event($followUp, EmailTrackingEvent::FOLLOW_UP_SCHEDULED, $user->id, ['due_date' => $followUp->due_date->toDateString()]);

        return ['follow_up' => $followUp, 'problem' => null];
    }

    /** Cancel open reminders for whoever received $email (lead or contact), e.g. after a bounce. */
    public function cancelAllForEmailRecipient(?User $user, OutboundEmail $email, string $reason): int
    {
        $count = $email->lead ? $this->cancelAllForLead($user, $email->lead, $reason) : 0;
        if ($email->marketing_contact_id) {
            foreach (EmailFollowUp::where('marketing_contact_id', $email->marketing_contact_id)->open()->get() as $followUp) {
                $count += (int) $this->cancel($user, $followUp, $reason);
            }
        }

        return $count;
    }

    public function complete(User $user, EmailFollowUp $followUp, ?string $notes = null): bool
    {
        $done = EmailFollowUp::whereKey($followUp->id)->where('status', EmailFollowUp::OPEN)->update([
            'status' => EmailFollowUp::COMPLETED,
            'completed_at' => now(),
            'completed_by_user_id' => $user->id,
            'notes' => $notes !== null && trim($notes) !== '' ? trim(($followUp->notes ? $followUp->notes . "\n" : '') . trim($notes)) : $followUp->notes,
            'updated_at' => now(),
        ]);
        if (!$done) {
            return false;
        }

        LeadSync::syncFollowUpDate($followUp->lead);
        $this->event($followUp, EmailTrackingEvent::FOLLOW_UP_COMPLETED, $user->id);

        return true;
    }

    public function cancel(?User $user, EmailFollowUp $followUp, string $reason = 'user'): bool
    {
        $done = EmailFollowUp::whereKey($followUp->id)->where('status', EmailFollowUp::OPEN)->update([
            'status' => EmailFollowUp::CANCELLED,
            'cancel_reason' => mb_substr($reason, 0, 40),
            'updated_at' => now(),
        ]);
        if (!$done) {
            return false;
        }

        LeadSync::syncFollowUpDate($followUp->lead);
        $this->event($followUp, EmailTrackingEvent::FOLLOW_UP_CANCELLED, $user?->id, ['reason' => $reason]);

        return true;
    }

    /** Cancel every open follow-up for a lead (opt-out / not interested). */
    public function cancelAllForLead(?User $user, ?SavedLead $lead, string $reason): int
    {
        if (!$lead) {
            return 0;
        }
        $count = 0;
        foreach (EmailFollowUp::where('saved_lead_id', $lead->id)->open()->get() as $followUp) {
            $count += (int) $this->cancel($user, $followUp, $reason);
        }

        return $count;
    }

    /** Leads may be saved by a team member; suppressions are kept per account owner. */
    public static function ownerIdOf(SavedLead $lead): int
    {
        return (int) (User::find($lead->user_id)?->getAccountOwner()?->id ?? $lead->user_id);
    }

    private function event(EmailFollowUp $followUp, string $type, ?int $userId, array $meta = []): void
    {
        EventRecorder::record([
            'owner_user_id' => $followUp->owner_user_id,
            'outbound_email_id' => $followUp->outbound_email_id,
            'saved_lead_id' => $followUp->saved_lead_id,
            'marketing_contact_id' => $followUp->marketing_contact_id,
            'user_id' => $userId,
            'event_type' => $type,
            'event_source' => $userId ? EmailTrackingEvent::SOURCE_USER : EmailTrackingEvent::SOURCE_SYSTEM,
            'dedupe_key' => $type . ':' . $followUp->id,
            'metadata' => $meta ?: null,
        ]);
    }
}
