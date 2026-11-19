<?php

namespace App\Services\EmailTracking;

use App\Models\EmailFollowUp;
use App\Models\LeadCenterLead;
use App\Models\SavedLead;

/**
 * Keeps the existing lead statuses (My Leads + Lead Center) in step with email
 * activity. It only moves a lead forward and never overwrites a final outcome
 * (converted / closed) unless the new outcome is "converted".
 */
class LeadSync
{
    private const SAVED_FINAL = ['converted', 'closed'];

    /** Reply classification => saved_leads.contact_status */
    private const SAVED_FOR_REPLY = [
        'interested' => 'responded',
        'meeting_requested' => 'responded',
        'other' => 'responded',
        'needs_follow_up' => 'follow_up',
        'converted' => 'converted',
        'not_interested' => 'not_interested',
    ];

    /** Reply classification => lead_center_leads.status */
    private const CENTER_FOR_REPLY = [
        'interested' => LeadCenterLead::STATUS_RESPONDED,
        'meeting_requested' => LeadCenterLead::STATUS_RESPONDED,
        'other' => LeadCenterLead::STATUS_RESPONDED,
        'needs_follow_up' => LeadCenterLead::STATUS_RESPONDED,
        'converted' => LeadCenterLead::STATUS_CONVERTED,
        'not_interested' => LeadCenterLead::STATUS_CLOSED,
    ];

    /** Gmail accepted an email to this lead. */
    public static function markContacted(?SavedLead $lead): void
    {
        if (!$lead) {
            return;
        }
        if ($lead->contact_status === null || $lead->contact_status === 'not_contacted') {
            $lead->forceFill(['contact_status' => 'contacted', 'is_contacted' => true])->save();
        }

        LeadCenterLead::where('saved_lead_id', $lead->id)->whereIn('user_id', self::accountUserIds($lead))
            ->where('status', LeadCenterLead::STATUS_PENDING)
            ->update(['status' => LeadCenterLead::STATUS_CONNECTED, 'updated_at' => now()]);
    }

    public static function applyReply(?SavedLead $lead, string $replyStatus): void
    {
        if (!$lead || !isset(self::SAVED_FOR_REPLY[$replyStatus])) {
            return;
        }

        $target = self::SAVED_FOR_REPLY[$replyStatus];
        if (!in_array($lead->contact_status, self::SAVED_FINAL, true) || $target === 'converted') {
            $lead->forceFill(['contact_status' => $target, 'is_contacted' => true])->save();
        }

        $center = self::CENTER_FOR_REPLY[$replyStatus];
        LeadCenterLead::where('saved_lead_id', $lead->id)->whereIn('user_id', self::accountUserIds($lead))
            ->when($center !== LeadCenterLead::STATUS_CONVERTED, fn ($q) => $q->whereNotIn('status', LeadCenterLead::OUTCOME_STATUSES))
            ->update(['status' => $center, 'updated_at' => now()]);
    }

    /** Recipient unsubscribed, or the reply said "not interested". */
    public static function applyOptOut(?SavedLead $lead): void
    {
        if (!$lead) {
            return;
        }
        if (!in_array($lead->contact_status, self::SAVED_FINAL, true)) {
            $lead->forceFill(['contact_status' => 'not_interested', 'is_contacted' => true])->save();
        }

        LeadCenterLead::where('saved_lead_id', $lead->id)->whereIn('user_id', self::accountUserIds($lead))
            ->whereNotIn('status', LeadCenterLead::OUTCOME_STATUSES)
            ->update(['status' => LeadCenterLead::STATUS_CLOSED, 'updated_at' => now()]);
    }

    /** Lead Center rows may belong to the company or any of its team members. */
    private static function accountUserIds(SavedLead $lead): array
    {
        return \App\Models\User::find($lead->user_id)?->getQuotaUserIds() ?? [$lead->user_id];
    }

    /** Mirror the earliest open follow-up into saved_leads.follow_up_date. */
    public static function syncFollowUpDate(?SavedLead $lead): void
    {
        if (!$lead) {
            return;
        }

        $next = EmailFollowUp::where('saved_lead_id', $lead->id)->open()->min('due_date');

        if ($next) {
            $lead->forceFill(['follow_up_date' => $next, 'follow_up_source' => 'email'])->save();
        } elseif ($lead->follow_up_source === 'email') {
            // Only clear dates this module set; manual dates from the Leads page stay
            $lead->forceFill(['follow_up_date' => null, 'follow_up_source' => null])->save();
        }
    }
}
