@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')

<div class="p-4 lg:p-8">

<!-- Search Form -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
    <form action="{{ route('user.search.post') }}" method="POST" id="searchForm">
        @csrf
        <input type="hidden" name="page_token" id="page_token" value="">
        <input type="hidden" name="original_lat" id="original_lat" value="{{ $searchData['original_lat'] ?? '' }}">
        <input type="hidden" name="original_lng" id="original_lng" value="{{ $searchData['original_lng'] ?? '' }}">

        <!-- Row 1: Search Query, Country, State, City -->
        <div class="p-4 pb-0">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"><i class="fas fa-search text-[10px] mr-1"></i>Search Query <span class="text-red-400">*</span></label>
                    <input type="text"
                           name="query"
                           id="search_query"
                           value="{{ old('query', $searchData['query'] ?? '') }}"
                           placeholder="e.g. travel agency, restaurant"
                           required
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    @error('query')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"><i class="fas fa-globe text-[10px] mr-1"></i>Country <span class="text-red-400">*</span></label>
                    <select name="country_id"
                            id="country_select"
                            required
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        <option value="">Select Country</option>
                        @if(isset($countries))
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}"
                                        data-name="{{ $country->name }}"
                                        {{ old('country_id', $searchData['country_id'] ?? '') == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('country_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"><i class="fas fa-map text-[10px] mr-1"></i>State</label>
                    <select name="state_id"
                            id="state_select"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                            disabled>
                        <option value="">Select State</option>
                    </select>
                    @error('state_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"><i class="fas fa-city text-[10px] mr-1"></i>City</label>
                    <select name="city_id"
                            id="city_select"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                            disabled>
                        <option value="">Select City</option>
                    </select>
                    @error('city_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Row 2: Radius, Max Reviews, Review Within Days, Search Button -->
        <div class="p-4 pt-3">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"><i class="fas fa-crosshairs text-[10px] mr-1"></i>Radius</label>
                    <select name="radius"
                            id="radius_select"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        <option value="5" {{ old('radius', $searchData['radius'] ?? 10) == 5 ? 'selected' : '' }}>5 km</option>
                        <option value="10" {{ old('radius', $searchData['radius'] ?? 10) == 10 ? 'selected' : '' }}>10 km</option>
                        <option value="25" {{ old('radius', $searchData['radius'] ?? 10) == 25 ? 'selected' : '' }}>25 km</option>
                        <option value="50" {{ old('radius', $searchData['radius'] ?? 10) == 50 ? 'selected' : '' }}>50 km</option>
                        <option value="100" {{ old('radius', $searchData['radius'] ?? 10) == 100 ? 'selected' : '' }}>100 km</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"><i class="fas fa-star text-[10px] mr-1"></i>Max Reviews</label>
                    <input type="number"
                           name="review_max"
                           id="review_max"
                           value="{{ old('review_max', $searchData['review_max'] ?? 0) }}"
                           placeholder="0 = No filter"
                           min="0"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                </div>

                <div class="relative">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"><i class="fas fa-calendar-alt text-[10px] mr-1"></i>Review Within Days</label>
                    @if(!auth()->user()->hasFeature('latest_review_insights'))
                        <div class="relative">
                            <input type="number"
                                   value="0"
                                   disabled
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-400 text-sm cursor-not-allowed">
                            <input type="hidden" name="latest_review_within_days" value="0">
                            <a href="{{ route('user.subscription') }}"
                               class="absolute top-0 right-0 -mt-1 -mr-1 inline-flex items-center bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm transition-all hover:shadow"
                               title="Upgrade to unlock this filter">
                                <i class="fas fa-lock mr-0.5 text-[8px]"></i>Upgrade
                            </a>
                        </div>
                    @else
                        <input type="number"
                               name="latest_review_within_days"
                               id="latest_review_within_days"
                               value="{{ old('latest_review_within_days', $searchData['latest_review_within_days'] ?? 0) }}"
                               placeholder="0 = No filter"
                               min="0"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    @endif
                </div>

                <div class="flex items-end">
                    <button type="submit" id="searchBtn" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold px-8 py-2.5 rounded-lg transition-all shadow-sm hover:shadow disabled:bg-gray-400 disabled:cursor-not-allowed disabled:shadow-none text-sm">
                        <i class="fas fa-search mr-2" id="searchIcon"></i>
                        <span id="btnText">Search</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Progress Bar (Hidden by default) -->
    <div id="progressContainer" class="mt-4 hidden">
        <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
            <div id="progressBar" class="bg-gradient-to-r from-blue-500 to-green-500 h-2 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
        </div>
        <div class="flex justify-between items-center mt-2">
            <p class="text-sm text-gray-600" id="progressText">Initializing search...</p>
            <span class="text-sm font-medium text-gray-700" id="progressPercent">0%</span>
        </div>
    </div>
    
    {{-- Errors are now shown in modal via JavaScript --}}
    
    @if(session('success'))
        <div class="mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="mt-4 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded">
            <i class="fas fa-info-circle mr-2"></i>
            {{ session('info') }}
        </div>
    @endif
</div>

<!-- Search Tips & Instructions -->
@if(!isset($searchPerformed) || !$searchPerformed)
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-sm p-6 border border-blue-100 mb-6">
    <div class="flex items-start space-x-4">
        <div class="bg-blue-100 p-3 rounded-full">
            <i class="fas fa-lightbulb text-blue-600 text-xl"></i>
        </div>
        <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Search Tips for Better Results</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <h4 class="font-medium text-gray-700 mb-2">Search Query Examples:</h4>
                    <ul class="space-y-1 text-gray-600">
                        <li>• "restaurants near me"</li>
                        <li>• "travel agencies"</li>
                        <li>• "hotels in lahore"</li>
                        <li>• "car dealerships"</li>
                        <li>• "beauty salons"</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium text-gray-700 mb-2">Pro Tips:</h4>
                    <ul class="space-y-1 text-gray-600">
                        <li>• Use specific business types for better results</li>
                        <li>• Select location for targeted search</li>
                        <li>• Adjust radius based on area density</li>
                        <li>• Try different keywords if no results</li>
                    </ul>
                </div>
            </div>
            <div class="mt-4 p-3 bg-white rounded-lg border border-blue-200">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    <strong>Note:</strong> Search results include business details, contact information, ratings, and social media links when available.
                </p>
            </div>
        </div>
    </div>
</div>
@endif


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
                                    onclick="loadNextPage('{{ $nextPageToken }}')"
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
                                            <a href="{{ $result['website'] }}" target="_blank" class="text-blue-600 hover:text-blue-800">
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
                                            <a href="{{ $social_link }}" target="_blank" class="{{ $color }} hover:opacity-80 transition-opacity">
                                                <i class="{{ $icon }} text-lg"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                                <div class="flex space-x-2">
                                    @if($result['profile'])
                                        <a href="{{ $result['profile'] }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium">
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
                        {!! json_encode($result) !!}
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
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
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
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
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

<!-- Custom Select2 Styling -->
<style>
    /* Custom Select2 styling to match your design */
    .select2-container--default .select2-selection--single {
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        height: 38px !important;
        line-height: 36px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 12px !important;
        padding-right: 20px !important;
        color: #374151 !important;
        font-size: 0.875rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
        top: 1px !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        outline: none !important;
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
</style>

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
    const originalStateId = "{{ old('state_id', $searchData['state_id'] ?? '') }}";
    const originalCityId = "{{ old('city_id', $searchData['city_id'] ?? '') }}";

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
        if (this.value) loadStates(this.value);
        clearPageToken();
    });

    $(state).on('change', function() {
        resetSelect(city, 'Select City');
        if (this.value) loadCities(this.value);
        clearPageToken();
    });

    $(city).on('change', clearPageToken);

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

document.addEventListener('submit', async function(e) {
    if (e.target.id !== 'searchForm') return;
    e.preventDefault();

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
                        const match = scriptContent.match(/let searchData = ({.*?});/s);
                        if (match && match[1]) {
                            try {
                                searchData = JSON.parse(match[1]);
                            } catch (e) {
                                console.error('Failed to parse searchData:', e);
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