@extends('layouts.app')

@section('title', 'Saved Leads')

@section('content')
<div class="p-4 lg:p-8">
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
    .compact-select { min-width: 120px; max-width: 140px; }
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

    <!-- Compact Filters Row -->
    <div id="filtersContainer" class="filters-container lg:!max-h-none lg:!overflow-visible open">
        <div class="grid grid-cols-1 lg:grid-cols-7 gap-2">
            <select name="country_id" id="country_select" class="search-input select-custom compact-select px-2 py-2 rounded-lg text-sm appearance-none cursor-pointer">
                <option value="">Country</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" {{ $countryId == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                @endforeach
            </select>

            <select name="state_id" id="state_select" class="search-input select-custom compact-select px-2 py-2 rounded-lg text-sm appearance-none cursor-pointer" disabled>
                <option value="">State</option>
            </select>

            <select name="city_id" id="city_select" class="search-input select-custom compact-select px-2 py-2 rounded-lg text-sm appearance-none cursor-pointer" disabled>
                <option value="">City</option>
            </select>

            <select name="status" class="search-input select-custom compact-select px-2 py-2 rounded-lg text-sm appearance-none cursor-pointer">
                <option value="">Status</option>
                <option value="not_contacted" {{ $status == 'not_contacted' ? 'selected' : '' }}>New</option>
                <option value="contacted" {{ $status == 'contacted' ? 'selected' : '' }}>Contacted</option>
                <option value="responded" {{ $status == 'responded' ? 'selected' : '' }}>Responded</option>
                <option value="converted" {{ $status == 'converted' ? 'selected' : '' }}>Converted</option>
                <option value="closed" {{ $status == 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
            
            <select name="rating" class="search-input select-custom compact-select px-2 py-2 rounded-lg text-sm appearance-none cursor-pointer">
                <option value="">Rating</option>
                <option value="4.5" {{ $rating == '4.5' ? 'selected' : '' }}>4.5+</option>
                <option value="4.0" {{ $rating == '4.0' ? 'selected' : '' }}>4.0+</option>
                <option value="3.5" {{ $rating == '3.5' ? 'selected' : '' }}>3.5+</option>
                <option value="3.0" {{ $rating == '3.0' ? 'selected' : '' }}>3.0+</option>
            </select>
            
            <select name="last_review" class="search-input select-custom compact-select px-2 py-2 rounded-lg text-sm appearance-none cursor-pointer">
                <option value="">Review</option>
                <option value="1-day" {{ $lastReview == '1-day' ? 'selected' : '' }}>1 day</option>
                <option value="1-week" {{ $lastReview == '1-week' ? 'selected' : '' }}>1 week</option>
                <option value="1-month" {{ $lastReview == '1-month' ? 'selected' : '' }}>1 month</option>
                <option value="3-months" {{ $lastReview == '3-months' ? 'selected' : '' }}>3 months</option>
                <option value="6-months" {{ $lastReview == '6-months' ? 'selected' : '' }}>6 months</option>
            </select>

            <select name="reviews_count" class="search-input select-custom compact-select px-2 py-2 rounded-lg text-sm appearance-none cursor-pointer">
                <option value="">Reviews #</option>
                <option value="lt30" {{ ($reviewsCount ?? '') == 'lt30' ? 'selected' : '' }}>< 30</option>
                <option value="lt50" {{ ($reviewsCount ?? '') == 'lt50' ? 'selected' : '' }}>< 50</option>
                <option value="lt100" {{ ($reviewsCount ?? '') == 'lt100' ? 'selected' : '' }}>< 100</option>
                <option value="gte100" {{ ($reviewsCount ?? '') == 'gte100' ? 'selected' : '' }}>100+</option>
            </select>
        </div>
        
        <!-- Mobile Buttons -->
        <div class="flex gap-2 mt-3 lg:hidden">
            <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg text-sm font-medium flex-1">Search</button>
            <a href="{{ route('user.leads') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm text-center border">Clear</a>
        </div>
    </div>
</form>


    @if($leads->count() > 0)
        <!-- Bulk Actions Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 text-primary-600 rounded border-gray-300">
                        <label for="selectAll" class="text-sm text-gray-700">Select All</label>
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
                            Delete Selected
                        </button>
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
                            <th class="text-left px-6 py-4">
                                <span class="text-sm font-semibold text-gray-700">Select</span>
                            </th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Business</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Contact</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Location</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Rating</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Status</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($leads as $lead)
                            <tr class="hover:bg-gray-50 cursor-pointer lead-row" data-lead-id="{{ $lead->id }}">
                                <td class="px-6 py-4">
                                    <input type="checkbox" 
                                           class="w-4 h-4 text-primary-600 rounded border-gray-300 lead-checkbox" 
                                           value="{{ $lead->id }}"
                                           onclick="event.stopPropagation()">
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">{{ $lead->name }}</div>
                                        <div class="text-xs text-orange-600 font-medium">{{ $lead->category ?? 'Business' }}</div>
                                    </div>
                                </td>
                                
                               <td class="px-6 py-4 max-w-xs">
                                <div class="space-y-1 truncate">
                                    @if($lead->phone)
                                        <div class="text-sm text-gray-600">
                                            <i class="fas fa-phone w-4"></i> {{ $lead->phone }}
                                        </div>
                                    @endif
                                    @if($lead->email)
                                        <div class="text-sm text-gray-600">
                                            <i class="fas fa-envelope w-4"></i> {{ $lead->email }}
                                        </div>
                                    @endif
                                    @if($lead->website)
                                        <div class="text-sm text-gray-600">
                                            <i class="fas fa-globe w-4"></i> 
                                            <a href="{{ $lead->website }}" target="_blank" class="text-blue-600 hover:text-blue-800 truncate">
                                                {{ str_replace(['http://', 'https://'], '', $lead->website) }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </td>

                                
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600">
                                        {{ $lead->search_location }}
                                    </div>

                                    <!-- <div class="text-sm text-gray-600">
                                        {{ $lead->city ? $lead->city . ', ' : '' }}{{ $lead->country }}
                                    </div>
                                    @if($lead->address)
                                        <div class="text-xs text-gray-500">{{ Str::limit($lead->address, 50) }}</div>
                                    @endif -->
                                </td>
                                
                              <td class="px-6 py-4">
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
                // Get latest review date from reviews_sample
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
                                

                               


                                <td class="px-6 py-4">
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
                                
                                <td class="px-6 py-4">
                                    <button class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1 rounded text-xs font-medium view-btn" 
                                            onclick="event.stopPropagation(); openLeadDetails({{ $lead->id }})">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </button>
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
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ $leads->firstItem() }}</span> to 
                        <span class="font-medium">{{ $leads->lastItem() }}</span> of 
                        <span class="font-medium">{{ $leads->total() }}</span> results
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
    if (lead.social_links && Array.isArray(lead.social_links) && lead.social_links.length > 0) {
        socialLinksHtml = lead.social_links.map(link => {
            let icon = 'fas fa-link';
            let color = 'text-gray-600';
            
            if (link.includes('facebook.com')) {
                icon = 'fab fa-facebook';
                color = 'text-blue-600';
            } else if (link.includes('instagram.com')) {
                icon = 'fab fa-instagram';
                color = 'text-pink-600';
            } else if (link.includes('linkedin.com')) {
                icon = 'fab fa-linkedin';
                color = 'text-blue-700';
            } else if (link.includes('youtube.com')) {
                icon = 'fab fa-youtube';
                color = 'text-red-600';
            }
            
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



document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('country_select');
    const stateSelect = document.getElementById('state_select');
    const citySelect = document.getElementById('city_select');
    
    // Get the current selected values from the server
    const selectedCountryId = "{{ $countryId ?? '' }}";
    const selectedStateId = "{{ $stateId ?? '' }}";
    const selectedCityId = "{{ $cityId ?? '' }}";
    const baseUrl = "{{ url('/') }}";

    // Debug info
    console.log('Debug Info:', {
        selectedCountryId,
        selectedStateId, 
        selectedCityId
    });

    // Set initial values
    if (selectedCountryId) {
        countrySelect.value = selectedCountryId;
        loadStates(selectedCountryId, selectedStateId);
    }

    // Event listeners
    countrySelect.addEventListener('change', function() {
        resetSelect(stateSelect, 'State');
        resetSelect(citySelect, 'City');
        if (this.value) {
            loadStates(this.value);
        }
    });

    stateSelect.addEventListener('change', function() {
        resetSelect(citySelect, 'City');
        if (this.value) {
            loadCities(this.value);
        }
    });

    function resetSelect(select, defaultText) {
        select.innerHTML = `<option value="">${defaultText}</option>`;
        select.disabled = true;
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
                    stateSelect.add(option);
                });
                stateSelect.disabled = false;
                
                // Load cities if state is selected
                if (selectedStateId) {
                    loadCities(selectedStateId, selectedCityId);
                }
            })
            .catch(error => {
                console.error('Error loading states:', error);
                stateSelect.innerHTML = '<option value="">Error loading states</option>';
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
                    citySelect.add(option);
                });
                citySelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading cities:', error);
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            });
    }
});

// Mobile filters toggle
function toggleFilters() {
    const filters = document.getElementById('filtersContainer');
    filters.classList.toggle('open');
}
</script>
@endpush