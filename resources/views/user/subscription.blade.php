@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')
        <!-- Search Form -->
        <div class="p-4 lg:p-8">
            <!-- Current Plan Overview -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <h3 class="text-xl font-bold text-gray-800">Current Plan</h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-gift mr-2"></i>Free Plan
                        </span>
                    </div>
                    <button class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-arrow-up mr-2"></i>Upgrade Now
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Monthly Leads</span>
                            <span class="text-sm text-orange-600 font-semibold">425/500</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-orange-500 h-2 rounded-full" style="width: 85%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">75 leads remaining</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Daily Searches</span>
                            <span class="text-sm text-green-600 font-semibold">23/50</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: 46%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">27 searches remaining today</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">API Keys</span>
                            <span class="text-sm text-blue-600 font-semibold">1/1</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Upgrade for unlimited</p>
                    </div>
                </div>
            </div>

            <!-- Pricing Plans -->
            <div class="mb-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Choose Your Plan</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Free Plan -->
                    <div class="bg-white rounded-xl border-2 border-gray-200 p-6">
                        <div class="text-center mb-6">
                            <h4 class="text-xl font-bold text-gray-800 mb-2">Free Plan</h4>
                            <div class="text-3xl font-bold text-gray-800 mb-1">$0</div>
                            <p class="text-sm text-gray-600">Perfect for getting started</p>
                        </div>
                        
                        <ul class="space-y-3 mb-6">
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                500 leads per month
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                50 searches per day
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                1 API key
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Basic support
                            </li>
                            <li class="flex items-center text-sm text-gray-400">
                                <i class="fas fa-times text-gray-400 mr-3"></i>
                                Export to CSV/Excel
                            </li>
                        </ul>
                        
                        <button class="w-full bg-gray-200 text-gray-500 py-2 rounded-lg font-medium cursor-not-allowed">
                            Current Plan
                        </button>
                    </div>

                    <!-- Monthly Pro -->
                    <div class="bg-white rounded-xl border-2 border-orange-500 p-6 relative">
                        <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                            <span class="bg-orange-500 text-white px-4 py-1 rounded-full text-xs font-medium">
                                Most Popular
                            </span>
                        </div>
                        
                        <div class="text-center mb-6">
                            <h4 class="text-xl font-bold text-gray-800 mb-2">Monthly Pro</h4>
                            <div class="text-3xl font-bold text-gray-800 mb-1">$29.99</div>
                            <p class="text-sm text-gray-600">per month</p>
                        </div>
                        
                        <ul class="space-y-3 mb-6">
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Unlimited leads
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Unlimited searches
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Unlimited API keys
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Priority support
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Export to CSV/Excel
                            </li>
                        </ul>
                        
                        <button class="w-full bg-orange-600 hover:bg-orange-700 text-white py-2 rounded-lg font-medium">
                            Upgrade to Pro
                        </button>
                    </div>

                    <!-- Yearly Pro -->
                    <div class="bg-white rounded-xl border-2 border-primary-500 p-6 relative">
                        <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                            <span class="bg-green-500 text-white px-4 py-1 rounded-full text-xs font-medium">
                                Best Value
                            </span>
                        </div>
                        
                        <div class="text-center mb-6">
                            <h4 class="text-xl font-bold text-gray-800 mb-2">Yearly Pro</h4>
                            <div class="text-3xl font-bold text-gray-800 mb-1">$299.99</div>
                            <p class="text-sm text-gray-600">per year</p>
                            <p class="text-xs text-green-600 font-medium">Save $60 (17% off)</p>
                        </div>
                        
                        <ul class="space-y-3 mb-6">
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Unlimited leads
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Unlimited searches
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Unlimited API keys
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Priority support
                            </li>
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                Export to CSV/Excel
                            </li>
                        </ul>
                        
                        <button class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2 rounded-lg font-medium">
                            Get 2 Months Free
                        </button>
                    </div>
                </div>
            </div>

            <!-- Usage Analytics & Billing -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Usage Analytics -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Usage Analytics</h3>
                    
                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600">This Month's Searches</span>
                                <span class="text-lg font-bold text-primary-600">247</span>
                            </div>
                            <p class="text-xs text-gray-500">↑ 23% from last month</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600">Leads Generated</span>
                                <span class="text-lg font-bold text-green-600">425</span>
                            </div>
                            <p class="text-xs text-gray-500">↑ 15% from last month</p>
                        </div>
                        
                        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-calculator text-green-600 mr-2"></i>
                                <span class="text-sm font-medium text-green-800">Yearly Plan Savings</span>
                            </div>
                            <p class="text-lg font-bold text-green-700">Save $60/year</p>
                            <p class="text-xs text-green-600">Equivalent to 2 months free</p>
                        </div>
                    </div>
                </div>

                <!-- Billing Information -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Billing Information</h3>
                        <button class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Payment Method</h4>
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <i class="fab fa-cc-visa text-blue-600 text-xl"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-800">**** **** **** 4242</p>
                                <p class="text-xs text-gray-500">Expires 12/25</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing History -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Billing History</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Aug 15, 2025</p>
                                    <p class="text-xs text-gray-500">Monthly Pro Plan</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-800">$29.99</p>
                                    <button class="text-xs text-primary-600 hover:text-primary-700">
                                        <i class="fas fa-download mr-1"></i>Invoice
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Jul 15, 2025</p>
                                    <p class="text-xs text-gray-500">Monthly Pro Plan</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-800">$29.99</p>
                                    <button class="text-xs text-primary-600 hover:text-primary-700">
                                        <i class="fas fa-download mr-1"></i>Invoice
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Jun 15, 2025</p>
                                    <p class="text-xs text-gray-500">Monthly Pro Plan</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-800">$29.99</p>
                                    <button class="text-xs text-primary-600 hover:text-primary-700">
                                        <i class="fas fa-download mr-1"></i>Invoice
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan Management Actions -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mt-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Plan Management</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button class="flex items-center justify-center px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium">
                        <i class="fas fa-arrow-up mr-2"></i>
                        Upgrade Plan
                    </button>
                    
                    <button class="flex items-center justify-center px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium">
                        <i class="fas fa-credit-card mr-2"></i>
                        Update Payment
                    </button>
                    
                    <button class="flex items-center justify-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                        <i class="fas fa-times mr-2"></i>
                        Cancel Plan
                    </button>
                </div>
                
                <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-1"></i>
                        <div>
                            <p class="text-sm font-medium text-yellow-800">Approaching Usage Limit</p>
                            <p class="text-xs text-yellow-700 mt-1">You've used 85% of your monthly lead quota. Consider upgrading to avoid interruptions.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
