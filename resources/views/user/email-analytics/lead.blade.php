@extends('layouts.app')

@section('title', 'Lead email activity')

@section('content')
<div class="p-4 lg:p-8 max-w-6xl mx-auto space-y-6">
    <a href="{{ route('user.email-analytics.index') }}" class="text-sm text-gray-600 hover:text-orange-600"><i class="fas fa-arrow-left mr-1"></i> Email Analytics</a>

    <div class="bg-white border border-orange-100 rounded-2xl p-5 sm:p-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 break-words">{{ $lead->name }}</h1>
            <p class="text-sm text-gray-600 mt-1 break-all">{{ $lead->email ?: 'No email address' }}@if($lead->category) · {{ $lead->category }}@endif</p>
        </div>
        <div class="text-sm text-right">
            <div class="text-xs text-gray-500">Lead status</div>
            <div class="font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', $lead->contact_status ?? 'not_contacted')) }}</div>
            @if($lead->follow_up_date)<div class="text-xs text-gray-500 mt-1">Next follow-up {{ $lead->follow_up_date->format('M j, Y') }}</div>@endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white border border-gray-200 rounded-2xl">
                <div class="p-5 border-b border-gray-100"><h2 class="text-base font-semibold text-gray-900">Email history</h2></div>
                <ul class="divide-y divide-gray-100">
                    @forelse($emails as $e)
                        <li class="p-4 flex flex-wrap items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <div class="font-medium text-gray-900 break-words">{{ $e->subject }}</div>
                                <div class="text-xs text-gray-500">{{ $e->status === 'sent' ? 'Accepted by Gmail' : $e->label() }} · {{ ($e->sent_at ?? $e->queued_at)?->format('M j, Y g:i A') }} · {{ $e->sender?->name }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($e->reply)<span class="text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200">{{ $e->reply->label() }}</span>@endif
                                <a href="{{ route('user.email-analytics.email', $e->id) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Details</a>
                            </div>
                        </li>
                    @empty
                        <li class="p-8 text-center text-sm text-gray-500">No emails sent to this lead yet.</li>
                    @endforelse
                </ul>
                @if($emails->hasPages())<div class="p-4 border-t border-gray-100">{{ $emails->links() }}</div>@endif
            </section>

            <section class="bg-white border border-gray-200 rounded-2xl p-5">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Activity timeline</h2>
                @include('user.email-analytics._timeline', ['timeline' => $timeline])
            </section>
        </div>

        @if($followUpsEnabled)
            <section class="bg-white border border-gray-200 rounded-2xl p-5 h-fit">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Follow-ups</h2>
                @foreach($followUps as $f)
                    <div class="mb-3 text-sm flex items-start justify-between gap-2">
                        <div>
                            <div class="{{ $f->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-800' }}">{{ $f->due_date->format('D, M j, Y') }} · {{ ucfirst($f->status) }}</div>
                            @if($f->notes)<div class="text-xs text-gray-500 break-words">{{ $f->notes }}</div>@endif
                        </div>
                        @if($f->status === 'open')
                            <div class="flex gap-1">
                                <form method="POST" action="{{ route('user.email-follow-ups.complete', $f->id) }}">@csrf<button class="text-xs px-2.5 py-1 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Done</button></form>
                                <form method="POST" action="{{ route('user.email-follow-ups.cancel', $f->id) }}">@csrf<button class="text-xs px-2 py-1 text-gray-400 hover:text-red-600" title="Cancel"><i class="fas fa-times"></i></button></form>
                            </div>
                        @endif
                    </div>
                @endforeach
                @include('user.email-analytics._follow-up-form', ['leadId' => $lead->id, 'followUpBlocked' => $followUpBlocked])
            </section>
        @endif
    </div>
</div>
@endsection
