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
        $_sub = Auth::user()->activeSubscription();
        $hasSeoAccess = $_sub
            && !$_sub->is_trial
            && !str_contains(strtolower($_sub->package->slug ?? ''), 'trial')
            && !str_contains(strtolower($_sub->package->name ?? ''), 'trial')
            && (($_sub->package->price ?? 0) > 0);
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
    <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-2 mb-6">
        @php
        $statCards = [
            ['href' => route('user.leads', array_merge(request()->except('status'), ['status' => ''])),          'active' => !request('status') && !request('has_follow_up'), 'color' => 'primary', 'icon' => 'fa-bookmark',       'label' => 'Total',      'value' => $stats['total']],
            ['href' => route('user.leads', array_merge(request()->except('status'), ['status' => 'contacted'])), 'active' => request('status') === 'contacted',  'color' => 'green',   'icon' => 'fa-phone',         'label' => 'Contacted',  'value' => $stats['contacted']],
            ['href' => route('user.leads', array_merge(request()->except('status'), ['status' => 'not_contacted'])), 'active' => request('status') === 'not_contacted', 'color' => 'orange', 'icon' => 'fa-clock',      'label' => 'Pending',    'value' => $stats['pending']],
            ['href' => route('user.leads', array_merge(request()->except('status'), ['status' => 'converted'])), 'active' => request('status') === 'converted',  'color' => 'emerald', 'icon' => 'fa-check-circle',  'label' => 'Converted',  'value' => $stats['converted']],
            ['href' => route('user.leads', array_merge(request()->except('status'), ['status' => 'responded'])), 'active' => request('status') === 'responded',  'color' => 'blue',    'icon' => 'fa-reply',         'label' => 'Responded',  'value' => $stats['responded']],
            ['href' => route('user.leads', array_merge(request()->except('status'), ['status' => 'closed'])),    'active' => request('status') === 'closed',     'color' => 'red',     'icon' => 'fa-times-circle',  'label' => 'Closed',     'value' => $stats['closed']],
            ['href' => route('user.leads', array_merge(request()->except('status'), ['status' => 'follow_up'])), 'active' => request('status') === 'follow_up',  'color' => 'purple',  'icon' => 'fa-calendar-check','label' => 'Follow Up',  'value' => $stats['follow_up']],
            ['href' => route('user.leads', array_merge(request()->except(['status','has_follow_up']), ['has_follow_up' => '1'])), 'active' => request('has_follow_up') === '1', 'color' => 'indigo', 'icon' => 'fa-calendar-alt', 'label' => 'Scheduled', 'value' => $stats['scheduled']],
        ];
        $ringMap = ['primary'=>'ring-primary-400','green'=>'ring-green-400','orange'=>'ring-orange-400','emerald'=>'ring-emerald-400','blue'=>'ring-blue-400','red'=>'ring-red-400','purple'=>'ring-purple-400','indigo'=>'ring-indigo-400'];
        $bgMap   = ['primary'=>'bg-primary-100','green'=>'bg-green-100','orange'=>'bg-orange-100','emerald'=>'bg-emerald-100','blue'=>'bg-blue-100','red'=>'bg-red-100','purple'=>'bg-purple-100','indigo'=>'bg-indigo-100'];
        $txtMap  = ['primary'=>'text-primary-600','green'=>'text-green-600','orange'=>'text-orange-600','emerald'=>'text-emerald-600','blue'=>'text-blue-600','red'=>'text-red-600','purple'=>'text-purple-600','indigo'=>'text-indigo-600'];
        $brdMap  = ['primary'=>'hover:border-primary-400','green'=>'hover:border-green-400','orange'=>'hover:border-orange-400','emerald'=>'hover:border-emerald-400','blue'=>'hover:border-blue-400','red'=>'hover:border-red-400','purple'=>'hover:border-purple-400','indigo'=>'hover:border-indigo-400'];
        @endphp

        @foreach($statCards as $card)
        @php $c = $card['color']; @endphp
        <a href="{{ $card['href'] }}"
           class="bg-white rounded-xl shadow-sm border border-gray-100 {{ $brdMap[$c] }} hover:shadow-md transition-all cursor-pointer {{ $card['active'] ? 'ring-2 '.$ringMap[$c] : '' }} p-3 flex flex-col items-center text-center gap-1.5">
            <div class="p-2 {{ $bgMap[$c] }} rounded-lg">
                <i class="fas {{ $card['icon'] }} {{ $txtMap[$c] }} text-base"></i>
            </div>
            <p class="text-[11px] font-medium text-gray-500 leading-tight">{{ $card['label'] }}</p>
            <p class="text-xl font-bold text-gray-900 leading-none">{{ $card['value'] }}</p>
        </a>
        @endforeach
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
        <option value="not_contacted"  {{ $status == 'not_contacted'  ? 'selected' : '' }}>Pending</option>
        <option value="contacted"      {{ $status == 'contacted'      ? 'selected' : '' }}>Contacted</option>
        <option value="responded"      {{ $status == 'responded'      ? 'selected' : '' }}>Responded</option>
        <option value="converted"      {{ $status == 'converted'      ? 'selected' : '' }}>Converted</option>
        <option value="closed"         {{ $status == 'closed'         ? 'selected' : '' }}>Closed</option>
        <option value="follow_up"      {{ $status == 'follow_up'      ? 'selected' : '' }}>Follow Up</option>
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

