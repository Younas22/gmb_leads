@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')
<main class="p-4 lg:p-8">
@include('user.welcome-tutorial-modal')

<!-- Email Verification Notice -->
@if(!auth()->user()->email_verified)
<div class="mb-6">
    <div class="bg-blue-50 border-l-4 border-blue-400 rounded-lg p-4 shadow-sm">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-envelope text-blue-600 text-2xl"></i>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-semibold text-blue-800 mb-1">
                    <i class="fas fa-info-circle mr-2"></i>Please Verify Your Email
                </h3>
                <p class="text-sm text-blue-700 mb-3">
                    We've sent a verification email to <strong>{{ auth()->user()->email }}</strong>. Please check your inbox and click the verification link to complete your registration.
                </p>
                <div class="flex flex-wrap gap-3">
                    <button onclick="resendVerification()" id="resendBtn" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Resend Verification Email
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resendVerification() {
    const btn = document.getElementById('resendBtn');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';

    fetch('{{ route("auth.resend.verification") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>Email Sent!';
            btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            btn.classList.add('bg-green-600');

            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('bg-green-600');
                btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                btn.disabled = false;
            }, 3000);
        } else {
            alert(data.message || 'Failed to send verification email');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending verification email');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>
@endif

<!-- Pending Subscription Warning -->
@if(auth()->user()->hasRestrictedAccess())
<div class="mb-6">
    <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 shadow-sm">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-semibold text-yellow-800 mb-1">
                    <i class="fas fa-clock mr-2"></i>Subscription Pending Activation
                </h3>
                <p class="text-sm text-yellow-700 mb-3">
                    Your payment has been submitted successfully and is currently under review by our admin team.
                    You will receive full access to all features once your subscription is activated.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('user.subscription') }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-info-circle mr-2"></i>
                        View Subscription Status
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Warning flash message from middleware -->
@if(session('warning'))
<div class="mb-6">
    <div class="bg-orange-50 border border-orange-200 text-orange-700 px-5 py-4 rounded-xl flex items-center">
        <i class="fas fa-exclamation-circle text-orange-500 mr-3"></i>
        <div>{{ session('warning') }}</div>
    </div>
</div>
@endif

