@extends('layouts.app')

@section('title', 'Saved Leads')

@section('content')
<div class="p-3 lg:p-4">
    <!-- Package Export Info -->
    @php
        $exportLimit = Auth::user()->getFeatureLimit('export_leads');
        $todayExportCount = \App\Models\ExportHistory::where('user_id', Auth::id())
            ->whereDate('created_at', today())
            ->count();
    @endphp

    @if($exportLimit !== -1)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 mb-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="bg-blue-100 p-1.5 rounded-md">
                        <i class="fas fa-download text-blue-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-blue-900">Monthly Export Limit</p>
                        <p class="text-[10px] text-blue-700">
                            Used <span class="font-semibold">{{ $todayExportCount }}</span> of
                            <span class="font-semibold">{{ $exportLimit }}</span> exports this month.
                        </p>
                    </div>
                </div>
                @if($todayExportCount >= $exportLimit)
                    <a href="{{ route('user.subscription') }}"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors">
                        <i class="fas fa-arrow-up mr-1"></i>Upgrade
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
            <div class="flex items-center space-x-3">
                <i class="fas fa-check-circle text-green-600 text-lg"></i>
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <div class="flex items-center space-x-3">
                <i class="fas fa-exclamation-circle text-red-600 text-lg"></i>
                <p class="text-sm text-red-800">{!! session('error') !!}</p>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-primary-100 rounded-lg">
                    <i class="fas fa-bookmark text-primary-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Leads</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-phone text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Contacted</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['contacted'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-lg">
                    <i class="fas fa-clock text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Converted</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['converted'] }}</p>
                </div>
            </div>
        </div>
    </div>



