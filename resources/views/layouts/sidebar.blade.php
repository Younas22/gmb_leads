<!-- Backdrop Overlay for Mobile -->
<div id="sidebar-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden transition-opacity"></div>

<!-- Sidebar -->
<div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col">

    <!-- Close Button (Mobile Only) -->
    <button id="close-sidebar-btn" style="margin-right: -10px; color:black;" class="lg:hidden absolute top-4 right-4 text-white hover:text-gray-200 z-10 transition-colors">
        <i class="fas fa-chevron-left text-xl"></i>
    </button>

    <!-- Logo -->
    <div class="flex items-center justify-center h-16 px-4 bg-white-600 flex-shrink-0 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            @php
                $siteLogo = \App\Models\Setting::get('site_logo');
                $siteName = \App\Models\Setting::get('site_name', config('app.name'));
            @endphp
            @if($siteLogo)
                <a href="{{ route('dashboard') }}" class="bg-white rounded-lg px-3 py-2 block">
                    <img src="{{ asset('public/' . $siteLogo) }}"
                         alt="{{ $siteName }} Logo"
                         class="h-10 w-auto object-contain">
                </a>
            @else
                <a href="{{ route('dashboard') }}">
                    <img src="{{ asset('public/assets/images/white-logo.svg') }}"
                         alt="{{ $siteName }} Logo"
                         class="h-20 w-auto">
                </a>
            @endif
        </div>
    </div>

    <!-- Admin Preview Banner -->
    @if(session('admin_preview_user'))
    <div class="px-4 py-3 bg-purple-50 border-b border-purple-200">
        <a href="{{ route('admin.switch.to.admin') }}" class="flex items-center justify-center px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-exchange-alt mr-2"></i>
            Switch back to Admin
        </a>
    </div>
    @endif

    <!-- Navigation - Scrollable -->
    <div class="flex-1 overflow-y-auto">
        <nav class="px-3 py-4">
            @php
                $isRestricted  = auth()->user()->hasRestrictedAccess();
                $hasPending    = auth()->user()->subscriptions()->where('status', 'pending')->exists();
            @endphp

            <div class="space-y-1">
                <!-- Dashboard - always visible -->
                <a href="{{ route('user.dashboard') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.dashboard') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                    <i class="fas fa-home w-5 text-center mr-3"></i>
                    Dashboard
                </a>

                @if($isRestricted && $hasPending)
                    <!-- Pending Subscription Notice -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                        <div class="flex items-start">
                            <i class="fas fa-clock text-yellow-600 mr-3 mt-1"></i>
                            <div>
                                <p class="text-sm font-semibold text-yellow-800">Subscription Pending</p>
                                <p class="text-xs text-yellow-700 mt-1">Your payment is under review. Full access will be granted once activated by admin.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Full navigation for active users -->
                    <a href="{{ route('user.search') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.search') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-search w-5 text-center mr-3"></i>
                        Find Leads
                    </a>
                    <a href="{{ route('user.leads') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.leads') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-bookmark w-5 text-center mr-3"></i>
                        My Leads
                    </a>
                    <a href="{{ route('user.lead-center.index') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.lead-center.*') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-bullseye w-5 text-center mr-3"></i>
                        Lead Center
                    </a>

                    {{-- Plan features: shown when included, shown with a lock and the admin's message when the
                         admin locked them (package or per-user "Custom features"), hidden otherwise --}}
                    @php
                        $navUser = auth()->user();
                        $navOn = fn (string $key) => \App\Services\PlanFeatures::enabled($navUser, $key);
                        $navLock = fn (string $key) => \App\Services\PlanFeatures::lockMessage($navUser, $key);
                    @endphp
                    @if($navOn('ai_sales_profile_enabled'))
                    <a href="{{ route('user.ai-profile.index') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.ai-profile.*') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-user-tie w-5 text-center mr-3"></i>
                        AI Sales Profile
                    </a>
                    <a href="{{ route('user.ai-outreach.index') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.ai-outreach.*') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-wand-magic-sparkles w-5 text-center mr-3"></i>
                        AI Outreach
                    </a>
                    @elseif($aiLock = $navLock('ai_sales_profile_enabled'))
                        @include('layouts._locked-nav-item', ['icon' => 'fa-user-tie', 'label' => 'AI Sales Profile', 'message' => $aiLock])
                        @include('layouts._locked-nav-item', ['icon' => 'fa-wand-magic-sparkles', 'label' => 'AI Outreach', 'message' => $aiLock])
                    @endif
                    @if($navOn('gmail_sending_enabled'))
                    <a href="{{ route('user.email-sending.index') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.email-sending.*') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-paper-plane w-5 text-center mr-3"></i>
                        Email Sending
                    </a>
                    @elseif($navOn('resend_connection_enabled'))
                    <a href="{{ route('user.email-sending.resend.show') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.email-sending.*') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-paper-plane w-5 text-center mr-3"></i>
                        Email Sending
                    </a>
                    @elseif($sendLock = ($navLock('gmail_sending_enabled') ?? $navLock('resend_connection_enabled')))
                        @include('layouts._locked-nav-item', ['icon' => 'fa-paper-plane', 'label' => 'Email Sending', 'message' => $sendLock])
                    @endif
                    @if($navOn('resend_campaigns_enabled'))
                    <a href="{{ route('user.campaigns.index') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.campaigns.*') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-bullhorn w-5 text-center mr-3"></i>
                        Campaigns
                    </a>
                    @elseif($campLock = $navLock('resend_campaigns_enabled'))
                        @include('layouts._locked-nav-item', ['icon' => 'fa-bullhorn', 'label' => 'Campaigns', 'message' => $campLock])
                    @endif
                    @if($navOn('email_analytics_enabled'))
                    <a href="{{ route('user.email-analytics.index') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.email-analytics.*') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-chart-line w-5 text-center mr-3"></i>
                        Email Analytics
                    </a>
                    @elseif($anaLock = $navLock('email_analytics_enabled'))
                        @include('layouts._locked-nav-item', ['icon' => 'fa-chart-line', 'label' => 'Email Analytics', 'message' => $anaLock])
                    @endif
                    @if($navOn('email_followups_enabled'))
                    <a href="{{ route('user.email-follow-ups.index') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.email-follow-ups.*') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-bell w-5 text-center mr-3"></i>
                        Follow-ups
                        @php $dueFollowUps = \App\Models\EmailFollowUp::ownedBy(auth()->user()->getAccountOwner())->open()->where('due_date', '<=', today()->toDateString())->count(); @endphp
                        @if($dueFollowUps > 0)<span class="ml-auto text-[11px] font-semibold px-2 py-0.5 rounded-full bg-orange-500 text-white" title="Due today or overdue">{{ $dueFollowUps }}</span>@endif
                    </a>
                    @elseif($fuLock = $navLock('email_followups_enabled'))
                        @include('layouts._locked-nav-item', ['icon' => 'fa-bell', 'label' => 'Follow-ups', 'message' => $fuLock])
                    @endif

                    <!-- Subscription - only for company owners and regular users, not team members -->
                    @if(!auth()->user()->isTeamMember())
                    <a href="{{ route('user.subscription') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.subscription') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-crown w-5 text-center mr-3"></i>
                        Subscription
                    </a>
                    @endif

                    <!-- Team Members - only for company owners -->
                    @if(auth()->user()->isCompany())
                    <a href="{{ route('user.team-members') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.team-members') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-users w-5 text-center mr-3"></i>
                        Team Members
                    </a>
                    @endif

                    {{-- @if(!auth()->user()->isTeamMember())
                    <!-- API Keys - Only for company owners and regular users, NOT team members -->
                    <a href="{{ route('user.api-keys') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.api-keys') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-key w-5 text-center mr-3"></i>
                        API Keys
                    </a>
                    @endif --}}

                    @if(!auth()->user()->isTeamMember())
                    <!-- Search History & Feedback - Only for company owners and regular users -->
                    <a href="{{ route('user.search-history') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.search-history') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}" style="display: none;">
                        <i class="fas fa-history w-5 text-center mr-3"></i>
                        Search History
                    </a>
                    <a href="{{ route('user.feedback.history') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.feedback.history') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}" style="display: none;">
                        <i class="fas fa-comments w-5 text-center mr-3"></i>
                        Feedback History
                    </a>
                    @endif

                    <!-- Affiliate Dashboard - Available to all users -->
                    <a href="{{ route('user.affiliate.index') }}" style="display: none;" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.affiliate.*') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-hand-holding-dollar w-5 text-center mr-3"></i>
                        Affiliate
                        @php $pendingEarning = auth()->user()->affiliateEarning; @endphp
                        @if($pendingEarning && $pendingEarning->available > 0)
                            <span class="ml-auto text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-semibold">${{ number_format($pendingEarning->available, 0) }}</span>
                        @endif
                    </a>

                    <!-- Profile Link - Available to all users -->
                    <a href="{{ route('user.profile') }}" class="flex items-center px-3 py-2.5 text-sm text-gray-600 hover:bg-gray-100 rounded-xl font-medium transition-colors {{ request()->routeIs('user.profile') ? 'text-orange-700 bg-orange-50 font-semibold' : '' }}">
                        <i class="fas fa-user-cog w-5 text-center mr-3"></i>
                        Profile Settings
                    </a>
                @endif
            </div>

            <!-- Current plan (real subscription; owners and regular users only) -->
            @php $sidebarSub = auth()->user()->isTeamMember() ? null : auth()->user()->activeSubscription(); @endphp
            @if($sidebarSub && $sidebarSub->package)
            <div class="mt-6 bg-[#FFF7F2] border border-[#FBE3D3] rounded-2xl p-4" data-testid="sidebar-plan">
                <i class="fas fa-crown text-[#F97316] text-lg" aria-hidden="true"></i>
                <p class="mt-1.5 text-[15px] font-semibold text-gray-900">{{ $sidebarSub->package->name }}</p>
                <p class="text-xs text-gray-600">
                    @if($sidebarSub->end_date)
                        Expires: {{ \Carbon\Carbon::parse($sidebarSub->end_date)->format('d M Y') }}
                    @else
                        No expiry
                    @endif
                </p>
                <a href="{{ route('user.subscription') }}" class="mt-3 flex items-center justify-center gap-2 w-full h-9 rounded-xl border border-[#F97316] bg-white text-[#EA580C] text-xs font-semibold hover:bg-[#FFF1E7] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F97316]">
                    Upgrade Plan <i class="fas fa-arrow-right text-[10px]" aria-hidden="true"></i>
                </a>
            </div>
            @endif

            <!-- Feedback Section - Only for company owners and regular users, NOT team members -->
            @if(!auth()->user()->isTeamMember())
            <div class="mt-6 bg-gray-50 rounded-2xl p-4 border border-gray-200">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-50 rounded-lg" aria-hidden="true"><i class="fas fa-comment-alt text-blue-600 text-sm"></i></span>
                        <h3 class="text-sm font-semibold text-gray-900">Help Us Improve</h3>
                    </div>
                    <p class="text-xs text-gray-600 mb-3 leading-relaxed">Share your feedback to make our lead generation tool better</p>
                    <div class="space-y-2">
                        <button onclick="openFeedbackModal()" class="w-full h-9 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 rounded-lg transition-colors">
                            <i class="fas fa-star mr-1"></i>
                            Add Your Feedback
                        </button>
                        <a href="{{ route('user.feedback.history') }}" class="flex items-center justify-center w-full h-9 bg-white border border-blue-200 text-blue-700 text-xs font-semibold px-4 rounded-lg transition-colors hover:bg-blue-50">
                            <i class="fas fa-history mr-1"></i>
                            View History
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </nav>
    </div>

    <!-- User Profile Section - Fixed at bottom -->
    <div class="p-4 border-t border-gray-200 flex-shrink-0">
        <!-- User Info with Dropdown -->
        <div class="relative">
            <button onclick="toggleProfileDropdown()" class="w-full flex items-center space-x-3 p-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                <!-- User Avatar -->
                <div class="relative">
                    @php
                        $sidebarAvatar = auth()->user()->avatar
                            ? (str_starts_with(auth()->user()->avatar, 'http') ? auth()->user()->avatar : asset('public/' . auth()->user()->avatar))
                            : asset('assets/avatar/placeholder-image.jpeg');
                    @endphp
                    <img src="{{ $sidebarAvatar }}" alt="Profile" class="w-10 h-10 rounded-full object-cover">
                    <!-- Online Status Indicator -->
                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></div>
                </div>
                
                <!-- User Info -->
                <div class="flex-1 min-w-0 text-left">
                    <p class="text-sm font-medium text-gray-800 truncate">
                        {{ auth()->user()->first_name ? auth()->user()->first_name . ' ' . auth()->user()->last_name : auth()->user()->name }}
                    </p>
                    <div class="flex items-center space-x-2">
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                        @if(auth()->user()->email_verified)
                            <i class="fas fa-check-circle text-green-500 text-xs" title="Email Verified"></i>
                        @endif
                    </div>
                </div>
                
                <!-- Dropdown Arrow -->
                <i class="fas fa-chevron-up text-gray-400 text-xs transition-transform duration-200" id="profileDropdownArrow"></i>
            </button>

            <!-- Dropdown Menu -->
            <div id="profileDropdown" class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2 hidden">
                <!-- User Status -->
                <div class="px-4 py-2 border-b border-gray-100">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                        <span class="text-xs text-gray-600">Online</span>
                        @if(auth()->user()->isAdmin())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                <i class="fas fa-crown mr-1"></i>Admin
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Last active: {{ auth()->user()->last_login ? auth()->user()->last_login->diffForHumans() : 'Never' }}</p>
                </div>

                <!-- Quick Actions -->
                <div class="py-1">
                    <a href="{{ route('user.profile') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-user-edit w-4 mr-3 text-gray-400"></i>
                        Edit Profile
                    </a>
                    @if(!auth()->user()->isTeamMember())
                    <a href="{{ route('user.profile') }}#security-tab" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-shield-alt w-4 mr-3 text-gray-400"></i>
                        Security Settings
                    </a>
                    <a href="{{ route('user.profile') }}#preferences-tab" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-cog w-4 mr-3 text-gray-400"></i>
                        Preferences
                    </a>
                    @endif
                </div>

                <!-- Account Stats -->
                <div class="px-4 py-2 border-t border-gray-100">
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="bg-blue-50 rounded p-2">
                            <p class="text-xs font-medium text-blue-600">{{ auth()->user()->savedLeads()->count() ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Leads</p>
                        </div>
                        <div class="bg-green-50 rounded p-2">
                            <p class="text-xs font-medium text-green-600">{{ \App\Models\UserExtensionDevice::where('user_id', auth()->id())->where('is_active', true)->count() }}</p>
                            <p class="text-xs text-gray-500">Devices</p>
                        </div>
                    </div>
                </div>

                <!-- Logout -->
                <div class="border-t border-gray-100 pt-1">
                    <button onclick="confirmLogout()" class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt w-4 mr-3"></i>
                        Sign Out
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Logout Form -->
    <form id="logoutForm" method="POST" action="{{ route('auth.logout') }}" class="hidden">
        @csrf
    </form>
</div>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-orange-100 rounded-full p-3 mr-4">
                        <i class="fas fa-sign-out-alt text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Sign Out</h3>
                </div>
                <p class="text-gray-600 mb-6">Are you sure you want to sign out of your account?</p>
                <div class="flex space-x-3">
                    <button onclick="closeLogoutModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button onclick="performLogout()" class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                        Sign Out
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Profile dropdown functionality
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    const arrow = document.getElementById('profileDropdownArrow');
    
    dropdown.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
    
    if (!button && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
        document.getElementById('profileDropdownArrow').classList.remove('rotate-180');
    }
});

