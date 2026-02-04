@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')
<main class="p-4 lg:p-8">
@include('user.welcome-tutorial-modal')
<!-- Welcome Section -->
<div class="mb-8">
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl p-4 sm:p-6 text-white">
        <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex-1 lg:pr-6">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-2">Welcome back, {{ $user->first_name }}!</h1>
                <p class="text-primary-100 text-sm sm:text-base">Your first month is free with unlimited lead searches. Start building your business network today!</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 lg:flex-shrink-0">
                <a href="{{ route('user.tutorials') }}" class="inline-flex items-center justify-center bg-orange-500 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-orange-600 transition-colors text-sm sm:text-base">
                    <i class="fas fa-play-circle mr-2"></i>
                    <span class="hidden sm:inline">Watch </span>Tutorials
                </a>
                <a href="{{ route('user.search') }}" class="inline-flex items-center justify-center bg-white text-primary-600 px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm sm:text-base">
                    <i class="fas fa-search mr-2"></i>
                    <span class="hidden sm:inline">Start </span>Searching
                </a>
                
                <a href="{{ route('user.leads') }}" class="inline-flex items-center justify-center bg-primary-800 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-primary-900 transition-colors text-sm sm:text-base">
                    <i class="fas fa-bookmark mr-2"></i>
                    <span class="hidden sm:inline">View </span>Leads
                </a>
            </div>
        </div>
    </div>
