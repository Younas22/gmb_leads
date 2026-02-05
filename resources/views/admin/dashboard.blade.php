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
                            <p class="text-3xl font-bold text-gray-800">$47,892</p>
                            <p class="text-xs text-green-600 mt-1">
                                <i class="fas fa-arrow-up mr-1"></i>+15.3% from last month
                            </p>
                        </div>
                        <div class="bg-green-100 rounded-lg p-3">
                            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
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

                <!-- API Usage -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">API Calls (24h)</p>
                            <p class="text-3xl font-bold text-gray-800">127.4K</p>
                            <p class="text-xs text-yellow-600 mt-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i>85% of daily limit
                            </p>
                        </div>
                        <div class="bg-blue-100 rounded-lg p-3">
                            <i class="fas fa-code text-blue-600 text-xl"></i>
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
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-green-100 rounded-lg p-2">
                                        <i class="fas fa-gift text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Free Plan</p>
                                        <p class="text-xs text-gray-500">1,847 users</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-800">64.9%</p>
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mt-1">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: 65%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-orange-100 rounded-lg p-2">
                                        <i class="fas fa-star text-orange-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Monthly Pro</p>
                                        <p class="text-xs text-gray-500">734 users</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-800">25.8%</p>
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mt-1">
                                        <div class="bg-orange-500 h-2 rounded-full" style="width: 26%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-primary-100 rounded-lg p-2">
                                        <i class="fas fa-crown text-primary-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Yearly Pro</p>
                                        <p class="text-xs text-gray-500">266 users</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-800">9.3%</p>
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mt-1">
                                        <div class="bg-primary-500 h-2 rounded-full" style="width: 9%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & System Alerts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- System Alerts -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">System Alerts</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3 p-3 bg-red-50 rounded-lg border border-red-200">
                                <div class="bg-red-100 rounded-lg p-2 mt-1">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-red-800">High API Usage Detected</p>
                                    <p class="text-xs text-red-600 mt-1">Google Places API approaching daily limit (85% used)</p>
                                    <p class="text-xs text-red-500 mt-2">2 minutes ago</p>
                                </div>
                                <button class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="flex items-start space-x-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                <div class="bg-yellow-100 rounded-lg p-2 mt-1">
                                    <i class="fas fa-info-circle text-yellow-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-yellow-800">Backup Completed</p>
                                    <p class="text-xs text-yellow-600 mt-1">Daily database backup completed successfully</p>
                                    <p class="text-xs text-yellow-500 mt-2">1 hour ago</p>
                                </div>
                                <button class="text-yellow-600 hover:text-yellow-800">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="flex items-start space-x-3 p-3 bg-green-50 rounded-lg border border-green-200">
                                <div class="bg-green-100 rounded-lg p-2 mt-1">
                                    <i class="fas fa-check-circle text-green-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-green-800">Server Update Complete</p>
                                    <p class="text-xs text-green-600 mt-1">Security patches applied successfully</p>
                                    <p class="text-xs text-green-500 mt-2">3 hours ago</p>
                                </div>
                                <button class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-times"></i>
                                </button>
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
                            
                            <button class="w-full flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>
                                Export Data
                            </button>
                            
                            <button class="w-full flex items-center justify-center px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                <i class="fas fa-cog mr-2"></i>
                                System Settings
                            </button>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">System Resources</h4>
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-600">CPU Usage</span>
                                        <span class="text-gray-800 font-medium">34%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: 34%"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-600">Memory</span>
                                        <span class="text-gray-800 font-medium">67%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-yellow-500 h-2 rounded-full" style="width: 67%"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-600">Storage</span>
                                        <span class="text-gray-800 font-medium">23%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 23%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