<style>
.search-form { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; }
.search-input { background: rgba(255,255,255,0.8); border: 1px solid #d1d5db; transition: all 0.2s; }
.search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); background: white; }
.select-custom { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e"); background-position: right 6px center; background-repeat: no-repeat; background-size: 14px; }
.btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.btn-primary:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); transform: translateY(-1px); }
.filters-container { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
.filters-container.open { max-height: 400px; }
@media (min-width: 1024px) {
    .compact-select { max-width: 110px; }
}
</style>

<!-- Compact Single Form -->
<form method="GET" action="{{ route('user.leads') }}" class="search-form rounded-xl shadow-sm p-3">
    
    <!-- Top Row: Search + Mobile Toggle -->
    <div class="flex gap-2 mb-3">
        <div class="flex-1 relative">
            <input type="text" 
                   name="search" 
                   value="{{ $search }}"
                   placeholder="Search by name, email, phone..." 
                   class="search-input w-full pl-9 pr-3 py-2 rounded-lg text-sm appearance-none">
            <div class="absolute left-3 top-1/2 -translate-y-1/2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
        
        <!-- Mobile Toggle -->
        <button type="button" onclick="toggleFilters()" class="lg:hidden search-input px-3 py-2 rounded-lg text-sm border bg-white">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
            </svg>
        </button>
        
        <!-- Desktop Buttons -->
        <button type="submit" class="hidden lg:block btn-primary text-white px-5 py-2 rounded-lg text-sm font-medium transition-all duration-200">
            Search
        </button>
        <a href="{{ route('user.leads') }}" class="hidden lg:block bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors border">
            Clear
        </a>
    </div>

    <!-- User Filter (Company Only) -->
    @if(auth()->user()->isCompany() || auth()->user()->isTeamMember())
    <div class="mb-3">
        <x-user-filter :selectedUserId="$selectedUserId ?? null" />
    </div>
    @endif

   <div class="flex flex-col lg:flex-row gap-1.5 lg:flex-nowrap">

    <!-- Country -->
    <div class="lg:flex-[2]">
        <select name="country_id" id="country_select"
            class="search-input px-2 py-2 rounded-lg text-sm appearance-none cursor-pointer w-full">
            <option value="">Country</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}" {{ $countryId == $country->id ? 'selected' : '' }}>
                    {{ $country->name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- State -->
    <div class="lg:flex-[2]">
        <select name="state_id" id="state_select"
            class="search-input px-2 py-2 rounded-lg text-sm appearance-none cursor-pointer w-full"
            disabled>
            <option value="">State</option>
        </select>
    </div>

    <!-- City -->
    <div class="lg:flex-[2]">
        <select name="city_id" id="city_select"
            class="search-input px-2 py-2 rounded-lg text-sm appearance-none cursor-pointer w-full"
            disabled>
            <option value="">City</option>
        </select>
    </div>

    <!-- Rest normal size -->
    <select name="status" class="search-input compact-select px-2 py-2 rounded-lg text-sm cursor-pointer lg:flex-1">
        <option value="">Status</option>
        <option value="not_contacted" {{ $status == 'not_contacted' ? 'selected' : '' }}>New</option>
        <option value="contacted"     {{ $status == 'contacted'     ? 'selected' : '' }}>Contacted</option>
        <option value="responded"     {{ $status == 'responded'     ? 'selected' : '' }}>Responded</option>
        <option value="converted"     {{ $status == 'converted'     ? 'selected' : '' }}>Converted</option>
        <option value="closed"        {{ $status == 'closed'        ? 'selected' : '' }}>Closed</option>
    </select>


    @if(auth()->user()->hasFeature('advanced_review_filters'))
        <select name="rating" class="search-input compact-select px-2 py-2 rounded-lg text-sm cursor-pointer lg:flex-1">
            <option value="">Rating</option>
            <option value="4.5" {{ $rating == '4.5' ? 'selected' : '' }}>4.5+</option>
            <option value="4.0" {{ $rating == '4.0' ? 'selected' : '' }}>4.0+</option>
            <option value="3.5" {{ $rating == '3.5' ? 'selected' : '' }}>3.5+</option>
            <option value="3.0" {{ $rating == '3.0' ? 'selected' : '' }}>3.0+</option>
        </select>

        <select name="last_review" class="search-input compact-select px-2 py-2 rounded-lg text-sm cursor-pointer lg:flex-1">
            <option value="">Review</option>
            <option value="1-day"    {{ $lastReview == '1-day'    ? 'selected' : '' }}>1 day</option>
            <option value="1-week"   {{ $lastReview == '1-week'   ? 'selected' : '' }}>1 week</option>
            <option value="1-month"  {{ $lastReview == '1-month'  ? 'selected' : '' }}>1 month</option>
            <option value="3-months" {{ $lastReview == '3-months' ? 'selected' : '' }}>3 months</option>
            <option value="6-months" {{ $lastReview == '6-months' ? 'selected' : '' }}>6 months</option>
        </select>

        <select name="reviews_count" class="search-input compact-select px-2 py-2 rounded-lg text-sm cursor-pointer lg:flex-1">
            <option value="">Reviews #</option>
            <option value="lt30"   {{ $reviewsCount == 'lt30'   ? 'selected' : '' }}>< 30</option>
            <option value="lt50"   {{ $reviewsCount == 'lt50'   ? 'selected' : '' }}>< 50</option>
            <option value="lt100"  {{ $reviewsCount == 'lt100'  ? 'selected' : '' }}>< 100</option>
            <option value="gte100" {{ $reviewsCount == 'gte100' ? 'selected' : '' }}>100+</option>
        </select>
    @else
        <!-- Rating Filter - Locked -->
        <div class="relative lg:flex-1">
            <select disabled class="search-input compact-select px-2 py-2 rounded-lg text-sm bg-gray-50 text-gray-400 cursor-not-allowed lg:w-full">
                <option value="">Rating</option>
            </select>
            <a href="{{ route('user.subscription') }}"
               class="absolute top-0 right-0 -mt-1 -mr-1 inline-flex items-center bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm transition-all hover:shadow z-10"
               title="Upgrade to unlock rating filters">
                <i class="fas fa-lock mr-0.5 text-[8px]"></i>Upgrade
            </a>
        </div>

        <!-- Review Filter - Locked -->
        <div class="relative lg:flex-1">
            <select disabled class="search-input compact-select px-2 py-2 rounded-lg text-sm bg-gray-50 text-gray-400 cursor-not-allowed lg:w-full">
                <option value="">Review</option>
            </select>
            <a href="{{ route('user.subscription') }}"
               class="absolute top-0 right-0 -mt-1 -mr-1 inline-flex items-center bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm transition-all hover:shadow z-10"
               title="Upgrade to unlock review filters">
                <i class="fas fa-lock mr-0.5 text-[8px]"></i>Upgrade
            </a>
        </div>

        <!-- Reviews Count Filter - Locked -->
        <div class="relative lg:flex-1">
            <select disabled class="search-input compact-select px-2 py-2 rounded-lg text-sm bg-gray-50 text-gray-400 cursor-not-allowed lg:w-full">
                <option value="">Reviews #</option>
            </select>
            <a href="{{ route('user.subscription') }}"
               class="absolute top-0 right-0 -mt-1 -mr-1 inline-flex items-center bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm transition-all hover:shadow z-10"
               title="Upgrade to unlock review count filters">
                <i class="fas fa-lock mr-0.5 text-[8px]"></i>Upgrade
            </a>
        </div>
    @endif

</div>

<!-- Has Email / Phone / Website toggle row -->
<div class="flex items-center gap-4 mt-2.5 pt-2.5 border-t border-gray-200">
    <span class="text-xs text-gray-500 font-medium uppercase tracking-wide flex-shrink-0">Has:</span>

    <!-- Email -->
    @php $emailOn = ($hasEmail ?? '1') !== '0'; @endphp
    <label class="flex items-center gap-1.5 cursor-pointer select-none">
        <input type="hidden" name="has_email" value="{{ $emailOn ? '1' : '0' }}">
        <button type="button"
            onclick="toggleFilter(this, 'has_email')"
            data-active="{{ $emailOn ? '1' : '0' }}"
            class="relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $emailOn ? 'bg-green-500' : 'bg-gray-300' }}">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 {{ $emailOn ? 'translate-x-4' : 'translate-x-0' }}"></span>
        </button>
        <span class="text-xs text-gray-600">Email</span>
    </label>

    <!-- Phone -->
    @php $phoneOn = ($hasPhone ?? '1') !== '0'; @endphp
    <label class="flex items-center gap-1.5 cursor-pointer select-none">
        <input type="hidden" name="has_phone" value="{{ $phoneOn ? '1' : '0' }}">
        <button type="button"
            onclick="toggleFilter(this, 'has_phone')"
            data-active="{{ $phoneOn ? '1' : '0' }}"
            class="relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $phoneOn ? 'bg-green-500' : 'bg-gray-300' }}">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 {{ $phoneOn ? 'translate-x-4' : 'translate-x-0' }}"></span>
        </button>
        <span class="text-xs text-gray-600">Phone</span>
    </label>

    <!-- Website -->
    @php $websiteOn = ($hasWebsite ?? '1') !== '0'; @endphp
    <label class="flex items-center gap-1.5 cursor-pointer select-none">
        <input type="hidden" name="has_website" value="{{ $websiteOn ? '1' : '0' }}">
        <button type="button"
            onclick="toggleFilter(this, 'has_website')"
            data-active="{{ $websiteOn ? '1' : '0' }}"
            class="relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $websiteOn ? 'bg-green-500' : 'bg-gray-300' }}">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 {{ $websiteOn ? 'translate-x-4' : 'translate-x-0' }}"></span>
        </button>
        <span class="text-xs text-gray-600">Website</span>
    </label>
</div>

</form>


    @if($leads->count() > 0)
        <!-- Bulk Actions Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 text-primary-600 rounded border-gray-300 cursor-pointer">
                        <label for="selectAll" class="text-sm text-gray-700 cursor-pointer">Select All</label>
                        <span id="selectedCount" class="text-sm text-gray-500">(0 selected)</span>
                    </div>
                    
                    <div id="bulkActions" class="flex items-center space-x-2 hidden">
                        <select id="bulkStatus" class="px-3 py-1 border border-gray-300 rounded text-sm">
                            <option value="">Change Status</option>
                            <option value="not_contacted">Not Contacted</option>
                            <option value="contacted">Contacted</option>
                            <option value="responded">Responded</option>
                            <option value="converted">Converted</option>
                            <option value="closed">Closed</option>
                        </select>
                        <button onclick="bulkUpdateStatus()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                            Update Status
                        </button>
                        <button onclick="bulkDelete()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                            <i class="fas fa-trash mr-1"></i>Delete Selected
                        </button>
                    </div>

                    <!-- Export Button -->
                    @php
                        $exportLimit = Auth::user()->getFeatureLimit('export_leads');
                        $todayExportCount = \App\Models\ExportHistory::where('user_id', Auth::id())
                            ->whereDate('created_at', today())
                            ->count();
                        $canExport = $exportLimit === -1 || $todayExportCount < $exportLimit;
                    @endphp

                    <div class="flex items-center gap-2">
                        <!-- Hide/Show Button -->
                        <button type="button" onclick="toggleLeadsVisibility()" id="toggleVisibilityBtn"
                                class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
                            <i class="fas fa-eye-slash" id="visibilityIcon"></i>
                            <span id="visibilityText">Hide Details</span>
                        </button>

                        <!-- Export Dropdown Button -->
                        <div class="relative inline-block text-left">
                        @if($canExport)
                            <button type="button" onclick="toggleExportDropdown()"
                                    class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                <div class="py-1" role="menu">
                                    <a href="{{ route('user.leads.export', request()->all()) }}"
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-file-csv text-blue-600 mr-2"></i>
                                        Export as CSV
                                    </a>
                                    <a href="{{ route('user.leads.export.excel', request()->all()) }}"
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-file-excel text-green-600 mr-2"></i>
                                        Export as Excel
                                    </a>
                                </div>
                            </div>
                        @else
                            <button disabled
                                    class="bg-gray-400 cursor-not-allowed text-white px-4 py-2 rounded text-sm font-medium flex items-center space-x-2"
                                    title="Daily export limit reached. Upgrade your package or try again tomorrow.">
                                <i class="fas fa-download"></i>
                                <span>Export (Limit Reached)</span>
                            </button>
                        @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leads Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-6 py-4 w-16">
                                <span class="text-sm font-semibold text-gray-700">Select</span>
                            </th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Business</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Contact</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700 w-48">Location</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700 w-36">Rating</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700 w-28">Status</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700 w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($leads as $lead)
                            <tr class="hover:bg-gray-50 cursor-pointer lead-row" data-lead-id="{{ $lead->id }}">
                                <td class="px-6 py-4 w-16">
                                    <input type="checkbox"
                                           class="w-4 h-4 text-primary-600 rounded border-gray-300 lead-checkbox"
                                           value="{{ $lead->id }}"
                                           onclick="event.stopPropagation()">
                                </td>

                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 contact-detail" data-type="name" data-original="{{ $lead->name }}">{{ $lead->name }}</div>
                                        <div class="text-xs text-orange-600 font-medium">{{ $lead->category ?? 'Business' }}</div>
                                    </div>
                                </td>

                               <td class="px-6 py-4 max-w-xs">
                                <div class="space-y-1 truncate">
                                    @if($lead->phone)
                                        <div class="flex items-center text-sm text-gray-600 gap-2">
                                            <i class="fas fa-phone w-4"></i>
                                            <span class="contact-detail" data-type="phone" data-original="{{ $lead->phone }}">{{ $lead->phone }}</span>
                                            <button onclick="event.stopPropagation(); copyToClipboard('{{ $lead->phone }}', this)"
                                                    class="text-blue-500 hover:text-blue-700 transition-colors copy-btn"
                                                    title="Copy phone">
                                                <i class="fas fa-copy text-xs"></i>
                                            </button>
                                        </div>
                                    @endif
                                    @if($lead->email)
                                        <div class="flex items-center text-sm text-gray-600 gap-2">
                                            <i class="fas fa-envelope w-4"></i>
                                            <span class="contact-detail" data-type="email" data-original="{{ $lead->email }}">{{ $lead->email }}</span>
                                            <button onclick="event.stopPropagation(); copyToClipboard('{{ $lead->email }}', this)"
                                                    class="text-blue-500 hover:text-blue-700 transition-colors copy-btn"
                                                    title="Copy email">
                                                <i class="fas fa-copy text-xs"></i>
                                            </button>
                                        </div>
                                    @endif
                                    @if($lead->website)
                                        <div class="flex items-center text-sm text-gray-600 gap-2">
                                            <i class="fas fa-globe w-4"></i>
                                            <a href="{{ $lead->website }}" target="_blank" class="text-blue-600 hover:text-blue-800 truncate contact-detail" data-type="website" data-original="{{ str_replace(['http://', 'https://'], '', $lead->website) }}">
                                                {{ str_replace(['http://', 'https://'], '', $lead->website) }}
                                            </a>
                                            <button onclick="event.stopPropagation(); copyToClipboard('{{ $lead->website }}', this)"
                                                    class="text-blue-500 hover:text-blue-700 transition-colors copy-btn"
                                                    title="Copy website">
                                                <i class="fas fa-copy text-xs"></i>
                                            </button>
                                        </div>
                                    @endif
                                    @if(!$lead->phone && !$lead->email && !$lead->website)
                                        <span class="text-xs text-gray-400 italic">No contact info</span>
                                    @endif
                                </div>
                            </td>


                                <td class="px-6 py-4 w-48">
                                    <div class="text-sm text-gray-600 contact-detail" data-type="location" data-original="{{ $lead->search_location }}">
                                        {{ $lead->search_location }}
                                    </div>

                                    <!-- <div class="text-sm text-gray-600">
                                        {{ $lead->city ? $lead->city . ', ' : '' }}{{ $lead->country }}
                                    </div>
                                    @if($lead->address)
                                        <div class="text-xs text-gray-500">{{ Str::limit($lead->address, 50) }}</div>
                                    @endif -->
                                </td>

                              <td class="px-6 py-4 w-36">
    @if($lead->rating && $lead->rating > 0)
        <div class="flex items-center space-x-1">
            <div class="flex text-yellow-400 text-sm">
                @for($i = 1; $i <= 5; $i++)
                    @if($i <= floor($lead->rating))
                        <i class="fas fa-star"></i>
                    @elseif($i <= $lead->rating)
                        <i class="fas fa-star-half-alt"></i>
                    @else
                        <i class="far fa-star"></i>
                    @endif
                @endfor
            </div>
            <span class="text-sm font-medium text-gray-700">{{ number_format($lead->rating, 1) }}</span>
        </div>
        @if($lead->total_reviews > 0)
            <div class="text-xs text-gray-500">{{ number_format($lead->total_reviews) }} reviews</div>

            @php
                // Try reviews_sample first (pro plan), then fall back to last_review_date column (growth plan)
                $reviewsSample = $lead->reviews_sample ? json_decode($lead->reviews_sample, true) : [];
                $latestReviewDate = null;

                if (!empty($reviewsSample) && is_array($reviewsSample)) {
                    $latestTime = 0;
                    foreach ($reviewsSample as $review) {
                        if (isset($review['time']) && $review['time'] > $latestTime) {
                            $latestTime = $review['time'];
                        }
                    }
                    if ($latestTime > 0) {
                        $latestReviewDate = \Carbon\Carbon::createFromTimestamp($latestTime);
                    }
                }

                // Fallback to last_review_date DB column (saved from API's latest_review_date)
                if (!$latestReviewDate && $lead->last_review_date) {
                    $latestReviewDate = \Carbon\Carbon::parse($lead->last_review_date);
                }
            @endphp

            @if($latestReviewDate)
                <p class="text-xs text-gray-400 mt-1">
                    Last: {{ $latestReviewDate->diffForHumans() }}
                </p>
            @endif
        @endif
    @else
        <span class="text-sm text-gray-400">No rating</span>
    @endif
</td>
                                

                               


                                <td class="px-6 py-4 w-28">
                                    @php
                                        $statusColors = [
                                            'not_contacted' => 'bg-red-100 text-red-700',
                                            'contacted' => 'bg-blue-100 text-blue-700',
                                            'responded' => 'bg-yellow-100 text-yellow-700',
                                            'converted' => 'bg-green-100 text-green-700',
                                            'closed' => 'bg-gray-100 text-gray-700'
                                        ];
                                        $statusLabels = [
                                            'not_contacted' => 'Not Contacted',
                                            'contacted' => 'Contacted',
                                            'responded' => 'Responded',
                                            'converted' => 'Converted',
                                            'closed' => 'Closed'
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 {{ $statusColors[$lead->contact_status] ?? 'bg-gray-100 text-gray-700' }} text-xs font-medium rounded-full">
                                        {{ $statusLabels[$lead->contact_status] ?? 'Unknown' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 w-24">
                                    <div class="flex flex-col gap-1">
                                        <button class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1 rounded text-xs font-medium view-btn"
                                                onclick="event.stopPropagation(); openLeadDetails({{ $lead->id }})">
                                            <i class="fas fa-eye mr-1"></i>
                                        </button>
                                        <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-medium copy-all-btn"
                                                onclick="event.stopPropagation(); copyAllLeadData({{ json_encode([
                                                    'name' => $lead->name,
                                                    'category' => $lead->category,
                                                    'phone' => $lead->phone,
                                                    'email' => $lead->email,
                                                    'website' => $lead->website,
                                                    'location' => $lead->search_location,
                                                    'address' => $lead->address,
                                                    'rating' => $lead->rating,
                                                    'total_reviews' => $lead->total_reviews,
                                                    'status' => $lead->contact_status
                                                ]) }})"
                                                title="Copy all lead data">
                                            <i class="fas fa-copy mr-1"></i>
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mt-6">
            <div class="px-6 py-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium">{{ $leads->firstItem() }}</span> to
                            <span class="font-medium">{{ $leads->lastItem() }}</span> of
                            <span class="font-medium">{{ $leads->total() }}</span> results
                        </div>
                        <!-- Per Page Selector -->
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-gray-500">Per page:</span>
                            <select onchange="changePerPage(this.value)" class="text-xs border border-gray-300 rounded px-2 py-1 cursor-pointer focus:outline-none focus:border-primary-400">
                                <option value="10"  {{ request('per_page', 30) == 10  ? 'selected' : '' }}>10</option>
                                <option value="20"  {{ request('per_page', 30) == 20  ? 'selected' : '' }}>20</option>
                                <option value="30"  {{ request('per_page', 30) == 30  ? 'selected' : '' }}>30</option>
                                <option value="50"  {{ request('per_page', 30) == 50  ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page', 30) == 100 ? 'selected' : '' }}>100</option>
                                <option value="all" {{ request('per_page') == 'all'   ? 'selected' : '' }}>All</option>
                            </select>
                        </div>
                    </div>
                    {{ $leads->links('pagination::tailwind') }}
                </div>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-100 text-center">
            <i class="fas fa-bookmark text-gray-400 text-4xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Saved Leads</h3>
            <p class="text-gray-600 mb-4">You haven't saved any leads yet. Start by searching for businesses and saving potential leads.</p>
            <a href="{{ route('user.search') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-search mr-2"></i>Search for Leads
            </a>
        </div>
    @endif
</div>

<!-- Lead Details Panel -->
<div id="leadDetailsPanel" class="fixed inset-y-0 right-0 w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50">
    <div class="h-full flex flex-col">
        <!-- Panel Header -->
        <div class="bg-primary-600 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">Lead Details</h3>
            <button onclick="closeLeadDetails()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Panel Content -->
        <div class="flex-1 overflow-y-auto p-6" id="leadDetailsContent">
            <!-- Content will be dynamically populated -->
        </div>
    </div>
</div>

<!-- Overlay -->
<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40" onclick="closeLeadDetails()"></div>

@endsection

@push('scripts')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Custom Select2 Styling -->
<style>
    /* Custom Select2 styling to match your design */
    .select2-container--default .select2-selection--single {
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        height: 38px !important;
        line-height: 36px !important;
        background: rgba(255,255,255,0.8) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 8px !important;
        padding-right: 20px !important;
        color: #374151 !important;
        font-size: 0.875rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 6px !important;
        top: 1px !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        outline: none !important;
        background: white !important;
    }

    .select2-dropdown {
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        margin-top: 4px !important;
    }

    .select2-container--default .select2-results__option {
        padding: 8px 12px !important;
        font-size: 0.875rem !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-results__option--highlighted[aria-selected]:hover {
        background-color: #3b82f6 !important;
        color: white !important;
    }

    .select2-container--default .select2-results__option[aria-selected="true"] {
        background-color: #dbeafe !important;
        color: #1e40af !important;
    }

    .select2-container--default .select2-search--dropdown {
        padding: 8px !important;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        padding: 6px 12px !important;
        font-size: 0.875rem !important;
        outline: none !important;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }

    .select2-container {
        width: 100% !important;
        font-family: inherit !important;
    }

    .select2-selection__placeholder {
        color: #9ca3af !important;
    }

    /* Disabled state */
    .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: #f9fafb !important;
        cursor: not-allowed !important;
    }

    /* Clear button styling */
    .select2-container--default .select2-selection__clear {
        color: #6b7280 !important;
        font-size: 1.2em !important;
        margin-right: 10px !important;
    }

    .select2-container--default .select2-selection__clear:hover {
        color: #ef4444 !important;
    }

    /* Compact select styling for leads page */
    .select2-container.compact-select {
        min-width: 120px;
        max-width: 140px;
    }
</style>

<!-- Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.lead-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateBulkActions();
});

