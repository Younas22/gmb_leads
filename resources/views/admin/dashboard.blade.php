@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
       <!-- Dashboard Content -->
        <main class="p-4 lg:p-8">
            <!-- Overview Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Users</p>
                            <p class="text-3xl font-bold text-gray-800">2,847</p>
                            <p class="text-xs text-green-600 mt-1">
                                <i class="fas fa-arrow-up mr-1"></i>+8.2% from last month
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
                            <p class="text-3xl font-bold text-gray-800">1,234</p>
                            <p class="text-xs text-blue-600 mt-1">
                                <i class="fas fa-arrow-up mr-1"></i>+12% conversion rate
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
                            <a href="#" class="text-primary-600 hover:text-primary-700 text-sm font-medium">View all</a>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <img src="https://images.unsplash.com/photo-1494790108755-2616b612b5bc?w=40&h=40&fit=crop&crop=face" alt="User" class="w-10 h-10 rounded-full">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Sarah Johnson</p>
                                        <p class="text-xs text-gray-500">sarah@example.com</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Free Plan
                                    </span>
                                    <p class="text-xs text-gray-400 mt-1">2 min ago</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop&crop=face" alt="User" class="w-10 h-10 rounded-full">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Mike Chen</p>
                                        <p class="text-xs text-gray-500">mike@company.com</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        Pro Plan
                                    </span>
                                    <p class="text-xs text-gray-400 mt-1">15 min ago</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=40&h=40&fit=crop&crop=face" alt="User" class="w-10 h-10 rounded-full">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Emily Davis</p>
                                        <p class="text-xs text-gray-500">emily@startup.io</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                        Yearly Plan
                                    </span>
                                    <p class="text-xs text-gray-400 mt-1">1 hour ago</p>
                                </div>
                            </div>
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
                            <button class="w-full flex items-center justify-center px-4 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                                <i class="fas fa-user-plus mr-2"></i>
                                Add New User
                            </button>
                            
                            <button class="w-full flex items-center justify-center px-4 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                                <i class="fas fa-box mr-2"></i>
                                Create Package
                            </button>
                            
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
        </main>
@endsection
