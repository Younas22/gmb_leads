@extends('layouts.app')

@section('title', $campaign->exists ? 'Edit campaign' : 'Create campaign')

@section('content')
@php $f = (array) ($campaign->segment_filters ?? []); @endphp
<div class="p-4 lg:p-8 max-w-4xl mx-auto space-y-6">
    @include('user.campaigns._nav', ['active' => $campaign->exists ? 'campaigns' : 'new'])

    @unless($campaign->exists)
        {{-- Option 1: a lead folder, every lead gets its own AI-written email --}}
        <section class="bg-white border border-orange-200 rounded-2xl p-5" data-testid="folder-send-picker">
            <h2 class="text-base font-semibold text-gray-900"><i class="fas fa-folder-open text-orange-500 mr-1"></i> Send to a lead folder</h2>
            <p class="text-sm text-gray-600 mt-1">Each lead receives <strong>its own AI email</strong> from AI Outreach (not one shared message), one at a time with a pause in between, through Gmail or Resend. You review all emails before anything is sent.</p>
            @if($leadFolders->isEmpty())
                <p class="text-sm text-gray-500 mt-3">No folders yet. In <a class="text-orange-600 hover:underline" href="{{ route('user.ai-outreach.index') }}">AI Outreach</a>, select analysed leads and use "Add to folder".</p>
            @else
                <form method="GET" action="{{ route('user.folder-sends.create') }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                    <select name="folder_id" required class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" aria-label="Lead folder">
                        <option value="">Choose a folder…</option>
                        @foreach($leadFolders as $lf)<option value="{{ $lf->id }}">{{ $lf->name }} ({{ $lf->leads_count }} leads · {{ $lf->ready_count }} AI emails ready)</option>@endforeach
                    </select>
                    <button class="px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold">Review emails →</button>
                </form>
            @endif
        </section>
        <p class="text-center text-xs font-semibold text-gray-400 uppercase tracking-wide">or write one message for your opted-in contacts</p>
    @endunless

    @if($senders->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">
            You need a sender on a verified Resend domain first. <a class="underline" href="{{ route('user.email-sending.resend.show') }}">Set up Resend</a>.
        </div>
    @endif

    <form method="POST" action="{{ $campaign->exists ? route('user.campaigns.update', $campaign->id) : route('user.campaigns.store') }}" class="bg-white border border-gray-200 rounded-2xl p-5 space-y-4">
        @csrf @if($campaign->exists) @method('PUT') @endif
        <h1 class="text-lg font-bold text-gray-900">{{ $campaign->exists ? 'Edit campaign' : 'Create campaign' }} <span class="text-sm font-normal text-gray-500">(saved as a draft; nothing is sent)</span></h1>
        @if($errors->any())<div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>@endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="block text-xs font-medium text-gray-600 mb-1" for="name">Campaign name (internal)</label>
                <input id="name" name="name" required maxlength="120" value="{{ old('name', $campaign->name) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1" for="sender_id">Sender</label>
                <select id="sender_id" name="sender_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                    @foreach($senders as $s)<option value="{{ $s->id }}" @selected((int) old('sender_id', $campaign->sender_id) === $s->id)>{{ $s->from_name }} &lt;{{ $s->from_email }}&gt;</option>@endforeach
                </select></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1" for="list_id">Contact list</label>
                <select id="list_id" name="list_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                    <option value="">All opted-in contacts</option>
                    @foreach($lists as $l)<option value="{{ $l->id }}" @selected((int) old('list_id', $campaign->list_id) === $l->id)>{{ $l->name }}</option>@endforeach
                </select></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1" for="filter_tag">Only contacts tagged (optional)</label>
                <input id="filter_tag" name="filter_tag" maxlength="40" value="{{ old('filter_tag', $f['tag'] ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1" for="filter_organization">Organization contains (optional)</label>
                <input id="filter_organization" name="filter_organization" maxlength="100" value="{{ old('filter_organization', $f['organization'] ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1" for="filter_consent_after">Opted in on/after (optional)</label>
                <input id="filter_consent_after" type="date" name="filter_consent_after" value="{{ old('filter_consent_after', $f['consent_after'] ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
        </div>

        @if($templates->isNotEmpty())
            <div><label class="block text-xs font-medium text-gray-600 mb-1" for="tpl">Start from a template</label>
                <select id="tpl" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" onchange="if (this.value) { const t = JSON.parse(this.value); document.getElementById('subject').value = t.subject; document.getElementById('body').value = t.body; }">
                    <option value="">—</option>
                    @foreach($templates as $t)<option value="{{ json_encode(['subject' => $t->subject, 'body' => $t->body]) }}">{{ $t->name }}</option>@endforeach
                </select></div>
        @endif
        {{-- AI suggestion from the AI Sales Profile: fills Subject + Message, which stay editable --}}
        <div class="rounded-xl border border-orange-200 bg-orange-50/50 p-4 space-y-2" data-testid="ai-write">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-sm font-semibold text-gray-900"><i class="fas fa-wand-magic-sparkles text-orange-500 mr-1"></i> Write with AI</span>
                <a href="{{ route('user.ai-profile.index') }}" class="text-xs text-orange-700 hover:underline">Uses your AI Sales Profile</a>
            </div>
            <label for="ai-brief" class="block text-xs text-gray-600">What is this campaign about? (optional)</label>
            <textarea id="ai-brief" rows="2" maxlength="600" placeholder="e.g. Announce our new online booking feature and invite them to a free demo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white"></textarea>
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <button type="button" id="ai-write-btn" onclick="cnmAiWrite(this)" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium"><i class="fas fa-wand-magic-sparkles mr-1"></i> Generate subject &amp; message</button>
                <p id="ai-write-status" class="text-xs text-gray-500" role="status">Fills the fields below; you can edit everything before saving. Uses 1 AI email draft from your plan.</p>
            </div>
        </div>

        <div><label class="block text-xs font-medium text-gray-600 mb-1" for="subject">Subject</label>
            <input id="subject" name="subject" required maxlength="200" value="{{ old('subject', $campaign->subject) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></div>
        <div><label class="block text-xs font-medium text-gray-600 mb-1" for="body">Message (plain text)</label>
            <textarea id="body" name="body" required rows="12" maxlength="20000" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">{{ old('body', $campaign->body) }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Personalize with <span class="font-mono">{{ '{{' }}first_name|there}}</span> or <span class="font-mono">{{ '{{' }}last_name}}</span>. Your name, postal address and an unsubscribe link are added automatically.</p></div>
        <button class="px-5 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold" @disabled($senders->isEmpty())>Save draft</button>
    </form>
</div>

@push('scripts')
<script>
function cnmAiWrite(btn) {
    const subject = document.getElementById('subject'), body = document.getElementById('body'), status = document.getElementById('ai-write-status');
    if ((subject.value.trim() || body.value.trim()) && !confirm('Replace the current subject and message with an AI suggestion?')) return;
    btn.disabled = true; status.className = 'text-xs text-gray-500'; status.textContent = 'Writing… (this can take up to a minute)';
    fetch(@json(route('user.campaigns.ai-write')), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ brief: document.getElementById('ai-brief').value }),
    }).then(async r => {
        const d = await r.json().catch(() => ({}));
        if (r.ok && d.ok) {
            subject.value = d.subject; body.value = d.body;
            status.className = 'text-xs text-green-700'; status.textContent = 'Done. Review and edit the text below, then save the draft.';
            body.focus();
        } else {
            status.className = 'text-xs text-red-600';
            status.textContent = d.message || (r.status === 429 ? 'Too many requests. Please wait a minute.' : 'AI could not write the message. Please try again.');
        }
    }).catch(() => { status.className = 'text-xs text-red-600'; status.textContent = 'Request failed. Please try again.'; })
      .finally(() => { btn.disabled = false; });
}
</script>
@endpush
@endsection
