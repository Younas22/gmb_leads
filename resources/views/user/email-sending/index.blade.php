@extends('layouts.app')

@section('title', 'Email Sending')

@section('content')
@include('user._ui-kit')
<div class="cnm-ui ui-page ui-stack">
    <div class="ui-head">
        <div class="ui-head-main">
            <span class="ui-head-icon"><i class="fas fa-paper-plane"></i></span>
            <div class="min-w-0">
                <h1 class="ui-title">Email Sending</h1>
                <p class="ui-sub">Send your approved AI drafts one at a time from <strong>your own Gmail</strong>, so replies land straight in your inbox. Every email needs your confirmation; nothing is sent automatically.</p>
            </div>
        </div>
        @include('user.email-sending._tabs', ['active' => 'gmail'])
    </div>

    {{-- Settings side by side; the email table below gets the full width --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5 items-start">
        {{-- Gmail connection --}}
        <section class="ui-card">
            <div class="ui-card-h"><h2 class="ui-card-t"><i class="fab fa-google"></i> Gmail connection</h2></div>
            <div class="ui-card-b">
            @if(!$configured)
                <p class="text-[12.5px] text-slate-600">Gmail sending is not configured on this server yet.</p>
            @elseif($account && $account->status === 'active')
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-[13px] font-semibold text-gray-900 break-all" data-testid="gmail-address">{{ $account->email }}</span>
                        </div>
                        <p class="text-[11.5px] text-slate-500 mt-0.5">Connected {{ $account->connected_at?->diffForHumans() }}{{ $account->last_used_at ? ' · last used ' . $account->last_used_at->diffForHumans() : '' }}</p>
                    </div>
                    <form method="POST" action="{{ route('user.email-sending.disconnect') }}" onsubmit="return confirm('Disconnect this Gmail account? Pending emails will be cancelled.');">
                        @csrf
                        <button class="ui-btn-danger ui-sm"><i class="fas fa-unlink"></i> Disconnect & revoke access</button>
                    </form>
                </div>
                <p class="text-[11.5px] text-slate-500 mt-2"><i class="fas fa-shield-halved text-green-600 mr-1"></i>Permission: send email only. CustomerNearMe cannot read your inbox.</p>
            @else
                @if($account && $account->status === 'needs_reconnect')
                    <div class="ui-note is-warn mb-3 !py-2">
                        <span>Access to <strong>{{ $account->email }}</strong> expired or was revoked. Reconnect to keep sending.</span>
                    </div>
                @else
                    <p class="text-[12.5px] text-slate-600 mb-3">No Gmail account connected. Connect once and send every approved draft from your own address.</p>
                @endif
                <form method="POST" action="{{ route('user.email-sending.connect') }}">
                    @csrf
                    <button class="ui-btn w-full">
                        <i class="fab fa-google"></i> {{ $account && $account->status === 'needs_reconnect' ? 'Reconnect Gmail' : 'Connect Gmail' }}
                    </button>
                </form>
                <p class="text-[11.5px] text-slate-500 mt-2">We only ask for permission to <strong>send</strong> email. You can revoke it at any time.</p>
            @endif

            <div class="mt-3 pt-3 border-t border-slate-100">
                <div class="flex justify-between text-[12.5px]"><span class="text-slate-600">Sent today (whole account)</span><span class="font-semibold text-gray-900">{{ $sentToday }} / {{ $dailyLimit === -1 ? 'unlimited' : $dailyLimit }}</span></div>
                <p class="text-[11px] text-slate-500 mt-1.5">Emails include your name, postal address and an unsubscribe link. Only email businesses where you have a legitimate reason to reach out.</p>
            </div>
            </div>
        </section>

        {{-- Phase 4: link tracking preference (per sender, off by default) --}}
        <section class="ui-card">
            <div id="link-tracking">
                <div class="ui-card-h"><h2 class="ui-card-t"><i class="fas fa-link"></i> Link tracking</h2></div>
                <div class="ui-card-b">
                @if(!$trackingAvailable)
                    <p class="text-[12px] text-slate-500">Not included in your plan. Links in your emails are always sent unchanged.</p>
                @else
                    <form method="POST" action="{{ route('user.email-sending.tracking.update') }}" class="space-y-2.5 text-[12.5px]">
                        @csrf @method('PUT')
                        <input type="hidden" name="link_tracking_enabled" value="0">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="ui-check" name="link_tracking_enabled" value="1" @checked(old('link_tracking_enabled', $trackingSetting->link_tracking_enabled))
                                   onchange="document.getElementById('tracking-disclosure').classList.toggle('hidden', !this.checked)">
                            <span class="text-gray-800 font-medium">Track link clicks in emails I send</span>
                        </label>
                        <div id="tracking-disclosure" class="{{ old('link_tracking_enabled', $trackingSetting->link_tracking_enabled) ? '' : 'hidden' }} text-[11.5px] text-slate-600 bg-[#FFF7ED] border border-[#FED7AA] rounded-lg p-2.5 space-y-1.5">
                            <p><strong>How it works:</strong> web links in your email are replaced with a {{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'CustomerNearMe' }} address that records the request and immediately forwards to the original page. Unsubscribe links are never changed. Your text, subject and recipient stay the same.</p>
                            <p><strong>Limits:</strong> email security systems often open links automatically, so a recorded request does not mean a person clicked. Clicks are shown as estimates. No open-tracking pixels are used.</p>
                            <p><strong>Privacy:</strong> no IP address or browser details are stored, only the time, which link, and a daily-changing anonymous fingerprint used to estimate unique clicks. Depending on where your recipients are (for example the EU or UK), you may need to tell them you track links. Check with your legal adviser.</p>
                            <label class="flex items-start gap-2 pt-1 cursor-pointer">
                                <input type="checkbox" name="accept_disclosure" value="1" class="ui-check mt-0.5" @checked($trackingSetting->disclosure_accepted_at)>
                                <span>I understand how link tracking works and will use it responsibly.</span>
                            </label>
                            @error('accept_disclosure')<p class="text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-center gap-2">
                            <label for="retention_days" class="text-[12px] text-slate-600">Keep click data for</label>
                            <select id="retention_days" name="retention_days" class="ui-field ui-sm !w-auto">
                                @foreach(\App\Models\EmailTrackingSetting::RETENTION_OPTIONS as $days)
                                    <option value="{{ $days }}" @selected($trackingSetting->retentionDays() === $days)>{{ $days }} days</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center justify-between gap-2 flex-wrap pt-1">
                            <p class="text-[11px] text-slate-500">Applies to emails you send after saving. Status: <strong>{{ $trackingSetting->link_tracking_enabled ? 'On' : 'Off' }}</strong></p>
                            <button class="ui-btn-light ui-sm">Save tracking settings</button>
                        </div>
                    </form>
                @endif
                </div>
            </div>
        </section>
    </div>

    {{-- History (full width) --}}
    <section class="ui-card">
        <div class="ui-card-h">
            <div>
                <h2 class="ui-card-t"><i class="fas fa-clock-rotate-left"></i> Sent emails</h2>
                <p class="ui-card-d">One-to-one emails (Gmail and Resend). Campaign emails are on the <a href="{{ \Illuminate\Support\Facades\Route::has('user.campaigns.index') ? route('user.campaigns.index') : '#' }}" class="ui-link">Campaigns</a> page.</p>
            </div>
            <form method="GET" class="flex gap-2">
                <select name="status" class="ui-field ui-sm !w-auto" aria-label="Filter by status" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach(\App\Models\OutboundEmail::LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                    <option value="unknown" @selected(request('status') === 'unknown')>Unknown outcome</option>
                </select>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="ui-table min-w-full">
                <thead>
                    <tr><th>Business</th><th>To</th><th>Subject</th><th>Status</th><th class="!text-right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($history as $m)
                        @php
                            $cls = match (true) {
                                $m->status === 'failed' && $m->outcome_unknown => 'is-warn',
                                $m->status === 'sent' => 'is-ok',
                                $m->status === 'failed' => 'is-bad',
                                $m->status === 'cancelled' => '',
                                default => 'is-brand',
                            };
                        @endphp
                        <tr>
                            <td class="text-gray-900">{{ $m->lead?->name ?? '—' }}<div class="text-[11.5px] text-slate-500">{{ $m->sender?->name }}</div></td>
                            <td class="text-slate-700 whitespace-nowrap">{{ $m->to_email }}</td>
                            <td class="text-slate-700 max-w-xs break-words">{{ $m->subject }}</td>
                            <td>
                                <span class="ui-badge {{ $cls }}" data-testid="status-{{ $m->id }}">{{ $m->status === 'sent' ? 'Accepted by ' . ($m->isResend() ? 'Resend' : 'Gmail') : $m->label() }}</span>
                                <span class="ui-badge ml-1">{{ $m->isResend() ? 'Resend' : 'Gmail' }}</span>
                                @if($m->status === 'sent' && $m->gmail_message_id)<div class="text-[11px] text-slate-400 mt-1 font-mono" title="Gmail message ID">{{ $m->gmail_message_id }}</div>@endif
                                <div class="text-[11.5px] text-slate-500 mt-1">{{ ($m->sent_at ?? $m->updated_at)?->format('M d, H:i') }}</div>
                                @if($m->status === 'failed' && $m->error_message)
                                    <div class="text-[11.5px] mt-1 {{ $m->outcome_unknown ? 'text-amber-700' : 'text-red-600' }} max-w-xs">{{ $m->error_message }}</div>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @if($analyticsAvailable && in_array($m->status, ['sent', 'failed'], true))
                                    <a href="{{ route('user.email-analytics.email', $m->id) }}" class="ui-btn-light ui-sm mb-1">{{ $m->status === 'sent' ? 'Details & reply' : 'Details' }}</a>
                                @endif
                                @if($m->status === 'pending')
                                    <form method="POST" action="{{ route('user.email-sending.cancel', $m->id) }}" class="inline">@csrf
                                        <button class="ui-btn-light ui-sm">Cancel</button>
                                    </form>
                                @elseif($m->status === 'failed' && (int) $m->user_id === (int) auth()->id())
                                    <form method="POST" action="{{ route('user.email-sending.retry', $m->id) }}" class="inline-flex flex-col items-end gap-1"
                                          @if($m->outcome_unknown) onsubmit="return this.confirm_not_sent.checked || (alert(@json($m->isResend() ? 'Please confirm you checked the Emails list in your Resend dashboard.' : 'Please confirm you checked your Gmail Sent folder.')), false);" @endif>
                                        @csrf
                                        @if($m->outcome_unknown)
                                            <label class="text-[11px] text-amber-800 flex items-center gap-1 whitespace-normal max-w-[12rem] text-left">
                                                <input type="checkbox" class="ui-check" name="confirm_not_sent" value="1"> {{ $m->isResend() ? 'I checked the Emails list in my Resend dashboard and it was not sent' : 'I checked my Gmail "Sent" folder and it was not sent' }}
                                            </label>
                                        @endif
                                        <button class="ui-btn ui-sm">Retry</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <div class="ui-empty">
                                <div class="ui-empty-i"><i class="fas fa-paper-plane"></i></div>
                                <p class="ui-empty-t">No emails sent yet</p>
                                <p class="mt-1">No emails sent yet. Approve a draft in <a class="ui-link" href="{{ route('user.ai-outreach.index') }}">AI Outreach</a>, then use "Send via Gmail".</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($history->hasPages())<div class="ui-card-f">{{ $history->links() }}</div>@endif
    </section>

    {{-- Suppression list --}}
    <section class="ui-card">
        <div class="ui-card-h">
            <div>
                <h2 class="ui-card-t"><i class="fas fa-ban"></i> Do-not-email list</h2>
                <p class="ui-card-d">Addresses here are never emailed. People who unsubscribe are added automatically and cannot be removed.</p>
            </div>
            <form method="POST" action="{{ route('user.email-sending.suppressions.store') }}" class="flex gap-2 w-full sm:w-auto">
                @csrf
                <input type="email" name="email" required maxlength="254" placeholder="name@business.com" aria-label="Email address to block" class="ui-field ui-sm flex-1 sm:!w-60">
                <button class="ui-btn-dark ui-sm">Add address</button>
            </form>
        </div>
        @error('email')<p class="text-[12px] text-red-600 px-4 pt-2">{{ $message }}</p>@enderror
        <ul class="divide-y divide-slate-100 px-4">
            @forelse($suppressions as $s)
                <li class="py-2 flex items-center justify-between gap-3 text-[12.5px]">
                    <span class="text-gray-800 break-all">{{ $s->email }} <span class="text-[11.5px] text-slate-500">· {{ ucfirst($s->reason) }} · {{ $s->created_at->format('M d, Y') }}</span></span>
                    @if($s->reason === 'manual')
                        <form method="POST" action="{{ route('user.email-sending.suppressions.destroy', $s->id) }}">@csrf @method('DELETE')
                            <button class="text-[12px] text-slate-500 hover:text-red-600">Remove</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="py-3 text-[12.5px] text-slate-500">No addresses yet.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
