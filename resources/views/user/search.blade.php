@extends('layouts.app')

@section('title', 'User Dashboard')

@php $extensionMode = \App\Models\Setting::get('extension_mode', false); @endphp

@section('content')

{{-- Find Leads page look: Poppins + custom fields (this page only). Kept outside the .p-4.lg:p-8
     wrapper, whose innerHTML is replaced when results come back. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* Page tokens (this page only) */
    body.bg-gray-50 { background-color: #f8fafc; }
    .cnm-find, .cnm-find button, .cnm-find input, .cnm-find select, .select2-dropdown,
    #sidebar, #main-content > header { font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; }

    /* Field shell: label above, outlined field with a leading icon */
    .cnm-find { --field-h: 60px; --field-border: #dce3ed; --focus: #f97316; --focus-ring: rgba(249, 115, 22, .16); }
    @media (max-width: 639px) { .cnm-find { --field-h: 56px; } }
    .cnm-label { display: flex; flex-wrap: wrap; align-items: center; column-gap: 6px; row-gap: 4px; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #111827; }
    .cnm-label .req { color: #dc2626; }
    .cnm-field { position: relative; }
    .cnm-field-icon { position: absolute; left: 0; top: 0; z-index: 2; width: 50px; height: var(--field-h); display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 15px; pointer-events: none; transition: color .15s ease; }
    .cnm-field:focus-within .cnm-field-icon { color: var(--focus); }
    .cnm-error { display: flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 12px; color: #dc2626; }

    /* Native inputs / selects */
    .cnm-input { width: 100%; height: var(--field-h); padding: 0 18px; border: 1px solid var(--field-border); border-radius: 14px; background-color: #ffffff; color: #111827; font-size: 15px; font-weight: 500; transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease; }
    .cnm-input::placeholder { color: #94a3b8; font-weight: 400; }
    .cnm-input:hover { border-color: #b6c2d2; }
    .cnm-input:focus { outline: none; border-color: var(--focus); box-shadow: 0 0 0 4px var(--focus-ring); }
    .cnm-input:disabled { background-color: #f1f5f9; border-color: #e2e8f0; color: #94a3b8; cursor: not-allowed; }
    .cnm-field.has-icon .cnm-input { padding-left: 50px; }
    select.cnm-input { -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 46px; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 18px center; background-size: 16px; }

    /* Select2 single selects: the real visible control */
    .cnm-find .select2-container { width: 100% !important; }
    .cnm-find .select2-container--default .select2-selection--single { height: var(--field-h) !important; border: 1px solid var(--field-border) !important; border-radius: 14px !important; background-color: #ffffff !important; outline: none !important; transition: border-color .15s ease, box-shadow .15s ease; }
    .cnm-find .select2-container--default .select2-selection--single:hover { border-color: #b6c2d2 !important; }
    .cnm-find .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: calc(var(--field-h) - 2px) !important; padding-left: 18px !important; padding-right: 46px !important; color: #111827 !important; font-size: 15px !important; font-weight: 500 !important; }
    .cnm-find .cnm-field.has-icon .select2-selection--single .select2-selection__rendered { padding-left: 50px !important; }
    .cnm-find .select2-container--default .select2-selection--single .select2-selection__placeholder { color: #94a3b8 !important; font-weight: 400 !important; }
    .cnm-find .select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(var(--field-h) - 2px) !important; width: 44px !important; right: 4px !important; top: 0 !important;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat center / 16px; transition: transform .15s ease; }
    .cnm-find .select2-container--default .select2-selection--single .select2-selection__arrow b { display: none !important; }
    .cnm-find .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow { transform: rotate(180deg); }
    .cnm-find .select2-container--default.select2-container--focus .select2-selection--single,
    .cnm-find .select2-container--default.select2-container--open .select2-selection--single { border-color: var(--focus) !important; box-shadow: 0 0 0 4px var(--focus-ring) !important; border-radius: 14px !important; }
    .cnm-find .select2-container--default.select2-container--disabled .select2-selection--single { background-color: #f1f5f9 !important; border-color: #e2e8f0 !important; cursor: not-allowed !important; }
    .cnm-find .select2-container--default.select2-container--disabled .select2-selection__rendered,
    .cnm-find .select2-container--default.select2-container--disabled .select2-selection__placeholder { color: #94a3b8 !important; }
    .cnm-find .select2-container--default .select2-selection__clear { color: #94a3b8 !important; font-size: 18px !important; margin-right: 4px !important; }
    .cnm-find .select2-container--default .select2-selection__clear:hover { color: #dc2626 !important; }

    /* Validation error */
    .cnm-field.is-invalid .cnm-input,
    .cnm-field.is-invalid .select2-container--default .select2-selection--single { border-color: #dc2626 !important; }
    .cnm-field.is-invalid .cnm-field-icon { color: #dc2626; }

    /* Search Query: the dominant field */
    .cnm-field.is-primary { --field-h: 64px; }
    @media (max-width: 639px) { .cnm-field.is-primary { --field-h: 58px; } }
    .cnm-field.is-primary .cnm-field-icon { color: #f97316; font-size: 17px; width: 54px; }
    .cnm-find .cnm-field.is-primary .select2-selection--single { border-color: #c3cdda !important; }
    .cnm-find .cnm-field.is-primary .select2-selection--single .select2-selection__rendered { font-size: 16px !important; padding-left: 54px !important; }

    /* Select2 dropdown (attached to <body>, so never clipped by the cards) */
    .select2-container--open .select2-dropdown { z-index: 1060; }
    .select2-dropdown { border: 1px solid #e2e8f0 !important; border-radius: 14px !important; box-shadow: 0 18px 36px -14px rgba(15, 23, 42, .28) !important; overflow: hidden; }
    .select2-dropdown--below { margin-top: 6px !important; }
    .select2-dropdown--above { margin-top: -6px !important; }
    .select2-container--default .select2-search--dropdown { padding: 8px !important; }
    .select2-container--default .select2-search--dropdown .select2-search__field { height: 44px; border: 1px solid #dce3ed !important; border-radius: 10px !important; padding: 0 14px !important; font-size: 14px !important; color: #111827; outline: none !important; }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus { border-color: #f97316 !important; box-shadow: 0 0 0 3px rgba(249, 115, 22, .16) !important; }
    .select2-container--default .select2-results > .select2-results__options { max-height: min(300px, 50vh) !important; }
    .select2-container--default .select2-results__option { padding: 11px 16px !important; font-size: 14px !important; color: #334155; }
    .select2-container--default .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-results__option--highlighted[aria-selected]:hover,
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background-color: #fff7ed !important; color: #c2410c !important; }
    .select2-container--default .select2-results__option[aria-selected="true"],
    .select2-container--default .select2-results__option--selected { background-color: #f8fafc !important; color: #111827 !important; font-weight: 600; }
    .select2-container--default .select2-results__message,
    .select2-container--default .select2-results__option--loading { color: #64748b !important; }

    /* Primary action: same height as the fields */
    .cnm-btn { height: var(--field-h); }

    @media (prefers-reduced-motion: reduce) {
        .cnm-find *, .select2-dropdown, .select2-selection__arrow { transition: none !important; }
    }
</style>

<div class="p-4 lg:p-8 cnm-find">

<div class="max-w-[1080px] mx-auto">

<!-- Search Heading (centred, compact) -->
<div class="text-center pt-2 sm:pt-6 pb-6 sm:pb-8 px-2">
    <h1 class="text-[26px] sm:text-[34px] leading-tight font-bold text-gray-900 tracking-tight">Find Your Next Business Leads</h1>
    <p class="mt-2 text-sm sm:text-[15px] leading-relaxed text-slate-500 max-w-xl mx-auto">Search businesses by keyword &amp; location — grab contacts, ratings, and more in seconds</p>
</div>

<!-- Search Form: the focus of the page -->
<div class="bg-white rounded-[20px] border border-slate-200 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_16px_40px_-24px_rgba(15,23,42,0.22)] mb-6">
    <form action="{{ route('user.search.post') }}" method="POST" id="searchForm" class="p-5 sm:p-8 lg:p-10 space-y-6 sm:space-y-7">
        @csrf
        <input type="hidden" name="page_token" id="page_token" value="">
        <input type="hidden" name="original_lat" id="original_lat" value="{{ $searchData['original_lat'] ?? '' }}">
        <input type="hidden" name="original_lng" id="original_lng" value="{{ $searchData['original_lng'] ?? '' }}">
        <input type="hidden" name="original_state_id" id="original_state_id" value="{{ $searchData['state_id'] ?? '' }}">
        <input type="hidden" name="original_city_id" id="original_city_id" value="{{ $searchData['city_id'] ?? '' }}">

        <!-- Row 1: Location (Country, State, City) -->
        <fieldset class="min-w-0">
            <legend class="sr-only">Location</legend>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                <div>
                    <label for="country_select" class="cnm-label">Country <span class="req" aria-hidden="true">*</span></label>
                    <div class="cnm-field has-icon @error('country_id') is-invalid @enderror">
                        <span class="cnm-field-icon" aria-hidden="true"><i class="fas fa-globe"></i></span>
                        <select name="country_id"
                                id="country_select"
                                required
                                aria-describedby="location-hint @error('country_id') country_select-error @enderror"
                                @error('country_id') aria-invalid="true" @enderror
                                class="cnm-input">
                            <option value="">Select Country</option>
                            @if(isset($countries))
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}"
                                            data-name="{{ $country->name }}"
                                            data-lat="{{ $country->latitude }}"
                                            data-lng="{{ $country->longitude }}"
                                            {{ old('country_id', $searchData['country_id'] ?? '') == $country->id ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    @error('country_id')
                        <p id="country_select-error" class="cnm-error" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="state_select" class="cnm-label">State</label>
                    <div class="cnm-field has-icon @error('state_id') is-invalid @enderror">
                        <span class="cnm-field-icon" aria-hidden="true"><i class="fas fa-map"></i></span>
                        <select name="state_id"
                                id="state_select"
                                @error('state_id') aria-invalid="true" aria-describedby="state_select-error" @enderror
                                class="cnm-input"
                                disabled>
                            <option value="">Select State</option>
                        </select>
                    </div>
                    @error('state_id')
                        <p id="state_select-error" class="cnm-error" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="city_select" class="cnm-label">City</label>
                    <div class="cnm-field has-icon @error('city_id') is-invalid @enderror">
                        <span class="cnm-field-icon" aria-hidden="true"><i class="fas fa-city"></i></span>
                        <select name="city_id"
                                id="city_select"
                                @error('city_id') aria-invalid="true" aria-describedby="city_select-error" @enderror
                                class="cnm-input"
                                disabled>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    @error('city_id')
                        <p id="city_select-error" class="cnm-error" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <p id="location-hint" class="mt-3 flex items-start gap-2 text-[13px] leading-relaxed text-slate-500">
                <i class="fas fa-info-circle text-blue-600 mt-[3px] flex-shrink-0" aria-hidden="true"></i>
                <span>Select <strong class="font-semibold text-gray-900">Country</strong> first to unlock State &amp; City. Choose only <strong class="font-semibold text-gray-900">State</strong> to search the entire state, or also select a <strong class="font-semibold text-gray-900">City</strong> to narrow results to that city only.</span>
            </p>
        </fieldset>

        <!-- Row 2: Search Query (dominant) + Radius + action -->
        <div class="pt-6 sm:pt-7 border-t border-slate-100">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_190px_auto] gap-4 sm:gap-5 items-end">
                <div class="sm:col-span-2 xl:col-span-1 min-w-0">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5 mb-2">
                        <label for="search_query" class="cnm-label !mb-0 !text-sm">Search Query <span class="req" aria-hidden="true">*</span></label>
                        <span class="inline-flex items-center gap-1.5 h-6 px-2.5 rounded-full bg-orange-50 border border-orange-200 text-orange-700 text-xs font-semibold whitespace-nowrap">
                            <i class="fas fa-layer-group text-[11px]" aria-hidden="true"></i>
                            {{ number_format($nicheCount) }} niches available
                        </span>
                    </div>
                    <div class="cnm-field has-icon is-primary @error('query') is-invalid @enderror">
                        <span class="cnm-field-icon" aria-hidden="true"><i class="fas fa-search"></i></span>
                        <select name="query"
                                id="search_query"
                                required
                                @error('query') aria-invalid="true" aria-describedby="search_query-error" @enderror
                                class="w-full">
                            @if(old('query', $searchData['query'] ?? ''))
                                <option value="{{ old('query', $searchData['query'] ?? '') }}" selected>{{ old('query', $searchData['query'] ?? '') }}</option>
                            @endif
                        </select>
                    </div>
                    @error('query')
                        <p id="search_query-error" class="cnm-error" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="radius_select" class="cnm-label">Radius</label>
                    <div class="cnm-field has-icon">
                        <span class="cnm-field-icon" aria-hidden="true"><i class="fas fa-crosshairs"></i></span>
                        <select name="radius"
                                id="radius_select"
                                class="cnm-input">
                            <option value="5" {{ old('radius', $searchData['radius'] ?? 10) == 5 ? 'selected' : '' }}>5 km</option>
                            <option value="10" {{ old('radius', $searchData['radius'] ?? 10) == 10 ? 'selected' : '' }}>10 km</option>
                            <option value="25" {{ old('radius', $searchData['radius'] ?? 10) == 25 ? 'selected' : '' }}>25 km</option>
                            <option value="50" {{ old('radius', $searchData['radius'] ?? 10) == 50 ? 'selected' : '' }}>50 km</option>
                            <option value="100" {{ old('radius', $searchData['radius'] ?? 10) == 100 ? 'selected' : '' }}>100 km</option>
                        </select>
                    </div>
                </div>

                <div>
                    @if($extensionMode)
                        {{-- Extension Mode: opens Google Maps in a new tab --}}
                        <button type="submit" id="searchBtn" class="cnm-btn w-full xl:w-auto inline-flex items-center justify-center gap-2.5 px-7 rounded-[14px] bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white text-[15px] font-semibold shadow-sm transition-colors whitespace-nowrap focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-200 disabled:bg-slate-300 disabled:shadow-none disabled:cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/></svg>
                            <span id="btnText">Open in Google Maps</span>
                        </button>
                    @else
                        <button type="submit" id="searchBtn" class="cnm-btn w-full xl:w-auto inline-flex items-center justify-center px-8 rounded-[14px] bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white text-[15px] font-semibold shadow-sm transition-colors whitespace-nowrap focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-200 disabled:bg-slate-300 disabled:shadow-none disabled:cursor-not-allowed">
                            <i class="fas fa-search mr-2" id="searchIcon"></i>
                            <span id="btnText">Search</span>
                        </button>
                    @endif
                </div>
            </div>

            @unless($extensionMode)
            {{-- Normal Mode: review filters --}}
            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5 xl:max-w-[60%]">
                <div>
                    <label for="review_max" class="cnm-label">Max Reviews</label>
                    <div class="cnm-field has-icon">
                        <span class="cnm-field-icon" aria-hidden="true"><i class="fas fa-star"></i></span>
                        <input type="number"
                               name="review_max"
                               id="review_max"
                               value="{{ old('review_max', $searchData['review_max'] ?? 0) }}"
                               placeholder="0 = No filter"
                               min="0"
                               class="cnm-input">
                    </div>
                </div>
                <div>
                    <label class="cnm-label" @if(auth()->user()->hasFeature('latest_review_insights')) for="latest_review_within_days" @endif>Review Within Days</label>
                    @if(!auth()->user()->hasFeature('latest_review_insights'))
                        <div class="cnm-field has-icon">
                            <span class="cnm-field-icon" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
                            <input type="number"
                                   value="0"
                                   disabled
                                   aria-label="Review Within Days (locked on your plan)"
                                   class="cnm-input">
                            <input type="hidden" name="latest_review_within_days" value="0">
                            <a href="{{ route('user.subscription') }}"
                               class="absolute top-1/2 -translate-y-1/2 right-3 z-10 inline-flex items-center gap-1 h-9 px-3 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-1"
                               title="Upgrade to unlock this filter">
                                <i class="fas fa-lock text-[10px]" aria-hidden="true"></i>Upgrade
                            </a>
                        </div>
                    @else
                        <div class="cnm-field has-icon">
                            <span class="cnm-field-icon" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
                            <input type="number"
                                   name="latest_review_within_days"
                                   id="latest_review_within_days"
                                   value="{{ old('latest_review_within_days', $searchData['latest_review_within_days'] ?? 0) }}"
                                   placeholder="0 = No filter"
                                   min="0"
                                   class="cnm-input">
                        </div>
                    @endif
                </div>
            </div>
            @endunless
        </div>
    </form>

    <!-- Progress Bar (Hidden by default) -->
    <div id="progressContainer" class="px-5 sm:px-8 lg:px-10 pb-6 hidden">
        <div class="bg-slate-100 rounded-full h-2 overflow-hidden">
            <div id="progressBar" class="bg-orange-500 h-2 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
        </div>
        <div class="flex justify-between items-center mt-2">
            <p class="text-sm text-slate-600" id="progressText">Initializing search...</p>
            <span class="text-sm font-semibold text-slate-800" id="progressPercent">0%</span>
        </div>
    </div>

    {{-- Errors are now shown in modal via JavaScript --}}

    @if(session('success'))
        <div class="mx-5 sm:mx-8 lg:mx-10 mb-6 bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-xl">
            <i class="fas fa-check-circle mr-2" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="mx-5 sm:mx-8 lg:mx-10 mb-6 bg-blue-50 border border-blue-200 text-blue-800 text-sm px-4 py-3 rounded-xl">
            <i class="fas fa-info-circle mr-2" aria-hidden="true"></i>
            {{ session('info') }}
        </div>
    @endif
</div>

<!-- Quick Tips: compact, below the form -->
@if(!isset($searchPerformed) || !$searchPerformed)
<section class="mb-6 rounded-2xl bg-slate-50 border border-slate-200 px-5 sm:px-6 py-5" aria-labelledby="search-tips-title">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
        <h3 id="search-tips-title" class="flex items-center gap-2 text-sm font-semibold text-gray-900">
            <i class="fas fa-lightbulb text-yellow-600" aria-hidden="true"></i>Quick Tips
        </h3>
        <span class="text-xs text-slate-500">Search Query Examples:</span>
        <ul class="flex flex-wrap items-center gap-1.5" aria-label="Search Query Examples">
            <li class="inline-flex items-center h-7 px-2.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-medium">restaurants</li>
            <li class="inline-flex items-center h-7 px-2.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-medium">hotels</li>
            <li class="inline-flex items-center h-7 px-2.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-medium">travel agencies</li>
            <li class="inline-flex items-center h-7 px-2.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-medium">car dealerships</li>
            <li class="inline-flex items-center h-7 px-2.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-medium">beauty salons</li>
        </ul>
    </div>
    <ul class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2.5 text-[13px] leading-snug text-slate-600">
        <li class="flex items-start gap-2.5"><i class="fas fa-keyboard text-slate-400 w-4 text-center mt-0.5 flex-shrink-0" aria-hidden="true"></i>Enter only the business type — no city or country needed</li>
        <li class="flex items-start gap-2.5"><i class="fas fa-location-dot text-slate-400 w-4 text-center mt-0.5 flex-shrink-0" aria-hidden="true"></i>Use the location field below to target a specific area</li>
        <li class="flex items-start gap-2.5"><i class="fas fa-crosshairs text-slate-400 w-4 text-center mt-0.5 flex-shrink-0" aria-hidden="true"></i>Adjust the radius to widen or narrow your search area</li>
        <li class="flex items-start gap-2.5"><i class="fas fa-rotate text-slate-400 w-4 text-center mt-0.5 flex-shrink-0" aria-hidden="true"></i>Try different keywords if results are too few</li>
    </ul>
    <p class="mt-4 pt-4 border-t border-slate-200 flex items-start gap-2.5 text-[13px] leading-snug text-slate-600">
        <i class="fas fa-info-circle text-blue-600 mt-0.5 flex-shrink-0" aria-hidden="true"></i>
        <span><strong class="font-semibold text-gray-900">Note:</strong> Search results include business details, contact information, ratings, and social media links when available.</span>
    </p>
</section>
@endif

</div>{{-- /max-w --}}

    @if(isset($searchPerformed) && $searchPerformed && isset($formattedResults))
        <!-- Results Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Search Results</h3>
                        <p class="text-sm text-gray-600">
                            Found {{ $totalResults ?? count($formattedResults) }} {{ $searchData['query'] }} Businesses
                            @if(isset($searchData['location_name']))
                                in {{ $searchData['location_name'] }}
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center space-x-3">
                        <input type="checkbox" class="w-4 h-4 text-primary-600 rounded border-gray-300" id="selectAll">
                        <label for="selectAll" class="text-sm text-gray-700">Select All ({{ count($formattedResults) }})</label>
                        
                        <button type="button" 
                                id="saveSelectedBtn"
                                onclick="saveSelectedLeads()"
                                class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                                disabled>
                            <i class="fas fa-save mr-2" id="saveIcon"></i>
                            <span id="saveText">Save Selected (0)</span>
                        </button>
                        
                        @if(isset($nextPageToken) && !empty($nextPageToken))
                            <button type="button" 
                                    id="loadMoreBtn"
                                    onclick="loadNextPage(@js($nextPageToken))"
                                    class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-6 py-2 rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-right mr-2" id="loadMoreIcon"></i>
                                <span id="loadMoreText">Load More Results</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Grid -->
        <div class="space-y-4" id="resultsList">
            @foreach($formattedResults as $index => $result)
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100" data-result-index="{{ $index }}">
                    <div class="flex items-start space-x-4">
                        <input type="checkbox" 
                               class="w-4 h-4 text-primary-600 rounded border-gray-300 mt-1 result-checkbox" 
                               data-index="{{ $index }}"
                               onchange="updateSelectedCount()">
                        
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800">{{ $result['name'] }}</h4>
                                    <p class="text-sm text-orange-600 font-medium">Business</p>
                                </div>
                                @if($result['rating'] > 0)
                                    <div class="flex items-center space-x-1">
                                        <div class="flex text-yellow-400">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= floor($result['rating']))
                                                    <i class="fas fa-star"></i>
                                                @elseif($i <= $result['rating'])
                                                    <i class="fas fa-star-half-alt"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">{{ number_format($result['rating'], 1) }}</span>
                                        @if($result['total_reviews'] > 0)
                                            <span class="text-xs text-gray-500">({{ number_format($result['total_reviews']) }})</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
                                <div class="space-y-2">
                                    @if($result['address'])
                                        <div class="flex items-start space-x-2">
                                            <i class="fas fa-map-marker-alt text-primary-600 mt-1"></i>
                                            <span class="text-gray-600">{{ $result['address'] }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-phone text-primary-600"></i>
                                        @if($result['phone'])
                                            <span class="text-gray-600">{{ $result['phone'] }}</span>
                                        @else
                                            <span class="text-gray-400 italic">N/A</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-envelope text-primary-600"></i>
                                        @if(!empty($result['emails']) && count($result['emails']) > 0)
                                            <span class="text-gray-600">{{ $result['emails'][0] }}</span>
                                        @else
                                            <span class="text-gray-400 italic">N/A</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-globe text-primary-600"></i>
                                        @if($result['website'])
                                            <a href="{{ \App\Support\SafeUrl::href($result['website']) }}" rel="noopener noreferrer" target="_blank" class="text-blue-600 hover:text-blue-800">
                                                {{ str_replace(['http://', 'https://'], '', $result['website']) }}
                                            </a>
                                        @else
                                            <span class="text-gray-400 italic">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Social Media Links -->
                            @if(!empty($result['social_links']) && count($result['social_links']) > 0)
                                <div class="mb-4">
                                    <p class="text-xs text-gray-500 mb-2">Social Media:</p>
                                    <div class="flex space-x-2">
                                        @foreach($result['social_links'] as $social_link)
                                            @php
                                                $icon = 'fab fa-external-link-alt';
                                                $color = 'text-gray-600';
                                                
                                                if(str_contains($social_link, 'facebook.com')) {
                                                    $icon = 'fab fa-facebook';
                                                    $color = 'text-blue-600';
                                                } elseif(str_contains($social_link, 'instagram.com')) {
                                                    $icon = 'fab fa-instagram';
                                                    $color = 'text-pink-600';
                                                } elseif(str_contains($social_link, 'twitter.com') || str_contains($social_link, 'x.com')) {
                                                    $icon = 'fab fa-twitter';
                                                    $color = 'text-blue-400';
                                                } elseif(str_contains($social_link, 'linkedin.com')) {
                                                    $icon = 'fab fa-linkedin';
                                                    $color = 'text-blue-700';
                                                } elseif(str_contains($social_link, 'youtube.com')) {
                                                    $icon = 'fab fa-youtube';
                                                    $color = 'text-red-600';
                                                } elseif(str_contains($social_link, 'tiktok.com')) {
                                                    $icon = 'fab fa-tiktok';
                                                    $color = 'text-black';
                                                }
                                            @endphp
                                            <a href="{{ \App\Support\SafeUrl::href($social_link) }}" rel="noopener noreferrer" target="_blank" class="{{ $color }} hover:opacity-80 transition-opacity">
                                                <i class="{{ $icon }} text-lg"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                                <div class="flex space-x-2">
                                    @if($result['profile'])
                                        <a href="{{ \App\Support\SafeUrl::href($result['profile']) }}" rel="noopener noreferrer" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium">
                                            <i class="fab fa-google mr-1"></i>View Profile
                                        </a>
                                    @endif
                                </div>
                                <div class="text-right">
                                    @if(!empty($result['opening_hours']) && is_array($result['opening_hours']) && count($result['opening_hours']) > 0)
                                        <span class="text-xs text-gray-500">{{ $result['opening_hours'][0] ?? '' }}</span>
                                    @endif
                                    @if(!empty($result['reviews']) && is_array($result['reviews']) && count($result['reviews']) > 0)
                                        <p class="text-xs text-gray-400 mt-1">
                                            Latest review: {{ \Carbon\Carbon::createFromTimestamp($result['reviews'][0]['time'])->diffForHumans() }}
                                        </p>
                                    @elseif(!empty($result['latest_review_date']) && is_numeric($result['latest_review_date']))
                                        <p class="text-xs text-gray-400 mt-1">
                                            Latest review: {{ \Carbon\Carbon::createFromTimestamp($result['latest_review_date'])->diffForHumans() }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden result data for save functionality -->
                    <script type="application/json" class="result-data">
                        {!! json_encode($result, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
                    </script>
                </div>
            @endforeach
        </div>

        @if(count($formattedResults) == 0)
            <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-100 text-center">
                <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">No Results Found</h3>
                <p class="text-gray-600">Try adjusting your search criteria or expanding the radius.</p>
            </div>
        @endif
    @endif
</div>

<!-- Error Modal -->
<div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="mx-auto mb-4 w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-3xl text-red-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Search Error</h3>
            <div id="errorModalMessage" class="text-gray-600 mb-4"></div>
            <div class="flex space-x-3">
                <button type="button"
                        onclick="closeErrorModal()"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg transition-colors">
                    Close
                </button>
                <button type="button"
                        id="retrySearchBtn"
                        onclick="retrySearchFromModal()"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Try Again
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Save Status Modal -->
<div id="saveModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="text-center">
            <div id="saveModalIcon" class="mx-auto mb-4 w-12 h-12 rounded-full flex items-center justify-center">
                <i class="fas fa-spinner fa-spin text-2xl text-primary-600" id="saveSpinner"></i>
                <i class="fas fa-check text-2xl text-green-600 hidden" id="saveSuccess"></i>
                <i class="fas fa-times text-2xl text-red-600 hidden" id="saveError"></i>
            </div>
            <h3 id="saveModalTitle" class="text-lg font-semibold mb-2">Saving Leads...</h3>
            <p id="saveModalMessage" class="text-gray-600 mb-4">Please wait while we save your selected leads.</p>
            <button type="button"
                    id="saveModalClose"
                    onclick="closeSaveModal()"
                    class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg hidden">
                Close
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

{{-- Field and Select2 styling for this page lives at the top of the content section --}}

<!-- Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Global variables
const baseUrl = "{{ url('/') }}";
let searchData = @json($searchData ?? []);

// Initialize form handlers
function initializeFormHandlers() {
    const countrySelect = document.getElementById('country_select');
    const stateSelect = document.getElementById('state_select');
    const citySelect = document.getElementById('city_select');

    // Use current searchData for state/city IDs (this gets updated after AJAX)
    const originalStateId = searchData.state_id || "";
    const originalCityId = searchData.city_id || "";

    if (!countrySelect) return;

    // Destroy existing Select2 instances if they exist
    if ($(countrySelect).hasClass("select2-hidden-accessible")) {
        $(countrySelect).select2('destroy');
    }
    if ($(stateSelect).hasClass("select2-hidden-accessible")) {
        $(stateSelect).select2('destroy');
    }
    if ($(citySelect).hasClass("select2-hidden-accessible")) {
        $(citySelect).select2('destroy');
    }

    // Remove existing listeners by cloning
    const newCountrySelect = countrySelect.cloneNode(true);
    const newStateSelect = stateSelect.cloneNode(true);
    const newCitySelect = citySelect.cloneNode(true);

    countrySelect.parentNode.replaceChild(newCountrySelect, countrySelect);
    stateSelect.parentNode.replaceChild(newStateSelect, stateSelect);
    citySelect.parentNode.replaceChild(newCitySelect, citySelect);

    const country = document.getElementById('country_select');
    const state = document.getElementById('state_select');
    const city = document.getElementById('city_select');

    // Initialize Select2 on query field with AJAX niche suggestions + free-text
    $('#search_query').select2({
        placeholder: 'e.g. Plumbing Service, Restaurant',
        allowClear: true,
        width: '100%',
        tags: true,
        minimumInputLength: 0,
        ajax: {
            url: baseUrl + '/user/api/niches',
            dataType: 'json',
            delay: 200,
            data: function(params) { return { q: params.term || '' }; },
            processResults: function(data) { return { results: data.results }; },
            cache: true
        },
        createTag: function(params) {
            var term = $.trim(params.term);
            if (!term) return null;
            return { id: term, text: term, newTag: true };
        }
    });

    // Before the niche list opens, scroll the field up (label still visible below the header)
    // so there is room underneath and Select2 opens the list below the field, not above it.
    $('#search_query').off('select2:opening.cnmScroll').on('select2:opening.cnmScroll', function () {
        const field = this.closest('.cnm-field') || this;
        const wrap = document.querySelector('.cnm-find');
        const keepAbove = 110;  // px from the top of the window: room for the label
        const listRoom = 340;   // the list (max 300px) + a small gap
        const scrollUp = () => {
            const top = field.getBoundingClientRect().top;
            if (top > keepAbove) window.scrollTo({ top: window.scrollY + top - keepAbove, behavior: 'auto' });
        };
        scrollUp();
        // A short page cannot scroll that far: add room at the bottom while the list is open
        const below = window.innerHeight - field.getBoundingClientRect().bottom;
        if (below < listRoom && wrap) {
            wrap.style.paddingBottom = (listRoom - below + 32) + 'px';
            scrollUp();
        }
    }).off('select2:close.cnmScroll').on('select2:close.cnmScroll', function () {
        const wrap = document.querySelector('.cnm-find');
        if (wrap) wrap.style.paddingBottom = '';
    });

    // Initialize Select2 on all three dropdowns
    $(country).select2({
        placeholder: 'Select Country',
        allowClear: false,
        width: '100%'
    });

    $(state).select2({
        placeholder: 'Select State',
        allowClear: true,
        width: '100%'
    });

    $(city).select2({
        placeholder: 'Select City',
        allowClear: true,
        width: '100%'
    });

    // Initialize form
    if (country.value) {
        loadStates(country.value, originalStateId);
    }

    // Event listeners using Select2 events
    $(country).on('change', function() {
        resetSelect(state, 'Select State');
        resetSelect(city, 'Select City');
        if (this.value) {
            loadStates(this.value);
            // Update searchData
            searchData.country_id = this.value;
            const selectedOption = this.options[this.selectedIndex];
            searchData.country_name = selectedOption ? selectedOption.text : '';
        }
        clearPageToken();
    });

    $(state).on('change', function() {
        resetSelect(city, 'Select City');
        if (this.value) {
            loadCities(this.value);
            // Update hidden input
            document.getElementById('original_state_id').value = this.value;
            // Update searchData
            searchData.state_id = this.value;
            const selectedOption = this.options[this.selectedIndex];
            searchData.state_name = selectedOption ? selectedOption.text : '';
        } else {
            document.getElementById('original_state_id').value = '';
            searchData.state_id = '';
            searchData.state_name = '';
        }
        clearPageToken();
    });

    $(city).on('change', function() {
        if (this.value) {
            // Update hidden input
            document.getElementById('original_city_id').value = this.value;
            // Update searchData
            searchData.city_id = this.value;
            const selectedOption = this.options[this.selectedIndex];
            searchData.city_name = selectedOption ? selectedOption.text : '';
        } else {
            document.getElementById('original_city_id').value = '';
            searchData.city_id = '';
            searchData.city_name = '';
        }
        clearPageToken();
    });

    const searchQuery = document.getElementById('search_query');
    const radiusSelect = document.getElementById('radius_select');

    if (searchQuery) searchQuery.addEventListener('input', clearPageToken);
    if (radiusSelect) radiusSelect.addEventListener('change', clearPageToken);

    function resetSelect(select, defaultText) {
        // Destroy Select2 before resetting
        if ($(select).hasClass("select2-hidden-accessible")) {
            $(select).select2('destroy');
        }

        select.innerHTML = `<option value="">${defaultText}</option>`;
        select.disabled = true;

        // Reinitialize Select2
        $(select).select2({
            placeholder: defaultText,
            allowClear: true,
            width: '100%'
        });
    }

    function loadStates(countryId, selectedStateId = null) {
        fetch(`${baseUrl}/user/api/states/${countryId}`)
            .then(response => response.json())
            .then(states => {
                resetSelect(state, 'Select State');
                states.forEach(st => {
                    const option = new Option(st.name, st.id);
                    if (st.latitude)  option.dataset.lat = st.latitude;
                    if (st.longitude) option.dataset.lng = st.longitude;
                    if (selectedStateId && st.id == selectedStateId) option.selected = true;
                    state.add(option);
                });
                state.disabled = false;

                // Refresh Select2 after adding options
                $(state).trigger('change.select2');

                if (selectedStateId) loadCities(selectedStateId, originalCityId);
            });
    }

    function loadCities(stateId, selectedCityId = null) {
        fetch(`${baseUrl}/user/api/cities/${stateId}`)
            .then(response => response.json())
            .then(cities => {
                resetSelect(city, 'Select City');
                cities.forEach(c => {
                    const option = new Option(c.name, c.id);
                    if (c.latitude)  option.dataset.lat = c.latitude;
                    if (c.longitude) option.dataset.lng = c.longitude;
                    if (selectedCityId && c.id == selectedCityId) option.selected = true;
                    city.add(option);
                });
                city.disabled = false;

                // Refresh Select2 after adding options
                $(city).trigger('change.select2');
            });
    }

    function clearPageToken() {
        const pageToken = document.getElementById('page_token');
        if (pageToken) pageToken.value = '';
    }
}

// Initialize result handlers
function initializeResultHandlers() {
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        // Remove old listener by cloning
        const newSelectAll = selectAllCheckbox.cloneNode(true);
        selectAllCheckbox.parentNode.replaceChild(newSelectAll, selectAllCheckbox);

        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.result-checkbox').forEach(cb => cb.checked = this.checked);
            updateSelectedCount();
        });
    }
}

// Use jQuery ready instead of DOMContentLoaded for better Select2 compatibility
$(document).ready(function() {
    // Check for server-side errors and show in modal
    @if($errors->has('api'))
        showErrorModal(@json($errors->first('api')));
    @endif

    // Initialize handlers
    initializeFormHandlers();
    initializeResultHandlers();
});

// Search functionality
let isSearching = false;

const extensionMode = {{ $extensionMode ? 'true' : 'false' }};

document.addEventListener('submit', async function(e) {
    if (e.target.id !== 'searchForm') return;
    e.preventDefault();

    // Extension Mode: open Google Maps in new tab
    if (extensionMode) {
        const query = (document.getElementById('search_query')?.value || '').trim();
        if (!query) return;

        const countryEl = document.getElementById('country_select');
        const stateEl   = document.getElementById('state_select');
        const cityEl    = document.getElementById('city_select');

        const cityOpt    = cityEl?.selectedIndex > 0   ? cityEl.options[cityEl.selectedIndex]   : null;
        const stateOpt   = stateEl?.selectedIndex > 0  ? stateEl.options[stateEl.selectedIndex]  : null;
        const countryOpt = countryEl?.selectedIndex > 0 ? countryEl.options[countryEl.selectedIndex] : null;

        // Build location string — deduplicate city/state if same name
        const cityName    = cityOpt?.text    || '';
        const stateName   = stateOpt?.text   || '';
        const countryName = countryOpt?.text || '';
        const uniqueParts = [];
        if (cityName    && !cityName.startsWith('Select'))    uniqueParts.push(cityName);
        if (stateName   && !stateName.startsWith('Select') && stateName.toLowerCase() !== cityName.toLowerCase())   uniqueParts.push(stateName);
        if (countryName && !countryName.startsWith('Select') && countryName.toLowerCase() !== stateName.toLowerCase() && countryName.toLowerCase() !== cityName.toLowerCase()) uniqueParts.push(countryName);
        const location = uniqueParts.join(', ');

        const fullQuery = location ? `${query} in ${location}` : query;
        const encodedQuery = fullQuery.replace(/[\s,]+/g, '+');

        // Get coordinates: city > state > country (same priority as backend)
        let lat = null, lng = null;
        if (cityOpt?.dataset.lat && cityOpt?.dataset.lng) {
            lat = parseFloat(cityOpt.dataset.lat);
            lng = parseFloat(cityOpt.dataset.lng);
        } else if (stateOpt?.dataset.lat && stateOpt?.dataset.lng) {
            lat = parseFloat(stateOpt.dataset.lat);
            lng = parseFloat(stateOpt.dataset.lng);
        } else if (countryOpt?.dataset.lat && countryOpt?.dataset.lng) {
            lat = parseFloat(countryOpt.dataset.lat);
            lng = parseFloat(countryOpt.dataset.lng);
        }

        // Convert radius (km) to Google Maps zoom level
        const radius = parseInt(document.getElementById('radius_select')?.value || 10);
        const radiusToZoom = { 5: 13, 10: 12, 25: 11, 50: 10, 100: 9 };
        const zoom = radiusToZoom[radius] ?? 12;

        let mapsUrl;
        if (lat && lng) {
            mapsUrl = `https://www.google.com/maps/search/${encodedQuery}/@${lat},${lng},${zoom}z`;
        } else {
            mapsUrl = `https://www.google.com/maps/search/${encodedQuery}?entry=ttu`;
        }

        window.open(mapsUrl, '_blank');
        return;
    }

    if (isSearching) return false;

    // Show loading state
    if (!showSearchLoading()) return false;

    // Start progress bar
    startProgress();

    // Get form data
    const formData = new FormData(e.target);

    try {
        const response = await fetch('{{ route('user.search.post') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const contentType = response.headers.get('content-type');

        // Check if response is JSON (error) or HTML (success)
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();

            // Handle error response
            hideSearchLoading();
            completeProgress();

            if (data.error || data.errors) {
                let errorMessage = data.error || 'An error occurred';

                // Check for validation errors
                if (data.errors) {
                    if (data.errors.api) {
                        errorMessage = Array.isArray(data.errors.api) ? data.errors.api[0] : data.errors.api;
                    } else {
                        // Handle other validation errors
                        const firstError = Object.values(data.errors)[0];
                        errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                    }
                }

                showErrorModal(errorMessage);
            }
        } else if (response.ok) {
            // HTML response - replace page content with results
            const html = await response.text();

            // Create a temporary container to parse the HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            // Extract the content section from the response
            const newContent = tempDiv.querySelector('.p-4.lg\\:p-8');

            if (newContent) {
                // Replace current content with new content
                const currentContent = document.querySelector('.p-4.lg\\:p-8');
                currentContent.innerHTML = newContent.innerHTML;

                // Extract and update searchData from the new HTML
                const scripts = tempDiv.querySelectorAll('script');
                scripts.forEach(script => {
                    const scriptContent = script.textContent;
                    if (scriptContent.includes('let searchData =')) {
                        // Extract JSON object from script
                        const match = scriptContent.match(/let searchData = ({[\s\S]*?});/);
                        if (match && match[1]) {
                            try {
                                searchData = JSON.parse(match[1]);
                                console.log('Updated searchData:', searchData);

                                // Update hidden inputs with new searchData
                                if (searchData.original_lat) document.getElementById('original_lat').value = searchData.original_lat;
                                if (searchData.original_lng) document.getElementById('original_lng').value = searchData.original_lng;
                                if (searchData.state_id) document.getElementById('original_state_id').value = searchData.state_id;
                                if (searchData.city_id) document.getElementById('original_city_id').value = searchData.city_id;
                            } catch (e) {
                                console.error('Failed to parse searchData:', e, 'Content:', match[1]);
                            }
                        }
                    }
                });

                // Reinitialize event handlers for the new content
                initializeFormHandlers();
                initializeResultHandlers();

                // Scroll to top to show results
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                // Fallback: reload page if content structure is different
                window.location.reload();
            }

            hideSearchLoading();
            completeProgress();
        } else {
            // Non-JSON error response
            hideSearchLoading();
            completeProgress();
            showErrorModal('An error occurred. Please try again.');
        }

    } catch (error) {
        hideSearchLoading();
        completeProgress();
        showErrorModal('An unexpected error occurred. Please try again.');
        console.error('Search error:', error);
    }
});

function showSearchLoading() {
    if (isSearching) return false;
    isSearching = true;
    const btn = document.getElementById('searchBtn');
    const icon = document.getElementById('searchIcon');
    const text = document.getElementById('btnText');

    btn.disabled = true;
    icon.className = 'fas fa-spinner fa-spin mr-2';
    text.textContent = 'Searching...';
    return true;
}

function hideSearchLoading() {
    isSearching = false;
    const btn = document.getElementById('searchBtn');
    const icon = document.getElementById('searchIcon');
    const text = document.getElementById('btnText');

    btn.disabled = false;
    icon.className = 'fas fa-search mr-2';
    text.textContent = 'Search';
}

function loadNextPage(nextPageToken) {
    document.getElementById('page_token').value = nextPageToken;

    // Preserve state_id and city_id from searchData for Load More requests
    if (searchData.state_id) {
        const stateSelect = document.getElementById('state_select');
        if (stateSelect) stateSelect.value = searchData.state_id;
    }
    if (searchData.city_id) {
        const citySelect = document.getElementById('city_select');
        if (citySelect) citySelect.value = searchData.city_id;
    }

    const btn = document.getElementById('loadMoreBtn');
    const icon = document.getElementById('loadMoreIcon');
    const text = document.getElementById('loadMoreText');

    btn.disabled = true;
    icon.className = 'fas fa-spinner fa-spin mr-2';
    text.textContent = 'Loading...';

    // Trigger form submission
    const form = document.getElementById('searchForm');
    const submitEvent = new Event('submit', { cancelable: true, bubbles: true });
    form.dispatchEvent(submitEvent);
}

function retrySearch() {
    closeErrorModal();
    const form = document.getElementById('searchForm');
    const submitEvent = new Event('submit', { cancelable: true, bubbles: true });
    form.dispatchEvent(submitEvent);
}

function retrySearchFromModal() {
    retrySearch();
}

function showErrorModal(message) {
    const modal = document.getElementById('errorModal');
    const messageEl = document.getElementById('errorModalMessage');

    // Set message (allowing HTML for links)
    messageEl.innerHTML = message;

    // Show modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeErrorModal() {
    const modal = document.getElementById('errorModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Select functionality
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.result-checkbox').forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});

function updateSelectedCount() {
    const count = document.querySelectorAll('.result-checkbox:checked').length;
    const total = document.querySelectorAll('.result-checkbox').length;
    const saveBtn = document.getElementById('saveSelectedBtn');
    const saveText = document.getElementById('saveText');
    const selectAll = document.getElementById('selectAll');
    
    saveText.textContent = `Save Selected (${count})`;
    saveBtn.disabled = count === 0;
    
    selectAll.checked = count === total && count > 0;
    selectAll.indeterminate = count > 0 && count < total;
}

// Save leads functionality
function saveSelectedLeads() {
    const checkboxes = document.querySelectorAll('.result-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one lead to save.');
        return;
    }

    const leadsData = [];
    checkboxes.forEach(checkbox => {
        const index = checkbox.dataset.index;
        const resultDiv = document.querySelector(`[data-result-index="${index}"]`);
        const dataScript = resultDiv.querySelector('.result-data');
        if (dataScript) {
            try {
                leadsData.push(JSON.parse(dataScript.textContent));
            } catch (e) {
                console.error('Failed to parse lead data:', e);
            }
        }
    });

    if (leadsData.length === 0) {
        showSaveError('No valid leads selected');
        return;
    }

    showSaveModal();

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        showSaveError('CSRF token not found. Please refresh the page.');
        console.error('CSRF token meta tag not found');
        return;
    }

    console.log('Saving leads:', {
        count: leadsData.length,
        searchData: searchData
    });

    fetch('{{ route("user.leads.save") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            leads: leadsData,
            search_data: searchData || {}
        })
    })
    .then(async response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        // Handle redirects (302, 301, etc.)
        if (response.redirected) {
            throw new Error('Request was redirected. Please refresh the page and try again.');
        }

        if (!response.ok) {
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                throw new Error(data.message || 'Failed to save leads');
            } else {
                throw new Error(`Server error: ${response.status}`);
            }
        }

        return response.json();
    })
    .then(data => {
        console.log('Save response:', data);
        if (data.success) {
            showSaveSuccess(data.message);
            // Uncheck saved leads
            checkboxes.forEach(cb => cb.checked = false);
            updateSelectedCount();
        } else {
            showSaveError(data.message || 'Failed to save leads');
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        showSaveError(error.message || 'An error occurred while saving leads');
    });
}

function showSaveModal() {
    document.getElementById('saveModal').classList.remove('hidden');
    document.getElementById('saveModal').classList.add('flex');
    document.getElementById('saveSpinner').classList.remove('hidden');
    document.getElementById('saveSuccess').classList.add('hidden');
    document.getElementById('saveError').classList.add('hidden');
    document.getElementById('saveModalClose').classList.add('hidden');
    document.getElementById('saveModalTitle').textContent = 'Saving Leads...';
    document.getElementById('saveModalMessage').textContent = 'Please wait while we save your selected leads.';
}

function showSaveSuccess(message) {
    document.getElementById('saveSpinner').classList.add('hidden');
    document.getElementById('saveSuccess').classList.remove('hidden');
    document.getElementById('saveModalClose').classList.remove('hidden');
    document.getElementById('saveModalTitle').textContent = 'Success!';
    document.getElementById('saveModalMessage').textContent = message;
}

function showSaveError(message) {
    document.getElementById('saveSpinner').classList.add('hidden');
    document.getElementById('saveError').classList.remove('hidden');
    document.getElementById('saveModalClose').classList.remove('hidden');
    document.getElementById('saveModalTitle').textContent = 'Error';
    document.getElementById('saveModalMessage').textContent = message;
}

function closeSaveModal() {
    document.getElementById('saveModal').classList.add('hidden');
    document.getElementById('saveModal').classList.remove('flex');
}
</script>



<script>
// Progress bar functionality
let progressInterval;

function startProgress() {
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const progressPercent = document.getElementById('progressPercent');
    
    // Show progress container
    progressContainer.classList.remove('hidden');
    
    let progress = 0;
    const messages = [
        'Initializing search...',
        'Connecting to Google Maps API...',
        'Searching for businesses...',
        'Fetching business details...',
        'Processing results...',
        'Almost done...'
    ];
    
    let messageIndex = 0;
    
    progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        
        if (progress >= 90) {
            progress = 90; // Stop at 90% until actual completion
        }
        
        progressBar.style.width = progress + '%';
        progressPercent.textContent = Math.round(progress) + '%';
        
        // Update message based on progress
        if (progress > messageIndex * 15 && messageIndex < messages.length - 1) {
            progressText.textContent = messages[messageIndex];
            messageIndex++;
        }
        
        if (progress >= 90) {
            progressText.textContent = 'Finalizing results...';
            clearInterval(progressInterval);
        }
    }, 800);
}

function completeProgress() {
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const progressPercent = document.getElementById('progressPercent');

    clearInterval(progressInterval);

    progressBar.style.width = '100%';
    progressPercent.textContent = '100%';
    progressText.textContent = 'Search completed successfully!';

    // Hide progress after 2 seconds
    setTimeout(() => {
        document.getElementById('progressContainer').classList.add('hidden');
    }, 2000);
}
</script>

@endpush