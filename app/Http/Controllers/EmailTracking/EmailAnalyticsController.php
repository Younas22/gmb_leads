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

        return view('user.email-analytics.email', [
            'email' => $model,
            'links' => $links,
            'timeline' => $this->timeline->forEmail($owner, $model),
            'canRecordReply' => $replies->canRecord($model),
            'followUps' => EmailFollowUp::ownedBy($owner)->where('outbound_email_id', $model->id)->latest('due_date')->get(),
            'followUpsEnabled' => PlanFeatures::enabled($user, 'email_followups_enabled'),
            'followUpBlocked' => match (true) {
                (bool) $model->lead => $followUps->blockedReason($model->lead),
                (bool) $model->contact => $followUps->blockedReasonForContact($model->contact),
                default => 'This email is not linked to a lead or contact.',
            },
        ]);
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
