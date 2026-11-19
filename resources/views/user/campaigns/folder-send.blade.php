@extends('layouts.app')

@section('title', 'Send to a lead folder')

@section('content')
@php
    $ready = array_values(array_filter($rows, fn ($r) => $r['ready']));
    $skipped = array_values(array_filter($rows, fn ($r) => !$r['ready']));
    $toApprove = count(array_filter($ready, fn ($r) => $r['needs_approval']));
@endphp
<div class="p-4 lg:p-8 max-w-6xl mx-auto space-y-6">
    @if(\Illuminate\Support\Facades\Route::has('user.campaigns.index') && \App\Services\PlanFeatures::enabled(auth()->user(), 'resend_campaigns_enabled'))
        @include('user.campaigns._nav', ['active' => 'new'])
    @endif

    <div class="bg-white border border-orange-100 rounded-2xl p-5">
        <h1 class="text-xl font-bold text-gray-900">Send to a lead folder</h1>
        <p class="text-sm text-gray-600 mt-1">Every lead gets <strong>its own AI email</strong> from AI Outreach, sent one at a time with a pause in between. Each email includes your postal address and an unsubscribe link, and anyone on your do-not-email list is skipped.</p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700" role="alert"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- Choose folder / provider / sender (reloads the review) --}}
    <form method="GET" action="{{ route('user.folder-sends.create') }}" class="bg-white border border-gray-200 rounded-2xl p-5 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm" id="choose">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="folder_id">Lead folder</label>
            <select id="folder_id" name="folder_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white" onchange="this.form.submit()">
                <option value="">Choose a folder…</option>
                @foreach($folders as $f)<option value="{{ $f->id }}" @selected($folder?->id === $f->id)>{{ $f->name }} ({{ $f->leads_count }} leads · {{ $f->ready_count }} AI emails)</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1" for="provider">Send through</label>
            <select id="provider" name="provider" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white" onchange="this.form.submit()">
                @forelse($providers as $k => $label)<option value="{{ $k }}" @selected($provider === $k)>{{ $label }}</option>@empty<option value="">No sending method connected</option>@endforelse
            </select>
        </div>
        @if($provider === 'resend')
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="sender_id">Resend sender</label>
                <select id="sender_id" name="sender_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white" onchange="this.form.submit()">
                    @foreach($senders as $s)<option value="{{ $s->id }}" @selected($senderId === $s->id)>{{ $s->from_name }} &lt;{{ $s->from_email }}&gt;</option>@endforeach
                </select>
            </div>
        @endif
    </form>

    @if($provider === 'resend')
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-900">
            Resend's policy does not allow cold outreach. Leads who are not opted-in contacts are only included when "Resend emails to leads" is switched on ({{ $leadOutreach ? 'it is on' : 'it is off' }}); complaints or bounces can get your Resend account limited. Gmail is the safer choice for first contact.
        </div>
    @endif

    @if($folder)
        @if($problems)
            <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-900" role="alert">
                <p class="font-medium">Fix this before sending:</p>
                <ul class="list-disc list-inside mt-1">@foreach($problems as $p)<li>{{ $p }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="bg-white border border-gray-200 rounded-2xl">
            <div class="p-5 border-b border-gray-100 flex flex-wrap items-center gap-3 text-sm">
                <h2 class="text-base font-semibold text-gray-900 mr-auto">{{ $folder->name }}</h2>
                <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-800 font-medium">{{ count($ready) }} ready</span>
                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-medium">{{ count($skipped) }} skipped</span>
                @if($toApprove)<span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 font-medium">{{ $toApprove }} not approved yet</span>@endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">
                        <tr><th class="px-4 py-3">Business</th><th class="px-4 py-3">To</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Its AI email</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse(array_merge($ready, $skipped) as $r)
                            <tr class="{{ $r['ready'] ? '' : 'bg-gray-50/60 text-gray-500' }}">
                                <td class="px-4 py-3 align-top font-medium {{ $r['ready'] ? 'text-gray-900' : '' }}">{{ $r['lead']->name }}</td>
                                <td class="px-4 py-3 align-top whitespace-nowrap">{{ $r['recipient'] ?: '—' }}</td>
                                <td class="px-4 py-3 align-top whitespace-nowrap">
                                    @if($r['ready'])
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200">Ready{{ $r['needs_approval'] ? ' · will be approved' : '' }}</span>
                                    @else
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200">Skip: {{ $r['reason'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top min-w-[16rem]">
                                    @if($r['draft'] && $r['draft']->subject)
                                        <details>
                                            <summary class="cursor-pointer text-gray-800">{{ $r['draft']->subject }}</summary>
                                            <pre class="mt-2 whitespace-pre-wrap break-words font-sans text-xs text-gray-700 bg-gray-50 border border-gray-200 rounded-lg p-3 max-h-60 overflow-y-auto">{{ $r['draft']->body }}</pre>
                                            <a href="{{ route('user.ai-outreach.analysis', $r['draft']->analysis_id ?? 0) }}" class="text-xs text-orange-600 hover:underline">Edit in AI Outreach</a>
                                        </details>
                                    @else — @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">This folder has no leads.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if(count($ready) > 0 && $problems === [])
            <form method="POST" action="{{ route('user.folder-sends.store') }}" class="bg-white border border-orange-200 rounded-2xl p-5 space-y-3 text-sm" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <input type="hidden" name="folder_id" value="{{ $folder->id }}">
                <input type="hidden" name="provider" value="{{ $provider }}">
                <input type="hidden" name="sender_id" value="{{ $provider === 'resend' ? $senderId : '' }}">
                <input type="hidden" name="expected_ready" value="{{ count($ready) }}">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                    <label for="interval" class="text-gray-700">Pause between two emails</label>
                    <select id="interval" name="interval" class="px-3 py-2 border border-gray-300 rounded-lg bg-white">
                        @foreach($intervals as $m)<option value="{{ $m }}" @selected($m === 3)>{{ $m }} minute{{ $m === 1 ? '' : 's' }}</option>@endforeach
                    </select>
                    <span class="text-xs text-gray-500">≈ {{ count($ready) }} emails take {{ max(0, count($ready) - 1) * 3 }} min at 3 minutes. Spacing emails out protects deliverability.</span>
                </div>
                <label class="flex items-start gap-2 text-gray-800">
                    <input type="checkbox" name="confirm" value="1" required class="mt-0.5">
                    <span>I reviewed these {{ count($ready) }} AI emails{{ $toApprove ? ' (' . $toApprove . ' will be approved now)' : '' }}, the facts in them are accurate, and I want each one sent to its lead through {{ $providers[$provider] ?? $provider }}.</span>
                </label>
                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-semibold"><i class="fas fa-paper-plane mr-1"></i> Approve & start sending {{ count($ready) }} email(s)</button>
                <p class="text-xs text-gray-500">You can stop at any time; emails that have not been sent yet are then cancelled.</p>
            </form>
        @endif
    @endif
</div>
@endsection
