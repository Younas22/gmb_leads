@extends('layouts.admin')

@section('title', 'Leads Report')

@section('content')
<main class="p-4 lg:p-8">
    <!-- Header with Export Button -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Leads Report</h1>
            <p class="text-sm text-gray-600 mt-1">Track lead generation and performance</p>
        </div>
        <a href="{{ route('admin.reports.export.leads') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-file-excel mr-2"></i>
            Export to Excel
        </a>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <form method="GET" action="{{ route('admin.reports.leads') }}" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <select name="range" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="7" {{ $dateRange == '7' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30" {{ $dateRange == '30' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90" {{ $dateRange == '90' ? 'selected' : '' }}>Last 90 Days</option>
                    <option value="all" {{ $dateRange == 'all' ? 'selected' : '' }}>All Time</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full sm:w-auto px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Leads</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($totalLeads) }}</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">This Month</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($thisMonthLeads) }}</p>
                </div>
                <div class="bg-green-100 rounded-lg p-3">
                    <i class="fas fa-chart-line text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Avg Per User</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($averageLeadsPerUser, 1) }}</p>
                </div>
                <div class="bg-orange-100 rounded-lg p-3">
                    <i class="fas fa-user-chart text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Active Users</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($uniqueUsers) }}</p>
                </div>
                <div class="bg-purple-100 rounded-lg p-3">
                    <i class="fas fa-user-friends text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Leads Over Time</h3>
        <div class="h-80">
            <canvas id="leadsChart"></canvas>
        </div>
    </div>

    <!-- Top Users Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Top Users by Leads</h3>
        </div>
        <div class="overflow-x-auto">
            @if($topUsers->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Leads</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($topUsers as $index => $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($index === 0)
                                        <i class="fas fa-trophy text-yellow-500 text-lg"></i>
                                    @elseif($index === 1)
                                        <i class="fas fa-medal text-gray-400 text-lg"></i>
                                    @elseif($index === 2)
                                        <i class="fas fa-award text-orange-400 text-lg"></i>
                                    @else
                                        <span class="text-sm text-gray-600">#{{ $index + 1 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($user->avatar)
                                        <img src="{{ asset('public/' . $user->avatar) }}" alt="User" class="w-8 h-8 rounded-full object-cover mr-3">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-xs font-semibold mr-3">
                                            {{ strtoupper(substr($user->first_name ?? $user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $user->first_name ? $user->first_name . ' ' . $user->last_name : $user->name }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-blue-600">{{ number_format($user->saved_leads_count) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $user->created_at->format('M d, Y') }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <i class="fas fa-users text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500">No data available</p>
                </div>
            @endif
        </div>
    </div>
</main>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('leadsChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($labels),
            datasets: [{
                label: 'Leads Saved',
                data: @json($leadsData),
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endpush
@endsection
