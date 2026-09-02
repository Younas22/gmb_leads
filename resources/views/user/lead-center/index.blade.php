@extends('layouts.app')

@section('title', 'Lead Center')

@section('content')
<div class="p-3 lg:p-4">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl lg:text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-bullseye text-primary-600"></i> Lead Center
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">Your business-development workspace — organize, contact and convert your prospects.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('user.leads') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                <i class="fas fa-bookmark"></i> My Leads
            </a>
            <a href="{{ route('user.lead-center.resources.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                <i class="fas fa-book"></i> Resources
            </a>
            <button type="button" onclick="openImportModal()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium bg-primary-600 hover:bg-primary-700 text-white transition-colors shadow-sm">
                <i class="fas fa-plus"></i> Import Leads
            </button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
            <div class="flex items-center space-x-3">
                <i class="fas fa-check-circle text-green-600 text-lg"></i>
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
            <div class="flex items-center space-x-3">
                <i class="fas fa-exclamation-circle text-red-600 text-lg"></i>
                <p class="text-sm text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Summary Cards -->
    @php
        $cardCfg = [
            'total'      => ['label' => 'Total Leads', 'icon' => 'fa-layer-group', 'text' => 'text-gray-800', 'bg' => 'bg-gray-100'],
            'pending'    => ['label' => 'Pending',     'icon' => 'fa-hourglass-half', 'text' => 'text-red-700', 'bg' => 'bg-red-50'],
            'connected'  => ['label' => 'Connected',   'icon' => 'fa-plug', 'text' => 'text-blue-700', 'bg' => 'bg-blue-50'],
            'responded'  => ['label' => 'Responded',   'icon' => 'fa-comment-dots', 'text' => 'text-yellow-700', 'bg' => 'bg-yellow-50'],
            'follow_up'  => ['label' => 'Follow Up',   'icon' => 'fa-calendar-check', 'text' => 'text-purple-700', 'bg' => 'bg-purple-50'],
            'closed'     => ['label' => 'Closed',      'icon' => 'fa-flag-checkered', 'text' => 'text-green-700', 'bg' => 'bg-green-50'],
        ];
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        @foreach($cardCfg as $key => $cfg)
            @php $cardIsActive = $key === 'total' ? empty($status) : $status === $key; @endphp
            <a href="{{ route('user.lead-center.index', array_merge(request()->except(['status','page']), $key === 'total' ? [] : ['status' => $key])) }}"
               class="bg-white rounded-xl shadow-sm border border-gray-100 p-3.5 hover:shadow-md transition-shadow {{ $cardIsActive ? 'ring-2 ring-primary-300' : '' }}">
                <div class="flex items-center gap-2.5">
                    <div class="{{ $cfg['bg'] }} {{ $cfg['text'] }} w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $cfg['icon'] }} text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-gray-800 leading-tight">{{ number_format($stats[$key] ?? 0) }}</p>
                        <p class="text-[11px] text-gray-500 leading-tight truncate">{{ $cfg['label'] }}</p>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <!-- Folders -->
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <a href="{{ route('user.lead-center.index', request()->except(['folder_id','page'])) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold transition-all {{ !$folderId ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400' }}">
            <i class="fas fa-inbox text-[10px]"></i> All Leads
        </a>
        <a href="{{ route('user.lead-center.index', array_merge(request()->except(['folder_id','page']), ['folder_id' => 'unfiled'])) }}"
           title="Leads not yet moved into any folder"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold transition-all {{ $folderId === 'unfiled' ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-orange-700 border-orange-200 hover:border-orange-400' }}">
            <i class="fas fa-box-open text-[10px]"></i> Unfiled
            <span class="{{ $folderId === 'unfiled' ? 'opacity-80' : 'opacity-60' }} font-normal">({{ $unfiledCount }})</span>
        </a>
        @foreach($folders as $folder)
            @php $isActive = (string)$folderId === (string)$folder->id; @endphp
            <a href="{{ route('user.lead-center.index', array_merge(request()->except(['folder_id','page']), ['folder_id' => $folder->id])) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold transition-all {{ $isActive ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-indigo-700 border-indigo-200 hover:border-indigo-400' }}">
                <i class="fas fa-folder text-[10px]"></i>
                {{ $folder->name }}
                <span class="{{ $isActive ? 'opacity-80' : 'opacity-60' }} font-normal">({{ $folder->leads_count }})</span>
            </a>
        @endforeach
        <button type="button" onclick="openFolderModal([], 'create')"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full border text-xs font-medium text-gray-500 border-dashed border-gray-400 hover:border-gray-500 hover:text-gray-700 transition-all">
            <i class="fas fa-plus text-[10px]"></i> New Folder
        </button>
    </div>

    <!-- Search + Filters -->
    <form method="GET" action="{{ route('user.lead-center.index') }}" class="mb-4">
        @if($folderId)<input type="hidden" name="folder_id" value="{{ $folderId }}">@endif
        <div class="flex flex-col lg:flex-row gap-1.5">
            <div class="lg:flex-[3]">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by company name or website…"
                       class="w-full px-3 py-2 rounded-lg text-sm border border-gray-300 focus:outline-none focus:ring-1 focus:ring-primary-400">
            </div>
            <select name="status" class="px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer lg:flex-1">
                <option value="">All Statuses</option>
                @foreach(\App\Models\LeadCenterLead::statusLabels() as $key => $label)
                    <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="lg:flex-1">
                <select name="country_id" id="filter_country_select" class="w-full px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer">
                    <option value="">Country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}" {{ (string)$countryId === (string)$country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                Search
            </button>
            <a href="{{ route('user.lead-center.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium border text-center">
                Clear
            </a>
        </div>
    </form>

    @if($leads->count() > 0)
        <!-- Bulk Actions Bar -->
        @php $btn = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors'; @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4">
            <div class="px-4 py-3 flex flex-wrap items-center gap-2 justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <input type="checkbox" id="selectAll" class="w-4 h-4 text-primary-600 rounded border-gray-300 cursor-pointer">
                    <label for="selectAll" class="text-xs text-gray-600 cursor-pointer font-medium">Select All</label>
                    <span id="selectedCount" class="text-xs text-gray-400">(0 selected)</span>

                    <div id="bulkActions" class="flex flex-wrap items-center gap-2 hidden">
                        <div class="w-px h-4 bg-gray-300 mx-1"></div>
                        <select id="bulkStatus" class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-primary-400">
                            <option value="">Change Status</option>
                            @foreach(\App\Models\LeadCenterLead::statusLabels() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button onclick="bulkUpdateStatus()" class="{{ $btn }} bg-green-600 hover:bg-green-700 text-white">
                            <i class="fas fa-check"></i> Update Status
                        </button>
                        <button onclick="openFolderModal(getSelectedLeadIds(), 'move')" class="{{ $btn }} bg-indigo-600 hover:bg-indigo-700 text-white">
                            <i class="fas fa-folder-open"></i> Move to Folder
                        </button>
                        <button onclick="bulkDelete()" class="{{ $btn }} bg-red-600 hover:bg-red-700 text-white">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="copyPageLeads()" title="Copy company name + website for every lead shown on this page"
                            class="{{ $btn }} bg-gray-700 hover:bg-gray-800 text-white">
                        <i class="fas fa-copy"></i> Copy Leads
                    </button>
                </div>
            </div>
        </div>

        <!-- Leads Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 w-10"></th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Company</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 hidden md:table-cell">Website</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 hidden xl:table-cell">Location</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 hidden lg:table-cell">Folder</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 w-36">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 w-20">Messages</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 hidden lg:table-cell">Last Updated</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($leads as $lead)
                            @php $statusColors = \App\Models\LeadCenterLead::statusColors(); @endphp
                            <tr class="hover:bg-gray-50" data-company="{{ $lead->company_name }}" data-website="{{ $lead->website }}">
                                <td class="px-4 py-3 w-10">
                                    <input type="checkbox" class="w-4 h-4 text-primary-600 rounded border-gray-300 lead-checkbox" value="{{ $lead->id }}">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-semibold text-gray-900">{{ $lead->company_name }}</div>
                                    @if($lead->saved_lead_id)
                                        <span class="text-[10px] text-gray-400"><i class="fas fa-bookmark"></i> from My Leads</span>
                                    @endif
                                    <div class="md:hidden text-xs text-gray-500 mt-0.5">
                                        @if($lead->website)
                                            <a href="{{ $lead->website }}" target="_blank" class="text-blue-600 hover:underline">{{ str_replace(['http://','https://'], '', $lead->website) }}</a>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell">
                                    @if($lead->website)
                                        <a href="{{ $lead->website }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 hover:underline break-all">
                                            {{ str_replace(['http://','https://'], '', $lead->website) }}
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-400 italic">No website</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 hidden xl:table-cell">
                                    <div class="flex items-center gap-1.5 text-sm text-gray-600">
                                        <span>{{ collect([$lead->cityRelation->name ?? null, $lead->stateRelation->name ?? null, $lead->countryRelation->name ?? null])->filter()->implode(', ') ?: 'Not set' }}</span>
                                        <button type="button" title="Edit location"
                                                onclick="openLocationModal({{ $lead->id }}, {{ $lead->country_id ?? 'null' }}, {{ $lead->state_id ?? 'null' }}, {{ $lead->city_id ?? 'null' }})"
                                                class="text-gray-300 hover:text-primary-600 transition-colors flex-shrink-0">
                                            <i class="fas fa-pen text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 hidden lg:table-cell">
                                    @if($lead->folder)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-full">
                                            <i class="fas fa-folder text-[9px]"></i> {{ $lead->folder->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Unfiled</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 w-36">
                                    <select class="lead-status-select text-xs font-medium rounded-full px-2 py-1 border-0 cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary-400 {{ $statusColors[$lead->status] ?? 'bg-gray-100 text-gray-700' }}"
                                            data-lead-id="{{ $lead->id }}" onchange="updateLeadStatus({{ $lead->id }}, this.value, this)">
                                        @foreach(\App\Models\LeadCenterLead::statusLabels() as $key => $label)
                                            <option value="{{ $key }}" {{ $lead->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3 w-20">
                                    <a href="{{ route('user.lead-center.conversation', $lead->id) }}"
                                       class="inline-flex items-center gap-1 text-sm font-medium {{ $lead->messages_count > 0 ? 'text-primary-700' : 'text-gray-400' }} hover:text-primary-800">
                                        <i class="fas fa-comment-dots"></i> {{ $lead->messages_count }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="text-xs text-gray-500" title="{{ $lead->updated_at->format('M j, Y g:i A') }}">
                                        {{ $lead->updated_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 w-20">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('user.lead-center.conversation', $lead->id) }}" title="Open conversation"
                                           class="bg-primary-600 hover:bg-primary-700 text-white w-7 h-7 rounded flex items-center justify-center text-xs">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button onclick="deleteLead({{ $lead->id }}, '{{ addslashes($lead->company_name) }}')" title="Remove"
                                                class="bg-red-100 hover:bg-red-200 text-red-600 w-7 h-7 rounded flex items-center justify-center text-xs">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mt-4">
            <div class="px-6 py-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium">{{ $leads->firstItem() }}</span> to
                            <span class="font-medium">{{ $leads->lastItem() }}</span> of
                            <span class="font-medium">{{ $leads->total() }}</span> results
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-gray-500">Per page:</span>
                            <select onchange="changePerPage(this.value)" class="text-xs border border-gray-300 rounded px-2 py-1 cursor-pointer focus:outline-none focus:border-primary-400">
                                <option value="10"  {{ request('per_page', 20) == 10  ? 'selected' : '' }}>10</option>
                                <option value="20"  {{ request('per_page', 20) == 20  ? 'selected' : '' }}>20</option>
                                <option value="30"  {{ request('per_page', 20) == 30  ? 'selected' : '' }}>30</option>
                                <option value="50"  {{ request('per_page', 20) == 50  ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
                                <option value="all" {{ request('per_page') == 'all'   ? 'selected' : '' }}>All</option>
                            </select>
                        </div>
                    </div>
                    {{ $leads->links('pagination::tailwind') }}
                </div>
            </div>
        </div>
    @elseif($activeFolder)
        <!-- Empty Folder State -->
        <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-100 text-center">
            <i class="fas fa-folder-open text-gray-400 text-4xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">This folder doesn't contain any leads yet</h3>
            <p class="text-gray-600 mb-4">Move leads into "{{ $activeFolder->name }}" from the Lead Center table, or import new leads directly.</p>
            <a href="{{ route('user.lead-center.index') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium">
                View All Leads
            </a>
        </div>
    @elseif($folderId === 'unfiled')
        <!-- Empty Unfiled State -->
        <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-100 text-center">
            <i class="fas fa-check-circle text-green-400 text-4xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Every lead is filed into a folder</h3>
            <p class="text-gray-600 mb-4">No unfiled leads right now — nice and organized.</p>
            <a href="{{ route('user.lead-center.index') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium">
                View All Leads
            </a>
        </div>
    @elseif($search || $status || $countryId || $stateId || $cityId)
        <!-- Empty Filtered State -->
        <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-100 text-center">
            <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No leads match your filters</h3>
            <p class="text-gray-600 mb-4">Try adjusting your search or clearing filters.</p>
            <a href="{{ route('user.lead-center.index') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium">
                Clear Filters
            </a>
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-sm p-10 border border-gray-100 text-center">
            <i class="fas fa-bullseye text-gray-400 text-4xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No leads in your Lead Center yet</h3>
            <p class="text-gray-600 mb-5 max-w-md mx-auto">Import leads or select leads from My Leads to start building your business pipeline.</p>
            <div class="flex items-center justify-center gap-3">
                <button type="button" onclick="openImportModal()" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-lg font-medium">
                    <i class="fas fa-plus mr-1"></i> Import Leads
                </button>
                <a href="{{ route('user.leads') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg font-medium border">
                    <i class="fas fa-bookmark mr-1"></i> Go to My Leads
                </a>
            </div>
        </div>
    @endif
</div>

@include('user.lead-center._import-modal')
@include('user.lead-center._folder-modal')
@include('user.lead-center._location-modal')

@push('scripts')
@include('partials.select2-assets')

<script>
const LC_ROUTES = {
    bulk: '{{ route("user.lead-center.bulk") }}',
    statusBase: '{{ url("/user/lead-center") }}',
    deleteBase: '{{ url("/user/lead-center") }}',
    foldersIndex: '{{ route("user.lead-center.folders.index") }}',
    foldersStore: '{{ route("user.lead-center.folders.store") }}',
    foldersDeleteBase: '{{ url("/user/lead-center/folders") }}',
    importPreview: '{{ route("user.lead-center.import.preview") }}',
    importStore: '{{ route("user.lead-center.import.store") }}',
    apiStatesBase: '{{ url("/user/api/states") }}',
    apiCitiesBase: '{{ url("/user/api/cities") }}',
    locationBase: '{{ url("/user/lead-center") }}',
};
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function showToast(message, type) {
    const t = document.createElement('div');
    t.className = `fixed bottom-6 right-6 z-[999] px-4 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center gap-2 transition-all
        ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
    t.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ===== Selection / Bulk actions =====
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.lead-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkActions();
});
document.querySelectorAll('.lead-checkbox').forEach(cb => cb.addEventListener('change', updateBulkActions));

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.lead-checkbox');
    const checkedBoxes = document.querySelectorAll('.lead-checkbox:checked');
    const selectAll = document.getElementById('selectAll');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');

    selectedCount.textContent = `(${checkedBoxes.length} selected)`;
    bulkActions.classList.toggle('hidden', checkedBoxes.length === 0);
    if (selectAll) {
        selectAll.checked = checkedBoxes.length === checkboxes.length && checkboxes.length > 0;
        selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
    }
}

function getSelectedLeadIds() {
    return Array.from(document.querySelectorAll('.lead-checkbox:checked')).map(cb => parseInt(cb.value));
}

function bulkPost(payload, successCb) {
    fetch(LC_ROUTES.bulk, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            successCb ? successCb(data) : setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Something went wrong', 'error');
        }
    })
    .catch(() => showToast('Something went wrong', 'error'));
}

function bulkUpdateStatus() {
    const status = document.getElementById('bulkStatus').value;
    const leadIds = getSelectedLeadIds();
    if (!status) { showToast('Please select a status', 'error'); return; }
    if (!leadIds.length) { showToast('Please select leads to update', 'error'); return; }
    bulkPost({ action: 'update_status', lead_ids: leadIds, status });
}

function bulkDelete() {
    const leadIds = getSelectedLeadIds();
    if (!leadIds.length) { showToast('Please select leads to remove', 'error'); return; }
    if (!confirm(`Remove ${leadIds.length} selected lead(s) from Lead Center? This cannot be undone.`)) return;
    bulkPost({ action: 'delete', lead_ids: leadIds });
}

function deleteLead(id, name) {
    if (!confirm(`Remove "${name}" from Lead Center? This cannot be undone.`)) return;
    fetch(`${LC_ROUTES.deleteBase}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 700); }
        else showToast(data.message || 'Failed to remove lead', 'error');
    })
    .catch(() => showToast('Failed to remove lead', 'error'));
}

function updateLeadStatus(id, status, selectEl) {
    fetch(`${LC_ROUTES.statusBase}/${id}/status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ status })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Reload so the status pill color, summary cards and "Last Updated" column stay accurate
            setTimeout(() => location.reload(), 700);
        } else {
            showToast(data.message || 'Failed to update status', 'error');
        }
    })
    .catch(() => showToast('Failed to update status', 'error'));
}

function copyPageLeads() {
    const rows = document.querySelectorAll('tbody tr[data-company]');
    if (!rows.length) { showToast('No leads on this page to copy', 'error'); return; }

    const lines = Array.from(rows).map(row => {
        const company = row.dataset.company || '';
        const website = row.dataset.website || '';
        return website ? `${company}\t${website}` : company;
    });

    navigator.clipboard.writeText(lines.join('\n'))
        .then(() => showToast(`Copied ${lines.length} lead(s) — company name + website`, 'success'))
        .catch(() => showToast('Failed to copy — your browser blocked clipboard access', 'error'));
}

function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// ===== Location filter cascading (search bar) =====
$(document).ready(function() {
    $('#filter_country_select').select2({ placeholder: 'Country', allowClear: true, width: '100%' });
});
</script>

@include('user.lead-center._location-select2-helper')
@include('user.lead-center._import-modal-scripts')
@include('user.lead-center._folder-modal-scripts')
@include('user.lead-center._location-modal-scripts')
@endpush
@endsection
