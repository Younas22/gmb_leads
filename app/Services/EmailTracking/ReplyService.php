<?php

namespace App\Services\EmailTracking;

use App\Models\EmailAuditLog;
use App\Models\EmailReplyRecord;
use App\Models\EmailSuppression;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Manual reply tracking. The user tells us a reply arrived; we never read the inbox.
 * Automatic detection would need Gmail read scopes and is NOT available (see
 * ReplySource for the interface a future, separately authorised sync would use).
 */
class ReplyService
{
    public function __construct(private readonly FollowUpService $followUps)
    {
    }

    /** Only emails that were (or may have been) sent can have a reply. */
    public function canRecord(OutboundEmail $email): bool
    {
        return $email->status === OutboundEmail::STATUS_SENT || $email->isUnknown();
    }

    /**
     * Create or correct the reply record for an email.
     *
     * @return array{reply: ?EmailReplyRecord, problem: ?string}
     */
    public function record(User $user, OutboundEmail $email, string $status, Carbon $receivedAt, ?string $notes, ?Carbon $followUpDue = null): array
    {
        if (!$this->canRecord($email)) {
            return ['reply' => null, 'problem' => 'Replies can only be recorded for emails that were sent.'];
        }
        $earliest = ($email->sent_at ?? $email->queued_at ?? $email->created_at)->copy()->startOfDay();
        if ($receivedAt->lt($earliest)) {
            return ['reply' => null, 'problem' => 'The reply date cannot be before the email was sent.'];
        }
        if ($receivedAt->gt(now()->addMinutes(5))) {
            return ['reply' => null, 'problem' => 'The reply date cannot be in the future.'];
        }

        $notes = $notes !== null && trim($notes) !== '' ? trim($notes) : null;

        $reply = DB::transaction(function () use ($user, $email, $status, $receivedAt, $notes) {
            $existing = EmailReplyRecord::where('outbound_email_id', $email->id)->lockForUpdate()->first();
            $previous = $existing?->reply_status;

            $reply = $existing ?? new EmailReplyRecord([
                'owner_user_id' => $email->owner_user_id,
                'outbound_email_id' => $email->id,
                'saved_lead_id' => $email->saved_lead_id,
                'source' => 'manual',
                'recorded_by_user_id' => $user->id,
            ]);
            $reply->fill([
                'reply_status' => $status,
                'reply_received_at' => $receivedAt,
                'notes' => $notes,
                'updated_by_user_id' => $user->id,
            ])->save();

            // Correcting away from "not interested" lifts the block this record created
            if ($previous === 'not_interested' && $status !== 'not_interested') {
                EmailSuppression::where('owner_user_id', $email->owner_user_id)
                    ->where('outbound_email_id', $email->id)->where('reason', EmailSuppression::REASON_NOT_INTERESTED)->delete();
            }

            EventRecorder::forEmail($email, $existing ? EmailTrackingEvent::REPLY_UPDATED : EmailTrackingEvent::REPLY_RECORDED,
                EmailTrackingEvent::SOURCE_USER, null, $user->id,
                array_filter(['reply_status' => $status, 'previous_status' => $previous]), $existing ? now() : $receivedAt);
            EmailAuditLog::record($email->owner_user_id, $user->id, $existing ? 'reply_updated' : 'reply_recorded', $email->id, ['reply_status' => $status, 'previous' => $previous]);

            return $reply;
        });

        $lead = $email->lead;
        LeadSync::applyReply($lead, $status);

        if ($status === 'not_interested') {
            // Respect the answer: no further outreach to this address
            EmailSuppression::suppress($email->owner_user_id, $email->to_email, EmailSuppression::REASON_NOT_INTERESTED, $email->id, $user->id);
            $this->followUps->cancelAllForEmailRecipient($user, $email, 'not_interested');
        } elseif ($status === 'converted') {
            $this->followUps->cancelAllForEmailRecipient($user, $email, 'converted');
        } elseif ($followUpDue && $lead) {
            $this->followUps->schedule($user, $lead, $followUpDue, $notes, $email);
        } elseif ($followUpDue && $email->contact) {
            // Phase 5: Resend contacts that are not saved leads
            $this->followUps->scheduleForContact($user, $email->contact, $followUpDue, $notes, $email);
        }

        return ['reply' => $reply, 'problem' => null];
    }

    /**
     * A reply found automatically in the connected Gmail inbox. Only creates a record
     * when none exists (a reply the user recorded or corrected is never overwritten).
     */
    public function recordDetected(OutboundEmail $email, Carbon $receivedAt): ?EmailReplyRecord
    {
        if (!$this->canRecord($email) || EmailReplyRecord::where('outbound_email_id', $email->id)->exists()) {
            return null;
        }

        $reply = new EmailReplyRecord([
            'owner_user_id' => $email->owner_user_id,
            'outbound_email_id' => $email->id,
            'saved_lead_id' => $email->saved_lead_id,
            'source' => 'gmail_inbox',
            'recorded_by_user_id' => $email->user_id,
            'reply_status' => 'replied',
            'reply_received_at' => $receivedAt,
            'notes' => null,
        ]);
        $reply->save();

        EventRecorder::forEmail($email, EmailTrackingEvent::REPLY_DETECTED, EmailTrackingEvent::SOURCE_GMAIL_INBOX,
            'reply_detected:' . $email->id, null, [], $receivedAt);
        LeadSync::applyReply($email->lead, 'replied');

        return $reply;
    }

    /** "This was not actually a reply": remove the record (kept in the event log). */
    public function remove(User $user, OutboundEmail $email): bool
    {
        $reply = EmailReplyRecord::where('outbound_email_id', $email->id)->first();
        if (!$reply) {
            return false;
        }

        DB::transaction(function () use ($user, $email, $reply) {
            if ($reply->reply_status === 'not_interested') {
                EmailSuppression::where('owner_user_id', $email->owner_user_id)
                    ->where('outbound_email_id', $email->id)->where('reason', EmailSuppression::REASON_NOT_INTERESTED)->delete();
            }
            $reply->delete();
            EventRecorder::forEmail($email, EmailTrackingEvent::REPLY_REMOVED, EmailTrackingEvent::SOURCE_USER, null, $user->id, ['previous_status' => $reply->reply_status]);
            EmailAuditLog::record($email->owner_user_id, $user->id, 'reply_removed', $email->id, ['previous' => $reply->reply_status]);
        });

        return true;
    }
}