// Individual checkbox change
document.querySelectorAll('.lead-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkActions);
});

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.lead-checkbox');
    const checkedBoxes = document.querySelectorAll('.lead-checkbox:checked');
    const selectAll = document.getElementById('selectAll');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = `(${checkedBoxes.length} selected)`;
    
    if (checkedBoxes.length > 0) {
        bulkActions.classList.remove('hidden');
    } else {
        bulkActions.classList.add('hidden');
    }
    
    selectAll.checked = checkedBoxes.length === checkboxes.length && checkboxes.length > 0;
    selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
}

// Bulk update status
function bulkUpdateStatus() {
    const checkedBoxes = document.querySelectorAll('.lead-checkbox:checked');
    const status = document.getElementById('bulkStatus').value;
    
    if (!status) {
        alert('Please select a status');
        return;
    }
    
    if (checkedBoxes.length === 0) {
        alert('Please select leads to update');
        return;
    }
    
    const leadIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    fetch('{{ route("user.leads.bulk") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            action: 'update_status',
            lead_ids: leadIds,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error updating leads');
        }
    })
    .catch(error => {
        alert('Error updating leads');
    });
}

// Bulk delete
function bulkDelete() {
    const checkedBoxes = document.querySelectorAll('.lead-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        alert('Please select leads to delete');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ${checkedBoxes.length} selected leads? This action cannot be undone.`)) {
        return;
    }
    
    const leadIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    fetch('{{ route("user.leads.bulk") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            action: 'delete',
            lead_ids: leadIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error deleting leads');
        }
    })
    .catch(error => {
        alert('Error deleting leads');
    });
}

