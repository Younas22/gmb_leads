<?php

namespace App\Http\Controllers\EmailTracking;

use App\Http\Controllers\Controller;
use App\Models\ConnectedEmailAccount;
use App\Models\EmailFollowUp;
use App\Models\EmailReplyRecord;
use App\Models\EmailTrackingEvent;
use App\Models\EmailTrackingLink;
use App\Models\OutboundEmail;
use App\Models\SavedLead;
use App\Models\User;
use App\Services\EmailTracking\ActivityTimeline;
use App\Services\EmailTracking\EmailAnalytics;
use App\Services\EmailTracking\FollowUpService;
use App\Services\EmailTracking\ReplyService;
use App\Services\PlanFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Email Analytics: account-wide numbers, email details and lead timelines. */
class EmailAnalyticsController extends Controller
{
    public const RANGES = ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'custom' => 'Custom range'];

    public function __construct(
        private readonly EmailAnalytics $analytics,
        private readonly ActivityTimeline $timeline,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $owner = $user->getAccountOwner();
        $memberIds = $user->getQuotaUserIds();
        $accounts = ConnectedEmailAccount::where('owner_user_id', $owner->id)->orderBy('email')->get(['id', 'email', 'user_id']);

        $data = $request->validate([
            'range' => ['nullable', Rule::in(array_keys(self::RANGES))],
            'from' => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
            'account' => ['nullable', 'integer', Rule::in($accounts->pluck('id')->all())],
            'status' => ['nullable', Rule::in(['unknown', ...array_keys(OutboundEmail::LABELS)])],
            'lead' => ['nullable', 'integer'],
            'member' => ['nullable', 'integer', Rule::in($memberIds)],
            'provider' => ['nullable', Rule::in([OutboundEmail::PROVIDER_GMAIL, OutboundEmail::PROVIDER_RESEND])],
            'campaign' => ['nullable', 'integer', Rule::exists('email_campaigns', 'id')->where('owner_user_id', $owner->id)],
        ]);

        if (!empty($data['lead']) && !SavedLead::whereIn('user_id', $memberIds)->whereKey($data['lead'])->exists()) {
            abort(404);
        }

        [$from, $to] = $this->period($data);
        $filters = [
            'from' => $from, 'to' => $to,
            'account' => $data['account'] ?? null, 'status' => $data['status'] ?? null,
            'lead' => $data['lead'] ?? null, 'member' => $data['member'] ?? null,
            'provider' => $data['provider'] ?? null, 'campaign' => $data['campaign'] ?? null,
        ];

        $emails = $this->analytics->cohort($owner, $filters)
            ->with(['lead:id,name', 'sender:id,name', 'reply'])
            ->withCount([
                'events as human_clicks_count' => fn ($q) => $q->where('event_type', EmailTrackingEvent::LINK_CLICK)->where('classification', EmailTrackingEvent::HUMAN),
                'events as link_requests_count' => fn ($q) => $q->where('event_type', EmailTrackingEvent::LINK_CLICK),
                // Resend emails: engagement reported by verified Resend webhooks
                'events as provider_opens_count' => fn ($q) => $q->where('event_type', EmailTrackingEvent::PROVIDER_OPENED),
                'events as provider_clicks_count' => fn ($q) => $q->where('event_type', EmailTrackingEvent::PROVIDER_CLICKED),
            ])
            ->latest('queued_at')->latest('id')
            ->paginate(25)->withQueryString();

        $members = User::whereIn('id', $memberIds)->orderBy('name')->get(['id', 'name']);
        $leadName = !empty($data['lead']) ? SavedLead::find($data['lead'])?->name : null;

        return view('user.email-analytics.index', [
            'summary' => $this->analytics->summary($owner, $filters),
            'trend' => $this->analytics->trend($owner, $filters),
            'emails' => $emails,
            'filters' => $filters + ['range' => $data['range'] ?? '30d'],
            'accounts' => $accounts,
            'members' => $members,
            'leadName' => $leadName,
            'campaigns' => \App\Models\EmailCampaign::ownedBy($owner)->latest('id')->limit(100)->get(['id', 'name']),
            // What is missing for opens / clicks / replies to be tracked, with the fix for each
            'trackingGaps' => \App\Services\EmailTracking\TrackingSetup::gaps($user),
            'autoReplies' => $this->detectsReplies($owner->id),
            'upcomingFollowUps' => PlanFeatures::enabled($user, 'email_followups_enabled')
                ? EmailFollowUp::ownedBy($owner)->open()->where('due_date', '<=', today()->addDays(7)->toDateString())
                    ->with('lead:id,name')->orderBy('due_date')->limit(8)->get()
                : collect(),
        ]);
    }

    public function email(Request $request, int $email, ReplyService $replies, FollowUpService $followUps)
    {
        $user = $request->user();
        $owner = $user->getAccountOwner();
        $model = OutboundEmail::ownedBy($owner)->with(['lead', 'sender:id,name', 'reply.recordedBy:id,name', 'reply.updatedBy:id,name'])->findOrFail($email);

        $links = EmailTrackingLink::where('outbound_email_id', $model->id)
            ->withCount([
                'events as requests_count',
                'events as human_count' => fn ($q) => $q->where('classification', EmailTrackingEvent::HUMAN),
                'events as scanner_count' => fn ($q) => $q->where('classification', EmailTrackingEvent::SCANNER),
                'events as internal_count' => fn ($q) => $q->where('classification', EmailTrackingEvent::INTERNAL),
            ])
            ->orderBy('link_position')->get();

        // Resend emails carry no app redirects: clicks come from Resend webhooks, one event per click
        $providerClicks = $model->provider === OutboundEmail::PROVIDER_RESEND
            ? EmailTrackingEvent::where('outbound_email_id', $model->id)->where('event_type', EmailTrackingEvent::PROVIDER_CLICKED)
                ->orderBy('occurred_at')->get(['metadata', 'occurred_at'])
            : collect();

        return view('user.email-analytics.email', [
            'email' => $model,
            'links' => $links,
            'providerClicks' => $providerClicks,
            'autoReplies' => $this->detectsReplies($owner->id),
            'timeline' => $this->timeline->forEmail($owner, $model),
            'canRecordReply' => $replies->canRecord($model),
            'tracking' => $this->trackingFor($model),
            'followUps' => EmailFollowUp::ownedBy($owner)->where('outbound_email_id', $model->id)->latest('due_date')->get(),
            'followUpsEnabled' => PlanFeatures::enabled($user, 'email_followups_enabled'),
            'followUpBlocked' => match (true) {
                (bool) $model->lead => $followUps->blockedReason($model->lead),
                (bool) $model->contact => $followUps->blockedReasonForContact($model->contact),
                default => 'This email is not linked to a lead or contact.',
            },
        ]);
    }

    /**
     * Delivered / opened / clicked / replied for one email, each with the reason when
     * it cannot be known (so "nothing tracked" is never a mystery).
     *
     * @return list<array{label: string, value: string, ok: ?bool, why: ?string}>
     */
    private function trackingFor(\App\Models\OutboundEmail $email): array
    {
        $events = \App\Models\EmailTrackingEvent::where('outbound_email_id', $email->id)->get(['event_type', 'classification', 'occurred_at']);
        $first = fn (array $types) => $events->whereIn('event_type', $types)->sortBy('occurred_at')->first();
        $resend = $email->provider === \App\Models\OutboundEmail::PROVIDER_RESEND;
        $domain = $resend ? \App\Models\ResendDomain::where('owner_user_id', $email->owner_user_id)
            ->where('name', substr(strrchr((string) $email->from_email, '@'), 1))->first() : null;

        $opened = $resend
            ? $first([\App\Models\EmailTrackingEvent::PROVIDER_OPENED])
            : $events->where('event_type', \App\Models\EmailTrackingEvent::OPENED)->where('classification', \App\Models\EmailTrackingEvent::HUMAN)->sortBy('occurred_at')->first();
        $clicked = $first([\App\Models\EmailTrackingEvent::PROVIDER_CLICKED, \App\Models\EmailTrackingEvent::LINK_CLICK]);
        $reply = \App\Models\EmailReplyRecord::where('outbound_email_id', $email->id)->first();

        $openWhy = match (true) {
            (bool) $opened => null,
            $resend && $domain && !$domain->open_tracking => 'Open tracking is off for ' . $domain->name . ' in Resend, so Resend does not report opens. Turn it on (Email Analytics → Tracking setup); it works for emails sent after that.',
            $resend => 'Resend has not reported an open yet. Some mail apps block images, so not every open can be seen.',
            !($email->open_tracking || $email->link_tracking) => 'Tracking was off when this email was sent.',
            default => 'No open seen yet. Some mail apps block images, so not every open can be seen.',
        };
        $clickWhy = match (true) {
            (bool) $clicked => null,
            $resend && $domain && !$domain->click_tracking => 'Click tracking is off for ' . $domain->name . ' in Resend.',
            !$resend && !$email->link_tracking => 'No tracked links in this email (or tracking was off).',
            default => 'No click yet.',
        };
        $replyWhy = match (true) {
            (bool) $reply => null,
            $this->detectsReplies($email->owner_user_id) => 'No reply found yet. Replies show here automatically when they arrive.',
            $resend => 'Replies to Resend emails are not detected yet: set up Resend receiving (see Tracking setup on Email Analytics). Use "Record reply" when one arrives.',
            default => 'Replies are not read automatically for this account (no Gmail with read access). Use "Record reply" when one arrives.',
        };

        $at = fn ($e) => $e?->occurred_at?->format('M j, H:i');

        return [
            ['label' => 'Delivered', 'ok' => $resend ? (bool) $email->delivered_at : null,
                'value' => $resend ? ($email->delivered_at ? $email->delivered_at->format('M j, H:i') : ($email->bounced_at ? 'Bounced' : 'Not yet')) : 'Not available (Gmail)',
                'why' => $resend ? null : 'Gmail does not report delivery.'],
            ['label' => 'Opened', 'ok' => (bool) $opened, 'value' => $opened ? $at($opened) : 'Not seen', 'why' => $openWhy],
            ['label' => 'Clicked', 'ok' => (bool) $clicked, 'value' => $clicked ? $at($clicked) : 'Not seen', 'why' => $clickWhy],
            ['label' => 'Replied', 'ok' => (bool) $reply, 'value' => $reply ? $reply->reply_received_at?->format('M j, H:i') . (in_array($reply->source, ['gmail_inbox', 'resend_inbound'], true) ? ' (detected automatically)' : ' (recorded)') : 'Not seen', 'why' => $replyWhy],
        ];
    }

    /** True when replies are found automatically: a Gmail inbox with read access, or Resend receiving in use. */
    private function detectsReplies(int $ownerId): bool
    {
        return ConnectedEmailAccount::where('owner_user_id', $ownerId)->get()->contains->canReadReplies()
            || EmailReplyRecord::where('owner_user_id', $ownerId)->where('source', 'resend_inbound')->exists();
    }

    public function lead(Request $request, int $lead, FollowUpService $followUps)
    {
        $user = $request->user();
        $owner = $user->getAccountOwner();
        $model = SavedLead::whereIn('user_id', $user->getQuotaUserIds())->findOrFail($lead);

        $emails = OutboundEmail::ownedBy($owner)->where('saved_lead_id', $model->id)
            ->with(['sender:id,name', 'reply'])->latest('queued_at')->latest('id')->paginate(20);

        return view('user.email-analytics.lead', [
            'lead' => $model,
            'emails' => $emails,
            'timeline' => $this->timeline->forLead($owner, $model),
            'followUps' => EmailFollowUp::ownedBy($owner)->where('saved_lead_id', $model->id)->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")->orderBy('due_date')->limit(50)->get(),
            'followUpsEnabled' => PlanFeatures::enabled($user, 'email_followups_enabled'),
            'followUpBlocked' => $followUps->blockedReason($model),
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function period(array $data): array
    {
        $to = now()->endOfDay();

        return match ($data['range'] ?? '30d') {
            'today' => [today()->startOfDay(), $to],
            '7d' => [today()->subDays(6)->startOfDay(), $to],
            'custom' => $this->custom($data['from'], $data['to']),
            default => [today()->subDays(29)->startOfDay(), $to],
        };
    }

    private function custom(string $from, string $to): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        // Keep queries and the chart bounded
        if ($start->diffInDays($end) > 366) {
            $start = $end->copy()->subDays(366)->startOfDay();
        }

        return [$start, $end];
    }
}
