@extends('layouts.app')

@section('title', $campaign->name)

@section('content')
@php $p = $progress; @endphp
<div class="p-4 lg:p-8 max-w-6xl mx-auto space-y-6">
    @include('user.campaigns._nav', ['active' => 'campaigns'])
    @include('user.partials._other-errors', ['inline' => []])

    <div class="bg-white border border-orange-100 rounded-2xl p-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $campaign->name }}</h1>
            <p class="text-sm text-gray-600">Status: <strong data-testid="campaign-status">{{ $campaign->label() }}</strong>
                @if($campaign->scheduled_at) · scheduled {{ $campaign->scheduled_at->format('M j, Y H:i') }}@endif</p>
            @if($campaign->last_error && $campaign->last_error !== 'broadcast_requested')<p class="text-sm text-red-600 mt-1">{{ $campaign->last_error }}</p>@endif
        </div>
        <div class="flex flex-wrap gap-2">
            @if($campaign->isEditable())
                <a href="{{ route('user.campaigns.edit', $campaign->id) }}" class="text-sm px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Edit</a>
            @endif
            @if($campaign->isCancellable())
                <form method="POST" action="{{ route('user.campaigns.cancel', $campaign->id) }}" onsubmit="return confirm('Cancel this campaign?');">@csrf
                    <button class="text-sm px-4 py-2 rounded-lg border border-gray-300 text-red-600 hover:bg-red-50">Cancel campaign</button></form>
            @endif
        </div>
    </div>

    @if($campaign->status !== 'draft')
        <section class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-sm" data-testid="campaign-progress">
            @foreach(['Recipients' => $p['recipients'], 'Skipped' => $p['skipped'], 'Accepted by Resend' => $p['accepted'], 'Delivered' => $p['delivered'], 'Bounced' => $p['bounced'], 'Complaints' => $p['complained'], 'Opened (reported)' => $p['opened'], 'Clicked (reported)' => $p['clicked']] as $label => $value)
                <div class="bg-white border border-gray-200 rounded-xl p-3"><div class="text-xs text-gray-500">{{ $label }}</div><div class="text-lg font-bold text-gray-900">{{ $value }}</div></div>
            @endforeach
        </section>
        <p class="text-xs text-gray-500 -mt-3">Delivered, bounced and complaint numbers come from verified Resend webhooks. Opens and clicks can include automatic image loading and security scanners. Unsubscribes: {{ $p['unsubscribed'] }}.</p>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="bg-white border border-gray-200 rounded-2xl p-5 lg:col-span-2">
            <h2 class="text-base font-semibold text-gray-900 mb-2">Preview</h2>
            <div class="text-xs text-gray-500 mb-1">From {{ $review['sender']?->fromHeader() ?? '—' }}@if($review['sample']) · shown for {{ $review['sample']->email }}@endif</div>
            <div class="font-semibold text-gray-900 mb-2 break-words">{{ $review['subject'] }}</div>
            <pre class="whitespace-pre-wrap break-words font-sans text-sm text-gray-800 bg-gray-50 border border-gray-200 rounded-lg p-3 max-h-96 overflow-y-auto">{{ $review['body'] }}</pre>
            <p class="text-xs text-gray-500 mt-2">Resend tracking on this domain: opens {{ $review['tracking']['open'] ? 'on' : 'off' }}, clicks {{ $review['tracking']['click'] ? 'on' : 'off' }} (changed only in Email Sending → Resend). CustomerNearMe link tracking is not added to Resend emails.</p>
        </section>

        <section class="bg-white border border-gray-200 rounded-2xl p-5">
            @if($campaign->isEditable())
                <h2 class="text-base font-semibold text-gray-900 mb-2">Review & approve</h2>
                <div class="text-sm space-y-1" data-testid="campaign-review">
                    <div><strong>{{ $review['eligible'] }}</strong> eligible opted-in recipients</div>
                    <div class="text-xs text-gray-500">{{ $review['excluded_suppressed'] }} opted-in contact(s) excluded because they are on your do-not-email list.</div>
                </div>
                @if($review['problems'] !== [])
                    <ul class="mt-3 text-sm text-amber-800 list-disc list-inside">@foreach($review['problems'] as $prob)<li>{{ $prob }}</li>@endforeach</ul>
                @endif

                <form method="POST" action="{{ route('user.campaigns.test', $campaign->id) }}" class="mt-4">@csrf
                    <button class="w-full text-sm px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50" @disabled($campaign->test_sends >= 5)>Send test to {{ auth()->user()->email }} ({{ $campaign->test_sends }}/5)</button>
                </form>

                @if($review['problems'] === [])
                    <form method="POST" action="{{ route('user.campaigns.approve', $campaign->id) }}" class="mt-4 space-y-2 text-sm">@csrf
                        <input type="hidden" name="expected_count" value="{{ $review['eligible'] }}">
                        <label class="flex items-center gap-2"><input type="radio" name="send_mode" value="now" checked onchange="document.getElementById('sched').classList.add('hidden')"> Send now</label>
                        <label class="flex items-center gap-2"><input type="radio" name="send_mode" value="schedule" onchange="document.getElementById('sched').classList.remove('hidden')"> Schedule</label>
                        <input id="sched" type="datetime-local" name="scheduled_at" class="hidden w-full px-3 py-2 border border-gray-300 rounded-lg" min="{{ now()->addMinutes(15)->format('Y-m-d\TH:i') }}">
                        <label class="flex items-start gap-2"><input type="checkbox" name="confirm_consent" value="1" required class="mt-0.5"> Every one of these {{ $review['eligible'] }} people opted in to receive my emails.</label>
                        <label class="flex items-start gap-2"><input type="checkbox" name="confirm" value="1" required class="mt-0.5"> I reviewed the campaign and approve sending it through my Resend account.</label>
                        <button class="w-full px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-semibold">Approve campaign</button>
                    </form>
                @endif
            @else
                <h2 class="text-base font-semibold text-gray-900 mb-2">Approval</h2>
                <p class="text-sm text-gray-600">Approved {{ $campaign->approved_at?->format('M j, Y H:i') }} for {{ $campaign->recipient_count }} recipients.</p>
                @if($campaign->status === 'unknown')<p class="text-sm text-amber-700 mt-2">Open Broadcasts in your Resend dashboard to see whether it was created. CustomerNearMe will not create it again.</p>@endif
            @endif
        </section>
    </div>

    @if($campaign->status !== 'draft')
        <section class="bg-white border border-gray-200 rounded-2xl">
            <div class="p-5 border-b border-gray-100"><h2 class="text-base font-semibold text-gray-900">Recipients</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase"><tr><th class="px-4 py-3">Recipient</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Delivery</th><th class="px-4 py-3">Reply</th><th class="px-4 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recipients as $r)
                            <tr>
                                <td class="px-4 py-2 break-all">{{ $r->to_email }}</td>
                                <td class="px-4 py-2 text-xs">{{ $r->status === 'cancelled' && $r->error_code ? 'Skipped (' . str_replace(['skipped_', '_'], ['', ' '], $r->error_code) . ')' : $r->label() }}</td>
                                <td class="px-4 py-2 text-xs">{{ $r->deliveryLabel() }}</td>
                                <td class="px-4 py-2 text-xs">{{ $r->reply?->label() ?? '—' }}</td>
                                <td class="px-4 py-2 text-right">@if(\App\Services\PlanFeatures::enabled(auth()->user(), 'email_analytics_enabled') && in_array($r->status, ['sent', 'failed'], true))<a href="{{ route('user.email-analytics.email', $r->id) }}" class="text-xs px-3 py-1 rounded-lg border border-gray-300 hover:bg-gray-50">Details & reply</a>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($recipients->hasPages())<div class="p-4 border-t border-gray-100">{{ $recipients->links() }}</div>@endif
        </section>
    @endif
</div>
@endsection
