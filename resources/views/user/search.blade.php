@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')

<div class="p-4 lg:p-8">

<!-- Search Form -->
<div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 mb-6">
    <form action="{{ route('user.search.post') }}" method="POST" class="flex flex-wrap items-end gap-4" id="searchForm">
        @csrf
        
        <!-- Hidden field for page token -->
        <input type="hidden" name="page_token" id="page_token" value="">
        
        <div class="flex-1 min-w-60">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search Query *</label>
            <input type="text" 
                   name="query" 
                   id="search_query"
                   value="{{ old('query', $searchData['query'] ?? '') }}"
                   placeholder="e.g. travel agency, restaurant" 
                   required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
            @error('query')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="flex-1 min-w-40">
            <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
            <select name="country_id" 
                    id="country_select" 
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
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
        
        <div class="flex-1 min-w-32">
            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
            <select name="state_id" 
                    id="state_select" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                    disabled>
                <option value="">Select State</option>
            </select>
            @error('state_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="flex-1 min-w-32">
            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
            <select name="city_id" 
                    id="city_select" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                    disabled>
                <option value="">Select City</option>
            </select>
            @error('city_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="min-w-24">
            <label class="block text-sm font-medium text-gray-700 mb-1">Radius</label>
            <select name="radius" 
                    id="radius_select"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                <option value="5" {{ old('radius', $searchData['radius'] ?? 10) == 5 ? 'selected' : '' }}>5 km</option>
                <option value="10" {{ old('radius', $searchData['radius'] ?? 10) == 10 ? 'selected' : '' }}>10 km</option>
                <option value="25" {{ old('radius', $searchData['radius'] ?? 10) == 25 ? 'selected' : '' }}>25 km</option>
                <option value="50" {{ old('radius', $searchData['radius'] ?? 10) == 50 ? 'selected' : '' }}>50 km</option>
                <option value="100" {{ old('radius', $searchData['radius'] ?? 10) == 100 ? 'selected' : '' }}>100 km</option>
            </select>
        </div>
        
        <button type="submit" id="searchBtn" class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-6 py-2 rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
            <i class="fas fa-search mr-2" id="searchIcon"></i>
            <span id="btnText">Search</span>
        </button>
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
    
    @if($errors->has('api'))
        <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle mr-2 mt-0.5 text-red-500"></i>
                <div>
                    <strong>Search Error:</strong> {{ $errors->first('api') }}
                    <div class="mt-2 text-sm">
                        <button type="button" onclick="retrySearch()" class="text-red-600 hover:text-red-800 underline">
                            Try Again
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
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
                            Found {{ $totalResults ?? count($formattedResults) }} {{ $searchData['query'] }} 
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
                                    @if($result['phone'])
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-phone text-primary-600"></i>
                                            <span class="text-gray-600">{{ $result['phone'] }}</span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="space-y-2">
                                    @if(!empty($result['emails']) && count($result['emails']) > 0)
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-envelope text-primary-600"></i>
                                            <span class="text-gray-600">{{ $result['emails'][0] }}</span>
                                        </div>
                                    @endif
                                    @if($result['website'])
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-globe text-primary-600"></i>
                                            <a href="{{ $result['website'] }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                                {{ str_replace(['http://', 'https://'], '', $result['website']) }}
                                            </a>
                                        </div>
                                    @endif
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
                                    @if(!empty($result['opening_hours']) && count($result['opening_hours']) > 0)
                                        <span class="text-xs text-gray-500">{{ $result['opening_hours'][0] ?? '' }}</span>
                                    @endif
                                    @if(!empty($result['reviews']) && count($result['reviews']) > 0)
                                        <p class="text-xs text-gray-400 mt-1">
                                            Latest review: {{ \Carbon\Carbon::createFromTimestamp($result['reviews'][0]['time'])->diffForHumans() }}
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('country_select');
    const stateSelect = document.getElementById('state_select');
    const citySelect = document.getElementById('city_select');
    const originalStateId = "{{ old('state_id', $searchData['state_id'] ?? '') }}";
    const originalCityId = "{{ old('city_id', $searchData['city_id'] ?? '') }}";
    const baseUrl = "{{ url('/') }}";

    // Initialize form
    if (countrySelect.value) {
        loadStates(countrySelect.value, originalStateId);
    }

    // Event listeners
    countrySelect.addEventListener('change', function() {
        resetSelect(stateSelect, 'Select State');
        resetSelect(citySelect, 'Select City');
        if (this.value) loadStates(this.value);
        clearPageToken();
    });

    stateSelect.addEventListener('change', function() {
        resetSelect(citySelect, 'Select City');
        if (this.value) loadCities(this.value);
        clearPageToken();
    });

    citySelect.addEventListener('change', clearPageToken);
    document.getElementById('search_query').addEventListener('input', clearPageToken);
    document.getElementById('radius_select').addEventListener('change', clearPageToken);

    function resetSelect(select, defaultText) {
        select.innerHTML = `<option value="">${defaultText}</option>`;
        select.disabled = true;
    }

    function loadStates(countryId, selectedStateId = null) {
        fetch(`${baseUrl}/user/api/states/${countryId}`)
            .then(response => response.json())
            .then(states => {
                resetSelect(stateSelect, 'Select State');
                states.forEach(state => {
                    const option = new Option(state.name, state.id);
                    if (selectedStateId && state.id == selectedStateId) option.selected = true;
                    stateSelect.add(option);
                });
                stateSelect.disabled = false;
                if (selectedStateId) loadCities(selectedStateId, originalCityId);
            });
    }

    function loadCities(stateId, selectedCityId = null) {
        fetch(`${baseUrl}/user/api/cities/${stateId}`)
            .then(response => response.json())
            .then(cities => {
                resetSelect(citySelect, 'Select City');
                cities.forEach(city => {
                    const option = new Option(city.name, city.id);
                    if (selectedCityId && city.id == selectedCityId) option.selected = true;
                    citySelect.add(option);
                });
                citySelect.disabled = false;
            });
    }

    function clearPageToken() {
        document.getElementById('page_token').value = '';
    }
});

