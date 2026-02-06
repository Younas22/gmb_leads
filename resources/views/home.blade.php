<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>BusinessFinder - Find Quality Business Leads Fast</title>
    <link rel="icon" type="image/png" href="{{ asset('public/assets/images/favicon.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-blue': '#3b82f6',
                        'dark-blue': '#2563eb',
                        'primary-orange': '#f97316',
                        'dark-orange': '#ea580c'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-50 backdrop-blur-sm bg-white/95">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
    <div class="flex-shrink-0 flex items-center">
        <img 
            src="{{ asset('public/assets/images/logo.svg') }}" 
            alt="CustomerNearMe Logo" 
            class="w-50 h-auto object-contain"
        >
    </div>
</div>

                
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-8">
                        <a href="#features" class="text-gray-700 hover:text-primary-blue px-3 py-2 text-sm font-medium transition-colors">Features</a>
                        <a href="#pricing" class="text-gray-700 hover:text-primary-blue px-3 py-2 text-sm font-medium transition-colors">Pricing</a>
                        <a href="#faq" class="text-gray-700 hover:text-primary-blue px-3 py-2 text-sm font-medium transition-colors">FAQ</a>
                        <a href="#contact" class="text-gray-700 hover:text-primary-blue px-3 py-2 text-sm font-medium transition-colors">Contact</a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    @guest
                        <a href="{{ route('auth.show') }}" class="text-gray-700 hover:text-primary-blue px-3 py-2 text-sm font-medium">Sign In</a>
                        <a href="{{ route('auth.show') }}" class="bg-primary-blue hover:bg-dark-blue text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Start Free</a>
                    @endguest

                    @auth
                        <a href="{{ route('user.dashboard') }}" class="bg-primary-blue hover:bg-dark-blue text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                            <i class="fas fa-th-large mr-2"></i>
                            Dashboard
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('payment_success'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl flex items-center">
            <svg class="w-5 h-5 mr-3 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <div>
                <strong>Payment Submitted!</strong> We’ve received your payment screenshot. Our team will verify it and activate your subscription shortly.
            </div>
        </div>
    </div>
    @endif

    <!-- Hero Section -->
    <section class="pt-20 pb-16 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
                    Find Quality Business Leads
                    <span class="text-primary-orange">In Minutes</span>
                </h1>
                <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                    Discover verified business contacts using Google Places API. Save time, build your pipeline, and grow your business with accurate lead data.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button class="bg-primary-blue hover:bg-dark-blue text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all hover:scale-105">
                        Start Free Trial
                    </button>
                    <button class="border-2 border-primary-blue text-primary-blue hover:bg-primary-blue hover:text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all">
                        See Live Demo
                    </button>
                </div>
                <div class="mt-12">
                    <div class="bg-white rounded-xl shadow-xl p-4 max-w-4xl mx-auto">
                        <img src="{{asset('public/assets/images/dashboard.jpg')}}" alt="BusinessFinder Dashboard" class="w-full rounded-lg">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Powerful Features</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">Everything you need to find and manage business leads efficiently</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 bg-primary-blue rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Google Places Integration</h3>
                    <p class="text-gray-600">Direct access to Google's comprehensive business database with real-time data and verified information.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 bg-primary-orange rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Advanced Search Filters</h3>
                    <p class="text-gray-600">Filter by location, business category, radius, ratings, and more to find your ideal prospects.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 bg-primary-blue rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Lead Management</h3>
                    <p class="text-gray-600">Organize, track, and manage your leads with built-in contact management and notes system.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 bg-primary-orange rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Export Capabilities</h3>
                    <p class="text-gray-600">Export your leads to CSV, Excel, or integrate with your CRM through our API.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 bg-primary-blue rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">API Dashboard</h3>
                    <p class="text-gray-600">Monitor your API usage, manage keys, and track performance with detailed analytics.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 bg-primary-orange rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Real-Time Updates</h3>
                    <p class="text-gray-600">Get the latest business information with real-time data updates from Google Places.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">How It Works</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">Get started in minutes with our simple 4-step process</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-blue rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-white font-bold text-xl">1</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Search Businesses</h3>
                    <p class="text-gray-600">Enter location, category, or keywords to find businesses in any area worldwide.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-orange rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-white font-bold text-xl">2</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Review Profiles</h3>
                    <p class="text-gray-600">View detailed business profiles with contact information, ratings, and reviews.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-blue rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-white font-bold text-xl">3</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Save & Organize</h3>
                    <p class="text-gray-600">Add promising leads to your database and organize them with tags and notes.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-orange rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-white font-bold text-xl">4</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Export & Engage</h3>
                    <p class="text-gray-600">Export your leads or use our CRM integration to start your outreach campaign.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Simple Pricing</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">Choose the plan that fits your business needs</p>
            </div>

            <!-- Individual / Company Tabs (only show if both types have packages) -->
            @if($companyPackages->count() > 0)
            <div class="flex justify-center mb-14">
                <div class="inline-flex bg-gray-100 rounded-full p-1">
                    <button class="tab-btn bg-primary-blue text-white px-6 py-2 rounded-full text-sm font-semibold transition-colors cursor-pointer" data-tab="user">Individual</button>
                    <button class="tab-btn text-gray-600 px-6 py-2 rounded-full text-sm font-semibold transition-colors hover:text-gray-900 cursor-pointer" data-tab="company">Company</button>
                </div>
            </div>
            @endif

            
            @php
                $featureLabels = [
                    'gmb_searches'        => 'GMB Searches',
                    'leads_per_month'     => 'Leads / Month',
                    'export_leads'        => 'Lead Exports',
                    'saved_lists'         => 'Saved Lists',
                    'email_support'       => 'Email Support',
                    'api_access'          => 'API Access',
                    'api_limit'          => 'API Limit',
                    'bulk_export'         => 'Bulk Export',
                    'crm_integration'     => 'CRM Integration',
                    'priority_support'    => 'Priority Support',
                    'api_calls'           => 'API Calls',
                    'dedicated_manager'   => 'Dedicated Manager',
                    'team_members'        => 'Team Members',
                    'team_analytics'      => 'Team Analytics',
                    'white_label'         => 'White Label',
                    'custom_branding'     => 'Custom Branding',
                    'sla_guarantee'       => 'SLA Guarantee',
                    'custom_integrations' => 'Custom Integrations',
                    'onboarding_training' => 'Onboarding & Training',
                ];
                $boolFeatures = [
                    'email_support', 'api_access', 'bulk_export',
                    'crm_integration', 'priority_support', 'dedicated_manager',
                    'team_analytics', 'white_label', 'custom_branding',
                    'sla_guarantee', 'custom_integrations', 'onboarding_training',
                ];
                $pricingTabs = [
                    ['type' => 'user',    'packages' => $userPackages],
                ];
                if($companyPackages->count() > 0) {
                    $pricingTabs[] = ['type' => 'company', 'packages' => $companyPackages];
                }
            @endphp

            @foreach($pricingTabs as $tab)
            <div class="mt-14 pricing-grid {{ $tab['type'] === 'user' ? '' : 'hidden' }}" data-tab="{{ $tab['type'] }}">
                <div class="grid sm:grid-cols-2 {{ $tab['type'] === 'user' ? 'lg:grid-cols-4' : 'lg:grid-cols-4' }} gap-6">
                    @foreach($tab['packages'] as $package)
                    <div class="rounded-xl p-8 flex flex-col {{ $package->is_popular ? 'bg-primary-blue text-white relative transform scale-105' : 'bg-white border-2 border-gray-200 hover:border-primary-blue transition-colors' }}">
                        @if($package->is_popular)
                            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-primary-orange text-white px-4 py-1 rounded-full text-sm font-semibold">
                                Popular
                            </div>
                        @endif

                        <div class="text-center mb-6">
                            <h3 class="text-xl font-semibold {{ $package->is_popular ? '' : 'text-gray-900' }} mb-2">{{ $package->name }}</h3>
                            <div class="text-4xl font-bold {{ $package->is_popular ? '' : 'text-gray-900' }}">
                                @if($package->price == 0)
                                    $0<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/month</span>
                                @elseif($package->billing_type === 'yearly')
                                    ${{ number_format((float)$package->price / 12, 0) }}<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/mo</span>
                                @elseif($package->billing_type === 'lifetime')
                                    ${{ number_format((float)$package->price, 0) }}<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}"> once</span>
                                @else
                                    ${{ number_format((float)$package->price, 0) }}<span class="text-lg {{ $package->is_popular ? 'opacity-80' : 'text-gray-600' }}">/month</span>
                                @endif
                            </div>
                            @if($package->billing_type === 'yearly' && $package->price > 0)
                                <div class="text-sm {{ $package->is_popular ? 'opacity-70' : 'text-gray-500' }} mt-1">billed ${{ number_format((float)$package->price, 0) }}/year</div>
                            @endif
                        </div>

                        <ul class="space-y-4 mb-8 flex-grow">
                            @foreach($package->features as $feature)
                                @if(isset($featureLabels[$feature->feature_key]))
                                    <li class="flex items-center">
                                        @if(in_array($feature->feature_key, $boolFeatures))
                                            @if($feature->feature_value === 'true')
                                                <svg class="w-5 h-5 {{ $package->is_popular ? 'text-green-400' : 'text-green-500' }} mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {{ $featureLabels[$feature->feature_key] }}
                                            @else
                                                <svg class="w-5 h-5 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                        @if($package->price == 0)
                        <a href="{{ route('auth.show') }}" class="block w-full text-center {{ $package->is_popular ? 'bg-white text-primary-blue hover:bg-gray-100' : 'border-2 border-primary-blue text-primary-blue hover:bg-primary-blue hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                            Start Free
                        </a>
                        @else
                        <button onclick="handleGetStarted(this)"
                                data-package-id="{{ $package->id }}"
                                data-package-name="{{ e($package->name) }}"
                                data-package-price="{{ $package->price }}"
                                class="w-full {{ $package->is_popular ? 'bg-white text-primary-blue hover:bg-gray-100' : 'border-2 border-primary-blue text-primary-blue hover:bg-primary-blue hover:text-white' }} py-3 rounded-lg font-semibold transition-colors">
                            Get Started
                        </button>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </section>

    <!-- Social Proof -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Trusted by Growing Businesses</h2>
                <div class="grid md:grid-cols-3 gap-8 mb-16">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary-blue mb-2">50,000+</div>
                        <div class="text-gray-600">Businesses Found</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary-orange mb-2">25,000+</div>
                        <div class="text-gray-600">Leads Generated</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary-blue mb-2">1,200+</div>
                        <div class="text-gray-600">Happy Users</div>
                    </div>
                </div>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-primary-blue rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">JS</span>
                        </div>
                        <div class="ml-3">
                            <div class="font-semibold text-gray-900">John Smith</div>
                            <div class="text-sm text-gray-600">Sales Director</div>
                        </div>
                    </div>
                    <p class="text-gray-700">"BusinessFinder helped us identify 500+ potential clients in our target market. The Google Places integration is incredibly accurate."</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-primary-orange rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">MJ</span>
                        </div>
                        <div class="ml-3">
                            <div class="font-semibold text-gray-900">Maria Johnson</div>
                            <div class="text-sm text-gray-600">Marketing Manager</div>
                        </div>
                    </div>
                    <p class="text-gray-700">"The export feature saved us hours of manual work. We can now focus on reaching out to qualified prospects instead of data collection."</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-primary-blue rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">DL</span>
                        </div>
                        <div class="ml-3">
                            <div class="font-semibold text-gray-900">David Lee</div>
                            <div class="text-sm text-gray-600">Business Owner</div>
                        </div>
                    </div>
                    <p class="text-gray-700">"Simple, fast, and reliable. BusinessFinder has become an essential tool for our lead generation process."</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
                <p class="text-xl text-gray-600">Everything you need to know about BusinessFinder</p>
            </div>
            
            <div class="space-y-6">
                <div class="bg-gray-50 rounded-lg p-6">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">Do I need my own Google Places API key?</h3>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="hidden mt-4 text-gray-600">
                        No, you don't need your own API key to get started. Our Free plan includes access to our shared API. For Pro plans, you can optionally use your own API key for unlimited requests.
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-6">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">How accurate is the business data?</h3>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="hidden mt-4 text-gray-600">
                        Our data comes directly from Google Places API, which maintains the most up-to-date and accurate business information available. Data is refreshed in real-time to ensure accuracy.
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-6">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">What export formats are supported?</h3>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="hidden mt-4 text-gray-600">
                        We support CSV and Excel exports for all plans. Pro users also get access to JSON exports and direct CRM integrations with popular platforms like Salesforce and HubSpot.
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-6">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">Can I cancel my subscription anytime?</h3>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="hidden mt-4 text-gray-600">
                        Yes, you can cancel your subscription at any time. There are no long-term contracts or cancellation fees. Your access will continue until the end of your billing period.
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-6">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">Is there a free trial for Pro plans?</h3>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="hidden mt-4 text-gray-600">
                        Yes! We offer a 14-day free trial for all Pro plans. No credit card required to start your trial. You can upgrade, downgrade, or cancel at any time.
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-6">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(this)">
                        <h3 class="text-lg font-semibold text-gray-900">Do you offer customer support?</h3>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="hidden mt-4 text-gray-600">
                        Yes! Free users get community support, while Pro users receive priority email support. We also offer live chat support for technical issues and onboarding assistance.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-primary-blue to-dark-blue">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Ready to Find Your Next Customer?</h2>
            <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                Join thousands of businesses using BusinessFinder to grow their customer base with verified leads.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button class="bg-white text-primary-blue hover:bg-gray-100 px-8 py-4 rounded-lg text-lg font-semibold transition-all hover:scale-105">
                    Start Free Trial
                </button>
                <button class="border-2 border-white text-white hover:bg-white hover:text-primary-blue px-8 py-4 rounded-lg text-lg font-semibold transition-all">
                    Schedule Demo
                </button>
            </div>
        </div>
    </section>

    {{-- Payment Modal --}}
    @include('partials.payment-modal')

        <!-- Footer -->
    <footer id="contact" class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <!-- Logo and Company Name -->
                <div class="flex items-center justify-center mb-6">
                    <div class="w-8 h-8 bg-gradient-to-r from-primary-blue to-primary-orange rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">BF</span>
                    </div>
                    <span class="ml-2 text-xl font-bold">BusinessFinder</span>
                </div>
                
                <!-- Contact Info -->
                <p class="text-gray-400 mb-6">
                    Find verified business contacts using Google Places API
                </p>
                
                <!-- Links -->
                <div class="flex flex-wrap justify-center gap-6 mb-8 text-sm">
                    <a href="#features" class="text-gray-400 hover:text-white transition-colors">Features</a>
                    <a href="#pricing" class="text-gray-400 hover:text-white transition-colors">Pricing</a>
                    <a href="#faq" class="text-gray-400 hover:text-white transition-colors">FAQ</a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">Contact Us</a>
                    <!-- <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a> -->
                    <!-- <a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a> -->
                </div>
                
                <!-- Copyright -->
                <div class="border-t border-gray-800 pt-6">
                    <p class="text-gray-400 text-sm">
                        © 2025 BusinessFinder. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer -->
    <!-- <footer id="contact" class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div class="md:col-span-2">
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 bg-gradient-to-r from-primary-blue to-primary-orange rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">BF</span>
                        </div>
                        <span class="ml-2 text-xl font-bold">BusinessFinder</span>
                    </div>
                    <p class="text-gray-400 mb-6 max-w-md">
                        The most powerful lead generation tool for finding verified business contacts using Google Places API.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.404-5.967 1.404-5.967s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.097.118.112.222.085.343-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.748-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 23.998 12.017 24c6.624 0 11.99-5.367 11.99-11.987C24.007 5.367 18.641.001 12.017.001z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Product</h3>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
                        <li><a href="#pricing" class="text-gray-400 hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">API Documentation</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Integrations</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Support</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Status Page</a></li>
                        <li><a href="#faq" class="text-gray-400 hover:text-white transition-colors">FAQ</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-400 text-sm mb-4 md:mb-0">
                        © 2025 BusinessFinder. All rights reserved.
                    </div>
                    <div class="flex space-x-6 text-sm">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer> -->

    <script>
        // FAQ Toggle Function
        function toggleFAQ(button) {
            const content = button.nextElementSibling;
            const icon = button.querySelector('svg');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('nav');
            if (window.scrollY > 100) {
                navbar.classList.add('shadow-lg');
            } else {
                navbar.classList.remove('shadow-lg');
            }
        });

        // Pricing: Individual/Company toggle
        (function() {
            var currentTab = 'user';

            document.querySelectorAll('.tab-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    currentTab = this.dataset.tab;
                    document.querySelectorAll('.tab-btn').forEach(function(b) {
                        var active = b.dataset.tab === currentTab;
                        b.classList.toggle('bg-primary-blue', active);
                        b.classList.toggle('text-white', active);
                        b.classList.toggle('text-gray-600', !active);
                    });
                    document.querySelectorAll('.pricing-grid').forEach(function(grid) {
                        grid.classList.toggle('hidden', grid.dataset.tab !== currentTab);
                    });
                });
            });
        })();
    </script>
</body>
</html>