<!-- Preserve lead_category across search form submissions -->
<input type="hidden" name="lead_category" value="{{ $leadCategory ?? '' }}">

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

<!-- Lead Category Filter Pills -->
@php
    $baseParams = request()->except(['lead_category', 'page']);
    $catPills = [
        ''            => ['label' => 'All Leads',    'icon' => '',   'count' => $stats['total'],                    'active_cls' => 'bg-gray-800 text-white border-gray-800',     'inactive_cls' => 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'],
        'seo_weak'    => ['label' => 'SEO Weak',     'icon' => '📉', 'count' => $categoryStats['seo_weak'],         'active_cls' => 'bg-orange-500 text-white border-orange-500',  'inactive_cls' => 'bg-white text-orange-600 border-orange-300 hover:border-orange-400'],
        'low_rating'  => ['label' => 'Low Rating',   'icon' => '⭐', 'count' => $categoryStats['low_rating'],        'active_cls' => 'bg-pink-500 text-white border-pink-500',      'inactive_cls' => 'bg-white text-pink-600 border-pink-300 hover:border-pink-400'],
        'hot'         => ['label' => 'Hot',          'icon' => '🔥', 'count' => $categoryStats['hot'],              'active_cls' => 'bg-red-500 text-white border-red-500',        'inactive_cls' => 'bg-white text-red-600 border-red-300 hover:border-red-400'],
        'good'        => ['label' => 'Good',         'icon' => '👍', 'count' => $categoryStats['good'],             'active_cls' => 'bg-yellow-500 text-white border-yellow-500',  'inactive_cls' => 'bg-white text-yellow-600 border-yellow-300 hover:border-yellow-400'],
        'competitive' => ['label' => 'Competitive',  'icon' => '🧠', 'count' => $categoryStats['competitive'],      'active_cls' => 'bg-blue-500 text-white border-blue-500',      'inactive_cls' => 'bg-white text-blue-600 border-blue-300 hover:border-blue-400'],
        'inactive'    => ['label' => 'Inactive',     'icon' => '❌', 'count' => $categoryStats['inactive'],         'active_cls' => 'bg-gray-400 text-white border-gray-400',      'inactive_cls' => 'bg-white text-gray-500 border-gray-300 hover:border-gray-400'],
    ];
@endphp
<div class="flex flex-wrap items-center gap-2 my-3">
    <span class="text-xs text-gray-500 font-medium uppercase tracking-wide mr-1">Filter by type:</span>
    @foreach($catPills as $catKey => $pill)
        @php
            $isActive = ($leadCategory ?? '') === $catKey;
            $url = route('user.leads', $catKey === '' ? $baseParams : array_merge($baseParams, ['lead_category' => $catKey]));
            $isSeoWeakPill = $catKey === 'seo_weak';
        @endphp
        @if($isSeoWeakPill && !$hasSeoAccess)
            <button type="button" onclick="showSeoUpgradeModal()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold bg-white text-gray-400 border-gray-200 cursor-not-allowed">
                <span>📉</span>
                {{ $pill['label'] }}
                <i class="fas fa-lock text-[10px] ml-0.5 text-purple-400"></i>
            </button>
        @else
            <a href="{{ $url }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold transition-all {{ $isActive ? $pill['active_cls'] : $pill['inactive_cls'] }}">
                @if($pill['icon']) <span>{{ $pill['icon'] }}</span> @endif
                {{ $pill['label'] }}
                <span class="ml-1 {{ $isActive ? 'opacity-80' : 'opacity-60' }} font-normal">({{ $pill['count'] }})</span>
            </a>
        @endif
    @endforeach
</div>

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
                        <!-- SEO Analytics Button -->
                        @if($hasSeoAccess)
                            <button type="button" onclick="startSeoAnalytics()" id="seoAnalyticsBtn"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
                                <i class="fas fa-tachometer-alt mr-1"></i>
                                <span>SEO Analytics</span>
                            </button>
                        @else
                            <button type="button" onclick="showSeoUpgradeModal()"
                                    class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
                                <i class="fas fa-lock mr-1"></i>
                                <span>SEO Analytics</span>
                            </button>
                        @endif

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
                            <button type="button" onclick="showExportLimitModal()"
                                    class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                                <i class="fas fa-lock text-xs ml-1"></i>
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
                <table class="w-full min-w-[760px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-3 w-10">
                                <span class="text-xs font-semibold text-gray-700">Sel</span>
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Business</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 w-28 hidden lg:table-cell">Lead Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700">Contact</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 w-36 hidden xl:table-cell">Location</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 w-28 hidden lg:table-cell">Rating</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 w-36">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-700 w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($leads as $lead)
                            <tr class="hover:bg-gray-50 cursor-pointer lead-row" data-lead-id="{{ $lead->id }}">
                                <td class="px-4 py-3 w-10">
                                    <input type="checkbox"
                                           class="w-4 h-4 text-primary-600 rounded border-gray-300 lead-checkbox"
                                           value="{{ $lead->id }}"
                                           onclick="event.stopPropagation()">
                                </td>

                                <td class="px-4 py-3">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 contact-detail" data-type="name" data-original="{{ $lead->name }}">{{ $lead->name }}</div>
                                    </div>
                                </td>

                                {{-- Lead Type Badge --}}
                                @php
                                    $cat = $lead->lead_category;
                                    $catCfg = [
                                        'hot'         => ['bg' => 'bg-red-50',    'border' => 'border-red-200',    'text' => 'text-red-600',    'icon' => '🔥', 'label' => 'Hot Lead',    'sub' => 'Contact now',       'tip' => 'No website · low reviews · recently active'],
                                        'good'        => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-700', 'icon' => '👍', 'label' => 'Good Lead',   'sub' => 'Worth trying',      'tip' => 'Moderate reviews · recently active'],
                                        'competitive' => ['bg' => 'bg-blue-50',   'border' => 'border-blue-200',   'text' => 'text-blue-600',   'icon' => '🧠', 'label' => 'Competitive', 'sub' => 'Strong pitch',     'tip' => 'High reviews or older activity'],
                                        'inactive'    => ['bg' => 'bg-gray-50',   'border' => 'border-gray-200',   'text' => 'text-gray-500',   'icon' => '❌', 'label' => 'Inactive',   'sub' => 'Skip this',         'tip' => 'No activity in 365+ days'],
                                    ];
                                    $cfg = $catCfg[$cat] ?? $catCfg['inactive'];
                                @endphp
                                <td class="px-4 py-3 w-28 hidden lg:table-cell">
                                    <div class="relative group inline-block">
                                        <div class="{{ $cfg['bg'] }} {{ $cfg['border'] }} border rounded-lg px-2 py-1.5 flex flex-col items-start cursor-default min-w-[90px]">
                                            <span class="{{ $cfg['text'] }} text-xs font-semibold leading-tight">{{ $cfg['icon'] }} {{ $cfg['label'] }}</span>
                                            <span class="text-gray-400 text-[10px] leading-tight">{{ $cfg['sub'] }}</span>
                                        </div>
                                        {{-- Tooltip --}}
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1.5 bg-gray-800 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-20 shadow-lg">
                                            {{ $cfg['tip'] }}
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                                        </div>
                                    </div>
                                </td>

                               <td class="px-4 py-3 max-w-xs">
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
                                        {{-- SEO Score Badge --}}
                                        <div id="seo-score-{{ $lead->id }}" class="mt-1">
                                            @if(!$hasSeoAccess)
                                                <button onclick="event.stopPropagation(); showSeoUpgradeModal()"
                                                        class="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-purple-50 text-purple-500 border border-purple-200 hover:bg-purple-100 transition-colors">
                                                    <i class="fas fa-lock text-[9px]"></i> SEO Score
                                                </button>
                                            @elseif($lead->seo_score === null)
                                                {{-- Not checked yet; will be filled by SEO Analytics button --}}
                                            @else
                                                @php
                                                    $seoScore = $lead->seo_score;
                                                    if ($seoScore <= 50) {
                                                        $seoBg = 'bg-red-100'; $seoText = 'text-red-700'; $seoLabel = 'Weak';
                                                    } elseif ($seoScore <= 70) {
                                                        $seoBg = 'bg-yellow-100'; $seoText = 'text-yellow-700'; $seoLabel = 'OK';
                                                    } else {
                                                        $seoBg = 'bg-green-100'; $seoText = 'text-green-700'; $seoLabel = 'Strong';
                                                    }
                                                @endphp
                                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $seoBg }} {{ $seoText }}">
                                                    <i class="fas fa-tachometer-alt"></i> SEO {{ $seoScore }} · {{ $seoLabel }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="mt-1">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-red-100 text-red-700">
                                                <i class="fas fa-ban"></i> No Website · SEO Weak
                                            </span>
                                        </div>
                                    @endif
                                    @if(!$lead->phone && !$lead->email && $lead->website)
                                        <span class="text-xs text-gray-400 italic">No contact info</span>
                                    @endif
                                </div>
                            </td>


                                <td class="px-4 py-3 w-36 hidden xl:table-cell">
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

                              <td class="px-4 py-3 w-28 hidden lg:table-cell">
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
                                

                               


                                <td class="px-4 py-3 w-36">
                                    @php
                                        $statusColors = [
                                            'not_contacted' => 'bg-red-100 text-red-700',
                                            'contacted'     => 'bg-blue-100 text-blue-700',
                                            'responded'     => 'bg-yellow-100 text-yellow-700',
                                            'converted'     => 'bg-green-100 text-green-700',
                                            'closed'        => 'bg-gray-100 text-gray-700',
                                            'follow_up'     => 'bg-purple-100 text-purple-700',
                                        ];
                                        $statusLabels = [
                                            'not_contacted' => 'Pending',
                                            'contacted'     => 'Contacted',
                                            'responded'     => 'Responded',
                                            'converted'     => 'Converted',
                                            'closed'        => 'Closed',
                                            'follow_up'     => 'Follow Up',
                                        ];

                                        // Build channel link from follow_up_source
                                        $fuSource   = $lead->follow_up_source;
                                        $fuDate     = $lead->follow_up_date;
                                        $chUrl      = '';
                                        $chIcon     = '';
                                        $chLabel    = '';
                                        if ($fuSource && $fuDate) {
                                            $socials = is_array($lead->social_links)
                                                ? $lead->social_links
                                                : (json_decode($lead->social_links ?? '[]', true) ?? []);
                                            if ($fuSource === 'email') {
                                                $chUrl   = $lead->email ? 'mailto:' . $lead->email : '';
                                                $chIcon  = 'fas fa-envelope';
                                                $chLabel = 'Email';
                                            } elseif ($fuSource === 'whatsapp') {
                                                $ph = preg_replace('/\D/', '', $lead->phone ?? '');
                                                $chUrl   = $ph ? 'https://wa.me/' . $ph : '';
                                                $chIcon  = 'fab fa-whatsapp';
                                                $chLabel = 'WhatsApp';
                                            } else {
                                                $pMap = ['facebook'=>['facebook.com'],'linkedin'=>['linkedin.com'],'x'=>['twitter.com','x.com'],'instagram'=>['instagram.com']];
                                                $iMap = ['facebook'=>'fab fa-facebook','linkedin'=>'fab fa-linkedin','x'=>'fab fa-x-twitter','instagram'=>'fab fa-instagram'];
                                                $lMap = ['facebook'=>'Facebook','linkedin'=>'LinkedIn','x'=>'X','instagram'=>'Instagram'];
                                                foreach ($socials as $sl) {
                                                    foreach ($pMap[$fuSource] ?? [] as $p) {
                                                        if (str_contains($sl, $p)) { $chUrl = $sl; break 2; }
                                                    }
                                                }
                                                $chIcon  = $iMap[$fuSource] ?? 'fas fa-link';
                                                $chLabel = $lMap[$fuSource] ?? $fuSource;
                                            }
                                        }
                                    @endphp
                                    <div class="space-y-1">
                                        <span class="inline-block px-2 py-1 {{ $statusColors[$lead->contact_status] ?? 'bg-gray-100 text-gray-700' }} text-xs font-medium rounded-full">
                                            {{ $statusLabels[$lead->contact_status] ?? 'Unknown' }}
                                        </span>

                                        @if($fuDate)
                                            @if($lead->contact_status === 'follow_up')
                                                {{-- Done --}}
                                                <div class="flex items-center gap-1 text-[10px] font-medium text-green-600">
                                                    <i class="fas fa-check-circle text-[9px]"></i>
                                                    Follow up was done on {{ \Carbon\Carbon::parse($fuDate)->format('M d, Y') }}
                                                </div>
                                            @else
                                                {{-- Pending follow-up --}}
                                                <div class="flex items-center gap-1 flex-wrap">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-purple-700 bg-purple-50 border border-purple-200 px-1.5 py-0.5 rounded-full">
                                                        <i class="fas fa-calendar-alt text-[9px]"></i>
                                                        {{ \Carbon\Carbon::parse($fuDate)->format('M d') }}
                                                    </span>
                                                    @if($chUrl)
                                                        <a href="{{ $chUrl }}"
                                                           target="{{ $fuSource === 'email' ? '_self' : '_blank' }}"
                                                           onclick="event.stopPropagation()"
                                                           title="Open {{ $chLabel }}"
                                                           class="inline-flex items-center gap-0.5 text-[10px] font-semibold text-white bg-purple-500 hover:bg-purple-700 px-1.5 py-0.5 rounded-full transition-colors">
                                                            <i class="{{ $chIcon }} text-[9px]"></i> {{ $chLabel }}
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="text-[9px] text-gray-400 leading-tight">Do follow up on this date</div>
                                            @endif
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 w-20">
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

<!-- SEO Progress Bar (fixed bottom, non-dismissible) -->
<div id="seoProgressBar" class="hidden fixed bottom-0 left-0 right-0 z-[60] bg-white border-t-4 border-blue-500 shadow-2xl px-6 py-4">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-tachometer-alt text-blue-600 text-sm animate-pulse"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">SEO Analysis Running...</p>
                    <p class="text-xs text-gray-500" id="seoProgressText">Preparing checks...</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-2xl font-bold text-blue-600" id="seoProgressPct">0%</span>
                <p class="text-[10px] text-gray-400 mt-0.5">Do not close this page</p>
            </div>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div id="seoProgressFill"
                 class="h-3 rounded-full transition-all duration-500 ease-out bg-gradient-to-r from-blue-500 to-indigo-500"
                 style="width: 0%"></div>
        </div>
        <p class="text-[10px] text-center text-gray-400 mt-1.5">
            <i class="fas fa-lock text-[9px] mr-1"></i>Analysis will complete automatically — please keep this page open
        </p>
    </div>
</div>

<!-- SEO Upgrade Modal -->
<div id="seoUpgradeModal" class="hidden fixed inset-0 z-[70] flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeSeoUpgradeModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-8 text-center">
        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-tachometer-alt text-purple-600 text-2xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">SEO Analytics — Paid Feature</h2>
        <p class="text-gray-600 text-sm mb-6">Upgrade to a paid plan to unlock SEO Analytics and see the performance score of every lead's website.</p>
        <div class="flex gap-3 justify-center">
            <a href="{{ route('user.subscription') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                <i class="fas fa-arrow-up mr-1"></i> Upgrade Now
            </a>
            <button onclick="closeSeoUpgradeModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Export Limit Modal -->
<div id="exportLimitModal" class="hidden fixed inset-0 z-[70] flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeExportLimitModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-8 text-center">
        <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-download text-orange-500 text-2xl"></i>
        </div>
        @if($exportLimit === 0)
            <h2 class="text-xl font-bold text-gray-900 mb-2">Export Not Available</h2>
            <p class="text-gray-600 text-sm mb-2">Export leads is not available on the <span class="font-semibold">Free Trial</span> plan.</p>
            <p class="text-gray-500 text-xs mb-6">Upgrade to a paid plan to unlock CSV & Excel export.</p>
        @else
            <h2 class="text-xl font-bold text-gray-900 mb-2">Export Limit Reached</h2>
            <p class="text-gray-600 text-sm mb-2">
                You have used <span class="font-semibold text-orange-600">{{ $todayExportCount }}</span>
                of <span class="font-semibold">{{ $exportLimit }}</span> exports this month.
            </p>
            <p class="text-gray-500 text-xs mb-6">Upgrade your package to get more exports or unlimited access.</p>
        @endif
        <div class="flex gap-3 justify-center">
            <a href="{{ route('user.subscription') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                <i class="fas fa-arrow-up mr-1"></i> Upgrade Now
            </a>
            <button onclick="closeExportLimitModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                Cancel
            </button>
        </div>
    </div>
</div>

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

// Global: currently open lead (used by channel info functions)
let _currentLead = null;

function showLeadDetails(lead) {
    _currentLead = lead;

    const panel = document.getElementById('leadDetailsPanel');
    const overlay = document.getElementById('overlay');
    const content = document.getElementById('leadDetailsContent');
    
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
    // Keep _currentLead.social_links always as a normalized array so getChannelContact works
    _currentLead.social_links = socialUrls;
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
        'not_contacted': 'orange',
        'contacted':     'blue',
        'responded':     'yellow',
        'converted':     'green',
        'closed':        'gray',
        'follow_up':     'purple'
    };

    const statusLabels = {
        'not_contacted': 'Pending',
        'contacted':     'Contacted',
        'responded':     'Responded',
        'converted':     'Converted',
        'closed':        'Closed',
        'follow_up':     'Follow Up'
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

            <!-- Follow Up Section -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 space-y-3">
                <h5 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-calendar-check text-purple-600"></i> Follow Up
                </h5>

                <!-- Response Channel -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Response Channel</label>
                    <select id="followUpSource"
                            onchange="renderChannelInfo(this.value)"
                            class="w-full p-2 border border-gray-300 rounded text-sm bg-white focus:border-purple-400 focus:ring-1 focus:ring-purple-200 outline-none">
                        <option value="">-- Select Channel --</option>
                        <option value="email"     ${lead.follow_up_source === 'email'     ? 'selected' : ''}>📧 Email</option>
                        <option value="facebook"  ${lead.follow_up_source === 'facebook'  ? 'selected' : ''}>📘 Facebook</option>
                        <option value="linkedin"  ${lead.follow_up_source === 'linkedin'  ? 'selected' : ''}>💼 LinkedIn</option>
                        <option value="x"         ${lead.follow_up_source === 'x'         ? 'selected' : ''}>𝕏 X (Twitter)</option>
                        <option value="whatsapp"  ${lead.follow_up_source === 'whatsapp'  ? 'selected' : ''}>💬 WhatsApp</option>
                        <option value="instagram" ${lead.follow_up_source === 'instagram' ? 'selected' : ''}>📸 Instagram</option>
                    </select>
                    <!-- Channel contact info (auto-populated on select) -->
                    <div id="channelInfoBox" class="mt-2"></div>
                </div>

                <!-- Follow-up Date -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Follow Up Date</label>
                    <input type="date" id="followUpDate"
                           value="${lead.follow_up_date || defaultFollowUpDate()}"
                           class="w-full p-2 border border-gray-300 rounded text-sm bg-white focus:border-purple-400 focus:ring-1 focus:ring-purple-200 outline-none">
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Note (Client Response)</label>
                    <textarea id="leadNotes"
                              class="w-full p-2 border border-gray-300 rounded text-sm bg-white focus:border-purple-400 focus:ring-1 focus:ring-purple-200 outline-none"
                              rows="3"
                              placeholder="e.g. Client said call back next week, interested in SEO...">${lead.notes || ''}</textarea>
                </div>

                <button onclick="saveFollowUp(${lead.id})"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                    <i class="fas fa-save"></i> Save Follow Up
                </button>
                <p class="text-xs text-gray-400">Added: ${lead.added_date}</p>
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
                <select id="statusSelect" class="w-full p-2 border border-gray-300 rounded text-sm">
                    <option value="not_contacted"  ${lead.status === 'not_contacted'  ? 'selected' : ''}>Pending</option>
                    <option value="contacted"      ${lead.status === 'contacted'      ? 'selected' : ''}>Contacted</option>
                    <option value="responded"      ${lead.status === 'responded'      ? 'selected' : ''}>Responded</option>
                    <option value="converted"      ${lead.status === 'converted'      ? 'selected' : ''}>Converted</option>
                    <option value="closed"         ${lead.status === 'closed'         ? 'selected' : ''}>Closed</option>
                    <option value="follow_up"      ${lead.status === 'follow_up'      ? 'selected' : ''}>Follow Up</option>
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

    // Auto-render channel info if a source is already saved
    setTimeout(() => {
        if (lead.follow_up_source) renderChannelInfo(lead.follow_up_source);
    }, 0);
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

// Default follow-up date: today + 3 days
function defaultFollowUpDate() {
    const d = new Date();
    d.setDate(d.getDate() + 3);
    return d.toISOString().split('T')[0];
}

// Save follow-up (source + date + notes together)
function saveFollowUp(leadId) {
    const source = document.getElementById('followUpSource').value;
    const date   = document.getElementById('followUpDate').value;
    const notes  = document.getElementById('leadNotes').value;

    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    fetch(`{{ url('/user/leads') }}/${leadId}/follow-up`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ follow_up_source: source, follow_up_date: date, notes: notes })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Follow Up';
        if (data.success) {
            showToast('Follow-up saved!', 'success');
            // Refresh row badge without full reload
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Error saving follow-up', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Follow Up';
        showToast('Error saving follow-up', 'error');
    });
}

