<?php

namespace App\Services\EmailTracking;

use App\Models\EmailFollowUp;
use App\Models\EmailReplyRecord;
use App\Models\EmailTrackingEvent;
use App\Models\OutboundEmail;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Email Analytics numbers for one account.
 *
 * Summary metrics are COHORT based: they describe the emails queued in the selected
 * period (whatever happened to them later), so every rate uses a matching denominator:
 *   reply rate        = emails with a recorded reply / emails accepted by Gmail
 *   est. click rate   = tracked emails with a probable-person click / accepted tracked emails
 * Charts use the activity date instead (when Gmail accepted, when a reply arrived...).
 *
 * There is deliberately no "delivered" or "open" rate: the app has no reliable data
 * for either (Gmail acceptance is not delivery, and no tracking pixels are used).
 */
class EmailAnalytics
{
    /**
     * @param array{from: Carbon, to: Carbon, account?: ?int, status?: ?string, lead?: ?int, member?: ?int} $filters
     */
    public function cohort(User $owner, array $filters): Builder
    {
        return OutboundEmail::ownedBy($owner)
            ->whereBetween('queued_at', [$filters['from'], $filters['to']])
            ->when($filters['account'] ?? null, fn ($q, $id) => $q->where('connected_account_id', $id))
            ->when($filters['lead'] ?? null, fn ($q, $id) => $q->where('saved_lead_id', $id))
            ->when($filters['member'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            // Phase 5
            ->when($filters['provider'] ?? null, fn ($q, $p) => $q->where('provider', $p))
            ->when($filters['campaign'] ?? null, fn ($q, $id) => $q->where('campaign_id', $id))
            ->when($filters['status'] ?? null, function ($q, $status) {
                return match ($status) {
                    'unknown' => $q->where('status', OutboundEmail::STATUS_FAILED)->where('outcome_unknown', true),
                    OutboundEmail::STATUS_FAILED => $q->where('status', OutboundEmail::STATUS_FAILED)->where('outcome_unknown', false),
                    default => $q->where('status', $status),
                };
            });
    }

    public function summary(User $owner, array $filters): array
    {
        $cohort = $this->cohort($owner, $filters);

        $counts = (clone $cohort)->toBase()->selectRaw("
            COUNT(*) AS queued,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS accepted,
            SUM(CASE WHEN status = 'failed' AND outcome_unknown = 0 THEN 1 ELSE 0 END) AS failed,
            SUM(CASE WHEN status = 'failed' AND outcome_unknown = 1 THEN 1 ELSE 0 END) AS unknown,
            SUM(CASE WHEN status IN ('pending', 'sending') THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN status = 'sent' AND link_tracking = 1 THEN 1 ELSE 0 END) AS tracked_accepted
        ")->first();

        $ids = (clone $cohort)->select('outbound_emails.id');
        $sentIds = (clone $cohort)->where('status', OutboundEmail::STATUS_SENT)->select('outbound_emails.id');

        $clicks = EmailTrackingEvent::where('owner_user_id', $owner->id)
            ->where('event_type', EmailTrackingEvent::LINK_CLICK)
            ->whereIn('outbound_email_id', $ids)
            ->toBase()
            ->selectRaw("
                COUNT(*) AS requests,
                SUM(CASE WHEN classification = 'probable_human' THEN 1 ELSE 0 END) AS human,
                SUM(CASE WHEN classification = 'suspected_scanner' THEN 1 ELSE 0 END) AS scanner,
                SUM(CASE WHEN classification = 'internal' THEN 1 ELSE 0 END) AS internal
            ")->first();

        $uniqueHuman = EmailTrackingEvent::where('owner_user_id', $owner->id)
            ->where('event_type', EmailTrackingEvent::LINK_CLICK)
            ->where('classification', EmailTrackingEvent::HUMAN)
            ->whereIn('outbound_email_id', $sentIds)
            ->whereIn('outbound_email_id', (clone $cohort)->where('status', OutboundEmail::STATUS_SENT)->where('link_tracking', true)->select('outbound_emails.id'))
            ->distinct()->count('outbound_email_id');

        $replies = EmailReplyRecord::where('owner_user_id', $owner->id)->whereIn('outbound_email_id', $ids);
        $repliesTotal = (clone $replies)->count();
        $repliesOnAccepted = EmailReplyRecord::where('owner_user_id', $owner->id)->whereIn('outbound_email_id', $sentIds)->count();
        $byStatus = (clone $replies)->toBase()->select('reply_status', DB::raw('COUNT(*) AS n'))->groupBy('reply_status')->pluck('n', 'reply_status')->all();

        $unsubscribes = EmailTrackingEvent::where('owner_user_id', $owner->id)
            ->where('event_type', EmailTrackingEvent::OPTED_OUT)
            ->whereIn('outbound_email_id', $ids)
            ->count();

        $accepted = (int) $counts->accepted;
        $trackedAccepted = (int) $counts->tracked_accepted;

        // Phase 5: provider evidence. Only Resend reports delivery; Gmail rows are never
        // counted as delivered OR as undelivered ("not available").
        $resend = (clone $cohort)->where('provider', OutboundEmail::PROVIDER_RESEND)->toBase()->selectRaw("
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS accepted,
            SUM(CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END) AS delivered,
            SUM(CASE WHEN bounced_at IS NOT NULL THEN 1 ELSE 0 END) AS bounced,
            SUM(CASE WHEN complained_at IS NOT NULL THEN 1 ELSE 0 END) AS complained
        ")->first();
        $resendAccepted = (int) ($resend->accepted ?? 0);
        $providerEvents = EmailTrackingEvent::where('owner_user_id', $owner->id)
            ->whereIn('event_type', [EmailTrackingEvent::PROVIDER_OPENED, EmailTrackingEvent::PROVIDER_CLICKED])
            ->whereIn('outbound_email_id', $ids)
            ->toBase()->select('event_type', DB::raw('COUNT(*) AS n'), DB::raw('COUNT(DISTINCT outbound_email_id) AS u'))
            ->groupBy('event_type')->get()->keyBy('event_type');
        // Gmail opens from the open pixel (people only; loads right after sending are scanners)
        $pixel = EmailTrackingEvent::where('owner_user_id', $owner->id)
            ->where('event_type', EmailTrackingEvent::OPENED)
            ->where('classification', EmailTrackingEvent::HUMAN)
            ->whereIn('outbound_email_id', $ids)
            ->toBase()->selectRaw('COUNT(*) AS n, COUNT(DISTINCT outbound_email_id) AS u')->first();
        $repliesDetected = EmailReplyRecord::where('owner_user_id', $owner->id)->whereIn('outbound_email_id', $ids)->where('source', 'gmail_inbox')->count();

        // No Resend emails in the selection = no delivery evidence at all ("Not available", never 0)
        $gmailOnly = ($filters['provider'] ?? null) === OutboundEmail::PROVIDER_GMAIL || $resendAccepted === 0;

        return [
            'queued' => (int) $counts->queued,
            'accepted' => $accepted,
            'failed' => (int) $counts->failed,
            'unknown' => (int) $counts->unknown,
            'in_progress' => (int) $counts->in_progress,
            'cancelled' => (int) $counts->cancelled,
            'tracked_accepted' => $trackedAccepted,
            'link_requests' => (int) $clicks->requests,
            'human_clicks' => (int) $clicks->human,
            'scanner_requests' => (int) $clicks->scanner,
            'internal_requests' => (int) $clicks->internal,
            'unique_clicks_estimate' => $uniqueHuman,
            'click_rate_estimate' => $trackedAccepted > 0 ? round($uniqueHuman / $trackedAccepted * 100, 1) : null,
            'replies' => $repliesTotal,
            'replies_on_accepted' => $repliesOnAccepted,
            'reply_rate' => $accepted > 0 ? round($repliesOnAccepted / $accepted * 100, 1) : null,
            'replies_by_status' => array_map('intval', $byStatus),
            'unsubscribes' => $unsubscribes,
            // Resend-only metrics (null = no evidence available for this selection)
            'resend_accepted' => $resendAccepted,
            'delivered' => $gmailOnly ? null : (int) ($resend->delivered ?? 0),
            'delivery_rate' => !$gmailOnly && $resendAccepted > 0 ? round((int) $resend->delivered / $resendAccepted * 100, 1) : null,
            'bounced' => $gmailOnly ? null : (int) ($resend->bounced ?? 0),
            'complained' => $gmailOnly ? null : (int) ($resend->complained ?? 0),
            'provider_opens' => (int) ($providerEvents[EmailTrackingEvent::PROVIDER_OPENED]->n ?? 0),
            'provider_unique_opens' => (int) ($providerEvents[EmailTrackingEvent::PROVIDER_OPENED]->u ?? 0),
            'provider_clicks' => (int) ($providerEvents[EmailTrackingEvent::PROVIDER_CLICKED]->n ?? 0),
            'provider_unique_clicks' => (int) ($providerEvents[EmailTrackingEvent::PROVIDER_CLICKED]->u ?? 0),
            'gmail_opens' => (int) ($pixel->n ?? 0),
            'gmail_unique_opens' => (int) ($pixel->u ?? 0),
            // All opened emails (Gmail pixel + Resend reports), counted once per email
            'opened_emails' => EmailTrackingEvent::where('owner_user_id', $owner->id)
                ->whereIn('outbound_email_id', $sentIds)
                ->where(fn ($q) => $q->where('event_type', EmailTrackingEvent::PROVIDER_OPENED)
                    ->orWhere(fn ($w) => $w->where('event_type', EmailTrackingEvent::OPENED)->where('classification', EmailTrackingEvent::HUMAN)))
                ->distinct()->count('outbound_email_id'),
            'replies_detected' => $repliesDetected,
            'follow_ups' => $this->followUpCounts($owner, $filters['lead'] ?? null),
        ];
    }

    /** Open follow-ups right now (not limited to the period). */
    public function followUpCounts(User $owner, ?int $leadId = null): array
    {
        $base = EmailFollowUp::ownedBy($owner)->open()->when($leadId, fn ($q) => $q->where('saved_lead_id', $leadId));
        $today = today()->toDateString();

        return [
            'overdue' => (clone $base)->where('due_date', '<', $today)->count(),
            'today' => (clone $base)->where('due_date', $today)->count(),
            'next_7_days' => (clone $base)->where('due_date', '>', $today)->where('due_date', '<=', today()->addDays(7)->toDateString())->count(),
        ];
    }

    /**
     * Daily activity in the period (by activity date).
     *
     * @return array{labels: list<string>, accepted: list<int>, clicks: list<int>, replies: list<int>}
     */
    public function trend(User $owner, array $filters): array
    {
        $from = $filters['from']->copy()->startOfDay();
        $to = $filters['to']->copy()->endOfDay();

        $emails = OutboundEmail::ownedBy($owner)
            ->when($filters['account'] ?? null, fn ($q, $id) => $q->where('connected_account_id', $id))
            ->when($filters['lead'] ?? null, fn ($q, $id) => $q->where('saved_lead_id', $id))
            ->when($filters['member'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['provider'] ?? null, fn ($q, $p) => $q->where('provider', $p))
            ->when($filters['campaign'] ?? null, fn ($q, $id) => $q->where('campaign_id', $id));
        $emailIds = (clone $emails)->select('outbound_emails.id');

        $accepted = (clone $emails)->where('status', OutboundEmail::STATUS_SENT)->whereBetween('sent_at', [$from, $to])
            ->toBase()->selectRaw('DATE(sent_at) AS d, COUNT(*) AS n')->groupBy('d')->pluck('n', 'd')->all();

        $clicks = EmailTrackingEvent::where('owner_user_id', $owner->id)
            ->where('event_type', EmailTrackingEvent::LINK_CLICK)->where('classification', EmailTrackingEvent::HUMAN)
            ->whereIn('outbound_email_id', $emailIds)->whereBetween('occurred_at', [$from, $to])
            ->toBase()->selectRaw('DATE(occurred_at) AS d, COUNT(*) AS n')->groupBy('d')->pluck('n', 'd')->all();

        $replies = EmailReplyRecord::where('owner_user_id', $owner->id)
            ->whereIn('outbound_email_id', $emailIds)->whereBetween('reply_received_at', [$from, $to])
            ->toBase()->selectRaw('DATE(reply_received_at) AS d, COUNT(*) AS n')->groupBy('d')->pluck('n', 'd')->all();

        $series = ['labels' => [], 'accepted' => [], 'clicks' => [], 'replies' => []];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $series['labels'][] = $day->format('M d');
            $series['accepted'][] = (int) ($accepted[$key] ?? 0);
            $series['clicks'][] = (int) ($clicks[$key] ?? 0);
            $series['replies'][] = (int) ($replies[$key] ?? 0);
        }

        return $series;
    }
}
