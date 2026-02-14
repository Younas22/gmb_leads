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

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Monthly Searches -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Monthly Credits</span>
                            <span class="text-sm font-semibold {{ $usageData['monthly_searches']['percentage'] >= 90 ? 'text-red-600' : 'text-green-600' }}">
                                {{ number_format($usageData['monthly_searches']['used']) }}/{{ number_format($usageData['monthly_searches']['limit']) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="{{ $usageData['monthly_searches']['percentage'] >= 90 ? 'bg-red-500' : 'bg-green-500' }} h-2 rounded-full"
                                style="width: {{ min($usageData['monthly_searches']['percentage'], 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ number_format($usageData['monthly_searches']['remaining']) }} searches remaining
                        </p>
                    </div>

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

                    <!-- Saved Lists -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Saved Lists</span>
                            <span class="text-sm font-semibold {{ $usageData['saved_lists']['percentage'] >= 90 ? 'text-red-600' : 'text-blue-600' }}">
                                {{ number_format($usageData['saved_lists']['used']) }}/{{ number_format($usageData['saved_lists']['limit']) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="{{ $usageData['saved_lists']['percentage'] >= 90 ? 'bg-red-500' : 'bg-blue-500' }} h-2 rounded-full"
                                style="width: {{ min($usageData['saved_lists']['percentage'], 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            @if($usageData['saved_lists']['limit'] >= 999999)
                                Unlimited saved lists
                            @else
                                {{ number_format($usageData['saved_lists']['remaining']) }} lists remaining
                            @endif
                        </p>
                    </div>

                    <!-- Monthly Export Lists -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Monthly Exports</span>
                            <span class="text-sm font-semibold {{ $usageData['monthly_exports']['percentage'] >= 90 ? 'text-red-600' : 'text-purple-600' }}">
                                {{ number_format($usageData['monthly_exports']['used']) }}/{{ number_format($usageData['monthly_exports']['limit']) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="{{ $usageData['monthly_exports']['percentage'] >= 90 ? 'bg-red-500' : 'bg-purple-500' }} h-2 rounded-full"
                                style="width: {{ min($usageData['monthly_exports']['percentage'], 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            @if($usageData['monthly_exports']['limit'] >= 999999)
                                Unlimited exports
                            @else
                                {{ number_format($usageData['monthly_exports']['remaining']) }} exports remaining
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pricing Plans -->
            <div class="mb-8" id="pricing-plans">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Choose Your Plan</h3>

                @php
                    $featureLabels = [
                        'search_credits'          => 'Credits / Month',
                        'leads_per_month'         => 'Leads / Month',
                        'export_leads'            => 'Lead Exports',
                        'saved_lists'             => 'Saved Lists',
                        'email_support'           => 'Email Support',
                        'api_access'              => 'API Access',
                        'api_limit'               => 'API Limit',
                        'bulk_export'             => 'Bulk Export',
                        'crm_integration'         => 'CRM Integration',
                        'priority_support'        => 'Priority Support',
                        'api_calls'               => 'API Calls',
                        'dedicated_manager'       => 'Dedicated Manager',
                        'team_members'            => 'Team Members',
                        'future_updates'          => 'Future Updates',
                        'advance_filter'          => 'Advanced Filters',
                        'team_analytics'          => 'Team Analytics',
                        'white_label'             => 'White Label',
                        'custom_branding'         => 'Custom Branding',
                        'sla_guarantee'           => 'SLA Guarantee',
                        'custom_integrations'     => 'Custom Integrations',
                        'onboarding_training'     => 'Onboarding & Training',
                        'basic_business_signals'  => 'Basic Business Signals',
                        'contact_ready_leads'     => 'Contact-Ready Leads',
                        'email_social_discovery'  => 'Email & Social Discovery',
                        'latest_review_insights'  => 'Latest Review Insights',
                        'advanced_review_filters' => 'Advanced Review Filters',
                        'full_review_intelligence'=> 'Full Review Intelligence',
                    ];
                    $boolFeatures = [
                        'email_support', 'api_access', 'bulk_export',
                        'crm_integration', 'priority_support', 'dedicated_manager',
                        'team_analytics', 'white_label', 'custom_branding',
                        'sla_guarantee', 'custom_integrations', 'onboarding_training',
                        'future_updates', 'advance_filter',
                        'basic_business_signals', 'contact_ready_leads',
                        'email_social_discovery', 'latest_review_insights',
                        'advanced_review_filters', 'full_review_intelligence',
                    ];
                    $intelligenceFeatures = [
                        'basic_business_signals', 'contact_ready_leads',
                        'email_social_discovery', 'latest_review_insights',
                        'advanced_review_filters', 'full_review_intelligence',
                    ];
                    $hideFromCards = array_merge($intelligenceFeatures, ['data_depth']);
                @endphp

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto pt-4">
                    @forelse($availablePlans as $package)
                    @php
                        $isCurrentPlan = $currentPlan && $currentPlan['package']->id === $package->id;
                        $borderClass = $isCurrentPlan ? 'border-green-500 border-4' : ($package->is_popular ? 'border-orange-500' : 'border-gray-200');
                    @endphp
                    <div class="rounded-xl p-8 flex flex-col relative {{ $package->is_popular ? 'bg-blue-500 text-white transform scale-105' : 'bg-white border-2 ' . $borderClass . ' hover:border-blue-500 transition-colors' }}">
                        @if($isCurrentPlan)
                            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-green-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                                Your Active Plan
                            </div>
                        @elseif($package->is_popular)
                            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-orange-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                                Popular
                            </div>
                        @endif

                        <div class="text-center mb-6">
                            <h3 class="text-xl font-semibold {{ $package->is_popular ? '' : 'text-gray-900' }} mb-1">{{ $package->name }}</h3>
                            @if($package->description)
                                <p class="text-sm {{ $package->is_popular ? 'opacity-75' : 'text-gray-500' }} mb-3">{{ $package->description }}</p>
                            @endif
                            <div class="text-4xl font-bold {{ $package->is_popular ? '' : 'text-gray-900' }}">
                                @if($package->price == 0)
                                    {{ $currency['symbol'] }}0<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/month</span>
                                @elseif($package->billing_type === 'yearly')
                                    {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price / 12, $currency), 0) }}<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/mo</span>
                                @elseif($package->billing_type === 'lifetime')
                                    {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 0) }}<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}"> once</span>
                                @else
                                    {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 0) }}<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/month</span>
                                @endif
                            </div>
                            @if($package->billing_type === 'yearly' && $package->price > 0)
                                <div class="text-sm {{ $package->is_popular ? 'opacity-70' : 'text-gray-500' }} mt-1">billed {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 0) }}/year</div>
                            @endif
                        </div>

                        <ul class="space-y-4 mb-8 flex-grow">
                            @php
                                $sortedFeatures = $package->features->sort(function($a, $b) use ($boolFeatures) {
                                    $aIsBool = in_array($a->feature_key, $boolFeatures);
                                    $bIsBool = in_array($b->feature_key, $boolFeatures);

                                    if (!$aIsBool && $bIsBool) return -1;
                                    if ($aIsBool && !$bIsBool) return 1;

                                    if ($aIsBool && $bIsBool) {
                                        if ($a->feature_value === 'true' && $b->feature_value !== 'true') return -1;
                                        if ($a->feature_value !== 'true' && $b->feature_value === 'true') return 1;
                                    }

                                    return 0;
                                });
                            @endphp
                            @foreach($sortedFeatures as $feature)
                                @if(isset($featureLabels[$feature->feature_key]) && !in_array($feature->feature_key, $hideFromCards))
                                    <li class="flex items-center">
                                        @if(in_array($feature->feature_key, $boolFeatures))
                                            @if($feature->feature_value === 'true')
                                                <svg class="w-5 h-5 {{ $package->is_popular ? 'text-green-400' : 'text-green-500' }} mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {{ $featureLabels[$feature->feature_key] }}
                                            @else
                                                <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                <span class="{{ $package->is_popular ? 'opacity-50' : 'text-gray-400' }}">{{ $featureLabels[$feature->feature_key] }}</span>
                                            @endif
                                        @else
                                            <svg class="w-5 h-5 {{ $package->is_popular ? 'text-green-400' : 'text-green-500' }} mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $featureLabels[$feature->feature_key] }}:
                                            <strong class="ml-1">{{ $feature->is_unlimited ? 'Unlimited' : number_format((int)$feature->feature_value) }}</strong>
                                        @endif
                                    </li>
                                @endif
                            @endforeach
                        </ul>

                        @if($isCurrentPlan)
                        <button class="w-full bg-gray-200 text-gray-500 py-3 rounded-lg font-semibold cursor-not-allowed">
                            Current Plan
                        </button>
                        @elseif($package->price == 0)
                        <button
                            data-package-id="{{ $package->id }}"
                            data-package-name="{{ e($package->name) }}"
                            data-package-price="0"
                            data-currency-symbol="{{ $currency['symbol'] }}"
                            onclick="openPaymentModal(this.dataset.packageId, this.dataset.packageName, this.dataset.packagePrice)"
                            class="w-full {{ $package->is_popular ? 'bg-white text-blue-500 hover:bg-gray-100' : 'border-2 border-blue-500 text-blue-500 hover:bg-blue-500 hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                            Select Free Plan
                        </button>
                        @else
                        <button onclick="openPaymentModal(this.dataset.packageId, this.dataset.packageName, this.dataset.packagePrice)"
                                data-package-id="{{ $package->id }}"
                                data-package-name="{{ e($package->name) }}"
                                data-package-price="{{ \App\Services\CurrencyHelper::convert((float)$package->price, $currency) }}"
                                data-currency-symbol="{{ $currency['symbol'] }}"
                                class="w-full {{ $package->is_popular ? 'bg-white text-blue-500 hover:bg-gray-100' : 'border-2 border-blue-500 text-blue-500 hover:bg-blue-500 hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                            Get Started
                        </button>
                        @endif
                    </div>
                    @empty
                        <div class="col-span-3 text-center py-8">
                            <p class="text-gray-500">No packages available at the moment.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Lead Intelligence by Plan -->
                @if($availablePlans->count() > 0)
                <div class="mt-14">
                    <div class="text-center mb-10">
                        <p class="text-xs font-bold text-orange-500 uppercase tracking-widest mb-3">What's Included</p>
                        <h3 class="text-2xl md:text-3xl font-extrabold text-gray-900 mb-3">Lead Intelligence by Plan</h3>
                        <p class="text-gray-500 max-w-xl mx-auto">Each plan unlocks deeper business data. See exactly what intelligence you get at every tier.</p>
                    </div>

                    <div class="max-w-5xl mx-auto overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-gray-200">
                                    <th class="text-left py-4 px-4 text-sm font-semibold text-gray-500 uppercase tracking-wider w-2/5">Feature</th>
                                    @foreach($availablePlans as $package)
                                        <th class="text-center py-4 px-4 w-1/5">
                                            <span class="text-sm font-bold {{ $package->is_popular ? 'text-blue-500' : 'text-gray-900' }}">{{ $package->name }}</span>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                @if($package->price == 0)
                                                    Free
                                                @else
                                                    {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 0) }}/mo
                                                @endif
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $intelligenceLabels = [
                                        'basic_business_signals'  => ['name' => 'Basic Business Signals',  'desc' => 'Name, address, profile, category, rating, total reviews'],
                                        'contact_ready_leads'     => ['name' => 'Contact-Ready Leads',     'desc' => 'Verified phone & website data'],
                                        'email_social_discovery'  => ['name' => 'Email & Social Discovery', 'desc' => 'Emails, Facebook, Instagram, etc.'],
                                        'latest_review_insights'  => ['name' => 'Latest Review Insights',  'desc' => 'Recent review text & sentiment'],
                                        'advanced_review_filters' => ['name' => 'Advanced Review Filters', 'desc' => 'Filter by rating, recency & keywords'],
                                        'full_review_intelligence'=> ['name' => 'Full Review Intelligence','desc' => 'Complete review history & analysis'],
                                    ];
                                @endphp
                                @foreach($intelligenceLabels as $featureKey => $meta)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                        <td class="py-4 px-4">
                                            <div class="font-medium text-gray-900 text-sm">{{ $meta['name'] }}</div>
                                            <div class="text-xs text-gray-400 mt-0.5">{{ $meta['desc'] }}</div>
                                        </td>
                                        @foreach($availablePlans as $package)
                                            @php
                                                $feat = $package->features->firstWhere('feature_key', $featureKey);
                                                $hasFeature = $feat && $feat->feature_value === 'true';
                                            @endphp
                                            <td class="text-center py-4 px-4">
                                                @if($hasFeature)
                                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100">
                                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
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
                    } elseif ($usageData['monthly_searches']['percentage'] >= 85) {
                        $showWarning = true;
                        $warningMessage = "You've used " . $usageData['monthly_searches']['percentage'] . "% of your monthly search quota. Upgrade for unlimited searches.";
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