// Update notes (kept for backward compat)
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
            showToast('Notes saved!', 'success');
        } else {
            showToast(data.message || 'Error updating notes', 'error');
        }
    })
    .catch(() => showToast('Error updating notes', 'error'));
}

// ─── Channel Info (Follow-Up Panel) ────────────────────────────────────────

// Extract the contact value for a given channel from the current lead
function getChannelContact(channel) {
    const lead = _currentLead;
    if (!lead) return null;

    const social = (Array.isArray(lead.social_links) ? lead.social_links : []);

    const platformKeys = {
        facebook:  ['facebook.com'],
        linkedin:  ['linkedin.com'],
        x:         ['twitter.com', 'x.com'],
        instagram: ['instagram.com'],
    };

    switch (channel) {
        case 'email':
            return { type: 'email', value: lead.email || '', href: lead.email ? `mailto:${lead.email}` : '' };
        case 'whatsapp': {
            const digits = (lead.phone || '').replace(/\D/g, '');
            return { type: 'phone', value: lead.phone || '', href: digits ? `https://wa.me/${digits}` : '' };
        }
        default: {
            const patterns = platformKeys[channel] || [];
            const found = social.find(u => patterns.some(p => u.includes(p))) || '';
            return { type: 'social', value: found, href: found };
        }
    }
}

