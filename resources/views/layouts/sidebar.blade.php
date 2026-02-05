<!-- Backdrop Overlay for Mobile -->
<div id="sidebar-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden transition-opacity"></div>

<!-- Sidebar -->
<div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col">

    <!-- Close Button (Mobile Only) -->
    <button id="close-sidebar-btn" style="margin-right: -10px;" class="lg:hidden absolute top-4 right-4 text-white hover:text-gray-200 z-10 transition-colors">
        <i class="fas fa-chevron-left text-xl"></i>
    </button>

    <!-- Logo -->
    <div class="flex items-center justify-center h-16 px-4 bg-primary-600 flex-shrink-0">
        <div class="flex items-center space-x-3">
            <img src="{{ asset('public/assets/images/white-logo.svg') }}" 
                 alt="BusinessFinder Logo" 
                 class="h-20 w-auto">
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
        <nav class="p-4">
            <div class="space-y-2">
                <a href="{{ route('user.dashboard') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('user.dashboard') ? 'text-primary-700 bg-primary-50' : '' }}">
                    <i class="fas fa-home w-5 text-center mr-3"></i>
                    Dashboard
                </a>
                <a href="{{ route('user.search') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('user.search') ? 'text-primary-700 bg-primary-50' : '' }}">
                    <i class="fas fa-search w-5 text-center mr-3"></i>
                    Search Places
                </a>
                <a href="{{ route('user.leads') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('user.leads') ? 'text-primary-700 bg-primary-50' : '' }}">
                    <i class="fas fa-bookmark w-5 text-center mr-3"></i>
                    Saved Leads
                </a>
                <a href="{{ route('user.api-keys') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('user.api-keys') ? 'text-primary-700 bg-primary-50' : '' }}">
                    <i class="fas fa-key w-5 text-center mr-3"></i>
                    API Keys
                </a>
                <a href="{{ route('user.search-history') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('user.search-history') ? 'text-primary-700 bg-primary-50' : '' }}">
                    <i class="fas fa-history w-5 text-center mr-3"></i>
                    Search History
                </a>
                <a href="{{ route('user.feedback.history') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('user.feedback.history') ? 'text-primary-700 bg-primary-50' : '' }}">
                    <i class="fas fa-comments w-5 text-center mr-3"></i>
                    Feedback History
                </a>
                
                <!-- Profile Link -->
                <a href="{{ route('user.profile') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('user.profile') ? 'text-primary-700 bg-primary-50' : '' }}">
                    <i class="fas fa-user-cog w-5 text-center mr-3"></i>
                    Profile Settings
                </a>
            </div>

            <!-- Package Info -->
            <div class="mt-6 bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg p-4" style="display: none;">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-10 h-10 bg-orange-500 rounded-full mb-3">
                        <i class="fas fa-gift text-white"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 mb-1">Free Plan</h3>
                    <p class="text-xs text-gray-600 mb-3">425 of 500 leads used</p>
                    <div class="w-full bg-orange-200 rounded-full h-2 mb-3">
                        <div class="bg-orange-500 h-2 rounded-full" style="width: 85%"></div>
                    </div>
                    <button class="w-full bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium py-2 px-4 rounded-lg transition-colors">
                        Upgrade Plan
                    </button>
                </div>
            </div>

            <!-- Feedback Section -->
            <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-100">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-10 h-10 bg-blue-500 rounded-full mb-3">
                        <i class="fas fa-comment-alt text-white"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 mb-1">Help Us Improve</h3>
                    <p class="text-xs text-gray-600 mb-3">Share your feedback to make our lead generation tool better</p>
                    <div class="space-y-2">
                        <button onclick="openFeedbackModal()" class="w-full bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium py-2 px-4 rounded-lg transition-colors">
                            <i class="fas fa-star mr-1"></i>
                            Add Your Feedback
                        </button>
                        <a href="{{ route('user.feedback.history') }}" class="block w-full bg-white border border-blue-200 text-blue-600 text-xs font-medium py-2 px-4 rounded-lg transition-colors hover:bg-blue-50">
                            <i class="fas fa-history mr-1"></i>
                            View History
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- User Profile Section - Fixed at bottom -->
    <div class="p-4 border-t border-gray-200 flex-shrink-0">
        <!-- User Info with Dropdown -->
        <div class="relative">
            <button onclick="toggleProfileDropdown()" class="w-full flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                <!-- User Avatar -->
                <div class="relative">
                    @if(auth()->user()->avatar)
                        <img src="{{ asset('public/' . auth()->user()->avatar) }}" alt="Profile" class="w-10 h-10 rounded-full object-cover">
                    @else
                        <div class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-semibold text-sm">
                            {{ strtoupper(substr(auth()->user()->first_name ?? auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name ?? '', 0, 1)) }}
                        </div>
                    @endif
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
                    <a href="{{ route('user.profile') }}#security-tab" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-shield-alt w-4 mr-3 text-gray-400"></i>
                        Security Settings
                    </a>
                    <a href="{{ route('user.profile') }}#preferences-tab" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-cog w-4 mr-3 text-gray-400"></i>
                        Preferences
                    </a>
                </div>

                <!-- Account Stats -->
                <div class="px-4 py-2 border-t border-gray-100">
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="bg-blue-50 rounded p-2">
                            <p class="text-xs font-medium text-blue-600">{{ auth()->user()->savedLeads()->count() ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Leads</p>
                        </div>
                        <div class="bg-green-50 rounded p-2">
                            <p class="text-xs font-medium text-green-600">{{ auth()->user()->searchHistories()->count() ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Searches</p>
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