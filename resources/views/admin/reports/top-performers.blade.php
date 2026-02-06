@extends('layouts.admin')

@section('title', 'Top Performers Report')

@section('content')
<main class="p-4 lg:p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Top Performers Report</h1>
        <p class="text-sm text-gray-600 mt-1">Identify and reward top performing users</p>
    </div>

    <!-- Top by Revenue -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Top Users by Revenue</h3>
        </div>
        <div class="overflow-x-auto">
            @if($topByRevenue->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Paid</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($topByRevenue as $index => $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($index === 0)
                                    <i class="fas fa-trophy text-yellow-500 text-lg"></i>
                                @elseif($index === 1)
                                    <i class="fas fa-medal text-gray-400 text-lg"></i>
                                @elseif($index === 2)
                                    <i class="fas fa-award text-orange-400 text-lg"></i>
                                @else
                                    <span class="text-sm">#{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    @if($user->avatar)
                                        <img src="{{ asset('public/' . $user->avatar) }}" class="w-8 h-8 rounded-full mr-3">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-xs mr-3">
                                            {{ strtoupper(substr($user->first_name ?? $user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $user->first_name ? $user->first_name . ' ' . $user->last_name : $user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-green-600">PKR {{ number_format($user->total_paid ?? 0, 0) }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-12 text-center"><p class="text-gray-500">No data available</p></div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Top by Leads -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Top by Leads Saved</h3>
            </div>
            <div class="p-6 space-y-4">
                @forelse($topByLeads->take(5) as $index => $user)
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-sm font-medium text-gray-600 mr-3">#{{ $index + 1 }}</span>
                        <div class="text-sm text-gray-900">{{ $user->first_name ?? $user->name }}</div>
                    </div>
                    <div class="text-sm font-bold text-blue-600">{{ number_format($user->saved_leads_count) }}</div>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center">No data available</p>
                @endforelse
            </div>
        </div>

        <!-- Top by Searches -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Top by Searches</h3>
            </div>
            <div class="p-6 space-y-4">
                @forelse($topBySearches->take(5) as $index => $user)
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-sm font-medium text-gray-600 mr-3">#{{ $index + 1 }}</span>
                        <div class="text-sm text-gray-900">{{ $user->first_name ?? $user->name }}</div>
                    </div>
                    <div class="text-sm font-bold text-green-600">{{ number_format($user->search_histories_count) }}</div>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center">No data available</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Most Active This Month -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mt-8">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Most Active This Month</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @forelse($mostActiveThisMonth->take(5) as $user)
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    @if($user->avatar)
                        <img src="{{ asset('public/' . $user->avatar) }}" class="w-12 h-12 rounded-full mx-auto mb-2">
                    @else
                        <div class="w-12 h-12 rounded-full bg-primary-600 flex items-center justify-center text-white mx-auto mb-2">
                            {{ strtoupper(substr($user->first_name ?? $user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="text-xs font-medium text-gray-900 truncate">{{ $user->first_name ?? $user->name }}</div>
                    <div class="text-xs text-gray-500">{{ $user->search_histories_count }} searches</div>
                </div>
                @empty
                <div class="col-span-full text-center py-4"><p class="text-sm text-gray-500">No data available</p></div>
                @endforelse
            </div>
        </div>
    </div>
</main>
@endsection
