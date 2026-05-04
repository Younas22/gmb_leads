@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')
        <!-- Free Trial Banner -->
        @if($currentPlan && $currentPlan['subscription']->is_trial)
            @php $trialEndTs = $currentPlan['end_date'] ? $currentPlan['end_date']->copy()->endOfDay()->timestamp : 0; @endphp
            <div class="mx-4 lg:mx-8 mt-4">
                <div class="bg-orange-50 border border-orange-200 px-5 py-4 rounded-xl">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-hourglass-half text-orange-500 text-xl flex-shrink-0 mt-1"></i>
                            <div>
                                <p class="font-bold text-orange-800 mb-2">Free Trial Active — Time Remaining</p>
                                <div class="flex items-center gap-2">
                                    <div class="bg-white border border-orange-200 rounded-lg px-3 py-2 text-center min-w-[56px]">
                                        <div id="trial-cd-days" class="text-2xl font-extrabold text-orange-600 leading-none">—</div>
                                        <div class="text-xs font-semibold text-orange-400 uppercase tracking-wide mt-0.5">days</div>
                                    </div>
                                    <span class="text-orange-400 font-bold text-xl">:</span>
                                    <div class="bg-white border border-orange-200 rounded-lg px-3 py-2 text-center min-w-[56px]">
                                        <div id="trial-cd-hrs" class="text-2xl font-extrabold text-orange-600 leading-none">—</div>
                                        <div class="text-xs font-semibold text-orange-400 uppercase tracking-wide mt-0.5">hrs</div>
                                    </div>
                                    <span class="text-orange-400 font-bold text-xl">:</span>
                                    <div class="bg-white border border-orange-200 rounded-lg px-3 py-2 text-center min-w-[56px]">
                                        <div id="trial-cd-min" class="text-2xl font-extrabold text-orange-600 leading-none">—</div>
                                        <div class="text-xs font-semibold text-orange-400 uppercase tracking-wide mt-0.5">min</div>
                                    </div>
                                    <span class="text-orange-400 font-bold text-xl">:</span>
                                    <div class="bg-white border border-orange-200 rounded-lg px-3 py-2 text-center min-w-[56px]">
                                        <div id="trial-cd-sec" class="text-2xl font-extrabold text-orange-600 leading-none">—</div>
                                        <div class="text-xs font-semibold text-orange-400 uppercase tracking-wide mt-0.5">sec</div>
                                    </div>
                                </div>
                                <p class="text-xs text-orange-600 mt-2">Upgrade to a paid plan to keep full access after trial ends.</p>
                            </div>
                        </div>
                        <a href="#pricing-plans" class="flex-shrink-0 bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors whitespace-nowrap">
                            Upgrade Now
                        </a>
                    </div>
                </div>
            </div>
            <script>
            (function() {
                var endTs = {{ $trialEndTs }} * 1000;
                function pad(n) { return n < 10 ? '0' + n : '' + n; }
                function tick() {
                    var diff = Math.floor((endTs - Date.now()) / 1000);
                    if (diff <= 0) {
                        ['days','hrs','min','sec'].forEach(function(id) {
                            var el = document.getElementById('trial-cd-' + id);
                            if (el) el.textContent = '00';
                        });
                        var badge = document.getElementById('trial-badge-text');
                        if (badge) badge.textContent = 'Expired';
                        return;
                    }
                    var d = Math.floor(diff / 86400);
                    var h = Math.floor((diff % 86400) / 3600);
                    var m = Math.floor((diff % 3600) / 60);
                    var s = diff % 60;
                    var dEl = document.getElementById('trial-cd-days');
                    var hEl = document.getElementById('trial-cd-hrs');
                    var mEl = document.getElementById('trial-cd-min');
                    var sEl = document.getElementById('trial-cd-sec');
                    if (dEl) dEl.textContent = d;
                    if (hEl) hEl.textContent = pad(h);
                    if (mEl) mEl.textContent = pad(m);
                    if (sEl) sEl.textContent = pad(s);
                    var badge = document.getElementById('trial-badge-text');
                    if (badge) badge.textContent = d + 'd ' + pad(h) + 'h ' + pad(m) + 'm left';
                    setTimeout(tick, 1000);
                }
                tick();
            })();
            </script>
        @endif

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
                                <i class="fas {{ $currentPlan['subscription']->is_trial ? 'fa-clock' : ($currentPlan['package']->price > 0 ? 'fa-crown' : 'fa-gift') }} mr-1.5 text-xs"></i>
                                {{ $currentPlan['package']->name }}
                                @if($currentPlan['subscription']->is_trial) &nbsp;· Free Trial @endif
                                @if($currentPlan['is_pending']) &nbsp;· Pending @endif
                            </span>
                            @if($currentPlan['subscription']->is_trial)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">
                                    <i class="fas fa-hourglass-half mr-1 text-xs"></i>
                                    <span id="trial-badge-text">...</span>
                                </span>
                            @elseif($currentPlan['package']->billing_type === 'yearly')
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
                                @if($currentPlan['subscription']->is_trial)
                                    Trial ends <strong class="text-orange-600">{{ $currentPlan['end_date']->format('M d, Y') }}</strong>
                                @else
                                    Renews <strong class="text-gray-600">{{ $currentPlan['end_date']->format('M d, Y') }}</strong>
                                @endif
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
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Choose Your Plan1</h3>

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
                                <form method="POST" action="{{ route('user.subscription.apply-free') }}">
                                    @csrf
                                    <input type="hidden" name="package_id" value="{{ $package->id }}">
                                    <button type="submit"
                                            class="w-full {{ $package->is_popular ? 'bg-white text-blue-600 hover:bg-gray-100' : 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                                        Select Free Plan
                                    </button>
                                </form>
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
                                <form method="POST" action="{{ route('user.subscription.apply-free') }}">
                                    @csrf
                                    <input type="hidden" name="package_id" value="{{ $package->id }}">
                                    <button type="submit"
                                            class="w-full {{ $package->is_popular ? 'bg-white text-blue-600 hover:bg-gray-100' : 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                                        Select Free Plan
                                    </button>
                                </form>
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

            <!-- Billing History -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-receipt text-primary-600 mr-2"></i>Billing History
                </h3>
                @if($billingHistory && $billingHistory->count() > 0)
                    <div class="space-y-3">
                        @foreach($billingHistory as $payment)
                            @php
                                $invoiceDate   = ($payment->paid_at ?? $payment->created_at)->format('M d, Y');
                                $invoiceDateFull = ($payment->paid_at ?? $payment->created_at)->format('d F Y');
                                $packageName   = $payment->subscription->package->name ?? 'Subscription';
                                $payMethodName = $payment->paymentMethod->name ?? '';
                                $currency      = $payment->currency ?? 'PKR';
                                $amount        = number_format($payment->amount, 2);
                                $invoiceNo     = 'INV-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
                                $screenshot    = $payment->screenshot ?? '';
                                $status        = $payment->status ?? 'paid';
                                $billingType   = optional(optional($payment->subscription)->package)->billing_type ?? '';
                            @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-gray-50 rounded-xl gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-file-invoice-dollar text-primary-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $packageName }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $invoiceNo }} &bull; {{ $invoiceDate }}
                                            @if($payMethodName) &bull; {{ $payMethodName }} @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 sm:flex-shrink-0">
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-800">{{ $currency }} {{ $amount }}</p>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold
                                            {{ $status === 'approved' || $status === 'paid' ? 'bg-green-100 text-green-700' : ($status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </div>
                                    <button type="button"
                                        onclick="openInvoiceModal({
                                            invoiceNo: '{{ $invoiceNo }}',
                                            date: '{{ $invoiceDateFull }}',
                                            package: '{{ addslashes($packageName) }}',
                                            billingType: '{{ $billingType }}',
                                            method: '{{ addslashes($payMethodName) }}',
                                            currency: '{{ $currency }}',
                                            amount: '{{ $amount }}',
                                            status: '{{ $status }}',
                                            screenshot: '{{ $screenshot ? asset('public/' . $screenshot) : '' }}',
                                            customerName: '{{ addslashes(auth()->user()->first_name . ' ' . auth()->user()->last_name) }}',
                                            customerEmail: '{{ auth()->user()->email }}'
                                        })"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                        <i class="fas fa-eye text-xs"></i>
                                        View Invoice
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10">
                        <i class="fas fa-receipt text-gray-300 text-4xl mb-3"></i>
                        <p class="text-sm text-gray-500">No billing history yet</p>
                        <p class="text-xs text-gray-400">Your payment history will appear here</p>
                    </div>
                @endif
            </div>

            <!-- Invoice Modal -->
            <div id="invoiceModal" class="fixed inset-0 z-50 flex items-center justify-center hidden" style="background:rgba(17,24,39,0.55);backdrop-filter:blur(3px);">
                <div style="width:100%;max-width:600px;margin:0 16px;max-height:94vh;display:flex;flex-direction:column;border-radius:12px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.25);">

                    <!-- Modal chrome bar -->
                    <div style="background:#fff;display:flex;align-items:center;justify-content:space-between;padding:14px 24px;border-bottom:1px solid #f3f4f6;flex-shrink:0;">
                        <span style="font-size:14px;font-weight:700;color:#111827;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-file-invoice" style="color:#ea580c;"></i> Payment Invoice
                        </span>
                        <button onclick="closeInvoiceModal()" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;" onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#9ca3af'">&times;</button>
                    </div>

                    <!-- Scrollable invoice body -->
                    <div style="overflow-y:auto;flex:1;background:#f9fafb;">
                        <div id="invoicePrintArea" style="background:#f9fafb;padding:16px;">
                            <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">

                                <!-- Header -->
                                <div style="padding:18px 32px 14px;text-align:center;border-bottom:1px solid #f3f4f6;">
                                    <img src="https://customernearme.com/public/images/logo/logo_1770443906.png"
                                         alt="CustomerNearMe"
                                         style="height:36px;object-fit:contain;display:inline-block;">
                                    <p style="margin:5px 0 0;font-size:10px;color:#9ca3af;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;">Payment Invoice</p>
                                </div>

                                <!-- Body -->
                                <div style="padding:18px 32px;">

                                    <!-- Customer + Invoice meta side by side -->
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;gap:12px;">
                                        <div>
                                            <p style="font-size:10px;color:#9ca3af;font-weight:500;letter-spacing:1px;text-transform:uppercase;margin:0 0 3px;">Bill To</p>
                                            <p id="inv-customer-name" style="font-size:13px;font-weight:700;color:#111827;margin:0;"></p>
                                            <p id="inv-customer-email" style="font-size:11px;color:#6b7280;margin:2px 0 0;word-break:break-all;"></p>
                                        </div>
                                        <div style="text-align:right;flex-shrink:0;">
                                            <p id="inv-number" style="font-size:12px;font-weight:700;color:#111827;margin:0;"></p>
                                            <p id="inv-date" style="font-size:11px;color:#6b7280;margin:2px 0 0;"></p>
                                        </div>
                                    </div>

                                    <!-- Info Box -->
                                    <div style="background:#fff7ed;border-left:3px solid #ea580c;padding:10px 14px;margin-bottom:14px;border-radius:0 6px 6px 0;">
                                        <table style="width:100%;border-collapse:collapse;font-size:12px;">
                                            <tr>
                                                <td style="padding:3px 0;color:#6b7280;width:40%;">Plan</td>
                                                <td id="inv-package" style="padding:3px 0;color:#111827;font-weight:600;text-align:right;"></td>
                                            </tr>
                                            <tr id="inv-billing-row">
                                                <td style="padding:3px 0;color:#6b7280;">Billing Cycle</td>
                                                <td id="inv-billing" style="padding:3px 0;color:#111827;font-weight:600;text-align:right;text-transform:capitalize;"></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:3px 0;color:#6b7280;">Payment Method</td>
                                                <td id="inv-method" style="padding:3px 0;color:#111827;font-weight:600;text-align:right;"></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:3px 0;color:#6b7280;">Status</td>
                                                <td style="padding:3px 0;text-align:right;">
                                                    <span id="inv-status" style="font-size:11px;font-weight:700;"></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Total row -->
                                    <div style="display:flex;justify-content:space-between;align-items:center;border-top:2px solid #f3f4f6;padding-top:12px;margin-bottom:12px;">
                                        <span style="font-size:13px;font-weight:700;color:#111827;">Total Amount Paid</span>
                                        <span id="inv-amount" style="font-size:20px;font-weight:800;color:#ea580c;"></span>
                                    </div>

                                    <!-- Screenshot link -->
                                    <div id="inv-screenshot-wrap" style="display:none;margin-bottom:10px;">
                                        <a id="inv-screenshot" href="#" target="_blank"
                                           style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#ea580c;font-weight:600;text-decoration:none;">
                                            <i class="fas fa-image"></i> View Payment Screenshot
                                        </a>
                                    </div>

                                    <p style="font-size:11px;color:#9ca3af;margin:0;">
                                        Questions? Contact our support team. This is a computer-generated invoice.
                                    </p>
                                </div>

                                <!-- Footer -->
                                <div style="background:#f9fafb;padding:12px 32px;text-align:center;border-top:1px solid #f3f4f6;">
                                    <p style="font-size:13px;font-weight:700;color:#111827;margin:0 0 2px;">{{ config('app.name') }}</p>
                                    <p style="font-size:11px;color:#9ca3af;margin:0 0 8px;">Professional GMB Lead Generation</p>
                                    <p style="font-size:10px;color:#9ca3af;margin:0;">
                                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                                    </p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Actions bar -->
                    <div style="background:#fff;display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:14px 24px;border-top:1px solid #f3f4f6;flex-shrink:0;">
                        <button onclick="closeInvoiceModal()" style="background:none;border:1px solid #e5e7eb;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:500;color:#6b7280;cursor:pointer;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='none'">Close</button>
                        <button onclick="printInvoice()" style="background:#ea580c;border:none;padding:9px 20px;border-radius:8px;font-size:13px;font-weight:600;color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:7px;" onmouseover="this.style.background='#c2410c'" onmouseout="this.style.background='#ea580c'">
                            <i class="fas fa-file-pdf"></i> Download / Print PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Plan Management Actions -->
            @php
                $currentPrice    = $currentPlan ? (float)$currentPlan['package']->price : 0;
                $hasHigherPlan   = $availablePlans->filter(fn($p) => (float)$p->price > $currentPrice)->count() > 0;
                $showUpgrade     = !$currentPlan || (!$currentPlan['is_pending'] && $hasHigherPlan);
                $showCancel      = $currentPlan && $currentPlan['is_active'];
                $showManagement  = $showUpgrade || $showCancel;
            @endphp
            @if($showManagement)
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mt-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Plan Management</h3>

                <div class="grid grid-cols-1 {{ $showUpgrade && $showCancel ? 'md:grid-cols-2' : 'md:grid-cols-1' }} gap-3 sm:gap-4">
                    @if($showUpgrade)
                    <a href="#pricing-plans" class="flex items-center justify-center px-3 sm:px-4 py-2 sm:py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm sm:text-base font-medium">
                        <i class="fas fa-{{ $currentPlan ? 'arrow-up' : 'rocket' }} mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                        {{ $currentPlan ? 'Upgrade Plan' : 'Get Started' }}
                    </a>
                    @endif

                    @if($showCancel)
                    <button onclick="document.getElementById('cancelPlanModal').classList.remove('hidden'); document.body.style.overflow='hidden';"
                            class="flex items-center justify-center px-3 sm:px-4 py-2 sm:py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm sm:text-base font-medium">
                        <i class="fas fa-times mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                        Cancel Plan
                    </button>
                    @endif
                </div>
            </div>
            @endif
                
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

    {{-- Cancel Plan Confirmation Modal --}}
    <div id="cancelPlanModal" class="fixed inset-0 z-50 flex items-center justify-center hidden" style="background:rgba(17,24,39,0.55);backdrop-filter:blur(3px);">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Cancel Plan</h3>
                </div>
                <p class="text-gray-600 mb-6">Are you sure you want to cancel your current plan? You will lose access to all premium features immediately.</p>
                <div class="flex gap-3 justify-end">
                    <button onclick="document.getElementById('cancelPlanModal').classList.add('hidden'); document.body.style.overflow='';"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Keep Plan
                    </button>
                    <form action="{{ route('user.subscription.cancel') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">
                            Yes, Cancel Plan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openInvoiceModal(data) {
            document.getElementById('inv-number').textContent  = data.invoiceNo;
            document.getElementById('inv-date').textContent    = data.date;
            document.getElementById('inv-package').textContent = data.package;
            document.getElementById('inv-amount').textContent  = data.currency + ' ' + data.amount;
            document.getElementById('inv-method').textContent  = data.method || '—';

            // Customer info (from auth user passed via blade)
            document.getElementById('inv-customer-name').textContent  = data.customerName  || '—';
            document.getElementById('inv-customer-email').textContent = data.customerEmail || '';

            var billingCell = document.getElementById('inv-billing');
            billingCell.textContent = data.billingType || '—';
            document.getElementById('inv-billing-row').style.display = data.billingType ? '' : 'none';

            var statusEl = document.getElementById('inv-status');
            var statusLabel = data.status.charAt(0).toUpperCase() + data.status.slice(1);
            statusEl.textContent = statusLabel;
            statusEl.removeAttribute('style');
            if (data.status === 'approved' || data.status === 'paid') {
                statusEl.style.cssText = 'display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;background:#dcfce7;color:#15803d;';
            } else if (data.status === 'pending') {
                statusEl.style.cssText = 'display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;background:#fef9c3;color:#a16207;';
            } else {
                statusEl.style.cssText = 'display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;background:#f3f4f6;color:#6b7280;';
            }

            var ssWrap = document.getElementById('inv-screenshot-wrap');
            if (data.screenshot) {
                document.getElementById('inv-screenshot').href = data.screenshot;
                ssWrap.style.display = 'block';
            } else {
                ssWrap.style.display = 'none';
            }

            document.getElementById('invoiceModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeInvoiceModal() {
            document.getElementById('invoiceModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function printInvoice() {
            var content = document.getElementById('invoicePrintArea').innerHTML;
            var win = window.open('', '_blank', 'width=620,height=700');
            win.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Invoice</title>');
            win.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">');
            win.document.write('<style>');
            win.document.write('@page{size:A4;margin:12mm;}');
            win.document.write('*{box-sizing:border-box;margin:0;padding:0;}');
            win.document.write('html,body{height:100%;}');
            win.document.write('body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;font-size:12px;line-height:1.5;color:#374151;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;}');
            win.document.write('table{width:100%;border-collapse:collapse;}');
            win.document.write('img{display:inline-block;max-width:100%;}');
            win.document.write('a{color:#ea580c;text-decoration:none;}');
            win.document.write('</style></head><body>');
            win.document.write(content);
            win.document.write('</body></html>');
            win.document.close();
            win.focus();
            setTimeout(function(){ win.print(); }, 500);
        }

        // Close modal on backdrop click
        document.getElementById('invoiceModal').addEventListener('click', function(e) {
            if (e.target === this) closeInvoiceModal();
        });

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
