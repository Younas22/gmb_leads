@extends('layouts.app')

@section('title', 'Resend')

@section('content')
@php $connected = $integration && filled($integration->api_key); @endphp
<div class="p-4 lg:p-8 max-w-7xl mx-auto space-y-6">
    @include('user.email-sending._tabs', ['active' => 'resend'])
    @include('user.partials._other-errors', ['inline' => ['api_key', 'domain', 'sender', 'signing_secret', 'acknowledge']])

    <div class="bg-white border border-orange-100 rounded-2xl p-5 sm:p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center flex-shrink-0"><i class="fas fa-envelope-circle-check text-xl"></i></div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Resend</h1>
            <p class="text-sm text-gray-600 mt-1 max-w-3xl">Use your own Resend account to email people who <strong>opted in</strong> to hear from you, from your own verified domain. Resend's rules do not allow cold outreach, purchased lists or scraped contacts, so leads found on Google Maps cannot be emailed through Resend. Use Gmail for one-to-one first contact.</p>
        </div>
    </div>

    @unless($canManage)
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">Only the account owner can connect Resend or change domains, senders and the webhook.</div>
    @endunless

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Connection --}}
        <section class="bg-white border border-gray-200 rounded-2xl p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-3">API key</h2>
            @if($connected)
                <div class="text-sm space-y-1" data-testid="resend-connection">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full {{ $integration->status === 'active' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                        <span class="font-semibold text-gray-900">{{ $integration->status === 'active' ? 'Connected' : ucfirst($integration->status) }}</span>
                    </div>
                    <div class="font-mono text-gray-700">{{ $integration->maskedKey() }}</div>
                    <div class="text-xs text-gray-500">Last verified {{ $integration->last_verified_at?->diffForHumans() ?? 'never' }}</div>
                    @if($integration->last_error)<div class="text-xs text-red-600">{{ $integration->last_error }}</div>@endif
                </div>
                <form method="POST" action="{{ route('user.email-sending.resend.check') }}" class="mt-3">@csrf
                    <button class="w-full text-sm px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"><i class="fas fa-stethoscope mr-1"></i> Check connection</button>
                </form>
            @else
                <p class="text-sm text-gray-600">Not connected.</p>
            @endif

            @if($canManage)
                <form method="POST" action="{{ route('user.email-sending.resend.connect') }}" class="mt-4 space-y-2" autocomplete="off">@csrf
                    <label for="api_key" class="block text-xs font-medium text-gray-600">{{ $connected ? 'Replace key' : 'Resend API key' }}</label>
                    <input id="api_key" type="password" name="api_key" required maxlength="255" placeholder="re_..." autocomplete="new-password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500">
                    @error('api_key')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-500">In Resend: API Keys → Create API Key → permission <strong>Full access</strong> (needed for domains, webhooks and campaigns). The key is stored encrypted and never shown again.</p>
                    <button class="w-full text-sm font-semibold px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white">{{ $connected ? 'Replace key' : 'Connect Resend' }}</button>
                </form>
                @if($connected)
                    <form method="POST" action="{{ route('user.email-sending.resend.disconnect') }}" class="mt-3" onsubmit="return confirm('Disconnect Resend? The API key is deleted from CustomerNearMe and pending Resend emails are cancelled.');">@csrf
                        <button class="w-full text-sm px-4 py-2 rounded-lg border border-gray-300 text-red-600 hover:bg-red-50"><i class="fas fa-unlink mr-1"></i> Disconnect</button>
                    </form>
                @endif
            @endif

            <div class="mt-5 pt-4 border-t border-gray-100 text-sm space-y-1">
                <div class="flex justify-between"><span class="text-gray-600">Resend emails today</span><span class="font-semibold">{{ $usage['daily'][0] }} / {{ $usage['daily'][1] === -1 ? 'unlimited' : $usage['daily'][1] }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">This month</span><span class="font-semibold">{{ $usage['monthly'][0] }} / {{ $usage['monthly'][1] === -1 ? 'unlimited' : $usage['monthly'][1] }}</span></div>
                <p class="text-xs text-gray-500">Your Resend plan has its own limits too; both apply.</p>
            </div>
        </section>

        {{-- Domains --}}
        <section class="bg-white border border-gray-200 rounded-2xl p-5 lg:col-span-2">
            <h2 class="text-base font-semibold text-gray-900 mb-1">Sending domains</h2>
            <p class="text-xs text-gray-500 mb-4">Status and DNS records come from Resend. Add the records at your DNS provider (for example Hostinger → Domains → DNS / Nameservers); CustomerNearMe never changes your DNS. Only <strong>Verified</strong> domains can send.</p>

            @if(!$connected)
                <p class="text-sm text-gray-500">Connect Resend first.</p>
            @else
                @forelse($domains as $d)
                    <div class="border border-gray-200 rounded-xl p-4 mb-3 {{ $d->present ? '' : 'opacity-60' }}" data-testid="domain-{{ $d->id }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="font-semibold text-gray-900">{{ $d->name }}</span>
                                <span class="ml-2 text-xs px-2 py-0.5 rounded-full border {{ $d->canSend() ? 'bg-green-50 text-green-700 border-green-200' : 'bg-amber-50 text-amber-800 border-amber-200' }}">{{ $d->present ? $d->statusLabel() : 'Removed in Resend' }}</span>
                                <span class="text-xs text-gray-500 ml-1">{{ $d->region }}</span>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('user.email-sending.resend.domains.verify', $d->id) }}">@csrf<button class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50">Verify</button></form>
                                <form method="POST" action="{{ route('user.email-sending.resend.domains.refresh', $d->id) }}">@csrf<button class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50">Refresh</button></form>
                            </div>
                        </div>
                        @if(!empty($d->records))
                            <div class="overflow-x-auto mt-3">
                                <table class="min-w-[640px] sm:min-w-full text-xs">
                                    <thead class="text-left text-gray-500"><tr><th class="py-1 pr-3">Record</th><th class="py-1 pr-3">Type</th><th class="py-1 pr-3">Name</th><th class="py-1 pr-3">Value</th><th class="py-1 pr-3">Priority</th><th class="py-1">Status</th></tr></thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($d->records as $r)
                                            <tr>
                                                <td class="py-1.5 pr-3 font-medium">{{ $r['record'] ?? '' }}</td>
                                                <td class="py-1.5 pr-3">{{ $r['type'] ?? '' }}</td>
                                                <td class="py-1.5 pr-3 font-mono whitespace-nowrap">{{ $r['name'] ?? '' }}</td>
                                                <td class="py-1.5 pr-3 font-mono break-all max-w-xs">
                                                    <span>{{ $r['value'] ?? '' }}</span>
                                                    <button type="button" class="ml-1 text-orange-600 hover:underline" onclick="navigator.clipboard.writeText(this.previousElementSibling.textContent); this.textContent='Copied';">Copy</button>
                                                </td>
                                                <td class="py-1.5 pr-3">{{ $r['priority'] ?? '' }}</td>
                                                <td class="py-1.5">{{ ucfirst(str_replace('_', ' ', $r['status'] ?? '')) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        @if($canManage && $d->present)
                            <form method="POST" action="{{ route('user.email-sending.resend.domains.tracking', $d->id) }}" class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-700">@csrf @method('PUT')
                                <span class="font-medium">Resend tracking:</span>
                                <input type="hidden" name="open_tracking" value="0"><label class="flex items-center gap-1"><input type="checkbox" name="open_tracking" value="1" @checked($d->open_tracking) @disabled(!$trackingPlan && !$d->open_tracking)> Opens</label>
                                <input type="hidden" name="click_tracking" value="0"><label class="flex items-center gap-1"><input type="checkbox" name="click_tracking" value="1" @checked($d->click_tracking) @disabled(!$trackingPlan && !$d->click_tracking)> Clicks</label>
                                <label class="flex items-center gap-1"><input type="checkbox" name="confirm" value="1" required> Apply to every email from this domain</label>
                                <button class="px-3 py-1 rounded-lg border border-gray-300 hover:bg-gray-50">Save</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 mb-3">No domains in your Resend account yet.</p>
                @endforelse

                @if($canManage)
                    <form method="POST" action="{{ route('user.email-sending.resend.domains.store') }}" class="flex flex-col sm:flex-row gap-2 mt-2">@csrf
                        <input name="domain" required maxlength="253" placeholder="mail.yourcompany.com" value="{{ old('domain') }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <select name="region" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                            @foreach(\App\Services\Resend\ResendDomainService::REGIONS as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                        </select>
                        <button class="px-4 py-2 rounded-lg bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium">Add domain</button>
                    </form>
                    @error('domain')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-500 mt-1">Tip: a subdomain such as <span class="font-mono">mail.yourcompany.com</span> keeps your main domain's reputation separate.</p>
                @endif
            @endif
        </section>
    </div>

    @if($connected)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Senders --}}
        <section class="bg-white border border-gray-200 rounded-2xl p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Sender identities</h2>
            <ul class="divide-y divide-gray-100 mb-4">
                @forelse($senders as $s)
                    <li class="py-2 text-sm" id="sender-{{ $s->id }}">
                        <div class="flex items-center justify-between gap-2">
                            <span><span class="font-medium text-gray-900">{{ $s->from_name }}</span> &lt;{{ $s->from_email }}&gt; @unless($s->canSend())<span class="text-xs text-amber-700">(domain not verified)</span>@endunless</span>
                            @if($canManage)
                                <form method="POST" action="{{ route('user.email-sending.resend.senders.destroy', $s->id) }}">@csrf @method('DELETE')<button class="text-xs text-gray-500 hover:text-red-600 cursor-pointer">Remove</button></form>
                            @endif
                        </div>
                        {{-- Where replies go. Use your connected Gmail so replies are found automatically. --}}
                        @if($canManage)
                            <form method="POST" action="{{ route('user.email-sending.resend.senders.update', $s->id) }}" class="mt-1.5 flex flex-wrap items-center gap-2 text-xs">@csrf @method('PUT')
                                <label for="reply-{{ $s->id }}" class="text-gray-600">Replies go to:</label>
                                <input id="reply-{{ $s->id }}" name="reply_to" type="email" multiple maxlength="254" value="{{ $s->reply_to }}" placeholder="{{ $s->from_email }}" class="px-2 py-1 border border-gray-300 rounded-md text-xs w-96 max-w-full">
                                <button class="px-2 py-1 rounded-md border border-gray-300 hover:bg-gray-50 cursor-pointer">Save</button>
                                <span class="text-gray-500 w-full">Up to {{ \App\Models\ResendSender::MAX_REPLY_TO }} addresses, separated by commas. Tip: add your own inbox and your Resend receiving address, so you can read replies and they are also tracked automatically.</span>
                                @error('reply_to')<span class="text-red-600 w-full">{{ $message }}</span>@enderror
                            </form>
                        @elseif($s->reply_to)
                            <p class="mt-1 text-xs text-gray-500">Replies go to {{ $s->reply_to }}</p>
                        @endif
                    </li>
                @empty
                    <li class="py-2 text-sm text-gray-500">No senders yet.</li>
                @endforelse
            </ul>
            @if($canManage && $domains->contains(fn ($d) => $d->canSend()))
                <form method="POST" action="{{ route('user.email-sending.resend.senders.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2">@csrf
                    <input name="from_name" required maxlength="100" placeholder="Sender name" value="{{ old('from_name') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <div class="flex items-center gap-1">
                        <input name="local_part" required maxlength="64" placeholder="hello" value="{{ old('local_part') }}" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <span class="text-gray-500">@</span>
                        <select name="domain_id" class="w-1/2 px-2 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                            @foreach($domains->filter(fn ($d) => $d->canSend()) as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                    </div>
                    <input name="reply_to" type="email" multiple maxlength="254" placeholder="Reply-to (optional, comma-separated)" value="{{ old('reply_to') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <button class="px-4 py-2 rounded-lg bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium">Add sender</button>
                </form>
                @error('sender')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            @elseif($canManage)
                <p class="text-xs text-gray-500">Verify a domain first to add senders.</p>
            @endif
        </section>

        {{-- Webhook --}}
        <section class="bg-white border border-gray-200 rounded-2xl p-5" data-testid="webhook-panel">
            <h2 class="text-base font-semibold text-gray-900 mb-1">Delivery events (webhook)</h2>
            <p class="text-xs text-gray-500 mb-3">Resend reports delivered, bounced, complained and (if enabled) opened/clicked events to this address. Without it, CustomerNearMe only knows that Resend accepted an email.</p>
            <div class="text-sm space-y-1">
                <div>Status: <strong>{{ ['needs_setup' => 'Not set up', 'active' => 'Active', 'failing' => 'Signature failures'][$webhook->status] ?? $webhook->status }}</strong></div>
                <div class="text-xs text-gray-600 break-all">Endpoint: <span class="font-mono">{{ $webhook->endpointUrl() }}</span></div>
                <div class="text-xs text-gray-600">Last event: {{ $webhook->last_event_at ? $webhook->last_event_type . ' · ' . $webhook->last_event_at->diffForHumans() : 'none yet' }}</div>
                <div class="text-xs text-gray-600">Verified: {{ $webhook->verified_count }} · Rejected: {{ $webhook->rejected_count }}@if($webhook->last_failure_at) · last rejection {{ $webhook->last_failure_at->diffForHumans() }} ({{ str_replace('_', ' ', $webhook->last_failure_reason) }})@endif</div>
            </div>
            @if($canManage)
                <form method="POST" action="{{ route('user.email-sending.resend.webhook.create') }}" class="mt-4 space-y-2">@csrf
                    <label class="flex items-start gap-2 text-xs text-gray-700"><input type="checkbox" name="confirm" value="1" required class="mt-0.5"> Create (or replace) the CustomerNearMe webhook in my Resend account</label>
                    <button class="text-sm font-semibold px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white">Set up automatically</button>
                </form>
                {{-- Kept open after a failed save, otherwise the error below is hidden in the collapsed section --}}
                <details class="mt-3 text-xs text-gray-600" @if($errors->has('signing_secret')) open @endif>
                    <summary class="cursor-pointer font-medium text-gray-800">Set up manually instead</summary>
                    <ol class="list-decimal list-inside mt-2 space-y-1">
                        <li>Resend → Webhooks → Add Webhook.</li>
                        <li>Endpoint URL: the endpoint above.</li>
                        <li>Events: email.sent, email.delivered, email.delivery_delayed, email.bounced, email.complained, email.failed, email.suppressed, email.opened, email.clicked, contact.updated, domain.updated.</li>
                        <li>Copy the signing secret (starts with <span class="font-mono">whsec_</span>) and paste it here.</li>
                    </ol>
                    <form method="POST" action="{{ route('user.email-sending.resend.webhook.secret') }}" class="mt-2 flex gap-2" autocomplete="off">@csrf @method('PUT')
                        <input type="password" name="signing_secret" required maxlength="255" placeholder="whsec_..." autocomplete="new-password" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg font-mono">
                        <button class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Save secret</button>
                    </form>
                    @error('signing_secret')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
                </details>
            @endif
            @if($receipts->isNotEmpty())
                <h3 class="text-xs font-semibold text-gray-700 mt-4 mb-1">Recent events</h3>
                <ul class="text-xs text-gray-600 divide-y divide-gray-100">
                    @foreach($receipts as $r)<li class="py-1 flex justify-between gap-2"><span class="font-mono">{{ $r->event_type }}</span><span>{{ $r->status }} · {{ $r->created_at->diffForHumans() }}</span></li>@endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Optional: one-to-one Resend emails to saved leads (owner decision, off by default) --}}
    <section class="bg-white border {{ $integration->allowsLeadOutreach() ? 'border-amber-300' : 'border-gray-200' }} rounded-2xl p-5" data-testid="lead-outreach-panel" id="lead-outreach">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-gray-900">One-to-one Resend emails to saved leads</h2>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $integration->allowsLeadOutreach() ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }}">{{ $integration->allowsLeadOutreach() ? 'Allowed since ' . $integration->lead_outreach_enabled_at->format('M j, Y') : 'Off' }}</span>
        </div>
        <p class="text-sm text-gray-600 mt-1">When on, an approved AI draft for a saved lead can be sent through Resend to that lead's own address, one email at a time after your review, even though the lead has not opted in. Campaigns are not affected: they always go to opted-in contacts only.</p>
        <div class="mt-3 text-xs text-amber-900 bg-amber-50 border border-amber-200 rounded-lg p-3 space-y-1">
            <p><strong>Risk:</strong> Resend's Acceptable Use Policy does not allow cold outreach or contacts found online. Resend can limit or close your account if recipients complain or bounces rise (Resend requires complaints below 0.08% and bounces below 4%). Gmail is the safer choice for first contact.</p>
            <p>Every email still includes your postal address and an unsubscribe link, the do-not-email list is always respected, and your Resend daily and monthly limits apply. Outreach rules depend on where recipients are (for example the EU or UK); check what applies to you.</p>
        </div>
        @if($canManage)
            <form method="POST" action="{{ route('user.email-sending.resend.lead-outreach') }}" class="mt-3 space-y-2">@csrf @method('PUT')
                @if($integration->allowsLeadOutreach())
                    <input type="hidden" name="enabled" value="0">
                    <button class="text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 text-gray-800 hover:bg-gray-50">Switch off (opted-in contacts only)</button>
                @else
                    <input type="hidden" name="enabled" value="1">
                    <label class="flex items-start gap-2 text-sm text-gray-800"><input type="checkbox" name="acknowledge" value="1" required class="mt-0.5"> <span>I understand the risk to my Resend account and will only send relevant one-to-one business emails.</span></label>
                    @error('acknowledge')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <button class="text-sm font-semibold px-4 py-2 rounded-lg bg-gray-900 hover:bg-gray-800 text-white">Allow Resend emails to leads</button>
                @endif
            </form>
        @endif
    </section>
    @endif

    <section class="bg-white border border-gray-200 rounded-2xl p-5 text-xs text-gray-600 space-y-1">
        <h2 class="text-sm font-semibold text-gray-900">Gmail and Resend are different</h2>
        <p><strong>Gmail:</strong> one-to-one emails from your own mailbox. Gmail does not report delivery, so delivery is shown as "Not available". Optional CustomerNearMe link tracking.</p>
        <p><strong>Resend:</strong> your verified domain, opted-in contacts only, campaigns, and provider-reported delivery, bounces, complaints and (if you enable them in Resend) opens and clicks.</p>
        <p>Replies are recorded by you in Email Analytics for both. Automatic reply detection is not set up.</p>
    </section>
</div>
@endsection
