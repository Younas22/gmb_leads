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

    /**
     * After an email is sent: schedule a follow-up reminder automatically (N days later),
     * so the user does not have to set the date. Never for campaigns, never twice for the
     * same lead/contact while one is open, and only with the follow-ups plan feature.
     */
    public function autoScheduleAfterSend(OutboundEmail $email): ?EmailFollowUp
    {
        $days = (int) config('email_tracking.auto_follow_up_days', 3);
        if ($days <= 0 || $email->campaign_id || $email->status !== OutboundEmail::STATUS_SENT) {
            return null;
        }
        $sender = User::find($email->user_id);
        if (!$sender || !\App\Services\PlanFeatures::enabled($sender, 'email_followups_enabled')) {
            return null;
        }

        if (!$email->saved_lead_id && !$email->marketing_contact_id) {
            return null;
        }
        // Same person = same lead, same contact, or an earlier email to the same address
        $open = fn () => EmailFollowUp::where('owner_user_id', $email->owner_user_id)->open()
            ->where(function ($q) use ($email) {
                $q->whereIn('outbound_email_id', OutboundEmail::where('owner_user_id', $email->owner_user_id)
                    ->where('to_email', $email->to_email)->select('id'));
                if ($email->saved_lead_id) {
                    $q->orWhere('saved_lead_id', $email->saved_lead_id);
                }
                if ($email->marketing_contact_id) {
                    $q->orWhere('marketing_contact_id', $email->marketing_contact_id);
                }
            });

        // This email IS the follow-up for reminders that were due: close them
        foreach ($open()->where('due_date', '<=', today()->toDateString())->get() as $due) {
            $this->complete($sender, $due, 'Followed up by email: "' . mb_substr((string) $email->subject, 0, 150) . '"');
        }
        if ($open()->exists()) {
            return null; // the earlier reminder still stands
        }

        $due = ($email->sent_at ?? now())->copy()->addDays($days)->startOfDay();
        $notes = 'Auto reminder: no reply yet? Follow up on "' . mb_substr((string) $email->subject, 0, 150) . '".';

        if ($email->saved_lead_id && $email->lead) {
            return $this->schedule($sender, $email->lead, $due, $notes, $email)['follow_up'];
        }
        $contact = $email->marketing_contact_id ? MarketingContact::find($email->marketing_contact_id) : null;

        return $contact ? $this->scheduleForContact($sender, $contact, $due, $notes, $email)['follow_up'] : null;
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