</div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Saved Leads -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Saved Leads</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_leads']) }}</p>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-infinity mr-1"></i>Unlimited this month
                    </p>
                </div>
                <div class="bg-primary-100 rounded-lg p-3">
                    <i class="fas fa-bookmark text-primary-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Searches Today -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Searches Today</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['searches_today']) }}</p>
                    <p class="text-xs text-blue-600 mt-1">
                        <i class="fas fa-rocket mr-1"></i>Free unlimited access
                    </p>
                </div>
                <div class="bg-orange-100 rounded-lg p-3">
                    <i class="fas fa-search text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Contact Rate -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Contacted</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['contacted_leads']) }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $stats['contact_rate'] }}% contact rate
                    </p>
                </div>
                <div class="bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-phone text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Converted</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['converted_leads']) }}</p>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-arrow-up mr-1"></i>{{ $stats['conversion_rate'] }}% success rate
                    </p>
                </div>
                <div class="bg-green-100 rounded-lg p-3">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    @if($stats['total_leads'] == 0)
    <!-- Getting Started Guide -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-sm p-6 border border-blue-100 mb-8">
        <div class="flex items-start space-x-4">
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-rocket text-blue-600 text-xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Get Started with Lead Generation</h3>
                <p class="text-gray-600 mb-4">You're on the free unlimited plan for your first month! Here's how to get started:</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-white p-4 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-2">
                            <div class="bg-blue-100 rounded p-1 mr-2">
                                <i class="fas fa-search text-blue-600 text-sm"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">1. Search</h4>
                        </div>
                        <p class="text-sm text-gray-600">Search for businesses like "restaurants in Dubai" or "travel agencies"</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-2">
                            <div class="bg-green-100 rounded p-1 mr-2">
                                <i class="fas fa-bookmark text-green-600 text-sm"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">2. Save</h4>
                        </div>
                        <p class="text-sm text-gray-600">Save promising leads with contact details and business info</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-2">
                            <div class="bg-orange-100 rounded p-1 mr-2">
                                <i class="fas fa-phone text-orange-600 text-sm"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">3. Contact</h4>
                        </div>
                        <p class="text-sm text-gray-600">Reach out to leads and track your progress</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('user.tutorials') }}" class="inline-flex items-center justify-center bg-orange-500 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-orange-600 transition-colors text-sm sm:text-base">
                        <i class="fas fa-play-circle mr-2"></i>
                        <span class="hidden sm:inline">Watch </span>Tutorials
                    </a>
                    <a href="{{ route('user.search') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-search mr-2"></i>Start First Search
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Leads (2/3 width) -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Leads</h3>
                    <a href="{{ route('user.leads') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">View all ({{ $stats['total_leads'] }})</a>
                </div>
            </div>
            <div class="p-6">
                @if(count($recentLeads) > 0)
                    <div class="space-y-4">
                        @foreach($recentLeads as $lead)
                        <div class="flex items-center space-x-4 p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
                            <div class="bg-primary-100 rounded-lg p-2">
                                <i class="fas fa-building text-primary-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $lead->name }}</p>
                                <p class="text-xs text-gray-500">{{ Str::limit($lead->address, 40) }} @if($lead->rating) • {{ number_format($lead->rating, 1) }} rating @endif @if($lead->phone) • {{ $lead->phone }} @endif</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                    @if($lead->contact_status == 'not_contacted') bg-gray-100 text-gray-800
                                    @elseif($lead->contact_status == 'contacted') bg-blue-100 text-blue-800
                                    @elseif($lead->contact_status == 'responded') bg-yellow-100 text-yellow-800
                                    @elseif($lead->contact_status == 'converted') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $lead->contact_status)) }}
                                </span>
                                <p class="text-xs text-gray-400 mt-1">{{ $lead->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-bookmark text-gray-300 text-4xl mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-500 mb-2">No leads saved yet</h4>
                        <p class="text-gray-400 mb-4">Start searching for businesses to build your lead database</p>
                        <a href="{{ route('user.search') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-search mr-2"></i>Start Searching
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions & Status (1/3 width) -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-bolt text-yellow-600 mr-2"></i>
                    Quick Actions
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('user.search') }}" class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors group">
                        <div class="bg-blue-100 group-hover:bg-blue-200 rounded-lg p-2 mr-3">
                            <i class="fas fa-search text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">New Search</p>
                            <p class="text-xs text-gray-500">Find new business leads</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('user.leads') }}" class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors group">
                        <div class="bg-green-100 group-hover:bg-green-200 rounded-lg p-2 mr-3">
                            <i class="fas fa-bookmark text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Manage Leads</p>
                            <p class="text-xs text-gray-500">View and organize saved leads</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('user.profile') }}" class="flex items-center p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors group">
                        <div class="bg-purple-100 group-hover:bg-purple-200 rounded-lg p-2 mr-3">
                            <i class="fas fa-user text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Profile Settings</p>
                            <p class="text-xs text-gray-500">Update your information</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Account Status -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-crown text-yellow-600 mr-2"></i>
                    Account Status
                </h3>
                <div class="space-y-4">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border border-green-200">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-gift text-green-600 mr-2"></i>
                            <span class="font-medium text-green-800">Free Trial Active</span>
                        </div>
                        <p class="text-sm text-green-700 mb-3">First month free with unlimited leads</p>
                        <div class="bg-white p-2 rounded border">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>Days remaining</span>
                                <span>{{ $stats['trial_days_left'] }} days</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full transition-all" style="width: {{ ($stats['trial_days_left'] / 30) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-2">
                        <p class="text-xs text-gray-500 mb-2">Want to continue after trial?</p>
                        <button class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors w-full">
                            <i class="fas fa-star mr-2"></i>View Plans
                        </button>
                    </div>
                </div>
            </div>

            @if(count($recentSearches) > 0)
            <!-- Recent Searches -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-history text-gray-600 mr-2"></i>
                    Recent Searches
                </h3>
                <div class="space-y-3">
                    @foreach($recentSearches as $search)
                    <div class="flex items-start space-x-3 p-2 hover:bg-gray-50 rounded">
                        <div class="bg-gray-100 rounded p-1 mt-1">
                            <i class="fas fa-search text-gray-600 text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ $search->search_query }}</p>
                            <p class="text-xs text-gray-500">{{ $search->search_location ?? 'No location' }}</p>
                            <p class="text-xs text-gray-400">{{ $search->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</main>

<script>
// Add any interactive functionality here
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh stats every 5 minutes
    setInterval(function() {
        // You can add AJAX call to refresh stats here
        console.log('Auto-refreshing stats...');
    }, 300000);
});
</script>
@endsection