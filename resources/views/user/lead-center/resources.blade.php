@extends('layouts.app')

@section('title', 'Lead Center Resources')

@section('content')
<div class="p-3 lg:p-4 max-w-5xl mx-auto">

    <div class="mb-4">
        <a href="{{ route('user.lead-center.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-primary-600 transition-colors">
            <i class="fas fa-arrow-left"></i> Back to Lead Center
        </a>
    </div>

    <div class="mb-5">
        <h1 class="text-xl lg:text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-book text-primary-600"></i> Lead Center Resources
        </h1>
        <p class="text-sm text-gray-500 mt-0.5">Your outreach playbook — targeted locations, AI prompts and message templates, all saved and ready to reuse.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Targeted Locations -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
                    <i class="fas fa-map-marker-alt text-primary-600"></i> Targeted Locations
                </h2>
                <p class="text-[11px] text-gray-400 mt-0.5">Markets you're planning to prospect next.</p>
            </div>
            <div class="p-4 space-y-2 border-b border-gray-100">
                <select id="target_country_select" class="w-full px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer">
                    <option value="">Country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
                <select id="target_state_select" class="w-full px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer" disabled>
                    <option value="">State</option>
                </select>
                <select id="target_city_select" class="w-full px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer" disabled>
                    <option value="">City</option>
                </select>
                <input type="text" id="target_notes" maxlength="500" placeholder="Notes (optional)"
                       class="w-full px-3 py-2 rounded-lg text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-primary-400">
                <button type="button" onclick="addTargetLocation()"
                        class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-plus mr-1"></i> Add Location
                </button>
            </div>
            <div id="targetLocationsList" class="p-3 space-y-2 max-h-[420px] overflow-y-auto flex-1">
                @forelse($targetLocations as $loc)
                    @include('user.lead-center._target-location-item', ['loc' => $loc])
                @empty
                    <p class="text-xs text-gray-400 italic text-center py-6" id="noTargetLocations">No targeted locations yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Prompts -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
                    <i class="fas fa-wand-magic-sparkles text-purple-600"></i> Prompts
                </h2>
                <p class="text-[11px] text-gray-400 mt-0.5">Saved AI prompts for finding or writing to prospects.</p>
            </div>
            <div class="p-4 space-y-2 border-b border-gray-100">
                <input type="text" id="prompt_title" maxlength="150" placeholder="Prompt title…"
                       class="w-full px-3 py-2 rounded-lg text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-purple-400">
                <textarea id="prompt_content" rows="4" maxlength="5000" placeholder="Prompt text…"
                          class="w-full px-3 py-2 rounded-lg text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-purple-400 resize-none"></textarea>
                <button type="button" onclick="addPrompt()"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-plus mr-1"></i> Save Prompt
                </button>
            </div>
            <div id="promptsList" class="p-3 space-y-2 max-h-[420px] overflow-y-auto flex-1">
                @forelse($prompts as $prompt)
                    @include('user.lead-center._text-resource-item', ['item' => $prompt, 'kind' => 'prompt'])
                @empty
                    <p class="text-xs text-gray-400 italic text-center py-6" id="noPrompts">No prompts saved yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Message Templates -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
                    <i class="fas fa-comment-dots text-teal-600"></i> Message Templates
                </h2>
                <p class="text-[11px] text-gray-400 mt-0.5">Reusable outreach messages — pick one right inside a conversation.</p>
            </div>
            <div class="p-4 space-y-2 border-b border-gray-100">
                <input type="text" id="template_title" maxlength="150" placeholder="Template title…"
                       class="w-full px-3 py-2 rounded-lg text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-teal-400">
                <textarea id="template_content" rows="4" maxlength="5000" placeholder="Message text…"
                          class="w-full px-3 py-2 rounded-lg text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-teal-400 resize-none"></textarea>
                <button type="button" onclick="addTemplate()"
                        class="w-full bg-teal-600 hover:bg-teal-700 text-white py-2 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-plus mr-1"></i> Save Template
                </button>
            </div>
            <div id="templatesList" class="p-3 space-y-2 max-h-[420px] overflow-y-auto flex-1">
                @forelse($templates as $template)
                    @include('user.lead-center._text-resource-item', ['item' => $template, 'kind' => 'template'])
                @empty
                    <p class="text-xs text-gray-400 italic text-center py-6" id="noTemplates">No message templates yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
@include('partials.select2-assets')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const LC_ROUTES = {
    apiStatesBase: '{{ url("/user/api/states") }}',
    apiCitiesBase: '{{ url("/user/api/cities") }}',
    locationsStore: '{{ route("user.lead-center.resources.locations.store") }}',
    locationsDeleteBase: '{{ url("/user/lead-center/resources/locations") }}',
    promptsStore: '{{ route("user.lead-center.resources.prompts.store") }}',
    promptsDeleteBase: '{{ url("/user/lead-center/resources/prompts") }}',
    templatesStore: '{{ route("user.lead-center.resources.templates.store") }}',
    templatesDeleteBase: '{{ url("/user/lead-center/resources/templates") }}',
};