<!-- Welcome Section -->
<div class="mb-8">
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl p-4 sm:p-6 text-white">
        <div class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex-1 lg:pr-6">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-2">Welcome back, {{ $user->first_name }}!</h1>
                <p class="text-primary-100 text-sm sm:text-base">
                    @if($usageData['daily_leads']['is_unlimited'])
                        You have unlimited leads today. Start building your business network!
                    @elseif($usageData['daily_leads']['has_plan'])
                        You have {{ number_format($usageData['daily_leads']['remaining']) }} leads remaining today out of {{ number_format($usageData['daily_leads']['limit']) }}.
                    @else
                        Start searching for businesses to build your lead database.
                    @endif
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 lg:flex-shrink-0">
                @if(!auth()->user()->hasRestrictedAccess())
                    <a href="{{ route('user.tutorials') }}" class="inline-flex items-center justify-center bg-orange-500 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-orange-600 transition-colors text-sm sm:text-base">
                        <i class="fas fa-play-circle mr-2"></i>
                        <span class="hidden sm:inline">Watch </span>Tutorials
                    </a>
                    <a href="{{ route('user.search') }}" class="inline-flex items-center justify-center bg-white text-primary-600 px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm sm:text-base">
                        <i class="fas fa-search mr-2"></i>
                        <span class="hidden sm:inline">Start </span>Searching
                    </a>

                    <a href="{{ route('user.leads') }}" class="inline-flex items-center justify-center bg-primary-800 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-primary-900 transition-colors text-sm sm:text-base">
                        <i class="fas fa-bookmark mr-2"></i>
                        <span class="hidden sm:inline">View </span>Leads
                    </a>
                @else
                    <a href="{{ route('user.subscription') }}" class="inline-flex items-center justify-center bg-yellow-500 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-yellow-600 transition-colors text-sm sm:text-base">
                        <i class="fas fa-clock mr-2"></i>
                        <span class="hidden sm:inline">View </span>Subscription Status
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

    <!-- Lead Status Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Total Leads -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Total Leads</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_leads']) }}</p>
                </div>
                <div class="bg-indigo-100 rounded-lg p-3">
                    <i class="fas fa-users text-indigo-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Contacted -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Contacted</p>
                    <p class="text-3xl font-bold text-blue-600">{{ number_format($stats['contacted_leads']) }}</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-phone-alt text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Pending</p>
                    <p class="text-3xl font-bold text-orange-500">{{ number_format($stats['pending_leads']) }}</p>
                </div>
                <div class="bg-orange-100 rounded-lg p-3">
                    <i class="fas fa-clock text-orange-500 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Converted -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">Converted</p>
                    <p class="text-3xl font-bold text-green-600">{{ number_format($stats['converted_leads']) }}</p>
                </div>
                <div class="bg-green-100 rounded-lg p-3">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Plan Stats -->
    @if($currentPlan)
    @php
        $pf           = $currentPlan['package']->features->keyBy('feature_key');
        $dl           = $usageData['daily_leads'];
        $dlFeature    = $pf->get('daily_leads_limit');
        $exportLeads  = $pf->get('export_leads');
        $maxDevices   = $pf->get('max_devices');
        $prioritySupp = $pf->get('priority_support');
    @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-bar text-orange-500"></i>
                Current Plan — Leads Limit
            </h3>
            <a href="{{ route('user.subscription') }}" class="text-xs text-primary-600 hover:underline">View Plan</a>
        </div>
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
                    @if($dl['is_unlimited'])
                        <span class="text-xl font-bold text-gray-800">Unlimited</span>
                    @else
                        <span class="text-xl font-bold {{ $dl['percentage'] >= 85 ? 'text-red-600' : 'text-gray-800' }}">
                            {{ number_format($dl['used']) }}
                        </span>
                        <span class="text-sm text-gray-500">/ {{ number_format($dl['limit']) }} today</span>
                    @endif
                </div>
                @if(!$dl['is_unlimited'])
                    <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2 mb-1">
                        <div class="h-1.5 rounded-full {{ $dl['percentage'] >= 85 ? 'bg-red-500' : 'bg-orange-400' }}"
                             style="width:<?php echo min($dl['percentage'], 100); ?>%"></div>
                    </div>
                    <p class="text-xs {{ $dl['percentage'] >= 85 ? 'text-red-500' : 'text-gray-400' }}">
                        {{ number_format($dl['remaining']) }} remaining today
                        @if($dl['percentage'] >= 85) · <span class="font-medium">Near limit</span> @endif
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
                    @if($prioritySupp && $prioritySupp->feature_value === 'true')
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
    </div>
    @endif

    @if($stats['total_leads'] == 0)
    <!-- Getting Started Guide -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-sm p-6 border border-blue-100 mb-8">
        <div class="flex items-start space-x-4">
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-rocket text-blue-600 text-xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Get Started with Lead Generation</h3>
                <p class="text-gray-600 mb-4">
                    @if($usageData['daily_leads']['is_unlimited'])
                        You have unlimited access! Here's how to get started:
                    @elseif($usageData['daily_leads']['has_plan'])
                        You have {{ number_format($usageData['daily_leads']['remaining']) }} leads remaining today. Here's how to get started:
                    @else
                        Here's how to get started:
                    @endif
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-white p-4 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-2">
                            <div class="bg-blue-100 rounded p-1 mr-2">
                                <i class="fas fa-search text-blue-600 text-sm"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">1. Search</h4>
                        </div>
                        <p class="text-sm text-gray-600">Search for businesses like "restaurants in Dubai" or "travel agencies"</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-2">
                            <div class="bg-green-100 rounded p-1 mr-2">
                                <i class="fas fa-bookmark text-green-600 text-sm"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">2. Save</h4>
                        </div>
                        <p class="text-sm text-gray-600">Save promising leads with contact details and business info</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-2">
                            <div class="bg-orange-100 rounded p-1 mr-2">
                                <i class="fas fa-phone text-orange-600 text-sm"></i>
                            </div>
                            <h4 class="font-medium text-gray-800">3. Contact</h4>
                        </div>
                        <p class="text-sm text-gray-600">Reach out to leads and track your progress</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('user.tutorials') }}" class="inline-flex items-center justify-center bg-orange-500 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium hover:bg-orange-600 transition-colors text-sm sm:text-base">
                        <i class="fas fa-play-circle mr-2"></i>
                        <span class="hidden sm:inline">Watch </span>Tutorials
                    </a>
                    <a href="{{ route('user.search') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-search mr-2"></i>Start First Search
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" style="display: none;">
        <!-- Recent Leads (2/3 width) -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Leads</h3>
                    <a href="{{ route('user.leads') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">View all ({{ $stats['total_leads'] }})</a>
                </div>
            </div>
            <div class="p-6">
                @if(count($recentLeads) > 0)
                    <div class="space-y-4">
                        @foreach($recentLeads as $lead)
                        <div class="flex items-center space-x-4 p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
                            <div class="bg-primary-100 rounded-lg p-2">
                                <i class="fas fa-building text-primary-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $lead->name }}</p>
                                <p class="text-xs text-gray-500">{{ Str::limit($lead->address, 40) }} @if($lead->rating) • {{ number_format($lead->rating, 1) }} rating @endif @if($lead->phone) • {{ $lead->phone }} @endif</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                    @if($lead->contact_status == 'not_contacted') bg-gray-100 text-gray-800
                                    @elseif($lead->contact_status == 'contacted') bg-blue-100 text-blue-800
                                    @elseif($lead->contact_status == 'responded') bg-yellow-100 text-yellow-800
                                    @elseif($lead->contact_status == 'converted') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $lead->contact_status)) }}
                                </span>
                                <p class="text-xs text-gray-400 mt-1">{{ $lead->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-bookmark text-gray-300 text-4xl mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-500 mb-2">No leads saved yet</h4>
                        <p class="text-gray-400 mb-4">Start searching for businesses to build your lead database</p>
                        <a href="{{ route('user.search') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-search mr-2"></i>Start Searching
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions & Status (1/3 width) -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-bolt text-yellow-600 mr-2"></i>
                    Quick Actions
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('user.search') }}" class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors group">
                        <div class="bg-blue-100 group-hover:bg-blue-200 rounded-lg p-2 mr-3">
                            <i class="fas fa-search text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">New Search</p>
                            <p class="text-xs text-gray-500">Find new business leads</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('user.leads') }}" class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors group">
                        <div class="bg-green-100 group-hover:bg-green-200 rounded-lg p-2 mr-3">
                            <i class="fas fa-bookmark text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Manage Leads</p>
                            <p class="text-xs text-gray-500">View and organize saved leads</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('user.profile') }}" class="flex items-center p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors group">
                        <div class="bg-purple-100 group-hover:bg-purple-200 rounded-lg p-2 mr-3">
                            <i class="fas fa-user text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Profile Settings</p>
                            <p class="text-xs text-gray-500">Update your information</p>
                        </div>
                    </a>

                    @if(auth()->user()->isCompany())
                    <a href="{{ route('user.team-members') }}" class="flex items-center p-3 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors group">
                        <div class="bg-indigo-100 group-hover:bg-indigo-200 rounded-lg p-2 mr-3">
                            <i class="fas fa-users text-indigo-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Team Members</p>
                            <p class="text-xs text-gray-500">Manage your team</p>
                        </div>
                    </a>
                    @endif
                </div>
            </div>

            <!-- Account Status -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-crown text-yellow-600 mr-2"></i>
                    Account Status
                </h3>
                <div class="space-y-4">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border border-green-200">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-gift text-green-600 mr-2"></i>
                            <span class="font-medium text-green-800">Free Trial Active</span>
                        </div>
                        <p class="text-sm text-green-700 mb-3">First month free with unlimited leads</p>
                        <div class="bg-white p-2 rounded border">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>Days remaining</span>
                                <span>{{ $stats['trial_days_left'] }} days</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full transition-all" style="width: {{ ($stats['trial_days_left'] / 30) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-2">
                        <p class="text-xs text-gray-500 mb-2">Want to continue after trial?</p>
                        <button class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors w-full">
                            <i class="fas fa-star mr-2"></i>View Plans
                        </button>
                    </div>
                </div>
            </div>

            @if(count($recentSearches) > 0)
            <!-- Recent Searches -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-history text-gray-600 mr-2"></i>
                    Recent Searches
                </h3>
                <div class="space-y-3">
                    @foreach($recentSearches as $search)
                    <div class="flex items-start space-x-3 p-2 hover:bg-gray-50 rounded">
                        <div class="bg-gray-100 rounded p-1 mt-1">
                            <i class="fas fa-search text-gray-600 text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ $search->search_query }}</p>
                            <p class="text-xs text-gray-500">{{ $search->search_location ?? 'No location' }}</p>
                            <p class="text-xs text-gray-400">{{ $search->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</main>

<script>
// Add any interactive functionality here
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh stats every 5 minutes
    setInterval(function() {
        // You can add AJAX call to refresh stats here
        console.log('Auto-refreshing stats...');
    }, 300000);
});
</script>
@endsection