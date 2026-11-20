@extends('layouts.app')

@section('title', 'AI Outreach')

@php
    $featureLabels = ['website_audit' => 'AI analyses', 'email_draft' => 'AI email drafts', 'offer_builder' => 'AI offers'];
    $featureIcons = ['website_audit' => 'fa-magnifying-glass-chart', 'email_draft' => 'fa-envelope-open-text', 'offer_builder' => 'fa-gift'];
@endphp

@section('content')
@include('user._ui-kit')
<div class="cnm-ui ui-page ui-stack">

    <div class="ui-head">
        <div class="ui-head-main">
            <span class="ui-head-icon"><i class="fas fa-wand-magic-sparkles"></i></span>
            <div class="min-w-0">
                <h1 class="ui-title flex items-center gap-2">AI Outreach <span class="ui-ai"><i class="fas fa-bolt"></i> AI</span></h1>
                <p class="ui-sub">AI checks each lead's website, matches it to your best-fit offer and writes a personal email <strong>draft</strong>. You review every word. Nothing is ever sent automatically.</p>
            </div>
        </div>
        <div class="ui-actions">
            <a href="{{ route('user.leads') }}" class="ui-btn"><i class="fas fa-plus"></i> Analyze leads</a>
            <a href="{{ route('user.ai-profile.index') }}" class="ui-btn-light"><i class="fas fa-user-tie"></i> AI Sales Profile</a>
        </div>
    </div>

    @unless($aiAvailable)
        <div class="ui-note"><i class="fas fa-info-circle text-slate-500"></i> AI is not configured on this server yet, so new analyses cannot start.</div>
    @endunless

    {{-- Usage --}}
    <div class="ui-stats c5">
        @foreach($usage['features'] as $feature => $f)
            @php $fPct = $f['limit'] > 0 ? min(100, (int) round($f['used'] / $f['limit'] * 100)) : 0; @endphp
            <div class="ui-stat">
                <p class="ui-stat-l"><i class="fas {{ $featureIcons[$feature] ?? 'fa-circle' }}"></i>{{ $featureLabels[$feature] ?? $feature }} · {{ $usage['month'] }}</p>
                <p class="ui-stat-v">{{ $f['used'] }} <small>/ {{ $f['limit'] === -1 ? 'unlimited' : ($f['limit'] === 0 ? 'not in plan' : $f['limit']) }}</small></p>
                @if($f['limit'] > 0)<div class="ui-bar"><span class="{{ $fPct >= 85 ? 'is-hot' : '' }}" style="width: {{ $fPct }}%"></span></div>@endif
                <p class="ui-stat-h">{{ number_format($f['input_tokens'] + $f['output_tokens']) }} tokens · {{ $f['failed'] }} failed</p>
            </div>
        @endforeach
        <div class="ui-stat !bg-[#FFF7ED] !border-[#FED7AA]">
            <p class="ui-stat-l"><i class="fas fa-pen-to-square"></i>Drafts</p>
            <p class="ui-stat-v">{{ $usage['drafts']['draft'] }} <small>to review</small></p>
            <p class="ui-stat-h">{{ $usage['drafts']['approved'] }} approved</p>
        </div>
        <div class="ui-stat">
            <p class="ui-stat-l"><i class="fas fa-coins"></i>Estimated AI cost</p>
            <p class="ui-stat-v">{{ $usage['estimated_cost_usd'] !== null ? '$' . number_format($usage['estimated_cost_usd'], 2) : '—' }}</p>
            <p class="ui-stat-h">Estimate from token counts this month</p>
        </div>
    </div>

    @if($batches->isNotEmpty())
        <div class="ui-card ui-card-b !py-3 flex flex-wrap items-center gap-2">
            <span class="text-[12px] font-semibold text-gray-900 mr-1"><i class="fas fa-clock-rotate-left text-orange-500 mr-1"></i>Recent requests</span>
            @foreach($batches as $b)
                <a href="{{ route('user.ai-outreach.batch', $b->id) }}" class="ui-badge hover:!border-orange-300 hover:!bg-orange-50 !h-7 !px-2.5">
                    {{ $b->created_at->format('M d, H:i') }} · {{ $b->total_items }} lead(s)
                </a>
            @endforeach
        </div>
    @endif

    {{-- Filters + results --}}
    <div class="ui-card">
        <form method="GET" class="p-3 sm:px-4 border-b border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2">
            @if(request()->filled('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
            <div class="relative sm:col-span-2 lg:col-span-2">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Search business name" aria-label="Search business name" class="ui-field !pl-8">
            </div>
            <select name="folder" class="ui-field" aria-label="Folder">
                <option value="">All folders</option>
                @foreach($folders as $fo)<option value="{{ $fo->id }}" @selected((int) request('folder') === $fo->id)>{{ $fo->name }}</option>@endforeach
            </select>
            <select name="status" class="ui-field" aria-label="Analysis status">
                <option value="">All analysis statuses</option>
                @foreach(\App\Models\AiLeadAnalysis::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2 lg:col-span-2 min-w-0">
                <select name="review" class="ui-field flex-1 min-w-0" aria-label="Email status">
                    <option value="">All email statuses</option>
                    <option value="draft" @selected(request('review') === 'draft')>Draft</option>
                    <option value="approved" @selected(request('review') === 'approved')>Approved</option>
                    <option value="discarded" @selected(request('review') === 'discarded')>Discarded</option>
                    <option value="none" @selected(request('review') === 'none')>No draft yet</option>
                </select>
                <button class="ui-btn-dark"><i class="fas fa-filter text-[11px]"></i> Filter</button>
            </div>
        </form>

        {{-- Put selected leads into a folder (same folders as My Leads); then send a folder from Campaigns --}}
        <form id="folder-form" method="POST" action="{{ route('user.ai-outreach.folders.add') }}" class="px-3 sm:px-4 py-2 border-b border-slate-100 bg-slate-50 flex flex-col sm:flex-row sm:items-center gap-2 text-[12.5px]">
            @csrf
            <span class="text-slate-700"><strong id="cnm-selected">0</strong> selected →</span>
            <select name="folder_id" class="ui-field ui-sm sm:!w-48" aria-label="Add to folder"
                    onchange="document.getElementById('cnm-new-folder').classList.toggle('hidden', this.value !== ''); ">
                <option value="">+ New folder…</option>
                @foreach($folders as $fo)<option value="{{ $fo->id }}">{{ $fo->name }}</option>@endforeach
            </select>
            <input id="cnm-new-folder" type="text" name="new_folder" maxlength="100" placeholder="New folder name" aria-label="New folder name" class="ui-field ui-sm sm:!w-48 {{ $folders->isNotEmpty() ? 'hidden' : '' }}">
            <button class="ui-btn-light ui-sm"><i class="fas fa-folder-plus"></i> Add to folder</button>
            <span class="hidden sm:inline text-slate-300" aria-hidden="true">|</span>
            <button type="submit" formaction="{{ route('user.ai-outreach.drafts.bulk-approve') }}" data-testid="bulk-approve"
                    onclick="return cnmConfirmBulkApprove()"
                    class="ui-btn ui-sm !bg-emerald-600 hover:!bg-emerald-700 !text-white !border-emerald-600"><i class="fas fa-check-double"></i> Approve emails</button>
            <a href="{{ route('user.folder-sends.create', array_filter(['folder_id' => request('folder')])) }}" class="sm:ml-auto ui-link"><i class="fas fa-paper-plane mr-1"></i> Send a folder</a>
        </form>
        @error('analysis_ids')<p class="px-4 pt-2 text-[12px] text-red-600">{{ $message }}</p>@enderror
        @error('folder_id')<p class="px-4 pt-2 text-[12px] text-red-600">{{ $message }}</p>@enderror

        @include('user.ai-outreach._table', ['rows' => $analyses, 'selectable' => true])

        {{-- Pagination + rows per page (like My Leads) --}}
        <div class="ui-card-f flex flex-col lg:flex-row items-center justify-between gap-2.5">
            <div class="flex flex-wrap items-center justify-center gap-3 text-[12.5px] text-slate-600">
                @if($analyses->total() > 0)
                    <span>Showing <strong class="text-gray-900">{{ $analyses->firstItem() }}</strong> to <strong class="text-gray-900">{{ $analyses->lastItem() }}</strong> of <strong class="text-gray-900">{{ $analyses->total() }}</strong> results</span>
                @endif
                <label class="flex items-center gap-2">
                    <span class="text-xs text-slate-500">Per page:</span>
                    <select id="cnm-per-page" onchange="cnmChangePerPage(this.value)" class="ui-field ui-sm !w-20 cursor-pointer" data-testid="per-page">
                        @foreach($perPageOptions as $n)
                            <option value="{{ $n }}" @selected((string) request('per_page', $perPageDefault) === $n)>{{ $n }}</option>
                        @endforeach
                        <option value="all" @selected(request('per_page') === 'all')>All</option>
                    </select>
                </label>
            </div>
            @if($analyses->hasPages())
                <div>{{ $analyses->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function cnmChangePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
function cnmConfirmBulkApprove() {
    const n = document.querySelectorAll('.cnm-row-check:checked').length;
    if (!n) { alert('Select at least one lead first.'); return false; }
    return confirm('Approve the email drafts of ' + n + ' selected lead(s)? Drafts that are already approved, discarded or not written yet are skipped. Nothing is sent automatically.');
}
function cnmUpdateSelected() {
    const n = document.querySelectorAll('.cnm-row-check:checked').length;
    const el = document.getElementById('cnm-selected');
    if (el) el.textContent = n;
}
</script>
@endpush
