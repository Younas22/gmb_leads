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
            @php
                $planFeatures = $currentPlan ? $currentPlan['package']->features->keyBy('feature_key') : collect();
                $dailyLimit     = $planFeatures->get('daily_leads_limit');
                $exportLeads    = $planFeatures->get('export_leads');
                $maxDevices     = $planFeatures->get('max_devices');
                $prioritySupport= $planFeatures->get('priority_support');
                $unlimitedMap   = $planFeatures->get('unlimited_map_scraping');
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-gray-100">
                    <div class="flex items-center flex-wrap gap-2">
                        <h3 class="text-lg sm:text-xl font-bold text-gray-800">Current Plan</h3>
                        @if($currentPlan)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                {{ $currentPlan['is_active'] ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                <i class="fas {{ $currentPlan['package']->price > 0 ? 'fa-crown' : 'fa-gift' }} mr-1.5 text-xs"></i>
                                {{ $currentPlan['package']->name }}
                                @if($currentPlan['is_pending']) &nbsp;· Pending @endif
                            </span>
                            @if($currentPlan['package']->billing_type === 'yearly')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Yearly</span>
                            @elseif($currentPlan['package']->billing_type === 'monthly' && $currentPlan['package']->price > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Monthly</span>
                            @endif
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                <i class="fas fa-gift mr-1.5 text-xs"></i>No Active Plan
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @if($currentPlan && $currentPlan['end_date'])
                            <span class="text-xs text-gray-400">
                                Renews <strong class="text-gray-600">{{ $currentPlan['end_date']->format('M d, Y') }}</strong>
                            </span>
                        @endif
                        @if(!$currentPlan || $currentPlan['package']->price == 0)
                            <a href="#pricing-plans" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                <i class="fas fa-arrow-up mr-1 text-xs"></i>Upgrade Now
                            </a>
                        @endif
                    </div>
                </div>

                @if($currentPlan)
                <!-- Feature Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y sm:divide-y-0 divide-gray-100">
                    <!-- Daily Leads Limit -->
                    <div class="px-6 py-5">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-7 h-7 rounded-lg bg-orange-50 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-orange-500 text-xs"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-500">Daily Leads Limit</span>
                        </div>
                        <div class="mt-2">
                            @if($usageData['daily_leads']['unlimited'])
                                <span class="text-xl font-bold text-gray-800"><i class="fas fa-infinity mr-1 text-green-500"></i>Unlimited</span>
                            @elseif($dailyLimit)
                                <span class="text-xl font-bold {{ $usageData['daily_leads']['percentage'] >= 85 ? 'text-red-600' : 'text-gray-800' }}">
                                    {{ number_format($usageData['daily_leads']['used']) }}
                                </span>
                                <span class="text-sm text-gray-500">/ {{ number_format((int)$dailyLimit->feature_value) }} today</span>
                            @else
                                <span class="text-xl font-bold text-gray-400">—</span>
                            @endif
                        </div>
                        @if(!$usageData['daily_leads']['unlimited'] && $dailyLimit)
                            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 mb-1">
                                <div class="h-1.5 rounded-full {{ $usageData['daily_leads']['percentage'] >= 85 ? 'bg-red-500' : 'bg-orange-400' }}"
                                     style="width: {{ min($usageData['daily_leads']['percentage'], 100) }}%"></div>
                            </div>
                            <p class="text-xs {{ $usageData['daily_leads']['percentage'] >= 85 ? 'text-red-500' : 'text-gray-400' }} mt-1">
                                {{ number_format($usageData['daily_leads']['remaining']) }} remaining today
                            </p>
                        @else
                            <p class="text-xs text-gray-400 mt-1">No daily limit on your plan</p>
                        @endif
                    </div>

                    <!-- Export Leads -->
                    <div class="px-6 py-5">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-7 h-7 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-download text-green-500 text-xs"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-500">Export Leads</span>
                        </div>
                        <div class="mt-2">
                            @if($exportLeads && $exportLeads->feature_value === 'unlimited')
                                <span class="text-xl font-bold text-gray-800">Unlimited</span>
                            @elseif($exportLeads && $exportLeads->feature_value !== 'false')
                                <span class="text-xl font-bold text-gray-800">{{ number_format((int)$exportLeads->feature_value) }}</span>
                                <span class="text-sm text-gray-500">/mo</span>
                            @else
                                <span class="text-xl font-bold text-gray-400">Not included</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-1">CSV / Excel downloads</p>
                    </div>

                    <!-- Devices Access -->
                    <div class="px-6 py-5">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-laptop text-blue-500 text-xs"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-500">Devices Access</span>
                        </div>
                        <div class="mt-2">
                            @if($maxDevices)
                                <span class="text-xl font-bold text-gray-800">{{ $maxDevices->feature_value }}</span>
                                <span class="text-sm text-gray-500">{{ (int)$maxDevices->feature_value === 1 ? 'Device' : 'Devices' }}</span>
                            @else
                                <span class="text-xl font-bold text-gray-400">—</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Simultaneous logins</p>
                    </div>

                    <!-- Priority Support -->
                    <div class="px-6 py-5">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-7 h-7 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-headset text-purple-500 text-xs"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-500">Priority Support</span>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            @if($prioritySupport && $prioritySupport->feature_value === 'true')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-semibold">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    Included
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-sm font-semibold">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Not included
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Fast-track support response</p>
                    </div>
                </div>

                <!-- Active Features Row -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap gap-2">
                    @foreach([
                        'unlimited_map_scraping' => 'Unlimited Map Scraping',
                        'basic_business_signals' => 'Basic Business Signals',
                        'contact_ready_leads'    => 'Contact-Ready Leads',
                        'email_scraping'         => 'Email Scraping',
                        'social_media_scraping'  => 'Social Media Scraping',
                        'website_extraction'     => 'Website Extraction',
                        'latest_review_insights' => 'Latest Review Insights',
                        'advanced_review_filters'=> 'Advanced Review Filters',
                    ] as $key => $label)
                        @php $f = $planFeatures->get($key); @endphp
                        @if($f && $f->feature_value === 'true')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white border border-green-200 text-green-700 text-xs font-medium">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                {{ $label }}
                            </span>
                        @endif
                    @endforeach
                </div>

                @else
                <!-- No Plan -->
                <div class="px-6 py-10 text-center">
                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-box-open text-gray-400 text-xl"></i>
                    </div>
                    <p class="text-gray-600 font-medium mb-1">No active plan yet</p>
                    <p class="text-sm text-gray-400 mb-4">Choose a plan below to start generating leads.</p>
                    <a href="#pricing-plans" class="inline-block bg-orange-500 hover:bg-orange-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                        View Plans
                    </a>
                </div>
                @endif
            </div>

            <!-- Pricing Plans -->
            <div class="mb-8" id="pricing-plans">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Choose Your Plan</h3>

                @php
                    $featureLabels = [
                        'unlimited_map_scraping'  => 'Unlimited Map Scraping',
                        'daily_leads_limit'       => 'Daily Leads Limit',
                        'basic_business_signals'  => 'Basic Business Signals',
                        'contact_ready_leads'     => 'Contact-Ready Leads',
                        'email_scraping'          => 'Email Scraping',
                        'social_media_scraping'   => 'Social Media Scraping',
                        'website_extraction'      => 'Website Extraction',
                        'latest_review_insights'  => 'Latest Review Insights',
                        'advanced_review_filters' => 'Advanced Review Filters',
                        'export_leads'            => 'Export Leads',
                        'max_devices'             => 'Devices Access',
                        'priority_support'        => 'Priority Support',
                    ];
                    $boolFeatures = [
                        'unlimited_map_scraping', 'basic_business_signals', 'contact_ready_leads',
                        'email_scraping', 'social_media_scraping', 'website_extraction',
                        'latest_review_insights', 'advanced_review_filters', 'priority_support',
                    ];
                    $intelligenceFeatures = [
                        'unlimited_map_scraping', 'basic_business_signals', 'contact_ready_leads',
                        'email_scraping', 'social_media_scraping', 'website_extraction',
                        'latest_review_insights', 'advanced_review_filters',
                    ];
                    $hideFromCards = $intelligenceFeatures;
                    $monthlyPlans = $availablePlans->filter(fn($p) => $p->billing_type === 'monthly');
                    $yearlyPlans  = $availablePlans->filter(fn($p) => $p->price == 0 || $p->billing_type === 'yearly');
                @endphp

                <!-- Monthly / Yearly Billing Toggle -->
                <div class="flex justify-center mb-6 mt-2">
                    <div class="inline-flex items-center bg-gray-100 rounded-full p-1 gap-1">
                        <button id="sub-billing-monthly-btn" onclick="subSwitchBilling('monthly')"
                                class="sub-billing-btn px-5 py-2 rounded-full text-sm font-semibold transition-colors bg-blue-600 text-white cursor-pointer">
                            Monthly
                        </button>
                        <button id="sub-billing-yearly-btn" onclick="subSwitchBilling('yearly')"
                                class="sub-billing-btn px-5 py-2 rounded-full text-sm font-semibold transition-colors text-gray-600 hover:text-gray-900 cursor-pointer flex items-center gap-2">
                            Yearly
                            <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full">Save 2 months</span>
                        </button>
                    </div>
                </div>

                {{-- Monthly Grid --}}
                <div id="sub-billing-monthly-grid">
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto pt-2">
                        @forelse($monthlyPlans as $package)
                        @php
                            $isCurrentPlan = $currentPlan && $currentPlan['package']->id === $package->id;
                            $borderClass   = $isCurrentPlan ? 'border-green-500 border-4' : ($package->is_popular ? 'border-orange-500' : 'border-gray-200');
                        @endphp
                        <div class="rounded-xl p-8 flex flex-col relative {{ $package->is_popular ? 'bg-blue-600 text-white transform scale-105' : 'bg-white border-2 ' . $borderClass . ' hover:border-blue-500 transition-colors' }}">
                            @if($isCurrentPlan)
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-green-500 text-white px-4 py-1 rounded-full text-sm font-semibold">Your Active Plan</div>
                            @elseif($package->is_popular)
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-orange-500 text-white px-4 py-1 rounded-full text-sm font-semibold">Popular</div>
                            @endif
                            <div class="text-center mb-6">
                                <h3 class="text-xl font-semibold {{ $package->is_popular ? '' : 'text-gray-900' }} mb-1">{{ $package->name }}</h3>
                                @if($package->description)
                                    <p class="text-sm {{ $package->is_popular ? 'opacity-75' : 'text-gray-500' }} mb-3">{{ $package->description }}</p>
                                @endif
                                <div class="text-4xl font-bold {{ $package->is_popular ? '' : 'text-gray-900' }}">
                                    @if($package->price == 0)
                                        {{ $currency['symbol'] }}0<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/month</span>
                                    @else
                                        {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 2) }}<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/month</span>
                                    @endif
                                </div>
                            </div>
                            @include('partials.pricing-features', compact('package', 'boolFeatures', 'featureLabels', 'hideFromCards'))
                            @if($isCurrentPlan)
                                <button class="w-full bg-gray-200 text-gray-500 py-3 rounded-lg font-semibold cursor-not-allowed">Current Plan</button>
                            @elseif($package->price == 0)
                                <button onclick="openPaymentModal(this.dataset.packageId, this.dataset.packageName, this.dataset.packagePrice)"
                                        data-package-id="{{ $package->id }}" data-package-name="{{ e($package->name) }}" data-package-price="0" data-currency-symbol="{{ $currency['symbol'] }}"
                                        class="w-full {{ $package->is_popular ? 'bg-white text-blue-600 hover:bg-gray-100' : 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                                    Select Free Plan
                                </button>
                            @else
                                <button onclick="openPaymentModal(this.dataset.packageId, this.dataset.packageName, this.dataset.packagePrice)"
                                        data-package-id="{{ $package->id }}" data-package-name="{{ e($package->name) }}"
                                        data-package-price="{{ \App\Services\CurrencyHelper::convert((float)$package->price, $currency) }}"
                                        data-currency-symbol="{{ $currency['symbol'] }}"
                                        class="w-full {{ $package->is_popular ? 'bg-white text-blue-600 hover:bg-gray-100' : 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                                    Get Started
                                </button>
                            @endif
                        </div>
                        @empty
                            <div class="col-span-3 text-center py-8"><p class="text-gray-500">No packages available.</p></div>
                        @endforelse
                    </div>
                </div>

                {{-- Yearly Grid --}}
                <div id="sub-billing-yearly-grid" class="hidden">
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto pt-2">
                        @forelse($yearlyPlans as $package)
                        @php
                            $isCurrentPlan = $currentPlan && $currentPlan['package']->id === $package->id;
                            $borderClass   = $isCurrentPlan ? 'border-green-500 border-4' : ($package->is_popular ? 'border-orange-500' : 'border-gray-200');
                        @endphp
                        <div class="rounded-xl p-8 flex flex-col relative {{ $package->is_popular ? 'bg-blue-600 text-white transform scale-105' : 'bg-white border-2 ' . $borderClass . ' hover:border-blue-500 transition-colors' }}">
                            @if($isCurrentPlan)
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-green-500 text-white px-4 py-1 rounded-full text-sm font-semibold">Your Active Plan</div>
                            @elseif($package->is_popular)
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-orange-500 text-white px-4 py-1 rounded-full text-sm font-semibold">Popular</div>
                            @endif
                            <div class="text-center mb-6">
                                <h3 class="text-xl font-semibold {{ $package->is_popular ? '' : 'text-gray-900' }} mb-1">{{ $package->name }}</h3>
                                @if($package->description)
                                    <p class="text-sm {{ $package->is_popular ? 'opacity-75' : 'text-gray-500' }} mb-3">{{ $package->description }}</p>
                                @endif
                                <div class="text-4xl font-bold {{ $package->is_popular ? '' : 'text-gray-900' }}">
                                    @if($package->price == 0)
                                        {{ $currency['symbol'] }}0<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/month</span>
                                    @else
                                        {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price / 12, $currency), 2) }}<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/mo</span>
                                    @endif
                                </div>
                                @if($package->billing_type === 'yearly' && $package->price > 0)
                                    <div class="text-sm {{ $package->is_popular ? 'opacity-70' : 'text-gray-500' }} mt-1">
                                        billed {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 2) }}/year
                                    </div>
                                @endif
                            </div>
                            @include('partials.pricing-features', compact('package', 'boolFeatures', 'featureLabels', 'hideFromCards'))
                            @if($isCurrentPlan)
                                <button class="w-full bg-gray-200 text-gray-500 py-3 rounded-lg font-semibold cursor-not-allowed">Current Plan</button>
                            @elseif($package->price == 0)
                                <button onclick="openPaymentModal(this.dataset.packageId, this.dataset.packageName, this.dataset.packagePrice)"
                                        data-package-id="{{ $package->id }}" data-package-name="{{ e($package->name) }}" data-package-price="0" data-currency-symbol="{{ $currency['symbol'] }}"
                                        class="w-full {{ $package->is_popular ? 'bg-white text-blue-600 hover:bg-gray-100' : 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                                    Select Free Plan
                                </button>
                            @else
                                <button onclick="openPaymentModal(this.dataset.packageId, this.dataset.packageName, this.dataset.packagePrice)"
                                        data-package-id="{{ $package->id }}" data-package-name="{{ e($package->name) }}"
                                        data-package-price="{{ \App\Services\CurrencyHelper::convert((float)$package->price, $currency) }}"
                                        data-currency-symbol="{{ $currency['symbol'] }}"
                                        class="w-full {{ $package->is_popular ? 'bg-white text-blue-600 hover:bg-gray-100' : 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                                    Get Started
                                </button>
                            @endif
                        </div>
                        @empty
                            <div class="col-span-3 text-center py-8"><p class="text-gray-500">No packages available.</p></div>
                        @endforelse
                    </div>
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
                                    @foreach($monthlyPlans as $package)
                                        <th class="text-center py-4 px-4 w-1/5">
                                            <span class="text-sm font-bold {{ $package->is_popular ? 'text-blue-600' : 'text-gray-900' }}">{{ $package->name }}</span>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                @if($package->price == 0)
                                                    Free
                                                @else
                                                    {{ $currency['symbol'] }}{{ number_format(\App\Services\CurrencyHelper::convert((float)$package->price, $currency), 2) }}/mo
                                                @endif
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $intelligenceLabels = [
                                        'unlimited_map_scraping'  => ['name' => 'Unlimited Map Scraping',   'desc' => 'Scrape as many Google Maps results as you need',  'type' => 'bool'],
                                        'basic_business_signals'  => ['name' => 'Basic Business Signals',   'desc' => 'Name, address, category, rating, total reviews',  'type' => 'bool'],
                                        'contact_ready_leads'     => ['name' => 'Contact-Ready Leads',      'desc' => 'Verified phone & website data included',          'type' => 'bool'],
                                        'email_scraping'          => ['name' => 'Email Scraping',           'desc' => 'Extract emails from business websites',           'type' => 'bool'],
                                        'social_media_scraping'   => ['name' => 'Social Media Scraping',    'desc' => 'Facebook, Instagram, Twitter & more',             'type' => 'bool'],
                                        'website_extraction'      => ['name' => 'Website Extraction',       'desc' => 'Pull full website URL from listings',             'type' => 'bool'],
                                        'latest_review_insights'  => ['name' => 'Latest Review Insights',  'desc' => 'Recent review text & sentiment signals',          'type' => 'bool'],
                                        'advanced_review_filters' => ['name' => 'Advanced Review Filters', 'desc' => 'Filter by rating, recency & keywords',            'type' => 'bool'],
                                        'daily_leads_limit'       => ['name' => 'Daily Leads Limit',        'desc' => 'Max leads you can collect per day',               'type' => 'value', 'suffix' => '/day'],
                                        'export_leads'            => ['name' => 'Export Leads',             'desc' => 'Download leads as CSV / Excel',                   'type' => 'value', 'suffix' => ''],
                                        'max_devices'             => ['name' => 'Devices Access',           'desc' => 'Number of devices that can access your account',  'type' => 'value', 'suffix' => ''],
                                        'priority_support'        => ['name' => 'Priority Support',         'desc' => 'Fast-track support response',                     'type' => 'bool'],
                                    ];
                                @endphp
                                @foreach($intelligenceLabels as $featureKey => $meta)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                        <td class="py-4 px-4">
                                            <div class="font-medium text-gray-900 text-sm">{{ $meta['name'] }}</div>
                                            <div class="text-xs text-gray-400 mt-0.5">{{ $meta['desc'] }}</div>
                                        </td>
                                        @foreach($monthlyPlans as $package)
                                            @php
                                                $feat = $package->features->firstWhere('feature_key', $featureKey);
                                                $val  = $feat ? $feat->feature_value : null;
                                            @endphp
                                            <td class="text-center py-4 px-4">
                                                @if($meta['type'] === 'bool')
                                                    @if($val === 'true')
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
                                                @else
                                                    @if(!$val || $val === 'false')
                                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </span>
                                                    @elseif($val === 'unlimited')
                                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Unlimited</span>
                                                    @else
                                                        <span class="text-sm font-semibold text-gray-800">
                                                            @if($featureKey === 'max_devices')
                                                                {{ $val }} {{ (int)$val === 1 ? 'Device' : 'Devices' }}
                                                            @else
                                                                {{ number_format((int)$val) }}{{ $meta['suffix'] }}
                                                            @endif
                                                        </span>
                                                    @endif
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

                        <!-- Daily Leads Limit Usage -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-orange-500 text-xs"></i>
                                    <span class="text-sm font-medium text-gray-600">Daily Leads Limit</span>
                                </div>
                                @if($usageData['daily_leads']['unlimited'])
                                    <span class="text-base font-bold text-green-600"><i class="fas fa-infinity mr-1"></i>Unlimited</span>
                                @else
                                    <span class="text-base font-bold {{ $usageData['daily_leads']['percentage'] >= 85 ? 'text-red-600' : 'text-orange-600' }}">
                                        {{ number_format($usageData['daily_leads']['used']) }} / {{ number_format($usageData['daily_leads']['limit']) }}
                                    </span>
                                @endif
                            </div>
                            @if(!$usageData['daily_leads']['unlimited'])
                                <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                    <div class="h-2 rounded-full transition-all {{ $usageData['daily_leads']['percentage'] >= 85 ? 'bg-red-500' : 'bg-orange-500' }}"
                                         style="width: {{ min($usageData['daily_leads']['percentage'], 100) }}%"></div>
                                </div>
                                <p class="text-xs text-gray-500">
                                    {{ number_format($usageData['daily_leads']['remaining']) }} leads remaining today
                                    @if($usageData['daily_leads']['percentage'] >= 85)
                                        &nbsp;· <span class="text-red-500 font-medium">Near limit</span>
                                    @endif
                                </p>
                            @else
                                <p class="text-xs text-gray-500">No daily limit on your plan</p>
                            @endif
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

                    if (!empty($usageData['daily_leads']) && $usageData['daily_leads']['percentage'] >= 85) {
                        $showWarning = true;
                        $warningMessage = "You've used " . $usageData['daily_leads']['percentage'] . "% of your daily leads limit. Consider upgrading to avoid interruptions.";
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

    <script>
        function subSwitchBilling(type) {
            var monthlyGrid = document.getElementById('sub-billing-monthly-grid');
            var yearlyGrid  = document.getElementById('sub-billing-yearly-grid');
            var monthlyBtn  = document.getElementById('sub-billing-monthly-btn');
            var yearlyBtn   = document.getElementById('sub-billing-yearly-btn');
            var isMonthly   = type === 'monthly';

            monthlyGrid.classList.toggle('hidden', !isMonthly);
            yearlyGrid.classList.toggle('hidden', isMonthly);

            monthlyBtn.classList.toggle('bg-blue-600', isMonthly);
            monthlyBtn.classList.toggle('text-white', isMonthly);
            monthlyBtn.classList.toggle('text-gray-600', !isMonthly);

            yearlyBtn.classList.toggle('bg-blue-600', !isMonthly);
            yearlyBtn.classList.toggle('text-white', !isMonthly);
            yearlyBtn.classList.toggle('text-gray-600', isMonthly);
        }
    </script>
@endsection