// Open lead details
function openLeadDetails(leadId) {
    fetch(`{{ url('/user/leads') }}/${leadId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showLeadDetails(data.lead);
            } else {
                console.error('API error:', data);
                alert('Error loading lead details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error loading lead details: ' + error.message);
        });
}

function showLeadDetails(lead) {
    const panel = document.getElementById('leadDetailsPanel');
    const overlay = document.getElementById('overlay');
    const content = document.getElementById('leadDetailsContent');
    
    console.log('Lead data:', lead); // Debug log
    
    // Build opening hours display
    let openingHoursHtml = '';
    if (lead.opening_hours && Array.isArray(lead.opening_hours) && lead.opening_hours.length > 0) {
        openingHoursHtml = lead.opening_hours.map(hour => `<div class="text-sm">${hour}</div>`).join('');
    } else {
        openingHoursHtml = '<div class="text-sm text-gray-500">No hours available</div>';
    }
    
    // Build reviews display
    let reviewsHtml = '';
    if (lead.reviews_sample && Array.isArray(lead.reviews_sample) && lead.reviews_sample.length > 0) {
        reviewsHtml = lead.reviews_sample.slice(0, 3).map(review => `
            <div class="border-l-4 border-primary-200 pl-4 py-2">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-800">${review.author_name || 'Anonymous'}</span>
                    <div class="flex items-center space-x-1">
                        <div class="flex text-yellow-400 text-xs">
                            ${'★'.repeat(Math.min(review.rating || 5, 5))}
                        </div>
                        <span class="text-xs text-gray-500">${review.relative_time_description || ''}</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600">${(review.text || 'No review text').substring(0, 200)}${review.text && review.text.length > 200 ? '...' : ''}</p>
            </div>
        `).join('');
    } else {
        reviewsHtml = '<div class="text-sm text-gray-500">No reviews available</div>';
    }
    
    // Build social links
    let socialLinksHtml = '';
    let socialLinksData = lead.social_links;
    if (typeof socialLinksData === 'string') {
        try { socialLinksData = JSON.parse(socialLinksData); } catch(e) { socialLinksData = null; }
    }
    // Normalize: object {platform: url} or array of urls
    let socialUrls = [];
    if (socialLinksData && Array.isArray(socialLinksData)) {
        socialUrls = socialLinksData;
    } else if (socialLinksData && typeof socialLinksData === 'object') {
        socialUrls = Object.values(socialLinksData);
    }
    if (socialUrls.length > 0) {
        socialLinksHtml = socialUrls.map(link => {
            link = link.replace(/\/+$/, '');
            let icon = 'fas fa-link';
            let color = 'text-gray-600';
            if (link.includes('facebook.com')) { icon = 'fab fa-facebook'; color = 'text-blue-600'; }
            else if (link.includes('instagram.com')) { icon = 'fab fa-instagram'; color = 'text-pink-600'; }
            else if (link.includes('linkedin.com')) { icon = 'fab fa-linkedin'; color = 'text-blue-700'; }
            else if (link.includes('youtube.com')) { icon = 'fab fa-youtube'; color = 'text-red-600'; }
            else if (link.includes('tiktok.com')) { icon = 'fab fa-tiktok'; color = 'text-gray-800'; }
            else if (link.includes('twitter.com') || link.includes('x.com')) { icon = 'fab fa-x-twitter'; color = 'text-gray-800'; }
            return `<a href="${link}" target="_blank" class="${color} hover:opacity-80 transition-opacity" title="${link}"><i class="${icon} text-lg"></i></a>`;
        }).join(' ');
    }
    
    // Status color mapping
    const statusColors = {
        'not_contacted': 'red',
        'contacted': 'blue',
        'responded': 'yellow',
        'converted': 'green',
        'closed': 'gray'
    };
    
    const statusLabels = {
        'not_contacted': 'Not Contacted',
        'contacted': 'Contacted', 
        'responded': 'Responded',
        'converted': 'Converted',
        'closed': 'Closed'
    };
    
    const statusColor = statusColors[lead.status] || 'gray';
    const statusLabel = statusLabels[lead.status] || 'Unknown';
    
    // Generate star rating display
    let starsHtml = '';
    if (lead.rating && lead.rating > 0) {
        const fullStars = Math.floor(lead.rating);
        const hasHalfStar = lead.rating % 1 !== 0;
        
        starsHtml = `
            <div class="flex items-center space-x-1">
                <div class="flex text-yellow-400">
                    ${'★'.repeat(fullStars)}${hasHalfStar ? '☆' : ''}${'☆'.repeat(5 - fullStars - (hasHalfStar ? 1 : 0))}
                </div>
                <span class="text-sm font-medium text-gray-700">${parseFloat(lead.rating).toFixed(1)}</span>
            </div>
        `;
    }
    
    content.innerHTML = `
        <div class="space-y-6">
            <!-- Business Info -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-xl font-bold text-gray-800">${lead.name || 'Unknown Business'}</h4>
                    ${starsHtml}
                </div>
                
                <div class="flex items-center space-x-2 mb-4">
                    ${lead.category ? `<span class="px-3 py-1 bg-orange-100 text-orange-800 text-sm font-medium rounded-full">${lead.category}</span>` : ''}
                    <span class="px-3 py-1 bg-${statusColor}-100 text-${statusColor}-800 text-sm font-medium rounded-full">${statusLabel}</span>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h5 class="font-semibold text-gray-800 mb-3">Contact Information</h5>
                <div class="space-y-2">
                    ${lead.address ? `
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-map-marker-alt text-primary-600 mt-0.5"></i>
                            <span class="text-sm text-gray-600">${lead.address}</span>
                        </div>
                    ` : ''}
                    ${lead.phone ? `
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-phone text-primary-600"></i>
                            <a href="tel:${lead.phone}" class="text-sm text-gray-600 hover:text-blue-600">${lead.phone}</a>
                        </div>
                    ` : ''}
                    ${lead.email ? `
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-envelope text-primary-600"></i>
                            <a href="mailto:${lead.email}" class="text-sm text-gray-600 hover:text-blue-600">${lead.email}</a>
                        </div>
                    ` : ''}
                    ${lead.website ? `
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-globe text-primary-600"></i>
                            <a href="${lead.website.startsWith('http') ? lead.website : 'https://' + lead.website}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800">${lead.website}</a>
                        </div>
                    ` : ''}
                    ${socialLinksHtml ? `
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-share-alt text-primary-600"></i>
                            <div class="flex space-x-2">${socialLinksHtml}</div>
                        </div>
                    ` : ''}
                </div>
            </div>

            <!-- Opening Hours -->
            ${openingHoursHtml !== '<div class="text-sm text-gray-500">No hours available</div>' ? `
                <div class="bg-gray-50 rounded-lg p-4">
                    <h5 class="font-semibold text-gray-800 mb-3">Opening Hours</h5>
                    <div class="space-y-1">${openingHoursHtml}</div>
                </div>
            ` : ''}

            <!-- Notes -->
            <div class="bg-blue-50 rounded-lg p-4">
                <h5 class="font-semibold text-gray-800 mb-2">Notes</h5>
                <textarea id="leadNotes" 
                          class="w-full p-2 border border-gray-300 rounded text-sm" 
                          rows="3" 
                          placeholder="Add your notes here...">${lead.notes || ''}</textarea>
                <button onclick="updateNotes(${lead.id})" 
                        class="mt-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                    Save Notes
                </button>
                <p class="text-xs text-gray-500 mt-2">Added: ${lead.added_date}</p>
            </div>

            <!-- Reviews -->
            ${reviewsHtml !== '<div class="text-sm text-gray-500">No reviews available</div>' ? `
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="font-semibold text-gray-800">Recent Reviews</h5>
                        ${lead.total_reviews ? `<span class="text-sm text-gray-500">${lead.total_reviews} total reviews</span>` : ''}
                    </div>
                    <div class="space-y-3">${reviewsHtml}</div>
                </div>
            ` : ''}

            <!-- Action Buttons -->
            <div class="space-y-3 pt-4 border-t border-gray-200">
                <select id="statusSelect" class="w-full p-2 border border-gray-300 rounded">
                    <option value="not_contacted" ${lead.status === 'not_contacted' ? 'selected' : ''}>Not Contacted</option>
                    <option value="contacted" ${lead.status === 'contacted' ? 'selected' : ''}>Contacted</option>
                    <option value="responded" ${lead.status === 'responded' ? 'selected' : ''}>Responded</option>
                    <option value="converted" ${lead.status === 'converted' ? 'selected' : ''}>Converted</option>
                    <option value="closed" ${lead.status === 'closed' ? 'selected' : ''}>Closed</option>
                </select>
                
                <button onclick="updateStatus(${lead.id})" 
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-medium">
                    <i class="fas fa-save mr-2"></i>Update Status
                </button>
                
                ${lead.google_profile_url ? `
                    <a href="${lead.google_profile_url}" target="_blank" 
                       class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium text-center block">
                        <i class="fab fa-google mr-2"></i>View Google Profile
                    </a>
                ` : ''}
                
                <button onclick="deleteLead(${lead.id})" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium">
                    <i class="fas fa-trash mr-2"></i>Delete Lead
                </button>
            </div>
        </div>
    `;

    // Show panel
    overlay.classList.remove('hidden');
    panel.classList.remove('translate-x-full');
}

function closeLeadDetails() {
    const panel = document.getElementById('leadDetailsPanel');
    const overlay = document.getElementById('overlay');

    panel.classList.add('translate-x-full');
    overlay.classList.add('hidden');
}

// Update lead status
function updateStatus(leadId) {
    const status = document.getElementById('statusSelect').value;
    
    fetch(`{{ url('/user/leads') }}/${leadId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error updating status');
        }
    })
    .catch(error => {
        alert('Error updating status');
    });
}