// Render the channel info box below the source select
function renderChannelInfo(channel) {
    const box = document.getElementById('channelInfoBox');
    if (!box) return;
    if (!channel) { box.innerHTML = ''; return; }

    const info = getChannelContact(channel);
    const icons = { email: '📧', whatsapp: '💬', facebook: '📘', linkedin: '💼', x: '𝕏', instagram: '📸' };
    const icon  = icons[channel] || '🔗';

    if (info && info.value) {
        const linkAttr = info.href ? `href="${info.href}" target="_blank"` : '';
        box.innerHTML = `
            <div class="flex items-center gap-2 bg-white border border-purple-200 rounded-lg px-3 py-2">
                <span class="text-base">${icon}</span>
                <a ${linkAttr} class="flex-1 text-sm text-blue-600 hover:underline truncate">${info.value}</a>
                <button onclick="startEditChannel('${channel}')"
                        class="flex-shrink-0 text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1 rounded transition-colors">
                    <i class="fas fa-edit mr-1"></i>Change
                </button>
            </div>`;
    } else {
        box.innerHTML = `
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                <p class="text-xs text-amber-700 mb-2">${icon} Not found — add it:</p>
                ${buildEditInput(channel, '')}
            </div>`;
    }
}

// Show inline edit input
function startEditChannel(channel) {
    const info = getChannelContact(channel);
    const box  = document.getElementById('channelInfoBox');
    box.innerHTML = `
        <div class="bg-white border border-purple-300 rounded-lg px-3 py-2">
            ${buildEditInput(channel, info ? info.value : '')}
        </div>`;
    document.getElementById('channelEditInput').focus();
}

