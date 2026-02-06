@extends('layouts.admin')

@section('title', 'System Overview Report')

@section('content')
<main class="p-4 lg:p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">System Overview Report</h1>
        <p class="text-sm text-gray-600 mt-1">Comprehensive system performance metrics</p>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Users</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_users']) }}</p>
                    <p class="text-xs {{ $growthRates['users'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                        <i class="fas fa-arrow-{{ $growthRates['users'] >= 0 ? 'up' : 'down' }}"></i>
                        {{ abs($growthRates['users']) }}% vs last month
                    </p>
                </div>
                <div class="bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Revenue</p>
                    <p class="text-3xl font-bold text-gray-800">PKR {{ number_format($stats['total_revenue'], 0) }}</p>
                    <p class="text-xs {{ $growthRates['revenue'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                        <i class="fas fa-arrow-{{ $growthRates['revenue'] >= 0 ? 'up' : 'down' }}"></i>
                        {{ abs($growthRates['revenue']) }}% vs last month
                    </p>
                </div>
                <div class="bg-green-100 rounded-lg p-3">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Searches</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_searches']) }}</p>
                    <p class="text-xs {{ $growthRates['searches'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                        <i class="fas fa-arrow-{{ $growthRates['searches'] >= 0 ? 'up' : 'down' }}"></i>
                        {{ abs($growthRates['searches']) }}% vs last month
                    </p>
                </div>
                <div class="bg-orange-100 rounded-lg p-3">
                    <i class="fas fa-search text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Leads</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_leads']) }}</p>
                    <p class="text-xs text-gray-500 mt-1">All time</p>
                </div>
                <div class="bg-purple-100 rounded-lg p-3">
                    <i class="fas fa-user-friends text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Active Subscriptions</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['active_subscriptions']) }}</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-2">
                    <i class="fas fa-crown text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Pending Payments</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['pending_payments']) }}</p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-2">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Packages</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_packages']) }}</p>
                </div>
                <div class="bg-green-50 rounded-lg p-2">
                    <i class="fas fa-box text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Active Packages</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['active_packages']) }}</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-2">
                    <i class="fas fa-check-circle text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trends Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">12-Month Trends</h3>
        <div class="h-96">
            <canvas id="trendsChart"></canvas>
        </div>
    </div>

    <!-- Monthly Data Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Monthly Breakdown</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">New Users</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Searches</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($monthlyTrends as $trend)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $trend['month'] }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ number_format($trend['users']) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-green-600">PKR {{ number_format($trend['revenue'], 0) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ number_format($trend['searches']) }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</main>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('trendsChart').getContext('2d');

    const monthlyData = @json($monthlyTrends);
    const labels = monthlyData.map(d => d.month);
    const users = monthlyData.map(d => d.users);
    const revenue = monthlyData.map(d => d.revenue);
    const searches = monthlyData.map(d => d.searches);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'New Users',
                    data: users,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Revenue (PKR)',
                    data: revenue,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                },
                {
                    label: 'Searches',
                    data: searches,
                    borderColor: 'rgb(249, 115, 22)',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
