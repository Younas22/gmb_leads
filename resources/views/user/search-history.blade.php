@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')
        <!-- Search Form -->
        <div class="p-4 lg:p-8">
            <!-- Filters -->
            <form method="GET" action="{{ route('user.search-history') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-6">
                <!-- User Filter (Company Only) -->
                @if(auth()->user()->isCompany() || auth()->user()->isTeamMember())
                <div class="mb-4 pb-4 border-b border-gray-200">
                    <x-user-filter :selectedUserId="$selectedUserId ?? null" />
                </div>
                @endif

                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                        <select name="date_range" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm">
                            <option value="7" {{ request('date_range') == '7' ? 'selected' : '' }}>Last 7 days</option>
                            <option value="30" {{ request('date_range') == '30' ? 'selected' : '' }}>Last 30 days</option>
                            <option value="90" {{ request('date_range') == '90' ? 'selected' : '' }}>Last 90 days</option>
                            <option value="all" {{ request('date_range') == 'all' ? 'selected' : '' }}>All time</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search Query</label>
                        <input type="text" name="query" value="{{ request('query') }}" placeholder="Filter by query..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm">
                    </div>
                    <div class="flex-1 min-w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input type="text" name="location" value="{{ request('location') }}" placeholder="Filter by location..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm">
                    </div>
                    <div class="min-w-32">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm">
                            <option value="">All</option>
                            <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    @if(request()->hasAny(['date_range', 'query', 'location', 'status']))
                        <a href="{{ route('user.search-history') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    @endif
                </div>
            </form>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 bg-primary-100 rounded-lg">
                            <i class="fas fa-search text-primary-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Searches</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_searches'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Successful</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['successful_searches'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 bg-orange-100 rounded-lg">
                            <i class="fas fa-chart-bar text-orange-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Avg Results</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['avg_results'] ?? 0, 1) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <i class="fas fa-fire text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Top Query</p>
                            <p class="text-lg font-bold text-gray-900">{{ $stats['top_query'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Section -->
            <!-- <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 mb-6">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-600">Export your search history data</p>
                    <a href="{{ route('user.export-search-history') }}" class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-download mr-2"></i>
                        Export CSV
                    </a>
                </div>
            </div> -->

            <!-- Search History Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Searches</h3>
                </div>
                
                <div class="space-y-0">
                    @forelse($histories as $history)
                        <div class="p-6 {{ !$loop->last ? 'border-b border-gray-100' : '' }} hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4 mb-2">
                                        <h4 class="text-lg font-semibold text-gray-800">
                                            "{{ $history->query }}" 
                                            @if($history->location)
                                                in {{ $history->location }}
                                            @endif
                                        </h4>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium 
                                            {{ $history->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            <i class="fas {{ $history->status === 'success' ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                            {{ ucfirst($history->status) }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600">
                                        <div><strong>Results:</strong> {{ $history->results_count ?? 0 }} businesses</div>
                                        <div><strong>Time:</strong> {{ $history->created_at->diffForHumans() }}</div>
                                        <div><strong>API:</strong> {{ $history->api_used ?? 'Google Maps' }}</div>
                                        <div><strong>Response:</strong> {{ $history->response_time ? number_format($history->response_time, 1) . 's' : 'N/A' }}</div>
                                    </div>
                                    @if($history->error_message && $history->status === 'failed')
                                        <div class="mt-2 text-sm text-red-600">
                                            <strong>Error:</strong> {{ $history->error_message }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex space-x-2">
                                    <form method="POST" action="{{ route('user.rerun-search') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="query" value="{{ $history->query }}">
                                        <input type="hidden" name="location" value="{{ $history->location }}">
                                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1 rounded text-xs">
                                            <i class="fas fa-redo mr-1"></i>Re-run
                                        </button>
                                    </form>
                                    @if($history->results_data)
                                        <a href="{{ route('user.leads', ['search' => $history->query, 'search_location' => $history->location]) }}" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                    @endif
                                    <!-- <form method="POST" action="{{ route('user.delete-search-history', $history->id) }}" class="inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this search history?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </form> -->
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <div class="text-gray-400 text-6xl mb-4">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No search history found</h3>
                            <p class="text-gray-500 mb-4">You haven't performed any searches yet or no results match your filters.</p>
                            <a href="{{ route('user.dashboard') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg">
                                Start Searching
                            </a>
                        </div>
                    @endforelse
                </div>
                
                <!-- Pagination -->
                @if($histories->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ $histories->firstItem() }}</span> 
                                to <span class="font-medium">{{ $histories->lastItem() }}</span> 
                                of <span class="font-medium">{{ $histories->total() }}</span> results
                            </div>
                            <div class="flex space-x-2">
                                @if ($histories->onFirstPage())
                                    <span class="px-3 py-1 border border-gray-300 rounded text-sm text-gray-500 cursor-not-allowed">
                                        Previous
                                    </span>
                                @else
                                    <a href="{{ $histories->previousPageUrl() }}" class="px-3 py-1 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50">
                                        Previous
                                    </a>
                                @endif

                                @foreach ($histories->getUrlRange(1, $histories->lastPage()) as $page => $url)
                                    @if ($page == $histories->currentPage())
                                        <span class="px-3 py-1 bg-primary-600 text-white rounded text-sm">{{ $page }}</span>
                                    @elseif ($page == 1 || $page == $histories->lastPage() || abs($page - $histories->currentPage()) <= 2)
                                        <a href="{{ $url }}" class="px-3 py-1 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50">{{ $page }}</a>
                                    @elseif ($page == 2 || $page == $histories->lastPage() - 1)
                                        <span class="px-3 py-1 text-sm text-gray-500">...</span>
                                    @endif
                                @endforeach

                                @if ($histories->hasMorePages())
                                    <a href="{{ $histories->nextPageUrl() }}" class="px-3 py-1 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50">
                                        Next
                                    </a>
                                @else
                                    <span class="px-3 py-1 border border-gray-300 rounded text-sm text-gray-500 cursor-not-allowed">
                                        Next
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
@endsection