// Build the edit input + save button HTML
function buildEditInput(channel, value) {
    const placeholders = {
        email:     'email@example.com',
        whatsapp:  '+1234567890',
        facebook:  'https://facebook.com/...',
        linkedin:  'https://linkedin.com/in/...',
        x:         'https://x.com/...',
        instagram: 'https://instagram.com/...',
    };
    const ph   = placeholders[channel] || 'https://...';
    const type = channel === 'email' ? 'email' : 'text';
    return `
        <div class="flex gap-2">
            <input type="${type}" id="channelEditInput" value="${value}"
                   placeholder="${ph}"
                   class="flex-1 p-1.5 border border-gray-300 rounded text-sm focus:border-purple-400 outline-none">
            <button onclick="saveChannelContact('${channel}')"
                    class="flex-shrink-0 bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-sm transition-colors">
                Save
            </button>
            <button onclick="renderChannelInfo('${channel}')"
                    class="flex-shrink-0 bg-gray-100 hover:bg-gray-200 text-gray-500 px-2 py-1 rounded text-sm transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
}

// Save updated contact via API then refresh display
function saveChannelContact(channel) {
    const input = document.getElementById('channelEditInput');
    if (!input || !_currentLead) return;

    const value  = input.value.trim();
    const leadId = _currentLead.id;
    const saveBtn = input.nextElementSibling;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(`{{ url('/user/leads') }}/${leadId}/contact`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ channel, value })
    })
    .then(r => r.json())
    .then(data => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Save';
        if (data.success) {
            // Update local lead cache
            if (channel === 'email') {
                _currentLead.email = value;
            } else if (channel === 'whatsapp') {
                _currentLead.phone = value;
            } else {
                const patterns = { facebook: 'facebook.com', linkedin: 'linkedin.com', x: ['twitter.com','x.com'], instagram: 'instagram.com' };
                const pats = [].concat(patterns[channel] || []);
                let links = Array.isArray(_currentLead.social_links) ? [..._currentLead.social_links] : [];
                const idx = links.findIndex(u => pats.some(p => u.includes(p)));
                if (value) {
                    if (idx >= 0) links[idx] = value; else links.push(value);
                } else if (idx >= 0) {
                    links.splice(idx, 1);
                }
                _currentLead.social_links = links;
            }
            showToast('Saved!', 'success');
            renderChannelInfo(channel);
        } else {
            showToast(data.message || 'Error saving', 'error');
        }
    })
    .catch(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Save';
        showToast('Error saving', 'error');
    });
}

// ─── Simple toast notification ───────────────────────────────────────────────

// Simple toast notification
function showToast(message, type) {
    const existing = document.getElementById('leadToast');
    if (existing) existing.remove();
    const colors = { success: 'bg-green-600', error: 'bg-red-600' };
    const toast = document.createElement('div');
    toast.id = 'leadToast';
    toast.className = `fixed bottom-6 left-1/2 -translate-x-1/2 ${colors[type] || 'bg-gray-700'} text-white px-5 py-2.5 rounded-lg shadow-lg text-sm font-medium z-[9999] transition-all`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
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

// ─── SEO Analytics with Progress Bar ─────────────────────────────────────────
const checkSeoUrl = '{{ url("/user/leads") }}';
const csrfToken   = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const seoBar  = document.getElementById('seoProgressBar');
const seoFill = document.getElementById('seoProgressFill');
const seoPct  = document.getElementById('seoProgressPct');
const seoText = document.getElementById('seoProgressText');

let seoTotal   = 0;
let seoDone    = 0;
let seoRunning = false;

function showSeoProgress() {
    seoBar.classList.remove('hidden');
    // Pad bottom of page so content isn't hidden behind bar
    document.body.style.paddingBottom = '110px';
}

function updateSeoProgress() {
    const pct = seoTotal > 0 ? Math.round((seoDone / seoTotal) * 100) : 100;
    seoFill.style.width  = pct + '%';
    seoPct.textContent   = pct + '%';
    seoText.textContent  = `Checked ${seoDone} of ${seoTotal} websites`;
}

function hideSeoProgress() {
    seoPct.textContent  = '100%';
    seoFill.style.width = '100%';
    seoText.textContent = `All ${seoTotal} websites checked!`;
    seoFill.classList.remove('from-blue-500', 'to-indigo-500');
    seoFill.classList.add('from-green-500', 'to-emerald-500');
    document.querySelector('#seoProgressBar p.text-\\[10px\\]').textContent = 'SEO analysis complete ✓';
    setTimeout(() => {
        seoBar.classList.add('hidden');
        document.body.style.paddingBottom = '';
    }, 3000);
    seoRunning = false;
    window.removeEventListener('beforeunload', seoBeforeUnload);
}

function seoBeforeUnload(e) {
    e.preventDefault();
    e.returnValue = 'SEO analysis is in progress. If you leave, checking will stop and some websites may not be analysed. Are you sure?';
    return e.returnValue;
}

function getSeoScoreHtml(score) {
    if (score === null || score === undefined || score < 0) {
        return '<span class="inline-flex items-center gap-1 text-[10px] text-gray-400"><i class="fas fa-minus-circle"></i> SEO N/A</span>';
    }
    let bg, txt, label;
    if (score <= 50)      { bg = 'bg-red-100';    txt = 'text-red-700';    label = 'Weak'; }
    else if (score <= 70) { bg = 'bg-yellow-100'; txt = 'text-yellow-700'; label = 'OK'; }
    else                  { bg = 'bg-green-100';  txt = 'text-green-700';  label = 'Strong'; }
    return `<span class="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded ${bg} ${txt}"><i class="fas fa-tachometer-alt"></i> SEO ${score} · ${label}</span>`;
}

async function checkOneLead(id) {
    try {
        const res  = await fetch(`${checkSeoUrl}/${id}/check-seo`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const data = await res.json();
        const el   = document.getElementById(`seo-score-${id}`);
        if (el) el.innerHTML = getSeoScoreHtml(data.score ?? -1);
    } catch (e) {
        const el = document.getElementById(`seo-score-${id}`);
        if (el) el.innerHTML = '';
    } finally {
        seoDone++;
        updateSeoProgress();
    }
}

async function runSeoChecks(idsToCheck) {
    if (!idsToCheck || idsToCheck.length === 0) return;

    seoTotal   = idsToCheck.length;
    seoDone    = 0;
    seoRunning = true;
    showSeoProgress();
    updateSeoProgress();
    window.addEventListener('beforeunload', seoBeforeUnload);

    const batchSize = 3;
    for (let i = 0; i < idsToCheck.length; i += batchSize) {
        const batch = idsToCheck.slice(i, i + batchSize);
        await Promise.all(batch.map(id => checkOneLead(id)));
    }

    hideSeoProgress();
}

function startSeoAnalytics() {
    if (seoRunning) return;

    const idsToCheck = Array.from(document.querySelectorAll('[id^="seo-score-"]'))
        .map(el => parseInt(el.id.replace('seo-score-', '')));

    if (idsToCheck.length === 0) {
        alert('No leads with websites found on this page.');
        return;
    }

    // Show spinner on each lead before starting
    idsToCheck.forEach(id => {
        const el = document.getElementById(`seo-score-${id}`);
        if (el) el.innerHTML = '<span class="inline-flex items-center gap-1 text-[10px] text-gray-400"><i class="fas fa-spinner fa-spin"></i> Checking...</span>';
    });

    runSeoChecks(idsToCheck);
}

function showSeoUpgradeModal() {
    document.getElementById('seoUpgradeModal').classList.remove('hidden');
}

function closeSeoUpgradeModal() {
    document.getElementById('seoUpgradeModal').classList.add('hidden');
}

function showExportLimitModal() {
    document.getElementById('exportLimitModal').classList.remove('hidden');
}

function closeExportLimitModal() {
    document.getElementById('exportLimitModal').classList.add('hidden');
}

// Close export modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeExportLimitModal();
});

async function retrySeoCheck(id, btn) {
    const container = document.getElementById(`seo-score-${id}`);
    if (!container) return;

    // Show spinner while retrying
    container.innerHTML = '<span class="seo-pending inline-flex items-center gap-1 text-[10px] text-gray-400"><i class="fas fa-spinner fa-spin"></i> Retrying...</span>';

    try {
        const res  = await fetch(`${checkSeoUrl}/${id}/check-seo`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success && data.score !== null && data.score >= 0) {
            container.innerHTML = getSeoScoreHtml(data.score);
        } else {
            // Still N/A — show retry button again
            container.innerHTML = `<span class="inline-flex items-center gap-1.5 text-[10px] text-gray-400">
                <i class="fas fa-exclamation-circle text-orange-400"></i>
                SEO N/A
                <button onclick="event.stopPropagation(); retrySeoCheck(${id}, this)"
                        class="ml-0.5 inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-orange-100 text-orange-600 hover:bg-orange-200 transition-colors">
                    <i class="fas fa-redo text-[8px]"></i> Retry
                </button>
            </span>`;
        }
    } catch (e) {
        container.innerHTML = `<span class="inline-flex items-center gap-1.5 text-[10px] text-gray-400">
            <i class="fas fa-exclamation-circle text-orange-400"></i>
            SEO N/A
            <button onclick="event.stopPropagation(); retrySeoCheck(${id}, this)"
                    class="ml-0.5 inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-orange-100 text-orange-600 hover:bg-orange-200 transition-colors">
                <i class="fas fa-redo text-[8px]"></i> Retry
            </button>
        </span>`;
    }
}
// ─────────────────────────────────────────────────────────────────────────────

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