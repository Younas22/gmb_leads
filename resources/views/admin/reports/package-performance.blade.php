@extends('layouts.admin')

@section('title', 'Package Performance Report')

@section('content')
<main class="p-4 lg:p-8">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Package Performance Report</h1>
            <p class="text-sm text-gray-600 mt-1">Analyze package popularity and revenue</p>
        </div>
        <a href="{{ route('admin.reports.export.package-performance') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-file-excel mr-2"></i>
            Export to Excel
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Packages</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($totalPackages) }}</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-box text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Active Packages</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($activePackages) }}</p>
                </div>
                <div class="bg-green-100 rounded-lg p-3">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Subscriptions</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($totalSubscriptions) }}</p>
                </div>
                <div class="bg-orange-100 rounded-lg p-3">
                    <i class="fas fa-users text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Most Popular</p>
                    <p class="text-lg font-bold text-gray-800">{{ $mostPopularPackage->name ?? 'N/A' }}</p>
                </div>
                <div class="bg-purple-100 rounded-lg p-3">
                    <i class="fas fa-trophy text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Package Distribution</h3>
        <div class="h-80">
            <canvas id="packageChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Package Performance Details</h3>
        </div>
        <div class="overflow-x-auto">
            @if($packageData->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscribers</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Market Share</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($packageData as $package)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $package['name'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ number_format($package['count']) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-2 mr-3" style="max-width: 100px;">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $package['percentage'] }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ $package['percentage'] }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-green-600">PKR {{ number_format($package['revenue'], 0) }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <i class="fas fa-box text-gray-400 text-2xl"></i>
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
    const ctx = document.getElementById('packageChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($labels),
            datasets: [{
                data: @json($counts),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'right' }}
        }
    });
});
</script>
@endpush
@endsection
