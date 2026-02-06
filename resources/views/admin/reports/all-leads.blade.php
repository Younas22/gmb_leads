@extends('layouts.admin')

@section('title', 'All Users Leads Report')

@section('content')
<div class="p-6 lg:p-8">
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-database text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Leads</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalLeads) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-calendar-alt text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">This Month</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($leadsThisMonth) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Users with Leads</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalUsersWithLeads) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-lg">
                    <i class="fas fa-trophy text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Most Active</p>
                    @if($mostActiveUser)
                        <p class="text-lg font-bold text-gray-900">{{ $mostActiveUser->first_name ?? $mostActiveUser->name }}</p>
                        <p class="text-xs text-gray-500">{{ number_format($mostActiveUser->saved_leads_count) }} leads</p>
                    @else
                        <p class="text-lg font-bold text-gray-900">N/A</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100">
        <form method="GET" action="{{ route('admin.reports.all-leads') }}" id="filterForm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-filter mr-2 text-primary-600"></i>Filters
                </h3>
                <a href="{{ route('admin.reports.all-leads') }}" class="text-sm text-gray-600 hover:text-primary-600 transition-colors">
                    <i class="fas fa-redo mr-1"></i>Reset All
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- User Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                    <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->first_name ? $user->first_name . ' ' . $user->last_name : $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <!-- Month Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                    <input type="month" name="month" value="{{ request('month') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <!-- Country -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                    <select name="country_id" id="country_select"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">All Countries</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ request('country_id') == $country->id ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- State -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                    <select name="state_id" id="state_select"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            disabled>
                        <option value="">Select State</option>
                    </select>
                </div>

                <!-- City -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <select name="city_id" id="city_select"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            disabled>
                        <option value="">Select City</option>
                    </select>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <input type="text" name="category" value="{{ request('category') }}"
                           placeholder="e.g. Restaurant, Hotel"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <!-- Contact Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact Status</label>
                    <select name="contact_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">All Statuses</option>
                        <option value="not_contacted" {{ request('contact_status') == 'not_contacted' ? 'selected' : '' }}>Not Contacted</option>
                        <option value="contacted" {{ request('contact_status') == 'contacted' ? 'selected' : '' }}>Contacted</option>
                        <option value="responded" {{ request('contact_status') == 'responded' ? 'selected' : '' }}>Responded</option>
                        <option value="converted" {{ request('contact_status') == 'converted' ? 'selected' : '' }}>Converted</option>
                        <option value="closed" {{ request('contact_status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by name, phone, email, or address..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <!-- Submit Button -->
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        <i class="fas fa-search mr-2"></i>Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    @if($leads->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
                    All Leads ({{ number_format($leads->total()) }})
                </h3>
                <a href="{{ route('admin.reports.export.all-leads', request()->all()) }}"
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                    <i class="fas fa-file-excel"></i>
                    <span>Export to Excel</span>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">User</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Contact</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Location</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Category</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Rating</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Social Media</th>
                            <th class="text-left px-6 py-4 text-sm font-semibold text-gray-700">Date Saved</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($leads as $lead)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        @if($lead->user)
                                            @if($lead->user->avatar)
                                                <img src="{{ asset('public/' . $lead->user->avatar) }}"
                                                     alt="{{ $lead->user->first_name ?? $lead->user->name }}"
                                                     class="w-10 h-10 rounded-full object-cover object-center flex-shrink-0 border-2 border-gray-200">
                                            @else
                                                <div class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-semibold flex-shrink-0 border-2 border-gray-200">
                                                    {{ strtoupper(substr($lead->user->first_name ?? $lead->user->name, 0, 1)) }}{{ strtoupper(substr($lead->user->last_name ?? '', 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $lead->user->first_name ? $lead->user->first_name . ' ' . $lead->user->last_name : $lead->user->name }}
                                                </div>
                                                <div class="text-xs text-gray-500">{{ $lead->user->email }}</div>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-500">N/A</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        @if($lead->phone)
                                            <div class="text-sm text-gray-600">
                                                <i class="fas fa-phone w-4 text-gray-400"></i> {{ $lead->phone }}
                                            </div>
                                        @endif
                                        @if($lead->email)
                                            <div class="text-sm text-gray-600">
                                                <i class="fas fa-envelope w-4 text-gray-400"></i> {{ $lead->email }}
                                            </div>
                                        @endif
                                        @if($lead->website)
                                            <div class="mt-2">
                                                <a href="{{ $lead->website }}" target="_blank"
                                                   class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors">
                                                    <i class="fas fa-globe mr-1.5"></i>Website
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600">
                                        @php
                                            $locationParts = [];
                                            if($lead->cityRelation) $locationParts[] = $lead->cityRelation->name;
                                            if($lead->stateRelation) $locationParts[] = $lead->stateRelation->name;
                                            if($lead->countryRelation) $locationParts[] = $lead->countryRelation->name;
                                        @endphp
                                        {{ implode(', ', $locationParts) ?: 'N/A' }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded-full">
                                        {{ $lead->category ?? 'N/A' }}
                                    </span>
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
                                        $socialLinks = $lead->social_links ? json_decode($lead->social_links, true) : [];
                                    @endphp

                                    @if(!empty($socialLinks) && is_array($socialLinks))
                                        <div class="flex space-x-2">
                                            @foreach($socialLinks as $link)
                                                @php
                                                    $icon = 'fas fa-link';
                                                    $color = 'text-gray-600';

                                                    if (strpos($link, 'facebook.com') !== false) {
                                                        $icon = 'fab fa-facebook';
                                                        $color = 'text-blue-600';
                                                    } elseif (strpos($link, 'instagram.com') !== false) {
                                                        $icon = 'fab fa-instagram';
                                                        $color = 'text-pink-600';
                                                    } elseif (strpos($link, 'linkedin.com') !== false) {
                                                        $icon = 'fab fa-linkedin';
                                                        $color = 'text-blue-700';
                                                    } elseif (strpos($link, 'youtube.com') !== false) {
                                                        $icon = 'fab fa-youtube';
                                                        $color = 'text-red-600';
                                                    } elseif (strpos($link, 'twitter.com') !== false || strpos($link, 'x.com') !== false) {
                                                        $icon = 'fab fa-twitter';
                                                        $color = 'text-blue-400';
                                                    } elseif (strpos($link, 'pinterest.com') !== false || strpos($link, 'pinterest.') !== false) {
                                                        $icon = 'fab fa-pinterest';
                                                        $color = 'text-red-600';
                                                    } elseif (strpos($link, 'threads.net') !== false) {
                                                        $icon = 'fab fa-threads';
                                                        $color = 'text-gray-800';
                                                    } elseif (strpos($link, 'tiktok.com') !== false) {
                                                        $icon = 'fab fa-tiktok';
                                                        $color = 'text-gray-900';
                                                    } elseif (strpos($link, 'snapchat.com') !== false) {
                                                        $icon = 'fab fa-snapchat';
                                                        $color = 'text-yellow-400';
                                                    }
                                                @endphp

                                                <a href="{{ $link }}" target="_blank" class="{{ $color }} hover:opacity-80 transition-opacity" title="{{ $link }}">
                                                    <i class="{{ $icon }} text-lg"></i>
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400">N/A</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600">{{ $lead->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $lead->created_at->format('h:i A') }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ $leads->firstItem() }}</span> to
                        <span class="font-medium">{{ $leads->lastItem() }}</span> of
                        <span class="font-medium">{{ $leads->total() }}</span> results
                    </div>
                    {{ $leads->appends(request()->all())->links('pagination::tailwind') }}
                </div>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-sm p-12 border border-gray-100 text-center">
            <div class="max-w-md mx-auto">
                <div class="bg-gray-100 rounded-full p-6 inline-block mb-4">
                    <i class="fas fa-database text-gray-400 text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">No Leads Found</h3>
                <p class="text-gray-600 mb-6">
                    @if(request()->hasAny(['user_id', 'start_date', 'end_date', 'month', 'category', 'country_id', 'state_id', 'city_id', 'contact_status', 'search']))
                        No leads match your current filters. Try adjusting your search criteria.
                    @else
                        There are no leads in the system yet. Users haven't saved any leads.
                    @endif
                </p>
                @if(request()->hasAny(['user_id', 'start_date', 'end_date', 'month', 'category', 'country_id', 'state_id', 'city_id', 'contact_status', 'search']))
                    <a href="{{ route('admin.reports.all-leads') }}"
                       class="inline-flex items-center bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-redo mr-2"></i>Clear All Filters
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('country_select');
    const stateSelect = document.getElementById('state_select');
    const citySelect = document.getElementById('city_select');

    const selectedCountryId = "{{ request('country_id', '') }}";
    const selectedStateId = "{{ request('state_id', '') }}";
    const selectedCityId = "{{ request('city_id', '') }}";
    const baseUrl = "{{ url('/') }}";

    // Load states if country is selected
    if (selectedCountryId) {
        loadStates(selectedCountryId, selectedStateId);
    }

    countrySelect.addEventListener('change', function() {
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';
        stateSelect.disabled = true;
        citySelect.disabled = true;

        if (this.value) {
            loadStates(this.value);
        }
    });

    stateSelect.addEventListener('change', function() {
        citySelect.innerHTML = '<option value="">Select City</option>';
        citySelect.disabled = true;

        if (this.value) {
            loadCities(this.value);
        }
    });

    function loadStates(countryId, selectedStateId = null) {
        fetch(`${baseUrl}/user/api/states/${countryId}`)
            .then(res => res.json())
            .then(states => {
                stateSelect.innerHTML = '<option value="">Select State</option>';
                states.forEach(state => {
                    const option = new Option(state.name, state.id, false, state.id == selectedStateId);
                    stateSelect.add(option);
                });
                stateSelect.disabled = false;

                if (selectedStateId) {
                    loadCities(selectedStateId, selectedCityId);
                }
            })
            .catch(error => console.error('Error loading states:', error));
    }

    function loadCities(stateId, selectedCityId = null) {
        fetch(`${baseUrl}/user/api/cities/${stateId}`)
            .then(res => res.json())
            .then(cities => {
                citySelect.innerHTML = '<option value="">Select City</option>';
                cities.forEach(city => {
                    const option = new Option(city.name, city.id, false, city.id == selectedCityId);
                    citySelect.add(option);
                });
                citySelect.disabled = false;
            })
            .catch(error => console.error('Error loading cities:', error));
    }
});
</script>
@endpush
