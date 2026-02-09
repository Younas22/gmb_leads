@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')
        <!-- Flash Messages -->
        @if(session('payment_success'))
        <div class="mx-4 lg:mx-8 mt-4">
            <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl flex items-center">
                <svg class="w-5 h-5 mr-3 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <strong>Payment Submitted!</strong> We've received your payment screenshot. Our team will verify it and activate your subscription shortly.
                </div>
            </div>
        </div>
        @endif

        <!-- Search Form -->
        <div class="p-4 lg:p-8">
            <!-- Current Plan Overview -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0 mb-6">
                    <div class="flex items-center flex-wrap gap-2 sm:space-x-4">
                        <h3 class="text-lg sm:text-xl font-bold text-gray-800">Current Plan</h3>
                        @if($currentPlan)
                            <span class="inline-flex items-center px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs sm:text-sm font-medium
                                {{ $currentPlan['is_active'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                <i class="fas {{ $currentPlan['package']->price > 0 ? 'fa-crown' : 'fa-gift' }} mr-1 sm:mr-2 text-xs"></i>
                                {{ $currentPlan['package']->name }}
                                @if($currentPlan['is_pending'])
                                    (Pending)
                                @endif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs sm:text-sm font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-gift mr-1 sm:mr-2 text-xs"></i>No Active Plan
                            </span>
                        @endif
                    </div>
                    @if(!$currentPlan || $currentPlan['package']->price == 0)
                        <a href="#pricing-plans" class="w-full sm:w-auto bg-orange-600 hover:bg-orange-700 text-white px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg text-sm sm:text-base font-medium text-center">
                            <i class="fas fa-arrow-up mr-1 sm:mr-2 text-xs"></i>Upgrade Now
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Monthly Leads -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Monthly Leads</span>
                            <span class="text-sm font-semibold {{ $usageData['monthly_leads']['percentage'] >= 90 ? 'text-red-600' : 'text-orange-600' }}">
                                {{ number_format($usageData['monthly_leads']['used']) }}/{{ number_format($usageData['monthly_leads']['limit']) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="{{ $usageData['monthly_leads']['percentage'] >= 90 ? 'bg-red-500' : 'bg-orange-500' }} h-2 rounded-full"
                                style="width: {{ min($usageData['monthly_leads']['percentage'], 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ number_format($usageData['monthly_leads']['remaining']) }} leads remaining
                        </p>
                    </div>

                    <!-- Daily Searches -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Daily Searches</span>
                            <span class="text-sm font-semibold {{ $usageData['daily_searches']['percentage'] >= 90 ? 'text-red-600' : 'text-green-600' }}">
                                {{ number_format($usageData['daily_searches']['used']) }}/{{ number_format($usageData['daily_searches']['limit']) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="{{ $usageData['daily_searches']['percentage'] >= 90 ? 'bg-red-500' : 'bg-green-500' }} h-2 rounded-full"
                                style="width: {{ min($usageData['daily_searches']['percentage'], 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ number_format($usageData['daily_searches']['remaining']) }} searches remaining today
                        </p>
                    </div>

                    <!-- API Keys -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">API Keys</span>
                            <span class="text-sm text-blue-600 font-semibold">
                                {{ $usageData['api_keys']['used'] }}/{{ $usageData['api_keys']['limit'] }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min($usageData['api_keys']['percentage'], 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            @if($usageData['api_keys']['limit'] >= 999999)
                                Unlimited API keys
                            @else
                                Upgrade for more keys
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pricing Plans -->
            <div class="mb-8" id="pricing-plans">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Choose Your Plan</h3>

                @php
                    $planCount = count($availablePlans);
                    $gridClass = match($planCount) {
                        1 => 'lg:grid-cols-1',
                        2 => 'lg:grid-cols-2',
                        3 => 'lg:grid-cols-3',
                        default => 'lg:grid-cols-4',
                    };
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 {{ $gridClass }} gap-6">
                    @forelse($availablePlans as $plan)
                        @php
                            $isCurrentPlan = $currentPlan && $currentPlan['package']->id === $plan->id;
                            $borderClass = $plan->is_popular ? 'border-orange-500' : 'border-gray-200';
                        @endphp

                        <div class="bg-white rounded-xl border-2 {{ $borderClass }} p-6 relative">
                            @if($plan->is_popular)
                                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                                    <span class="bg-orange-500 text-white px-4 py-1 rounded-full text-xs font-medium">
                                        Most Popular
                                    </span>
                                </div>
                            @endif

                            <div class="text-center mb-6">
                                <h4 class="text-xl font-bold text-gray-800 mb-2">{{ $plan->name }}</h4>
                                <div class="text-3xl font-bold text-gray-800 mb-1">
                                    {{ $currency['symbol'] }} {{ number_format(\App\Services\CurrencyHelper::convert((float)$plan->price, $currency), 0) }}
                                </div>
                                <p class="text-sm text-gray-600">{{ $plan->billing_type }}</p>
                                @if($plan->description)
                                    <p class="text-xs text-gray-500 mt-1">{{ $plan->description }}</p>
                                @endif
                            </div>

                            <ul class="space-y-3 mb-6">
                                @php
                                    // Sort features: numbers first, then true, then false
                                    $sortedFeatures = $plan->features->sortBy(function($feature) {
                                        if (is_numeric($feature->feature_value) || $feature->is_unlimited) {
                                            return 0; // Numbers/unlimited first
                                        } elseif ($feature->feature_value === 'true') {
                                            return 1; // True values second
                                        } else {
                                            return 2; // False values last
                                        }
                                    });
                                @endphp
                                @forelse($sortedFeatures as $feature)
                                    <li class="flex items-center text-sm">
                                        @if($feature->feature_value === 'true')
                                            <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @elseif($feature->feature_value === 'false')
                                            <svg class="w-5 h-5 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @endif

                                        @if($feature->feature_value === 'false')
                                            <span class="text-gray-400">{{ $feature->display_name }}</span>
                                        @elseif($feature->is_unlimited)
                                            <span>Unlimited {{ $feature->display_name }}</span>
                                        @elseif($feature->feature_value === 'true')
                                            <span>{{ $feature->display_name }}</span>
                                        @else
                                            <span>{{ $feature->formatted_display }}</span>
                                        @endif
                                    </li>
                                @empty
                                    <li class="flex items-center text-sm text-gray-400">
                                        <i class="fas fa-info-circle text-gray-400 mr-3"></i>
                                        No features listed
                                    </li>
                                @endforelse
                            </ul>

                            @if($isCurrentPlan)
                                <button class="w-full bg-gray-200 text-gray-500 py-2 sm:py-2.5 rounded-lg text-sm sm:text-base font-medium cursor-not-allowed">
                                    Current Plan
                                </button>
                            @else
                                <button
                                    data-package-id="{{ $plan->id }}"
                                    data-package-name="{{ $plan->name }}"
                                    data-package-price="{{ \App\Services\CurrencyHelper::convert((float)$plan->price, $currency) }}"
                                    data-currency-symbol="{{ $currency['symbol'] }}"
                                    onclick="openPaymentModal(this.dataset.packageId, this.dataset.packageName, this.dataset.packagePrice)"
                                    class="w-full bg-orange-600 hover:bg-orange-700 text-white py-2 sm:py-2.5 rounded-lg text-sm sm:text-base font-medium">
                                    @if($plan->price > 0)
                                        Upgrade to {{ $plan->name }}
                                    @else
                                        Select Free Plan
                                    @endif
                                </button>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-3 text-center py-8">
                            <p class="text-gray-500">No packages available at the moment.</p>
                        </div>
                    @endforelse
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
                                <span class="text-lg font-bold text-primary-600">{{ number_format($analyticsData['monthly_searches']) }}</span>
                            </div>
                            <p class="text-xs text-gray-500">
                                @if($analyticsData['searches_change'] > 0)
                                    ↑ {{ abs($analyticsData['searches_change']) }}% from last month
                                @elseif($analyticsData['searches_change'] < 0)
                                    ↓ {{ abs($analyticsData['searches_change']) }}% from last month
                                @else
                                    No change from last month
                                @endif
                            </p>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-600">Leads Generated</span>
                                <span class="text-lg font-bold text-green-600">{{ number_format($analyticsData['monthly_leads']) }}</span>
                            </div>
                            <p class="text-xs text-gray-500">
                                @if($analyticsData['leads_change'] > 0)
                                    ↑ {{ abs($analyticsData['leads_change']) }}% from last month
                                @elseif($analyticsData['leads_change'] < 0)
                                    ↓ {{ abs($analyticsData['leads_change']) }}% from last month
                                @else
                                    No change from last month
                                @endif
                            </p>
                        </div>

                        @if($currentPlan && $currentPlan['package']->price > 0)
                            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                    <span class="text-sm font-medium text-blue-800">Active Subscription</span>
                                </div>
                                <p class="text-sm text-blue-700">{{ $currentPlan['package']->name }}</p>
                                @if($currentPlan['end_date'])
                                    <p class="text-xs text-blue-600">Renews on {{ $currentPlan['end_date']->format('M d, Y') }}</p>
                                @endif
                            </div>
                        @else
                            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-calculator text-green-600 mr-2"></i>
                                    <span class="text-sm font-medium text-green-800">Upgrade & Save</span>
                                </div>
                                <p class="text-sm text-green-700">Get more features with a paid plan</p>
                                <p class="text-xs text-green-600">Unlock unlimited potential</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Billing Information -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base sm:text-lg font-bold text-gray-800">Billing Information</h3>
                        @if($userPaymentMethod)
                            <button class="text-primary-600 hover:text-primary-700 text-xs sm:text-sm font-medium">
                                <i class="fas fa-edit mr-1 text-xs"></i>Edit
                            </button>
                        @endif
                    </div>

                    <!-- Payment Method -->
                    @if($userPaymentMethod)
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Payment Method</h4>
                            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-credit-card text-blue-600 text-xl"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $userPaymentMethod->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $userPaymentMethod->description ?? 'Payment method on file' }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Payment Method</h4>
                            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                <i class="fas fa-credit-card text-gray-400 text-xl"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">No payment method added</p>
                                    <p class="text-xs text-gray-500">Add a payment method to upgrade</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Billing History -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Billing History</h4>
                        @if($billingHistory && $billingHistory->count() > 0)
                            <div class="space-y-2">
                                @foreach($billingHistory as $payment)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">
                                                {{ ($payment->paid_at ?? $payment->created_at)->format('M d, Y') }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ $payment->subscription->package->name ?? 'Payment' }}
                                                @if($payment->paymentMethod)
                                                    - {{ $payment->paymentMethod->name }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-800">
                                                {{ $payment->currency ?? 'PKR' }} {{ number_format($payment->amount, 2) }}
                                            </p>
                                            @if($payment->screenshot)
                                                <a href="{{ asset('public/' . $payment->screenshot) }}" target="_blank"
                                                   class="text-xs text-primary-600 hover:text-primary-700">
                                                    <i class="fas fa-image mr-1"></i>Receipt
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6">
                                <i class="fas fa-receipt text-gray-300 text-3xl mb-2"></i>
                                <p class="text-sm text-gray-500">No billing history yet</p>
                                <p class="text-xs text-gray-400">Your payment history will appear here</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Plan Management Actions -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mt-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Plan Management</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
                    <a href="#pricing-plans" class="flex items-center justify-center px-3 sm:px-4 py-2 sm:py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm sm:text-base font-medium">
                        <i class="fas fa-arrow-up mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                        Upgrade Plan
                    </a>

                    <button class="flex items-center justify-center px-3 sm:px-4 py-2 sm:py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm sm:text-base font-medium">
                        <i class="fas fa-credit-card mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                        Update Payment
                    </button>

                    <button class="flex items-center justify-center px-3 sm:px-4 py-2 sm:py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm sm:text-base font-medium">
                        <i class="fas fa-times mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                        Cancel Plan
                    </button>
                </div>
                
                @php
                    $showWarning = false;
                    $warningMessage = '';

                    if ($usageData['monthly_leads']['percentage'] >= 85) {
                        $showWarning = true;
                        $warningMessage = "You've used " . $usageData['monthly_leads']['percentage'] . "% of your monthly lead quota. Consider upgrading to avoid interruptions.";
                    } elseif ($usageData['daily_searches']['percentage'] >= 85) {
                        $showWarning = true;
                        $warningMessage = "You've used " . $usageData['daily_searches']['percentage'] . "% of your daily search quota. Upgrade for unlimited searches.";
                    } elseif ($currentPlan && $currentPlan['is_pending']) {
                        $showWarning = true;
                        $warningMessage = "Your subscription is pending approval. You'll have limited access until your payment is verified.";
                    }
                @endphp

                @if($showWarning)
                    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mt-1"></i>
                            <div>
                                <p class="text-sm font-medium text-yellow-800">
                                    @if($currentPlan && $currentPlan['is_pending'])
                                        Subscription Pending
                                    @else
                                        Approaching Usage Limit
                                    @endif
                                </p>
                                <p class="text-xs text-yellow-700 mt-1">{{ $warningMessage }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    {{-- Payment Modal --}}
    @include('partials.payment-modal')
@endsection
