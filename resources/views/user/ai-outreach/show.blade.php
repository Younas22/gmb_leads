@extends('layouts.app')

@section('title', 'AI analysis')

@php
    $lead = $analysis->lead;
    $audit = $analysis->audit;
    $signals = $audit?->extracted_signals ?? [];
    $findings = $audit?->verified_findings ?? [];
    $strengths = $signals['strengths'] ?? [];
    $isOpen = in_array($analysis->status, ['pending', 'analyzing'], true);
    $draftErrors = $errors->any() && !$errors->has('send');
    $sendPreview = $sendPreview ?? null;
    $outbound = $outbound ?? collect();
@endphp

@section('content')
<div class="p-4 lg:p-8 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center gap-3 text-sm">
        <a href="{{ route('user.ai-outreach.index') }}" class="text-gray-600 hover:text-orange-600"><i class="fas fa-arrow-left mr-1"></i> AI Outreach</a>
        @if($analysis->batch)
            <span class="text-gray-300">/</span>
            <a href="{{ route('user.ai-outreach.batch', $analysis->batch_id) }}" class="text-gray-600 hover:text-orange-600">Request of {{ $analysis->batch->created_at->format('M d, H:i') }}</a>
        @endif
    </div>

    {{-- Header --}}
    <div class="bg-white border border-orange-100 rounded-2xl p-5 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 break-words">{{ $lead?->name ?? 'Lead removed' }}</h1>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    @include('user.ai-outreach._badges', ['analysis' => $analysis, 'which' => 'status'])
                    @include('user.ai-outreach._badges', ['analysis' => $analysis, 'which' => 'website'])
                    @if($analysis->match_confidence)
                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full border border-gray-200 text-gray-700">Match confidence: {{ ucfirst($analysis->match_confidence) }}</span>
                    @endif
                </div>
                @if($audit?->url_final || $lead?->website)
                    <p class="text-sm text-gray-600 mt-2 break-all"><i class="fas fa-globe text-gray-400 mr-1"></i>
                        <a href="{{ \App\Support\SafeUrl::href($audit?->url_final ?? $lead?->website) }}" target="_blank" rel="noopener noreferrer nofollow" class="hover:text-orange-600">{{ $audit?->url_final ?? $lead?->website }}</a>
                    </p>
                @endif
                @if($analysis->error_message && !$isOpen && $analysis->status !== 'completed')
                    <p class="text-sm text-red-600 mt-2">{{ $analysis->error_message }}
                        @if($analysis->error_code === 'profile_incomplete')
                            <a class="underline font-medium" href="{{ route('user.ai-profile.index') }}">Open AI Sales Profile</a>
                        @endif
                    </p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                @if(in_array($analysis->status, ['failed', 'quota_exhausted'], true))
                    <form method="POST" action="{{ route('user.ai-outreach.analysis.retry', $analysis->id) }}">@csrf
                        <button class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white"><i class="fas fa-redo"></i> Retry</button>
                    </form>
                @endif
                @if($analysis->status === 'completed')
                    <form method="POST" action="{{ route('user.ai-outreach.analysis.regenerate', $analysis->id) }}" onsubmit="return confirm('Generate a new draft? This uses one AI email draft from your monthly allowance.');">@csrf
                        <button class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"><i class="fas fa-rotate"></i> Regenerate draft</button>
                    </form>
                @endif
            </div>
        </div>
        @if($isOpen)
            <p class="text-sm text-gray-600 mt-4"><i class="fas fa-circle-notch fa-spin text-orange-500 mr-1"></i> This lead is being analysed in the background. Refresh in a minute.</p>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        {{-- Left: evidence + analysis --}}
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white border border-gray-200 rounded-2xl p-5">
                <h2 class="text-base font-semibold text-gray-900 mb-1 flex items-center gap-2"><i class="fas fa-magnifying-glass text-orange-500"></i> Website check</h2>
                @if(!$audit)
                    <p class="text-sm text-gray-600 mt-2">
                        @if($analysis->website_status === 'no_website')
                            No website URL is on file for this lead, so no website was checked. The draft does not mention website problems.
                        @else
                            {{ $analysis->websiteLabel() }}.
                        @endif
                    </p>
                @elseif($audit->status !== 'verified')
                    <p class="text-sm text-gray-600 mt-2">{{ $audit->error_message ?? $analysis->websiteLabel() }} No conclusions about the website were drawn.</p>
                @else
                    <p class="text-xs text-gray-500">Checked {{ $audit->audited_at?->diffForHumans() }} · {{ count($audit->fetch_log ?? []) }} request(s) · only verifiable facts are listed</p>
                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div><dt class="inline text-gray-500">Title:</dt> <dd class="inline text-gray-900">{{ $signals['title'] ?? '—' }}</dd></div>
                        <div><dt class="inline text-gray-500">Description:</dt> <dd class="inline text-gray-900">{{ $signals['meta_description'] ?? '—' }}</dd></div>
                        @if(!empty($signals['h1']))<div><dt class="inline text-gray-500">Main heading:</dt> <dd class="inline text-gray-900">{{ $signals['h1'][0] }}</dd></div>@endif
                    </dl>

                    <h3 class="text-sm font-semibold text-gray-900 mt-4 mb-2">Verified findings ({{ count($findings) }})</h3>
                    @forelse($findings as $f)
                        <div class="border border-gray-100 rounded-lg p-3 mb-2">
                            <p class="text-sm text-gray-900"><span class="text-xs font-mono text-orange-600 mr-1">{{ $f['id'] }}</span> {{ $f['description'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">Evidence: {{ $f['evidence'] }} · <span class="break-all">{{ $f['source_url'] }}</span> · <span class="text-green-700">{{ $f['verification'] }}</span></p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-600">None of our checks found an issue.</p>
                    @endforelse

                    @if($strengths)
                        <h3 class="text-sm font-semibold text-gray-900 mt-4 mb-2">Strengths</h3>
                        <ul class="text-sm text-gray-700 space-y-1">
                            @foreach($strengths as $s)
                                <li><span class="text-xs font-mono text-green-700 mr-1">{{ $s['id'] }}</span>{{ $s['description'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </section>

            <section class="bg-white border border-gray-200 rounded-2xl p-5">
                <h2 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2"><i class="fas fa-bullseye text-orange-500"></i> Offer match</h2>
                @if($analysis->business_summary)
                    <p class="text-sm text-gray-700">{{ $analysis->business_summary }}</p>
                @endif
                @if($analysis->recommendedOffer)
                    <div class="mt-3 bg-orange-50 border border-orange-100 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Recommended</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $analysis->recommendedOffer->title }}</p>
                        @if($analysis->match_reason)<p class="text-sm text-gray-700 mt-1">{{ $analysis->match_reason }}</p>@endif
                    </div>
                @elseif($analysis->status === 'completed')
                    <p class="text-sm text-gray-600 mt-2">No single offer clearly matched; the draft is a general introduction.</p>
                @endif
                @if($supportingOffers->isNotEmpty())
                    <p class="text-xs text-gray-500 mt-3">Also relevant: {{ $supportingOffers->pluck('title')->implode(', ') }}</p>
                @endif
                @if(!empty($analysis->likely_needs))
                    <h3 class="text-sm font-semibold text-gray-900 mt-4 mb-1">Likely needs</h3>
                    <ul class="text-sm text-gray-700 list-disc pl-5 space-y-1">
                        @foreach($analysis->likely_needs as $need)
                            <li>{{ $need['need'] }} <span class="text-xs text-gray-400">({{ implode(', ', $need['basis']) }})</span></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        {{-- Right: draft --}}
        <div class="lg:col-span-3 space-y-6">
            <section class="bg-white border border-gray-200 rounded-2xl">
                <div class="p-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2"><i class="fas fa-envelope-open-text text-orange-500"></i> Email draft</h2>
                    @if($draft)
                        <div class="flex items-center gap-2">
                            @include('user.ai-outreach._badges', ['analysis' => $analysis, 'which' => 'draft', 'draft' => $draft])
                            <span class="text-[11px] px-2 py-0.5 rounded-full border border-gray-200 text-gray-600">{{ $draft->personalization_level === 'specific' ? 'Personalised' : 'General' }}</span>
                            @if($draft->user_edited)<span class="text-[11px] px-2 py-0.5 rounded-full border border-gray-200 text-gray-600">Edited</span>@endif
                        </div>
                    @endif
                </div>

                @if(!$draft)
                    <div class="p-8 text-center text-sm text-gray-600">{{ $isOpen ? 'The draft will appear here when it is ready.' : 'No draft has been generated for this lead yet.' }}</div>
                @else
                    <form method="POST" action="{{ route('user.ai-outreach.draft.update', $draft->id) }}" class="p-5 space-y-4">
                        @csrf @method('PUT')
                        @if($draftErrors)
                            <div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <fieldset @disabled($draft->review_status === 'discarded') class="space-y-4">
                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-800 mb-1">Subject</label>
                                <input id="subject" name="subject" type="text" maxlength="200" required value="{{ old('subject', $draft->subject) }}" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500">
                            </div>
                            <div>
                                <label for="body" class="block text-sm font-medium text-gray-800 mb-1">Message</label>
                                <textarea id="body" name="body" rows="14" maxlength="10000" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500">{{ old('body', $draft->body) }}</textarea>
                            </div>
                        </fieldset>
                        <div class="flex flex-wrap items-center gap-2">
                            @if($draft->review_status !== 'discarded')
                                <button type="submit" class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg bg-gray-900 hover:bg-gray-800 text-white"><i class="fas fa-save"></i> Save changes</button>
                            @endif
                            <button type="button" onclick="cnmCopyDraft(this)" class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"><i class="fas fa-copy"></i> Copy email</button>
                        </div>
                    </form>
                    <div class="px-5 pb-5 flex flex-wrap gap-2">
                        @if($draft->review_status !== 'approved' && $draft->review_status !== 'discarded')
                            <form method="POST" action="{{ route('user.ai-outreach.draft.approve', $draft->id) }}">@csrf
                                <button class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white"><i class="fas fa-check"></i> Mark approved</button>
                            </form>
                        @endif
                        @if($draft->review_status !== 'discarded')
                            <form method="POST" action="{{ route('user.ai-outreach.draft.discard', $draft->id) }}">@csrf
                                <button class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 text-red-600 hover:bg-red-50"><i class="fas fa-trash-alt"></i> Discard</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('user.ai-outreach.draft.restore', $draft->id) }}">@csrf
                                <button class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"><i class="fas fa-undo"></i> Restore</button>
                            </form>
                        @endif
                        <p class="w-full text-xs text-gray-500 mt-1"><i class="fas fa-shield-halved mr-1"></i> Approving does not send anything. Check every claim before you use this email.</p>
                    </div>

                    {{-- Phase 3: individual Gmail sending (approved drafts only, always user-confirmed) --}}
                    @if($outbound->isNotEmpty() || $sendPreview || $errors->has('send'))
                        <div class="mx-5 mb-5 border border-orange-100 bg-orange-50/40 rounded-xl p-4" id="gmail-send">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2"><i class="fab fa-google text-orange-500"></i> Send via Gmail</h3>
                                <a href="{{ route('user.email-sending.index') }}" class="text-xs text-orange-700 hover:underline">Sending history</a>
                            </div>

                            @if($errors->has('send'))
                                <div class="mt-3 bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-sm text-red-700" role="alert">{{ $errors->first('send') }}</div>
                            @endif

                            @foreach($outbound as $o)
                                <div class="mt-2 text-sm text-gray-700" data-testid="outbound-status">
                                    <span class="font-medium">{{ $o->label() }}</span> to {{ $o->to_email }}
                                    <span class="text-xs text-gray-500">· {{ ($o->sent_at ?? $o->updated_at)?->format('M d, H:i') }}</span>
                                    @if($o->status === 'failed' && $o->error_message)<div class="text-xs {{ $o->outcome_unknown ? 'text-amber-700' : 'text-red-600' }}">{{ $o->error_message }}</div>@endif
                                </div>
                            @endforeach

                            @if($sendPreview)
                                @php $blocking = $sendPreview['problems']; @endphp
                                @if($blocking !== [])
                                    <ul class="mt-3 text-sm text-amber-800 list-disc list-inside space-y-0.5">
                                        @foreach($blocking as $p)<li>{{ $p }}</li>@endforeach
                                    </ul>
                                @endif
                                @if(!$outbound->contains(fn ($o) => in_array($o->status, \App\Models\OutboundEmail::ACTIVE_STATUSES, true)))
                                    <button type="button" onclick="document.getElementById('send-modal').classList.remove('hidden')"
                                            class="mt-3 inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white">
                                        <i class="fas fa-paper-plane"></i> Review & send
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endif

                    {{-- Phase 5: Resend (opted-in contacts only, always user-confirmed) --}}
                    @if($resendPreview || $errors->has('resend_send'))
                        <div class="mx-5 mb-5 border border-gray-200 bg-white rounded-xl p-4" id="resend-send">
                            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2"><i class="fas fa-envelope-circle-check text-orange-500"></i> Send via Resend</h3>
                            @if(($resendPreview['mode'] ?? null) === 'lead' && $resendPreview['problems'] === [])
                                <p class="text-xs text-amber-800 mt-1">One-to-one email to this saved lead (not an opted-in contact). Allowed because you switched on Resend emails to leads; Resend's policy does not allow cold outreach, so complaints can limit your Resend account.</p>
                            @else
                                <p class="text-xs text-gray-500 mt-1">Only for people who opted in to your emails (Resend does not allow cold outreach). For a first contact with a business from your leads, use Gmail, or allow one-to-one Resend emails to leads in Email Sending → Resend.</p>
                            @endif
                            @if($errors->has('resend_send'))
                                <div class="mt-3 bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-sm text-red-700" role="alert">{{ $errors->first('resend_send') }}</div>
                            @endif
                            @foreach($outbound->where('provider', 'resend') as $o)
                                <div class="mt-2 text-sm text-gray-700" data-testid="resend-status">
                                    <span class="font-medium">{{ $o->label() }}</span> to {{ $o->to_email }} · <span class="text-xs text-gray-500">Delivery: {{ $o->deliveryLabel() }}</span>
                                    @if($o->status === 'failed' && $o->error_message)<div class="text-xs {{ $o->outcome_unknown ? 'text-amber-700' : 'text-red-600' }}">{{ $o->error_message }}</div>@endif
                                </div>
                            @endforeach
                            @if($resendPreview)
                                @if($resendPreview['problems'] !== [])
                                    <ul class="mt-3 text-sm text-amber-800 list-disc list-inside space-y-0.5" data-testid="resend-problems">
                                        @foreach($resendPreview['problems'] as $p)<li>{{ $p }}</li>@endforeach
                                    </ul>
                                @endif
                                @if($resendPreview['problems'] === [] && !$outbound->contains(fn ($o) => in_array($o->status, \App\Models\OutboundEmail::ACTIVE_STATUSES, true)))
                                    <button type="button" onclick="document.getElementById('resend-modal').classList.remove('hidden')"
                                            class="mt-3 inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg border border-orange-300 text-orange-700 bg-orange-50 hover:bg-orange-100">
                                        <i class="fas fa-paper-plane"></i> Review & send via Resend
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endif

                    @if(!empty($draft->evidence_snapshot['sources']))
                        <div class="px-5 pb-5">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">What this draft is based on</h3>
                            <ul class="text-sm text-gray-700 space-y-1">
                                @foreach($draft->evidence_snapshot['sources'] as $src)
                                    <li><span class="text-xs font-mono text-orange-600 mr-1">{{ $src['id'] }}</span>{{ $src['description'] ?? '' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif
            </section>

            @if($history->count() > 1)
                <section class="bg-white border border-gray-200 rounded-2xl p-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Draft history</h2>
                    <ul class="divide-y divide-gray-100">
                        @foreach($history as $h)
                            <li class="py-2 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span class="text-gray-800 break-words min-w-0">{{ $h->subject ?? '(no subject)' }}</span>
                                <span class="text-xs text-gray-500 whitespace-nowrap">
                                    {{ $h->created_at->format('M d, H:i') }} · {{ $h->reviewLabel() }}{{ $h->is_current ? ' · current' : '' }}{{ $h->user_edited ? ' · edited' : '' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</div>

@if($draft && $resendPreview && $resendPreview['problems'] === [])
    {{-- Phase 5: Resend review / confirmation --}}
    <div id="resend-modal" class="{{ $errors->hasAny(['confirm_consent', 'sender_id']) ? '' : 'hidden' }} fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-3 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="resend-modal-title">
        <form method="POST" action="{{ route('user.email-sending.resend.send', $draft->id) }}" onsubmit="this.querySelector('button[type=submit]').disabled = true;" class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden shadow-xl">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="recipient" value="{{ $resendPreview['recipient'] }}">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3 shrink-0">
                <h2 id="resend-modal-title" class="text-lg font-semibold text-gray-900">Review before sending via Resend</h2>
                <button type="button" onclick="document.getElementById('resend-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600" aria-label="Close"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5 space-y-4 text-sm overflow-y-auto flex-1 min-h-0">
                <div>
                    <label for="sender_id" class="text-xs font-medium text-gray-500 uppercase">From (verified sender)</label>
                    <select id="sender_id" name="sender_id" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                        @foreach($resendSenders as $s)<option value="{{ $s->id }}" @selected($s->id === $resendPreview['sender']?->id)>{{ $s->from_name }} &lt;{{ $s->from_email }}&gt;</option>@endforeach
                    </select>
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 uppercase">{{ $resendPreview['mode'] === 'lead' ? 'To (saved lead, not an opted-in contact)' : 'To (opted-in contact)' }}</div>
                    <div class="text-gray-900">{{ $resendPreview['recipient'] }}</div>
                    @if($resendPreview['contact'])<div class="text-xs text-gray-500">Consent: {{ config('resend_user.consent_sources.' . $resendPreview['contact']->consent_source, $resendPreview['contact']->consent_source) }} · {{ $resendPreview['contact']->consent_at?->format('M j, Y') }}</div>@endif
                    @if($resendPreview['mode'] === 'lead')<div class="mt-1 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1.5">No consent record. Sent because you allowed one-to-one Resend emails to leads. Resend's policy does not allow cold outreach, so complaints can get your Resend account limited.</div>@endif
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 uppercase">Subject</div>
                    <div class="text-gray-900 break-words">{{ $resendPreview['subject'] }}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 uppercase mb-1">Message (exactly as it will be sent)</div>
                    <pre class="whitespace-pre-wrap break-words font-sans text-gray-800 bg-gray-50 border border-gray-200 rounded-lg p-3 max-h-56 overflow-y-auto">{{ $resendPreview['body'] }}</pre>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600" data-testid="resend-tracking">
                    Tracking for this sender's domain (set in Resend): opens <strong>{{ $resendPreview['tracking']['open'] ? 'on' : 'off' }}</strong>, clicks <strong>{{ $resendPreview['tracking']['click'] ? 'on' : 'off' }}</strong>.
                    CustomerNearMe link tracking is never added to Resend emails. Delivery, bounce and complaint updates come from Resend.
                </div>
                @if($resendPreview['mode'] === 'lead')
                    <label class="flex items-start gap-2 text-gray-800"><input type="checkbox" name="confirm_lead_outreach" value="1" required class="mt-0.5"><span>This person has not opted in. I am sending one relevant business email; it includes my postal address and an unsubscribe link, and I will respect any opt-out.</span></label>
                @else
                    <label class="flex items-start gap-2 text-gray-800"><input type="checkbox" name="confirm_consent" value="1" required class="mt-0.5"><span>This person opted in to receive emails from me, and the consent record above is accurate.</span></label>
                @endif
                <label class="flex items-start gap-2 text-gray-800"><input type="checkbox" name="confirm" value="1" required class="mt-0.5"><span>I have reviewed this email and want to send it through my Resend account.</span></label>
            </div>
            <div class="px-5 py-4 border-t border-gray-100 bg-white flex flex-col-reverse sm:flex-row sm:justify-end gap-2 shrink-0">
                <button type="button" onclick="document.getElementById('resend-modal').classList.add('hidden')" class="w-full sm:w-auto px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">Cancel</button>
                <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold"><i class="fas fa-paper-plane mr-1"></i> Send via Resend</button>
            </div>
        </form>
    </div>
@endif

@if($draft && $sendPreview)
    {{-- Final review / confirmation. Nothing is sent until the user ticks the box and confirms. --}}
    <div id="send-modal" class="{{ $errors->has('send') || $errors->has('recipient') || $errors->has('confirm') ? '' : 'hidden' }} fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-3 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="send-modal-title">
        <form method="POST" action="{{ route('user.email-sending.send', $draft->id) }}" onsubmit="this.querySelector('button[type=submit]').disabled = true;" class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden shadow-xl">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <input type="hidden" name="tracking_shown" value="{{ ($sendPreview['tracking']['enabled'] ?? false) ? '1' : '0' }}">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3 shrink-0">
                <h2 id="send-modal-title" class="text-lg font-semibold text-gray-900">Review before sending</h2>
                <button type="button" onclick="document.getElementById('send-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600" aria-label="Close"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5 space-y-4 text-sm overflow-y-auto flex-1 min-h-0">
                @foreach(['send', 'recipient', 'confirm'] as $k)
                    @error($k)<div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-red-700" role="alert">{{ $message }}</div>@enderror
                @endforeach
                @if($sendPreview['problems'] !== [])
                    <ul class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-amber-800 list-disc list-inside">
                        @foreach($sendPreview['problems'] as $p)<li>{{ $p }}</li>@endforeach
                    </ul>
                @endif
                <div>
                    <div class="text-xs font-medium text-gray-500 uppercase">From</div>
                    <div class="text-gray-900" data-testid="send-from">{{ $sendPreview['account']?->email ?? 'No Gmail connected' }}</div>
                </div>
                <div>
                    <label for="recipient" class="text-xs font-medium text-gray-500 uppercase">To</label>
                    <input id="recipient" name="recipient" type="email" required maxlength="254" value="{{ old('recipient', $sendPreview['recipient']) }}"
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500">
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 uppercase">Subject</div>
                    <div class="text-gray-900 break-words">{{ $sendPreview['subject'] }}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 uppercase mb-1">Message (exactly as it will be sent)</div>
                    <pre class="whitespace-pre-wrap break-words font-sans text-gray-800 bg-gray-50 border border-gray-200 rounded-lg p-3 max-h-56 overflow-y-auto">{{ $sendPreview['body'] }}</pre>
                </div>
                {{-- Phase 4: exactly what link tracking will do to this email --}}
                <div class="rounded-lg border px-3 py-2 text-xs {{ ($sendPreview['tracking']['enabled'] ?? false) ? 'border-orange-200 bg-orange-50/60 text-gray-700' : 'border-gray-200 bg-gray-50 text-gray-600' }}" data-testid="tracking-disclosure">
                    @if(!($sendPreview['tracking']['enabled'] ?? false))
                        <i class="fas fa-link-slash mr-1"></i> Link tracking is off: links are sent exactly as shown.
                    @elseif($sendPreview['tracking']['links'] === [])
                        <i class="fas fa-link mr-1"></i> Link tracking is on, but this email has no web links to track. It is sent exactly as shown.
                    @else
                        <p class="font-medium text-gray-900"><i class="fas fa-link mr-1 text-orange-500"></i> Link tracking is on for {{ count($sendPreview['tracking']['links']) }} link(s)</p>
                        <p class="mt-1">In the sent email these links will point to a {{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'CustomerNearMe' }} tracking address that forwards straight to the same page. The text, subject and recipient do not change. The unsubscribe link is never tracked.</p>
                        <ul class="mt-1 list-disc list-inside break-all">
                            @foreach($sendPreview['tracking']['links'] as $link)<li>{{ $link }}</li>@endforeach
                        </ul>
                        <p class="mt-1 text-gray-500">Turn this off in Email Sending settings to send links unchanged.</p>
                    @endif
                </div>
                <p class="text-xs text-gray-500">Sent today: {{ $sendPreview['sent_today'] }} / {{ $sendPreview['daily_limit'] === -1 ? 'unlimited' : $sendPreview['daily_limit'] }}. To change the text, close this window, edit the draft and approve it again.</p>
                <label class="flex items-start gap-2 text-gray-800">
                    <input type="checkbox" name="confirm" value="1" required class="mt-0.5">
                    <span>I have reviewed this email, the facts in it are accurate, and I want to send it from my Gmail account.</span>
                </label>
            </div>
            <div class="px-5 py-4 border-t border-gray-100 bg-white flex flex-col-reverse sm:flex-row sm:justify-end gap-2 shrink-0">
                <button type="button" onclick="document.getElementById('send-modal').classList.add('hidden')" class="w-full sm:w-auto px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">Cancel</button>
                <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold"><i class="fas fa-paper-plane mr-1"></i> Send email</button>
            </div>
        </form>
    </div>
@endif

<script>
function cnmCopyDraft(btn) {
    const subject = document.getElementById('subject')?.value || '';
    const body = document.getElementById('body')?.value || '';
    navigator.clipboard.writeText('Subject: ' + subject + '\n\n' + body).then(() => {
        btn.innerHTML = '<i class="fas fa-check"></i> Copied';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copy email'; }, 2000);
    });
}
</script>
@endsection