function showToast(message, type) {
    const t = document.createElement('div');
    t.className = `fixed bottom-6 right-6 z-[999] px-4 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center gap-2 transition-all
        ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
    t.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => showToast('Copied to clipboard', 'success'))
        .catch(() => showToast('Failed to copy', 'error'));
}
</script>

@include('user.lead-center._location-select2-helper')
<script>
const targetLoc = lcCascadingLocation('target_country_select', 'target_state_select', 'target_city_select');

// ===== Targeted Locations =====
function addTargetLocation() {
    const values = targetLoc.values();
    const notes = document.getElementById('target_notes').value.trim();

    if (!values.country_id && !values.state_id && !values.city_id && !notes) {
        showToast('Choose a location or add a note first', 'error');
        return;
    }

    fetch(LC_ROUTES.locationsStore, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ ...values, notes })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { showToast(data.message || 'Failed to add location', 'error'); return; }

        document.getElementById('noTargetLocations')?.remove();
        const loc = data.location;
        const parts = [loc.city_relation?.name, loc.state_relation?.name, loc.country_relation?.name].filter(Boolean).join(', ');
        const html = `
            <div class="flex items-start justify-between gap-2 bg-gray-50 border border-gray-100 rounded-lg px-3 py-2" data-location-id="${loc.id}">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">${escapeHtml(parts || 'No location')}</p>
                    ${loc.notes ? `<p class="text-xs text-gray-500 mt-0.5">${escapeHtml(loc.notes)}</p>` : ''}
                </div>
                <button onclick="deleteTargetLocation(${loc.id})" class="text-gray-300 hover:text-red-500 flex-shrink-0"><i class="fas fa-trash-alt text-xs"></i></button>
            </div>`;
        document.getElementById('targetLocationsList').insertAdjacentHTML('afterbegin', html);

        targetLoc.reset();
        document.getElementById('target_notes').value = '';
        showToast('Location added', 'success');
    })
    .catch(() => showToast('Failed to add location', 'error'));
}

function deleteTargetLocation(id) {
    if (!confirm('Remove this targeted location?')) return;
    fetch(`${LC_ROUTES.locationsDeleteBase}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) document.querySelector(`[data-location-id="${id}"]`)?.remove();
        else showToast('Failed to remove location', 'error');
    })
    .catch(() => showToast('Failed to remove location', 'error'));
}

// ===== Prompts & Templates (same shape, different endpoints) =====
function addTextResource(kind, titleId, contentId, storeUrl, listId, emptyId, accentClass) {
    const title = document.getElementById(titleId).value.trim();
    const content = document.getElementById(contentId).value.trim();

    if (!title || !content) { showToast('Please fill in both title and content', 'error'); return; }

    fetch(storeUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ title, content })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { showToast(data.message || 'Failed to save', 'error'); return; }

        document.getElementById(emptyId)?.remove();
        const item = data[kind];
        const html = textResourceHtml(item, kind, accentClass);
        document.getElementById(listId).insertAdjacentHTML('afterbegin', html);

        document.getElementById(titleId).value = '';
        document.getElementById(contentId).value = '';
        showToast('Saved', 'success');
    })
    .catch(() => showToast('Failed to save', 'error'));
}

function textResourceHtml(item, kind, accentClass) {
    const deleteBase = kind === 'prompt' ? LC_ROUTES.promptsDeleteBase : LC_ROUTES.templatesDeleteBase;
    return `
        <div class="bg-gray-50 border border-gray-100 rounded-lg px-3 py-2" data-${kind}-id="${item.id}">
            <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-semibold ${accentClass} truncate">${escapeHtml(item.title)}</p>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button onclick='copyText(${JSON.stringify(item.content)})' title="Copy" class="text-gray-300 hover:text-primary-600"><i class="fas fa-copy text-xs"></i></button>
                    <button onclick="delete${kind === 'prompt' ? 'Prompt' : 'Template'}(${item.id})" title="Delete" class="text-gray-300 hover:text-red-500"><i class="fas fa-trash-alt text-xs"></i></button>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-1 whitespace-pre-wrap line-clamp-3">${escapeHtml(item.content)}</p>
        </div>`;
}

function addPrompt() {
    addTextResource('prompt', 'prompt_title', 'prompt_content', LC_ROUTES.promptsStore, 'promptsList', 'noPrompts', 'text-purple-700');
}
function deletePrompt(id) {
    if (!confirm('Delete this prompt?')) return;
    fetch(`${LC_ROUTES.promptsDeleteBase}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => { if (data.success) document.querySelector(`[data-prompt-id="${id}"]`)?.remove(); })
        .catch(() => showToast('Failed to delete prompt', 'error'));
}

function addTemplate() {
    addTextResource('template', 'template_title', 'template_content', LC_ROUTES.templatesStore, 'templatesList', 'noTemplates', 'text-teal-700');
}
function deleteTemplate(id) {
    if (!confirm('Delete this template?')) return;
    fetch(`${LC_ROUTES.templatesDeleteBase}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => { if (data.success) document.querySelector(`[data-template-id="${id}"]`)?.remove(); })
        .catch(() => showToast('Failed to delete template', 'error'));
}
</script>
@endpush
@endsection