// Search functionality
let isSearching = false;

document.getElementById('searchForm').addEventListener('submit', function(e) {
    if (!document.getElementById('page_token').value && !showSearchLoading()) {
        e.preventDefault();
        return false;
    }
    setTimeout(hideSearchLoading, 120000);
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
    
    document.getElementById('searchForm').submit();
}

function retrySearch() {
    document.getElementById('searchForm').submit();
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
        leadsData.push(JSON.parse(dataScript.textContent));
    });

    showSaveModal();

    fetch('{{ route("user.leads.save") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            leads: leadsData,
            search_data: @json($searchData ?? [])
        })
    })
    .then(response => response.json())
    .then(data => {
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
        showSaveError('An error occurred while saving leads');
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

// Form submission handler
document.getElementById('searchForm').addEventListener('submit', function(e) {
    const searchBtn = document.getElementById('searchBtn');
    const searchIcon = document.getElementById('searchIcon');
    const btnText = document.getElementById('btnText');
    
    // Disable button and show loading state
    searchBtn.disabled = true;
    searchIcon.className = 'fas fa-spinner fa-spin mr-2';
    btnText.textContent = 'Searching...';
    
    // Start progress bar
    startProgress();
});

// Simulate completion (you'll replace this with actual response handling)
// This is just for demo - remove in production
setTimeout(() => {
    if (document.getElementById('progressContainer') && !document.getElementById('progressContainer').classList.contains('hidden')) {
        completeProgress();
        
        // Reset button
        const searchBtn = document.getElementById('searchBtn');
        const searchIcon = document.getElementById('searchIcon');
        const btnText = document.getElementById('btnText');
        
        searchBtn.disabled = false;
        searchIcon.className = 'fas fa-search mr-2';
        btnText.textContent = 'Search';
    }
}, 5000); // Remove this setTimeout in production

// Retry search function
function retrySearch() {
    document.getElementById('searchForm').submit();
}
</script>

@endpush