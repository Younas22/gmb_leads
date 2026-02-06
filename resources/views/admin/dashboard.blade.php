@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
       <!-- Dashboard Content .-->
        <main class="p-4 lg:p-8">
            <!-- Overview Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Users</p>
                            <p class="text-3xl font-bold text-gray-800">{{ \App\Models\User::count() }}</p>
                            <p class="text-xs text-green-600 mt-1">
                                <i class="fas fa-arrow-up mr-1"></i>+{{ \App\Models\User::whereMonth('created_at', now()->month)->count() }} this month
                            </p>
                        </div>
                        <div class="bg-primary-100 rounded-lg p-3">
                            <i class="fas fa-users text-primary-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Monthly Revenue</p>
                            <p class="text-3xl font-bold text-gray-800">PKR {{ number_format($paymentStats['this_month'] ?? 0, 0) }}</p>
                            <p class="text-xs text-blue-600 mt-1">
                                <i class="fas fa-rupee-sign mr-1"></i>Total: PKR {{ number_format($paymentStats['total_revenue'] ?? 0, 0) }}
                            </p>
                        </div>
                        <div class="bg-green-100 rounded-lg p-3">
                            <i class="fas fa-rupee-sign text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Active Subscriptions -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Active Subscriptions</p>
                            <p class="text-3xl font-bold text-gray-800">{{ \App\Models\Subscription::where('status', 'active')->count() }}</p>
                            <p class="text-xs text-blue-600 mt-1">
                                <i class="fas fa-users mr-1"></i>{{ \App\Models\Subscription::count() }} total
                            </p>
                        </div>
                        <div class="bg-orange-100 rounded-lg p-3">
                            <i class="fas fa-crown text-orange-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Search Activity Today -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Searches Today</p>
                            <p class="text-3xl font-bold text-gray-800">{{ number_format($searchStats['searches_today']) }}</p>
                            <p class="text-xs text-blue-600 mt-1">
                                <i class="fas fa-user mr-1"></i>{{ $searchStats['active_users_today'] }} active users
                            </p>
                        </div>
                        <div class="bg-blue-100 rounded-lg p-3">
                            <i class="fas fa-search text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Panels -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Recent Users -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Recent Users</h3>
                            <a href="{{ route('admin.users') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">View all</a>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @php
                                $recentUsers = \App\Models\User::latest()->take(5)->get();
                            @endphp
                            @forelse($recentUsers as $recentUser)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    @if($recentUser->avatar)
                                        <img src="{{ asset('public/' . $recentUser->avatar) }}" alt="User" class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-semibold text-sm">
                                            {{ strtoupper(substr($recentUser->first_name ?? $recentUser->name, 0, 1)) }}{{ strtoupper(substr($recentUser->last_name ?? '', 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">{{ $recentUser->first_name ? $recentUser->first_name . ' ' . $recentUser->last_name : $recentUser->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $recentUser->email }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    @if($recentUser->user_type === 'admin')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Admin
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            User
                                        </span>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-1">{{ $recentUser->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-4 text-gray-500">
                                <i class="fas fa-users text-gray-300 text-2xl mb-2"></i>
                                <p class="text-sm">No users yet</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Package Distribution -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Package Distribution</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @forelse($packageStats as $pkg)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-{{ $pkg['color'] }}-100 rounded-lg p-2">
                                        <i class="fas {{ $pkg['icon'] }} text-{{ $pkg['color'] }}-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">{{ $pkg['name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($pkg['count']) }} {{ Str::plural('user', $pkg['count']) }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-800">{{ $pkg['percentage'] }}%</p>
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mt-1">
                                        <div class="bg-{{ $pkg['color'] }}-500 h-2 rounded-full" style="width: {{ $pkg['percentage'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-4 text-gray-500">
                                <i class="fas fa-box text-gray-300 text-2xl mb-2"></i>
                                <p class="text-sm">No packages configured yet</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & System Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Activity Stats -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Today's Activity</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Searches Today -->
                            <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fas fa-search text-blue-600 text-lg"></i>
                                    <span class="text-2xl font-bold text-blue-900">{{ number_format($searchStats['searches_today']) }}</span>
                                </div>
                                <p class="text-sm font-medium text-blue-800">Searches Today</p>
                                <p class="text-xs text-blue-600 mt-1">{{ $searchStats['active_users_today'] }} active users</p>
                            </div>

                            <!-- Leads Saved Today -->
                            <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fas fa-user-plus text-green-600 text-lg"></i>
                                    <span class="text-2xl font-bold text-green-900">{{ number_format($searchStats['leads_today']) }}</span>
                                </div>
                                <p class="text-sm font-medium text-green-800">Leads Saved</p>
                                <p class="text-xs text-green-600 mt-1">Today's total</p>
                            </div>

                            <!-- Average Searches -->
                            <div class="p-4 bg-purple-50 rounded-lg border border-purple-100">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fas fa-chart-line text-purple-600 text-lg"></i>
                                    <span class="text-2xl font-bold text-purple-900">{{ $searchStats['avg_searches_per_user'] }}</span>
                                </div>
                                <p class="text-sm font-medium text-purple-800">Avg Per User</p>
                                <p class="text-xs text-purple-600 mt-1">Active users only</p>
                            </div>
                        </div>

                        <!-- Recent Activity Summary -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="space-y-3">
                                @if($recentFeedback->where('status', 'pending')->count() > 0)
                                <div class="flex items-center justify-between p-2 bg-orange-50 rounded">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-comment text-orange-600"></i>
                                        <span class="text-sm text-orange-800">{{ $recentFeedback->where('status', 'pending')->count() }} pending feedback</span>
                                    </div>
                                    <span class="text-xs text-orange-600">Needs attention</span>
                                </div>
                                @endif

                                @if(($paymentStats['pending_payments'] ?? 0) > 0)
                                <div class="flex items-center justify-between p-2 bg-yellow-50 rounded">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-clock text-yellow-600"></i>
                                        <span class="text-sm text-yellow-800">{{ $paymentStats['pending_payments'] }} pending payments</span>
                                    </div>
                                    <span class="text-xs text-yellow-600">Awaiting approval</span>
                                </div>
                                @endif

                                @if(\App\Models\User::whereMonth('created_at', now()->month)->count() > 0)
                                <div class="flex items-center justify-between p-2 bg-green-50 rounded">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-user-check text-green-600"></i>
                                        <span class="text-sm text-green-800">{{ \App\Models\User::whereMonth('created_at', now()->month)->count() }} new users this month</span>
                                    </div>
                                    <span class="text-xs text-green-600">Growing</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Quick Actions</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <a href="{{ route('admin.users') }}" class="w-full flex items-center justify-center px-4 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                                <i class="fas fa-users mr-2"></i>
                                Manage Users
                            </a>

                            <a href="{{ route('admin.packages.index') }}" class="w-full flex items-center justify-center px-4 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                                <i class="fas fa-box mr-2"></i>
                                Manage Packages
                            </a>

                            <a href="{{ route('admin.subscriptions.index') }}" class="w-full flex items-center justify-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                <i class="fas fa-crown mr-2"></i>
                                Manage Subscriptions
                            </a>

                            <a href="{{ route('admin.payments.index') }}" class="w-full flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-receipt mr-2"></i>
                                Manage Payments
                            </a>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Quick Stats</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-600">Total Revenue</span>
                                    <span class="text-sm font-bold text-gray-800">PKR {{ number_format($paymentStats['total_revenue'] ?? 0, 0) }}</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-600">Active Users</span>
                                    <span class="text-sm font-bold text-gray-800">{{ \App\Models\User::where('status', 'active')->where('user_type', 'user')->count() }}</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-600">Active Subscriptions</span>
                                    <span class="text-sm font-bold text-gray-800">{{ \App\Models\Subscription::where('status', 'active')->count() }}</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-600">Total Leads</span>
                                    <span class="text-sm font-bold text-gray-800">{{ number_format(\App\Models\SavedLead::count()) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History Section -->
            <div class="mt-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Payment History</h3>
                            <div class="flex items-center space-x-3">
                                @if(($paymentStats['pending_payments'] ?? 0) > 0)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ $paymentStats['pending_payments'] }} Pending
                                </span>
                                @endif
                                <a href="{{ route('admin.subscriptions.index') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">View all</a>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($recentPayments as $payment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($payment->user && $payment->user->avatar)
                                                <img src="{{ asset('public/' . $payment->user->avatar) }}" alt="User" class="w-8 h-8 rounded-full object-cover">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-semibold text-xs">
                                                    {{ strtoupper(substr($payment->user->first_name ?? $payment->user->name ?? 'U', 0, 1)) }}
                                                </div>
                                            @endif
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $payment->user ? ($payment->user->first_name ? $payment->user->first_name . ' ' . $payment->user->last_name : $payment->user->name) : 'Deleted User' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $payment->subscription->package->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-500">{{ $payment->subscription->package->billing_type ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($payment->paymentMethod)
                                            <i class="fas {{ $payment->paymentMethod->icon ?? 'fa-money-bill' }} text-gray-400 mr-2"></i>
                                            <span class="text-sm text-gray-900">{{ $payment->paymentMethod->name }}</span>
                                            @else
                                            <span class="text-sm text-gray-500">N/A</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">PKR {{ number_format($payment->amount, 0) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($payment->status === 'completed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 mr-1.5"></span>
                                                Completed
                                            </span>
                                        @elseif($payment->status === 'pending')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 mr-1.5"></span>
                                                Pending
                                            </span>
                                        @elseif($payment->status === 'failed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 mr-1.5"></span>
                                                Failed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ ucfirst($payment->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $payment->created_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $payment->created_at->diffForHumans() }}</div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="bg-gray-100 rounded-full p-3 mb-3">
                                                <i class="fas fa-receipt text-gray-400 text-xl"></i>
                                            </div>
                                            <p class="text-sm text-gray-500">No payments yet</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- User Feedback Section -->
            <div class="mt-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">User Feedback</h3>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                {{ $recentFeedback->where('status', 'pending')->count() }} Pending
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @forelse($recentFeedback as $fb)
                            <div class="flex items-start justify-between p-4 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors">
                                <!-- User Info + Message -->
                                <div class="flex items-start space-x-3 flex-1">
                                    <!-- Avatar -->
                                    @if($fb->user && $fb->user->avatar)
                                        <img src="{{ asset('public/' . $fb->user->avatar) }}" alt="User" class="w-9 h-9 rounded-full object-cover flex-shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-primary-600 flex items-center justify-center text-white font-semibold text-xs flex-shrink-0">
                                            {{ strtoupper(substr($fb->user->first_name ?? $fb->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <!-- User Name + Type Badge -->
                                        <div class="flex items-center space-x-2 flex-wrap">
                                            <p class="text-sm font-medium text-gray-800">
                                                {{ $fb->user ? ($fb->user->first_name ? $fb->user->first_name . ' ' . $fb->user->last_name : $fb->user->name) : 'Unknown' }}
                                            </p>

                                            <!-- Feedback Type Badge -->
                                            @if($fb->feedback_type === 'suggestion')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-lightbulb mr-1"></i> Suggestion
                                                </span>
                                            @elseif($fb->feedback_type === 'bug')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <i class="fas fa-bug mr-1"></i> Bug Report
                                                </span>
                                            @elseif($fb->feedback_type === 'feature')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-plus-circle mr-1"></i> Feature Request
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <i class="fas fa-comment mr-1"></i> General
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Message -->
                                        <p class="text-xs text-gray-600 mt-1 truncate">{{ $fb->message }}</p>
                                    </div>
                                </div>

                                <!-- Right: Rating + Status + Time -->
                                <div class="flex flex-col items-end space-y-1 ml-4 flex-shrink-0">
                                    <!-- Stars -->
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star text-xs {{ $i <= $fb->rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                        @endfor
                                    </div>

                                    <!-- Status Badge -->
                                    @if($fb->status === 'pending')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Pending</span>
                                    @elseif($fb->status === 'reviewed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Reviewed</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Resolved</span>
                                    @endif

                                    <!-- Time -->
                                    <p class="text-xs text-gray-400">{{ $fb->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-6 text-gray-500">
                                <i class="fas fa-comments text-gray-300 text-2xl mb-2"></i>
                                <p class="text-sm">No feedback yet</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </main>
@endsection
