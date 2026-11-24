@extends('layouts.app')

@section('title', 'Email details')

@section('content')
@php $reply = $email->reply; @endphp
<div class="p-4 lg:p-8 max-w-6xl mx-auto space-y-6">
    <a href="{{ route('user.email-analytics.index') }}" class="text-sm text-gray-600 hover:text-orange-600"><i class="fas fa-arrow-left mr-1"></i> Email Analytics</a>

    <div class="bg-white border border-orange-100 rounded-2xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 break-words">{{ $email->subject }}</h1>
                <p class="text-sm text-gray-600 mt-1 break-all">
                    To {{ $email->to_email }}@if($email->lead) · <a href="{{ route('user.email-analytics.lead', $email->saved_lead_id) }}" class="text-orange-600 hover:underline">{{ $email->lead->name }}</a>@endif
                </p>
                <p class="text-xs text-gray-500 mt-1">From {{ $email->from_email }} · {{ $email->sender?->name }} · queued {{ $email->queued_at?->format('M j, Y g:i A') }}</p>
            </div>
            <div class="text-right">
                <span class="inline-flex text-xs font-medium px-2.5 py-1 rounded-full border {{ $email->status === 'sent' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-700 border-gray-200' }}">
                    {{ $email->status === 'sent' ? 'Accepted by ' . ($email->isResend() ? 'Resend' : 'Gmail') : $email->label() }}
                </span>
                <div class="text-xs text-gray-500 mt-1" data-testid="delivery">{{ $email->providerLabel() }} · Delivery: {{ $email->deliveryLabel() }}</div>
                @if($email->sent_at)<div class="text-xs text-gray-500 mt-1">{{ $email->sent_at->format('M j, Y g:i A') }}</div>@endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Tracking for this email: each status, or why it is not known --}}
            <section class="bg-white border border-gray-200 rounded-2xl p-5" data-testid="email-tracking">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Tracking</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    @foreach($tracking as $t)
                        <div class="rounded-lg border px-3 py-2 {{ $t['ok'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-gray-200 bg-gray-50' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-medium text-gray-600">{{ $t['label'] }}</span>
                                <span class="text-sm font-semibold {{ $t['ok'] ? 'text-emerald-700' : 'text-gray-700' }}">
                                    @if($t['ok'])<i class="fas fa-check-circle mr-1"></i>@endif{{ $t['value'] }}
                                </span>
                            </div>
                            @if($t['why'])<p class="text-[11.5px] text-gray-500 mt-1">{{ $t['why'] }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Reply --}}
            <section class="bg-white border border-gray-200 rounded-2xl p-5" id="reply">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h2 class="text-base font-semibold text-gray-900">Reply</h2>
                    @if($canRecordReply)
                        <button type="button" onclick="document.getElementById('reply-modal').classList.remove('hidden')"
                                class="text-sm font-semibold px-4 py-2 rounded-lg {{ $reply ? 'border border-gray-300 text-gray-700 hover:bg-gray-50' : 'bg-orange-500 hover:bg-orange-600 text-white' }}">
                            <i class="fas fa-reply mr-1"></i> {{ $reply ? 'Correct reply' : 'Record reply' }}
                        </button>
                    @endif
                </div>
                @error('reply')<div class="mb-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2" role="alert">{{ $message }}</div>@enderror
                @if($reply)
                    <div class="text-sm" data-testid="reply-record">
                        <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200">{{ $reply->label() }}</span>
                        <span class="text-gray-600 ml-1">received {{ $reply->reply_received_at->format('M j, Y g:i A') }}</span>
                        @if($reply->notes)<p class="mt-2 text-gray-700 whitespace-pre-line break-words">{{ $reply->notes }}</p>@endif
                        <p class="mt-2 text-xs text-gray-500">
                            @if($reply->source === 'resend_inbound') Detected automatically (reply received through Resend)
                            @elseif($reply->source === 'gmail_inbox') Detected automatically (found in the connected Gmail inbox)
                            @else Recorded manually by {{ $reply->recordedBy?->name ?? 'a team member' }}@endif
                            @if($reply->updated_by_user_id && $reply->updated_at->ne($reply->created_at)) · last changed by {{ $reply->updatedBy?->name ?? 'a team member' }} {{ $reply->updated_at->diffForHumans() }}@endif
                        </p>
                        <form method="POST" action="{{ route('user.email-analytics.reply.destroy', $email->id) }}" class="mt-2" onsubmit="return confirm('Remove this reply record? Use this only if no reply was actually received.');">
                            @csrf @method('DELETE')
                            <button class="text-xs text-gray-500 hover:text-red-600">This was not a reply · remove</button>
                        </form>
                    </div>
                @elseif($canRecordReply)
                    <p class="text-sm text-gray-600">No reply recorded. When the business answers in Gmail, record it here so your pipeline stays up to date.</p>
                @else
                    <p class="text-sm text-gray-500">Replies can be recorded once the email has been sent.</p>
                @endif
                @unless($autoReplies)
                    <p class="mt-3 text-xs text-gray-400"><i class="fas fa-circle-info mr-1"></i> Replies are not detected automatically yet: connect Gmail with read access, or set up Resend receiving (see Tracking setup on Email Analytics).</p>
                @endunless
            </section>

            {{-- Tracked links --}}
            <section class="bg-white border border-gray-200 rounded-2xl p-5">
                <h2 class="text-base font-semibold text-gray-900 mb-1">Link activity</h2>
                @if($email->isResend())
                    @if($providerClicks->isEmpty())
                        <p class="text-sm text-gray-500">No clicks reported by Resend yet. Clicks are tracked by Resend when click tracking is on for the sending domain.</p>
                    @else
                        <p class="text-xs text-gray-500 mb-3">Clicks reported by Resend (verified webhook). Security scanners can also open links.</p>
                        <ul class="divide-y divide-gray-100 text-sm">
                            @foreach($providerClicks as $c)
                                <li class="py-2 flex justify-between gap-3">
                                    <span class="text-gray-800 break-all">{{ $c->metadata['link'] ?? 'Link' }}</span>
                                    <span class="text-gray-500 whitespace-nowrap">{{ $c->occurred_at?->format('M j, H:i') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @elseif(!$email->link_tracking)
                    <p class="text-sm text-gray-500">Link tracking was off for this email; its links were sent unchanged.</p>
                @else
                    <p class="text-xs text-gray-500 mb-3">A request is recorded each time a tracked link is opened. Security scanners often open links automatically, so only "probable person" requests count toward estimated clicks.</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-semibold text-gray-600 uppercase"><tr><th class="py-2 pr-3">#</th><th class="py-2 pr-3">Destination</th><th class="py-2 pr-3 text-right">Probable person</th><th class="py-2 pr-3 text-right">Likely scanner</th><th class="py-2 text-right">Your tests</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($links as $l)
                                    <tr>
                                        <td class="py-2 pr-3 text-gray-500">{{ $l->link_position }}</td>
                                        <td class="py-2 pr-3 text-gray-800 break-all max-w-md">{{ $l->original_url }}</td>
                                        <td class="py-2 pr-3 text-right font-semibold text-gray-900">{{ $l->human_count }}</td>
                                        <td class="py-2 pr-3 text-right text-gray-600">{{ $l->scanner_count }}</td>
                                        <td class="py-2 text-right text-gray-600">{{ $l->internal_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Message --}}
            <section class="bg-white border border-gray-200 rounded-2xl p-5">
                <h2 class="text-base font-semibold text-gray-900 mb-2">Message as sent</h2>
                <pre class="whitespace-pre-wrap break-words font-sans text-sm text-gray-800 bg-gray-50 border border-gray-200 rounded-lg p-3 max-h-96 overflow-y-auto">{{ $email->body }}</pre>
                @if($email->link_tracking)<p class="text-xs text-gray-500 mt-2">Shown with original links. In the sent email, the links listed above pointed to tracking addresses that redirect to these pages.</p>@endif
                @if($email->mime_sha256)<p class="text-[11px] text-gray-400 mt-1 font-mono break-all">MIME SHA-256: {{ $email->mime_sha256 }}</p>@endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="bg-white border border-gray-200 rounded-2xl p-5">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Activity</h2>
                @include('user.email-analytics._timeline', ['timeline' => $timeline])
            </section>

            @if($followUpsEnabled && ($email->saved_lead_id || $email->marketing_contact_id))
                <section class="bg-white border border-gray-200 rounded-2xl p-5">
                    <h2 class="text-base font-semibold text-gray-900 mb-3">Follow-up</h2>
                    @foreach($followUps as $f)
                        <div class="mb-3 text-sm flex items-start justify-between gap-2">
                            <div>
                                <div class="{{ $f->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-800' }}">{{ $f->due_date->format('D, M j, Y') }} · {{ ucfirst($f->status) }}</div>
                                @if($f->notes)<div class="text-xs text-gray-500 break-words">{{ $f->notes }}</div>@endif
                            </div>
                            @if($f->status === 'open')
                                <form method="POST" action="{{ route('user.email-follow-ups.complete', $f->id) }}">@csrf
                                    <button class="text-xs px-2.5 py-1 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 whitespace-nowrap">Mark done</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                    @include('user.email-analytics._follow-up-form', ['leadId' => $email->saved_lead_id, 'contactId' => $email->saved_lead_id ? null : $email->marketing_contact_id, 'emailId' => $email->id, 'followUpBlocked' => $followUpBlocked])
                </section>
            @endif
        </div>
    </div>
</div>

@if($canRecordReply)
    <div id="reply-modal" class="{{ $errors->hasAny(['reply_status', 'reply_received_at', 'notes', 'follow_up_date']) ? '' : 'hidden' }} fixed inset-0 z-50 bg-black/40 flex items-start sm:items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="reply-modal-title">
        <form method="POST" action="{{ route('user.email-analytics.reply.store', $email->id) }}" class="bg-white rounded-2xl w-full max-w-lg shadow-xl">
            @csrf
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <h2 id="reply-modal-title" class="text-lg font-semibold text-gray-900">{{ $reply ? 'Correct reply' : 'Record a reply' }}</h2>
                <button type="button" onclick="document.getElementById('reply-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600" aria-label="Close"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5 space-y-4 text-sm">
                <fieldset>
                    <legend class="text-xs font-medium text-gray-600 mb-2">How did they respond?</legend>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(\App\Models\EmailReplyRecord::STATUSES as $key => $label)
                            <label class="flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50">
                                <input type="radio" name="reply_status" value="{{ $key }}" required @checked(old('reply_status', $reply?->reply_status) === $key)
                                       onchange="document.getElementById('reply-follow-up').classList.toggle('hidden', this.value !== 'needs_follow_up')">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('reply_status')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </fieldset>
                <div>
                    <label for="reply_received_at" class="block text-xs font-medium text-gray-600 mb-1">Reply received</label>
                    <input id="reply_received_at" type="datetime-local" name="reply_received_at" required max="{{ now()->format('Y-m-d\TH:i') }}"
                           value="{{ old('reply_received_at', ($reply?->reply_received_at ?? now())->format('Y-m-d\TH:i')) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500">
                    @error('reply_received_at')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="reply-notes" class="block text-xs font-medium text-gray-600 mb-1">Notes (optional)</label>
                    <textarea id="reply-notes" name="notes" rows="3" maxlength="1000" placeholder="Your own summary. Do not paste the whole email."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500">{{ old('notes', $reply?->notes) }}</textarea>
                </div>
                @if($followUpsEnabled)
                    <div id="reply-follow-up" class="{{ old('reply_status', $reply?->reply_status) === 'needs_follow_up' ? '' : 'hidden' }}">
                        <label for="reply-fu" class="block text-xs font-medium text-gray-600 mb-1">Remind me to follow up on (optional)</label>
                        <input id="reply-fu" type="date" name="follow_up_date" min="{{ today()->toDateString() }}" value="{{ old('follow_up_date') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        @error('follow_up_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @endif
                <p class="text-xs text-gray-500">"Not interested" adds this address to your do-not-email list and cancels open follow-ups. Lead statuses in My Leads and Lead Center are updated.</p>
            </div>
            <div class="p-5 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('reply-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold">Save reply</button>
            </div>
        </form>
    </div>
@endif
@endsection