// Update notes
function updateNotes(leadId) {
    const notes = document.getElementById('leadNotes').value;
    
    fetch(`{{ url('/user/leads') }}/${leadId}/notes`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ notes: notes })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Notes updated successfully');
        } else {
            alert(data.message || 'Error updating notes');
        }
    })
    .catch(error => {
        alert('Error updating notes');
    });
}

// Delete lead
function deleteLead(leadId) {
    if (!confirm('Are you sure you want to delete this lead? This action cannot be undone.')) {
        return;
    }
    
    fetch(`{{ url('/user/leads') }}/${leadId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeLeadDetails();
            location.reload();
        } else {
            alert(data.message || 'Error deleting lead');
        }
    })
    .catch(error => {
        alert('Error deleting lead');
    });
}

// Close panel with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLeadDetails();
    }
});



// Use jQuery ready for Select2 compatibility
$(document).ready(function() {
    const countrySelect = $('#country_select');
    const stateSelect = $('#state_select');
    const citySelect = $('#city_select');

    // Get the current selected values from the server
    const selectedCountryId = "{{ $countryId ?? '' }}";
    const selectedStateId = "{{ $stateId ?? '' }}";
    const selectedCityId = "{{ $cityId ?? '' }}";
    const baseUrl = "{{ url('/') }}";

    // Flag to prevent change handlers firing during initialization
    let isInitializing = false;

    // Initialize Select2 on all three dropdowns
    countrySelect.select2({
        placeholder: 'Country',
        allowClear: true,
        width: '100%'
    });

    stateSelect.select2({
        placeholder: 'State',
        allowClear: true,
        width: '100%'
    });

    citySelect.select2({
        placeholder: 'City',
        allowClear: true,
        width: '100%'
    });

    // Set initial values
    if (selectedCountryId) {
        isInitializing = true;
        countrySelect.val(selectedCountryId).trigger('change.select2');
        loadStates(selectedCountryId, selectedStateId);
    }

    // Event listeners using Select2 events
    countrySelect.on('change', function() {
        if (isInitializing) return;
        resetSelect(stateSelect, 'State');
        resetSelect(citySelect, 'City');
        if (this.value) {
            loadStates(this.value);
        }
    });

    stateSelect.on('change', function() {
        if (isInitializing) return;
        resetSelect(citySelect, 'City');
        if (this.value) {
            loadCities(this.value);
        }
    });

    function resetSelect(select, defaultText) {
        // Destroy Select2 before resetting
        if (select.hasClass("select2-hidden-accessible")) {
            select.select2('destroy');
        }

        select[0].innerHTML = `<option value="">${defaultText}</option>`;
        select[0].disabled = true;

        // Reinitialize Select2
        select.select2({
            placeholder: defaultText,
            allowClear: true,
            width: '100%'
        });
    }

    function loadStates(countryId, selectedStateId = null) {
        console.log('Loading states for country:', countryId);

        fetch(`${baseUrl}/user/api/states/${countryId}`)
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(states => {
                console.log('States loaded:', states.length);

                resetSelect(stateSelect, 'State');
                states.forEach(state => {
                    const option = new Option(state.name, state.id);
                    if (selectedStateId && state.id == selectedStateId) {
                        option.selected = true;
                        console.log('State selected:', state.name);
                    }
                    stateSelect[0].add(option);
                });
                stateSelect[0].disabled = false;

                // Refresh Select2 after adding options
                stateSelect.trigger('change.select2');

                // Load cities if state is selected
                if (selectedStateId) {
                    loadCities(selectedStateId, selectedCityId);
                } else {
                    isInitializing = false;
                }
            })
            .catch(error => {
                console.error('Error loading states:', error);
                stateSelect[0].innerHTML = '<option value="">Error loading states</option>';
                isInitializing = false;
            });
    }

    function loadCities(stateId, selectedCityId = null) {
        console.log('Loading cities for state:', stateId);

        fetch(`${baseUrl}/user/api/cities/${stateId}`)
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(cities => {
                console.log('Cities loaded:', cities.length);

                resetSelect(citySelect, 'City');
                cities.forEach(city => {
                    const option = new Option(city.name, city.id);
                    if (selectedCityId && city.id == selectedCityId) {
                        option.selected = true;
                        console.log('City selected:', city.name);
                    }
                    citySelect[0].add(option);
                });
                citySelect[0].disabled = false;

                // Refresh Select2 after adding options
                citySelect.trigger('change.select2');

                // Initialization complete — user-triggered changes can now fire
                isInitializing = false;
            })
            .catch(error => {
                console.error('Error loading cities:', error);
                citySelect[0].innerHTML = '<option value="">Error loading cities</option>';
                isInitializing = false;
            });
    }
});

// Toggle Email/Phone/Website filter buttons
function toggleFilter(btn, fieldName) {
    const isActive = btn.dataset.active === '1';
    const newActive = !isActive;

    btn.dataset.active = newActive ? '1' : '0';
    btn.classList.toggle('bg-green-500', newActive);
    btn.classList.toggle('bg-gray-300', !newActive);

    const knob = btn.querySelector('span');
    knob.classList.toggle('translate-x-4', newActive);
    knob.classList.toggle('translate-x-0', !newActive);

    // Update the hidden input value
    const hiddenInput = btn.closest('label').querySelector('input[type="hidden"]');
    hiddenInput.value = newActive ? '1' : '0';

    // Auto-submit form
    btn.closest('form') && btn.closest('form').submit();
}

// Per page change
function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Mobile filters toggle
function toggleFilters() {
    const filters = document.getElementById('filtersContainer');
    filters.classList.toggle('open');
}

// Export dropdown toggle
function toggleExportDropdown() {
    const dropdown = document.getElementById('exportDropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('exportDropdown');
    const button = event.target.closest('button[onclick="toggleExportDropdown()"]');

    if (dropdown && !dropdown.contains(event.target) && !button) {
        dropdown.classList.add('hidden');
    }
});

// Toggle leads visibility (hide/show contact details)
let isDetailsHidden = false;

function toggleLeadsVisibility() {
    const contactDetails = document.querySelectorAll('.contact-detail');
    const copyButtons = document.querySelectorAll('.copy-btn');
    const copyAllButtons = document.querySelectorAll('.copy-all-btn');
    const visibilityIcon = document.getElementById('visibilityIcon');
    const visibilityText = document.getElementById('visibilityText');

    isDetailsHidden = !isDetailsHidden;

    contactDetails.forEach(function(element) {
        const original = element.getAttribute('data-original');
        const type = element.getAttribute('data-type');

        if (isDetailsHidden) {
            // Mask the second half
            let masked = maskText(original, type);
            element.textContent = masked;

            // If it's a link, update href too
            if (element.tagName === 'A') {
                element.href = 'javascript:void(0)';
                element.classList.add('pointer-events-none');
            }
        } else {
            // Show original
            element.textContent = original;

            // If it's a link, restore href
            if (element.tagName === 'A') {
                const fullWebsite = element.getAttribute('data-original');
                element.href = fullWebsite.startsWith('http') ? fullWebsite : 'https://' + fullWebsite;
                element.classList.remove('pointer-events-none');
            }
        }
    });

    // Toggle individual copy buttons visibility
    copyButtons.forEach(function(btn) {
        if (isDetailsHidden) {
            btn.style.display = 'none';
        } else {
            btn.style.display = 'inline-block';
        }
    });

    // Toggle "Copy All" buttons visibility
    copyAllButtons.forEach(function(btn) {
        if (isDetailsHidden) {
            btn.style.display = 'none';
        } else {
            btn.style.display = 'block';
        }
    });

    // Update button text and icon
    if (isDetailsHidden) {
        visibilityIcon.className = 'fas fa-eye';
        visibilityText.textContent = 'Show Details';
    } else {
        visibilityIcon.className = 'fas fa-eye-slash';
        visibilityText.textContent = 'Hide Details';
    }
}

function maskText(text, type) {
    if (!text) return '';

    const length = text.length;
    const halfLength = Math.ceil(length / 2);

    if (type === 'email') {
        // For email: show part before @ and mask rest
        const atIndex = text.indexOf('@');
        if (atIndex > 0) {
            const beforeAt = text.substring(0, Math.min(3, atIndex));
            return beforeAt + '***@***';
        }
    } else if (type === 'name') {
        // For name: show first word, mask rest
        const words = text.split(' ');
        if (words.length > 1) {
            return words[0] + ' ***';
        }
        // If single word, show first half
        const firstHalf = text.substring(0, Math.ceil(length / 2));
        return firstHalf + '***';
    } else if (type === 'location') {
        // For location: show first part, mask rest
        const parts = text.split(',');
        if (parts.length > 1) {
            return parts[0] + ', ***';
        }
        // If no comma, show first half
        const firstHalf = text.substring(0, Math.ceil(length / 2));
        return firstHalf + '***';
    }

    // For phone and website: show first half, mask second half
    const firstHalf = text.substring(0, halfLength);
    const stars = '*'.repeat(Math.min(length - halfLength, 10)); // Limit stars to 10 for readability
    return firstHalf + stars;
}

// Copy to clipboard function
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(function() {
        // Store original HTML
        const originalHTML = button.innerHTML;

        // Change icon to check
        button.innerHTML = '<i class="fas fa-check text-xs"></i>';
        button.classList.remove('text-blue-500', 'hover:text-blue-700');
        button.classList.add('text-green-500');

        // Reset after 2 seconds
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.classList.remove('text-green-500');
            button.classList.add('text-blue-500', 'hover:text-blue-700');
        }, 2000);
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
        alert('Failed to copy to clipboard');
    });
}

// Copy all lead data function
function copyAllLeadData(leadData) {
    // Format the lead data as a readable string
    let text = '';

    if (leadData.name) text += `Business Name: ${leadData.name}\n`;
    if (leadData.category) text += `Category: ${leadData.category}\n`;
    if (leadData.phone) text += `Phone: ${leadData.phone}\n`;
    if (leadData.email) text += `Email: ${leadData.email}\n`;
    if (leadData.website) text += `Website: ${leadData.website}\n`;
    if (leadData.location) text += `Location: ${leadData.location}\n`;
    if (leadData.address) text += `Address: ${leadData.address}\n`;
    if (leadData.rating) text += `Rating: ${leadData.rating} (${leadData.total_reviews || 0} reviews)\n`;
    if (leadData.status) text += `Status: ${leadData.status}\n`;

    navigator.clipboard.writeText(text).then(function() {
        // Show success message
        alert('All lead data copied to clipboard!');
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
        alert('Failed to copy to clipboard');
    });
}
</script>
@endpush