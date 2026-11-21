@extends('layouts.app')

@section('title', 'Email Analytics')

@section('content')
@include('user._ui-kit')
@php
    $s = $summary;
    $pct = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.') . '%';
    $ranges = \App\Http\Controllers\EmailTracking\EmailAnalyticsController::RANGES;
    // Phase 5.1: "accepted" counts every provider in the current filter, so name it after that filter
    $hasResend = $s['resend_accepted'] > 0 || \App\Services\PlanFeatures::enabled(auth()->user(), 'resend_connection_enabled');
    $provider = in_array(request('provider'), ['gmail', 'resend'], true) ? request('provider') : ($hasResend ? null : 'gmail');
    $acceptedBy = match ($provider) { 'gmail' => 'Gmail', 'resend' => 'Resend', default => 'Gmail / Resend' };
    $unknownHint = match ($provider) { 'gmail' => 'Check Gmail "Sent"', 'resend' => 'Check your Resend dashboard', default => 'Check Gmail "Sent" or Resend' };
@endphp
<div class="cnm-ui ui-page ui-stack">

    <div class="ui-head">
        <div class="ui-head-main !items-start">
            <span class="ui-head-icon"><i class="fas fa-chart-line"></i></span>
            <div class="min-w-0">
                <h1 class="ui-title">Email Analytics</h1>
                @if($hasResend)
                <p class="ui-sub">Results for emails sent through Gmail and Resend. "Accepted" means the provider took the email for sending; it does not prove it reached the inbox (only Resend reports delivery). Replies are recorded by you. Gmail link clicks are estimates.</p>
                @else
                <p class="ui-sub">Results for emails sent from your Gmail. "Accepted by Gmail" means Gmail took the email for sending; it does not prove it reached the inbox. Replies are recorded by you. Link clicks are estimates.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="ui-card p-3 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 items-end" id="analytics-filters">
        <div>
            <label class="ui-label" for="range">Period</label>
            <select id="range" name="range" class="ui-field"
                    onchange="document.getElementById('custom-range').classList.toggle('hidden', this.value !== 'custom'); if (this.value !== 'custom') this.form.submit();">
                @foreach($ranges as $value => $label)<option value="{{ $value }}" @selected($filters['range'] === $value)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div id="custom-range" class="col-span-2 grid grid-cols-2 gap-2 {{ $filters['range'] === 'custom' ? '' : 'hidden' }}">
            <div>
                <label class="ui-label" for="from">From</label>
                <input id="from" type="date" name="from" value="{{ request('from', $filters['from']->toDateString()) }}" class="ui-field">
            </div>
            <div>
                <label class="ui-label" for="to">To</label>
                <input id="to" type="date" name="to" value="{{ request('to', $filters['to']->toDateString()) }}" class="ui-field">
            </div>
        </div>
        <div>
            <label class="ui-label" for="account">Sender account</label>
            <select id="account" name="account" class="ui-field">
                <option value="">All</option>
                @foreach($accounts as $a)<option value="{{ $a->id }}" @selected((int) request('account') === $a->id)>{{ $a->email }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="ui-label" for="status">Email status</label>
            <select id="status" name="status" class="ui-field">
                <option value="">All</option>
                @foreach(\App\Models\OutboundEmail::LABELS as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $value === 'sent' ? 'Accepted by ' . $acceptedBy : $label }}</option>@endforeach
                <option value="unknown" @selected(request('status') === 'unknown')>Unknown outcome</option>
            </select>
        </div>
        <div>
            <label class="ui-label" for="provider">Provider</label>
            <select id="provider" name="provider" class="ui-field">
                <option value="">All</option>
                <option value="gmail" @selected(request('provider') === 'gmail')>Gmail</option>
                <option value="resend" @selected(request('provider') === 'resend')>Resend</option>
            </select>
        </div>
        @if($campaigns->isNotEmpty())
            <div>
                <label class="ui-label" for="campaign">Campaign</label>
                <select id="campaign" name="campaign" class="ui-field">
                    <option value="">All</option>
                    @foreach($campaigns as $c)<option value="{{ $c->id }}" @selected((int) request('campaign') === $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
        @endif
        @if($members->count() > 1)
            <div>
                <label class="ui-label" for="member">Team member</label>
                <select id="member" name="member" class="ui-field">
                    <option value="">Everyone</option>
                    @foreach($members as $m)<option value="{{ $m->id }}" @selected((int) request('member') === $m->id)>{{ $m->name }}</option>@endforeach
                </select>
            </div>
        @endif
        @if($filters['lead'])<input type="hidden" name="lead" value="{{ $filters['lead'] }}">@endif
        <div class="flex gap-2">
            <button class="ui-btn flex-1">Apply</button>
            <a href="{{ route('user.email-analytics.index') }}" class="ui-btn-light !px-3" title="Reset filters" aria-label="Reset filters"><i class="fas fa-rotate-left"></i></a>
        </div>
        @if($errors->any())<div class="col-span-full text-sm text-red-600">{{ $errors->first() }}</div>@endif
    </form>

    <p class="text-[11.5px] text-slate-500 !mt-2">
        Showing emails queued {{ $filters['from']->format('M j, Y') }} – {{ $filters['to']->format('M j, Y') }}@if($leadName) for <strong>{{ $leadName }}</strong>@endif. All counts below describe these emails, including clicks and replies that came in later.
    </p>

    {{-- Tracking setup: why opens / clicks / replies may be missing, and the one-click fix --}}
    @if(!empty($trackingGaps))
        <section class="ui-card ui-card-b !border-amber-200 !bg-amber-50/60" data-testid="tracking-setup">
            <h2 class="ui-card-t !text-amber-900"><i class="fas fa-triangle-exclamation !text-amber-600"></i> Tracking setup</h2>
            <ul class="mt-2 space-y-2.5">
                @foreach($trackingGaps as $gap)
                    <li class="flex flex-col sm:flex-row sm:items-center gap-2 justify-between bg-white border border-amber-100 rounded-lg px-3 py-2">
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold text-gray-900">{{ $gap['title'] }}</p>
                            <p class="text-[12px] text-slate-600">{{ $gap['text'] }}</p>
                        </div>
                        @if(!empty($gap['action']))
                            @if($gap['action']['type'] === 'resend_tracking' && !auth()->user()->isTeamMember())
                                <form method="POST" action="{{ route('user.email-sending.resend.domains.tracking', $gap['action']['domain_id']) }}" class="shrink-0">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="open_tracking" value="1">
                                    <input type="hidden" name="click_tracking" value="1">
                                    <input type="hidden" name="confirm" value="1">
                                    <button class="ui-btn ui-sm cursor-pointer"><i class="fas fa-toggle-on"></i> {{ $gap['action']['label'] }}</button>
                                </form>
                            @elseif($gap['action']['type'] === 'link')
                                <a href="{{ $gap['action']['url'] }}" class="ui-btn-light ui-sm shrink-0">{{ $gap['action']['label'] }}</a>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Summary cards --}}
    <div class="ui-stats c5" data-testid="summary-cards">
        @foreach([
            ['Emails queued', $s['queued'], null, 'fa-inbox'],
            ['Accepted by ' . $acceptedBy, $s['accepted'], 'Not proof of inbox delivery', 'fa-check'],
            ['Failed', $s['failed'], null, 'fa-circle-xmark'],
            ['Unknown outcome', $s['unknown'], $unknownHint, 'fa-circle-question'],
            ['Opened', $s['opened_emails'], 'Estimate · ' . $pct($s['accepted'] > 0 ? round($s['opened_emails'] / $s['accepted'] * 100, 1) : null) . ' of sent emails', 'fa-envelope-open'],
            ['Unsubscribes', $s['unsubscribes'], null, 'fa-user-slash'],
            ['Recorded link requests', $s['link_requests'], 'Includes scanners', 'fa-link'],
            ['Estimated unique clicks', $s['unique_clicks_estimate'], 'Estimate · ' . $pct($s['click_rate_estimate']) . ' of tracked emails', 'fa-arrow-pointer'],
            ['Replies', $s['replies'], $s['replies_detected'] . ' found in Gmail · ' . ($s['replies'] - $s['replies_detected']) . ' recorded by you', 'fa-reply'],
            ['Reply rate', $pct($s['reply_rate']), $s['replies_on_accepted'] . ' of ' . $s['accepted'] . ' accepted emails', 'fa-percent'],
            ['Follow-ups due', $s['follow_ups']['overdue'] + $s['follow_ups']['today'], $s['follow_ups']['overdue'] . ' overdue · ' . $s['follow_ups']['next_7_days'] . ' next 7 days', 'fa-bell'],
        ] as [$label, $value, $hint, $icon])
            <div class="ui-stat">
                <div class="ui-stat-l"><i class="fas {{ $icon }}"></i> {{ $label }}</div>
                <div class="ui-stat-v">{{ $value }}</div>
                @if($hint)<div class="ui-stat-h">{{ $hint }}</div>@endif
            </div>
        @endforeach
    </div>

    {{-- Phase 5: delivery evidence reported by the provider (Resend webhooks only) --}}
    @if($s['resend_accepted'] > 0 || \App\Services\PlanFeatures::enabled(auth()->user(), 'resend_connection_enabled'))
    <section class="ui-card ui-card-b" data-testid="provider-evidence">
        <h2 class="ui-card-t"><i class="fas fa-envelope-circle-check"></i> Provider-reported delivery (Resend)</h2>
        <p class="ui-card-d mb-2.5">Gmail does not report delivery, so Gmail emails are shown as "Not available", never as 0 delivered. Resend numbers come from verified Resend webhooks.</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 text-[12.5px]">
            @foreach([
                ['Accepted by Resend', $s['resend_accepted']],
                ['Delivered (verified)', $s['delivered'] === null ? 'Not available' : $s['delivered'] . ($s['delivery_rate'] !== null ? ' · ' . $pct($s['delivery_rate']) : '')],
                ['Bounced', $s['bounced'] ?? 'Not available'],
                ['Spam complaints', $s['complained'] ?? 'Not available'],
                ['Opens reported', $s['provider_opens'] . ' (' . $s['provider_unique_opens'] . ' emails)'],
                ['Clicks reported', $s['provider_clicks'] . ' (' . $s['provider_unique_clicks'] . ' emails)'],
            ] as [$label, $value])
                <div class="border border-slate-100 bg-slate-50 rounded-lg px-3 py-2"><div class="text-[11px] text-slate-500">{{ $label }}</div><div class="font-semibold text-gray-900 mt-0.5">{{ $value }}</div></div>
            @endforeach
        </div>
        <p class="text-[11px] text-gray-500 mt-2">Opens and clicks reported by Resend can include automatic image loading and security scanners. Delivery rate = delivered / emails accepted by Resend. Gmail link requests above are measured separately and are never added to Resend clicks.</p>
    </section>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3.5">
        <section class="ui-card ui-card-b lg:col-span-2">
            <h2 class="ui-card-t"><i class="fas fa-chart-column"></i> Daily activity</h2>
            <p class="ui-card-d mb-2">By activity date: accepted by {{ $acceptedBy }}, estimated Gmail person clicks (scanners excluded), replies you recorded.</p>
            @if(array_sum($trend['accepted']) + array_sum($trend['clicks']) + array_sum($trend['replies']) === 0)
                <p class="text-sm text-gray-500 py-10 text-center">No activity in this period yet.</p>
            @else
                <div class="relative h-56"><canvas id="trend-chart" aria-label="Daily email activity chart" role="img"></canvas></div>
            @endif
        </section>

        <section class="ui-card ui-card-b">
            <h2 class="ui-card-t mb-2.5"><i class="fas fa-reply"></i> Replies by outcome</h2>
            @if($s['replies'] === 0)
                <p class="text-sm text-gray-500">No replies recorded for these emails. Open an email below and use "Record reply" when someone answers.</p>
            @else
                <ul class="space-y-1.5 text-[12.5px]">
                    @foreach(\App\Models\EmailReplyRecord::STATUSES as $key => $label)
                        @php $n = $s['replies_by_status'][$key] ?? 0; @endphp
                        <li class="flex items-center gap-2">
                            <span class="w-36 text-gray-700">{{ $label }}</span>
                            <span class="flex-1 h-2 bg-orange-50 rounded"><span class="block h-2 bg-orange-400 rounded" style="width: {{ $s['replies'] ? round($n / $s['replies'] * 100) : 0 }}%"></span></span>
                            <span class="w-8 text-right font-medium text-gray-900">{{ $n }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-3 pt-3 border-t border-slate-100 text-[11.5px] text-slate-500 space-y-1">
                <p><strong class="text-gray-700">Link requests:</strong> {{ $s['human_clicks'] }} probable person · {{ $s['scanner_requests'] }} likely scanner · {{ $s['internal_requests'] }} your own tests.</p>
                <p>Email security systems often open links automatically, so a click does not guarantee a person engaged.</p>
                <p>@if($hasResend)Gmail: open tracking and inbox delivery are not measured. Resend: see provider-reported delivery above.@else Open tracking and inbox delivery are not measured.@endif Automatic reply detection is not available.</p>
            </div>
        </section>
    </div>

    @if($upcomingFollowUps->isNotEmpty())
        <section class="ui-card ui-card-b">
            <div class="flex items-center justify-between mb-2">
                <h2 class="ui-card-t"><i class="fas fa-bell"></i> Follow-up reminders</h2>
                <a href="{{ route('user.email-follow-ups.index') }}" class="ui-link text-[12.5px]">All follow-ups</a>
            </div>
            <ul class="divide-y divide-gray-100">
                @foreach($upcomingFollowUps as $f)
                    <li class="py-1.5 flex flex-wrap items-center justify-between gap-2 text-[12.5px]">
                        @if($f->saved_lead_id)
                            <a href="{{ route('user.email-analytics.lead', $f->saved_lead_id) }}" class="text-gray-900 font-medium hover:text-orange-600">{{ $f->lead?->name ?? 'Lead' }}</a>
                        @else
                            <span class="text-gray-900 font-medium">{{ $f->contact?->displayName() ?? 'Contact' }}</span>
                        @endif
                        <span class="{{ $f->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-500' }}">{{ $f->isOverdue() ? 'Overdue · ' : '' }}{{ $f->due_date->format('D, M j') }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Emails --}}
    <section class="ui-card">
        <div class="ui-card-h"><h2 class="ui-card-t"><i class="fas fa-envelope"></i> Emails ({{ $emails->total() }})</h2></div>
        <div class="overflow-x-auto">
            <table class="ui-table min-w-full">
                <thead>
                    <tr><th>Business</th><th>Subject</th><th>Status</th><th>Clicks (est.)</th><th>Reply</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($emails as $e)
                        <tr>
                            <td>
                                @if($e->saved_lead_id)
                                    <a href="{{ route('user.email-analytics.lead', $e->saved_lead_id) }}" class="text-gray-900 hover:text-orange-600">{{ $e->lead?->name ?? '—' }}</a>
                                @else — @endif
                                <div class="text-xs text-gray-500 break-all">{{ $e->to_email }}</div>
                            </td>
                            <td class="text-gray-700 max-w-xs break-words">{{ $e->subject }}<div class="text-xs text-gray-500">{{ $e->sender?->name }} · {{ $e->queued_at?->format('M j, H:i') }}</div></td>
                            <td>
                                <span class="text-xs font-medium">{{ $e->status === 'sent' ? 'Accepted by ' . ($e->isResend() ? 'Resend' : 'Gmail') : $e->label() }}</span>
                                <div class="text-[11px] text-gray-500">{{ $e->providerLabel() }} · Delivery: {{ $e->deliveryLabel() }}</div>
                            </td>
                            <td class="text-gray-700">
                                @if($e->link_tracking){{ $e->human_clicks_count }} <span class="text-xs text-gray-400">/ {{ $e->link_requests_count }} req.</span>@else<span class="text-xs text-gray-400">Not tracked</span>@endif
                            </td>
                            <td>
                                @if($e->reply)<span class="ui-badge is-ok">{{ $e->reply->label() }}</span>@else<span class="text-xs text-gray-400">—</span>@endif
                            </td>
                            <td class="text-right"><a href="{{ route('user.email-analytics.email', $e->id) }}" class="ui-btn-light ui-sm">Details</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="!py-8 text-center text-[12.5px] text-slate-500">No emails in this period. Send an approved draft from <a href="{{ route('user.ai-outreach.index') }}" class="text-orange-600 hover:underline">AI Outreach</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($emails->hasPages())<div class="ui-card-f">{{ $emails->links() }}</div>@endif
    </section>
</div>

@if(array_sum($trend['accepted']) + array_sum($trend['clicks']) + array_sum($trend['replies']) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const el = document.getElementById('trend-chart');
    if (!el || typeof Chart === 'undefined') return;
    const data = @json($trend);
    new Chart(el, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                { label: @json('Accepted by ' . $acceptedBy), data: data.accepted, backgroundColor: '#F97316' },
                { label: 'Est. person clicks', data: data.clicks, backgroundColor: '#FDBA74' },
                { label: 'Replies (recorded)', data: data.replies, backgroundColor: '#111827' },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
        },
    });
})();
</script>
@endif
@endsection