// Logout functionality
function confirmLogout() {
    document.getElementById('logoutModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Close profile dropdown
    document.getElementById('profileDropdown').classList.add('hidden');
    document.getElementById('profileDropdownArrow').classList.remove('rotate-180');
}

function closeLogoutModal() {
    document.getElementById('logoutModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function performLogout() {
    // Show loading state
    const logoutBtn = event.target;
    const originalText = logoutBtn.innerHTML;
    logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing out...';
    logoutBtn.disabled = true;
    
    // Submit logout form
    document.getElementById('logoutForm').submit();
}

// Close modal when clicking outside
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLogoutModal();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // ESC to close modals
    if (e.key === 'Escape') {
        closeLogoutModal();
        document.getElementById('profileDropdown').classList.add('hidden');
        document.getElementById('profileDropdownArrow').classList.remove('rotate-180');
    }
    
    // Ctrl/Cmd + Shift + Q for quick logout
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'Q') {
        e.preventDefault();
        confirmLogout();
    }
});

// Auto-hide dropdown after 10 seconds of inactivity
let dropdownTimer;
document.getElementById('profileDropdown').addEventListener('mouseenter', function() {
    clearTimeout(dropdownTimer);
});

document.getElementById('profileDropdown').addEventListener('mouseleave', function() {
    dropdownTimer = setTimeout(() => {
        this.classList.add('hidden');
        document.getElementById('profileDropdownArrow').classList.remove('rotate-180');
    }, 10000);
});
</script>
{{-- Locked plan feature: the admin's message, opened from a locked sidebar item --}}
<div id="lockedFeatureModal" class="fixed inset-0 z-[70] hidden" role="dialog" aria-modal="true" aria-labelledby="lockedFeatureTitle">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="cnmCloseLockedFeature()"></div>
    <div class="relative flex items-center justify-center min-h-full p-4">
        <div class="relative bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
            <div class="flex items-center gap-3 mb-3">
                <span class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0" aria-hidden="true">
                    <i class="fas fa-lock text-amber-600"></i>
                </span>
                <h3 id="lockedFeatureTitle" class="text-base font-semibold text-gray-900"></h3>
            </div>
            <p id="lockedFeatureMessage" class="text-sm text-gray-600 leading-relaxed whitespace-pre-line"></p>
            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <button type="button" onclick="cnmCloseLockedFeature()" class="h-10 px-4 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
                @if(!auth()->user()->isTeamMember())
                <a href="{{ route('user.subscription') }}" class="inline-flex items-center h-10 px-4 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold">View plans</a>
                @endif
            </div>
        </div>
    </div>
</div>
<script>
function cnmShowLockedFeature(btn) {
    // textContent only: the message is admin text, never HTML
    document.getElementById('lockedFeatureTitle').textContent = btn.dataset.feature + ' is locked';
    document.getElementById('lockedFeatureMessage').textContent = btn.dataset.message;
    const modal = document.getElementById('lockedFeatureModal');
    modal.classList.remove('hidden');
    modal.querySelector('button').focus();
}
function cnmCloseLockedFeature() {
    document.getElementById('lockedFeatureModal').classList.add('hidden');
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') cnmCloseLockedFeature();
});
</script>
