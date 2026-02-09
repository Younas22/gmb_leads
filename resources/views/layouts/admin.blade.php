<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - {{ \App\Models\Setting::get('site_name', config('app.name')) }}</title>

    @php
        $siteFavicon = \App\Models\Setting::get('site_favicon');
    @endphp
    @if($siteFavicon)
        <link rel="icon" type="image/png" href="{{ asset('public/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('public/assets/images/favicon.png') }}">
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        },
                        orange: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Mobile Menu Button -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button id="mobile-menu-btn" class="bg-primary-600 text-white p-2 rounded-lg shadow-lg">
            <i class="fas fa-bars text-lg"></i>
        </button>
    </div>

    <!-- Backdrop Overlay for Mobile -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden transition-opacity"></div>

    <!-- Admin Sidebar -->
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
                    <div class="bg-white rounded-lg px-3 py-2">
                        <img src="{{ asset('public/' . $siteLogo) }}" alt="{{ $siteName }} Logo" class="h-10 w-auto object-contain">
                    </div>
                @else
                    <img src="{{ asset('public/assets/images/white-logo.svg') }}" alt="{{ $siteName }} Logo" class="h-20 w-auto">
                @endif
            </div>
        </div>

        <!-- Admin Badge -->
        <div class="px-4 py-3 bg-purple-50 border-b border-purple-100">
            <div class="flex items-center space-x-2">
                <div class="bg-purple-600 rounded-full p-1.5">
                    <i class="fas fa-crown text-white text-xs"></i>
                </div>
                <span class="text-sm font-medium text-purple-700">Admin Panel</span>
            </div>
        </div>

        <!-- Navigation - Scrollable -->
        <div class="flex-1 overflow-y-auto">
            <nav class="p-4">
                <div class="space-y-1">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Overview</p>

                    <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.dashboard') ? 'text-primary-700 bg-primary-50' : '' }}">
                        <i class="fas fa-chart-line w-5 text-center mr-3"></i>
                        Dashboard
                    </a>

                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-6 mb-2">Management</p>

                    <a href="{{ route('admin.users') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.users') ? 'text-primary-700 bg-primary-50' : '' }}">
                        <i class="fas fa-users w-5 text-center mr-3"></i>
                        Users
                    </a>

                    <a href="{{ route('admin.packages.index') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.packages.*') ? 'text-primary-700 bg-primary-50' : '' }}">
                        <i class="fas fa-box w-5 text-center mr-3"></i>
                        Packages
                    </a>

                    <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.subscriptions.*') ? 'text-primary-700 bg-primary-50' : '' }}">
                        <i class="fas fa-credit-card w-5 text-center mr-3"></i>
                        Subscriptions
                    </a>

                    <a href="{{ route('admin.payments.index') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.payments.*') ? 'text-primary-700 bg-primary-50' : '' }}">
                        <i class="fas fa-money-bill-wave w-5 text-center mr-3"></i>
                        Payment History
                    </a>

                    <a href="{{ route('admin.feedback.history') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.feedback.*') ? 'text-primary-700 bg-primary-50' : '' }}">
                        <i class="fas fa-comments w-5 text-center mr-3"></i>
                        User Feedback
                    </a>

                    <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                        <i class="fas fa-chart-bar w-5 text-center mr-3"></i>
                        API Usage
                    </a>

                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-6 mb-2">Reports</p>

                    <!-- Reports Dropdown -->
                    <div class="relative">
                        <button onclick="toggleReportsDropdown()" class="w-full flex items-center justify-between px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.reports.*') ? 'text-primary-700 bg-primary-50' : '' }}">
                            <div class="flex items-center">
                                <i class="fas fa-chart-pie w-5 text-center mr-3"></i>
                                Reports
                            </div>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200" id="reportsDropdownArrow"></i>
                        </button>

                        <div id="reportsDropdown" class="ml-4 mt-1 space-y-1 hidden">
                            <a href="{{ route('admin.reports.revenue') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors {{ request()->routeIs('admin.reports.revenue') ? 'text-primary-700 bg-primary-50' : '' }}">
                                <i class="fas fa-dollar-sign w-4 text-center mr-2"></i>
                                Revenue Report
                            </a>
                            <a href="{{ route('admin.reports.users') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors {{ request()->routeIs('admin.reports.users') ? 'text-primary-700 bg-primary-50' : '' }}">
                                <i class="fas fa-user-plus w-4 text-center mr-2"></i>
                                User Growth
                            </a>
                            <a href="{{ route('admin.reports.leads') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors {{ request()->routeIs('admin.reports.leads') ? 'text-primary-700 bg-primary-50' : '' }}">
                                <i class="fas fa-users w-4 text-center mr-2"></i>
                                Leads Report
                            </a>
                            <a href="{{ route('admin.reports.all-leads') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors {{ request()->routeIs('admin.reports.all-leads') ? 'text-primary-700 bg-primary-50' : '' }}">
                                <i class="fas fa-database w-4 text-center mr-2"></i>
                                All Leads Report
                            </a>
                            <a href="{{ route('admin.reports.search') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors {{ request()->routeIs('admin.reports.search') ? 'text-primary-700 bg-primary-50' : '' }}">
                                <i class="fas fa-search w-4 text-center mr-2"></i>
                                Search Report
                            </a>
                            <a href="{{ route('admin.reports.package-performance') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors {{ request()->routeIs('admin.reports.package-performance') ? 'text-primary-700 bg-primary-50' : '' }}">
                                <i class="fas fa-box w-4 text-center mr-2"></i>
                                Package Performance
                            </a>
                            <a href="{{ route('admin.reports.export-activity') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors {{ request()->routeIs('admin.reports.export-activity') ? 'text-primary-700 bg-primary-50' : '' }}">
                                <i class="fas fa-file-export w-4 text-center mr-2"></i>
                                Export Activity
                            </a>
                            <a href="{{ route('admin.reports.top-performers') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors {{ request()->routeIs('admin.reports.top-performers') ? 'text-primary-700 bg-primary-50' : '' }}">
                                <i class="fas fa-trophy w-4 text-center mr-2"></i>
                                Top Performers
                            </a>
                            <a href="{{ route('admin.reports.user-activity') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors {{ request()->routeIs('admin.reports.user-activity') ? 'text-primary-700 bg-primary-50' : '' }}">
                                <i class="fas fa-user-clock w-4 text-center mr-2"></i>
                                User Activity
                            </a>
                            <a href="{{ route('admin.reports.system-overview') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors {{ request()->routeIs('admin.reports.system-overview') ? 'text-primary-700 bg-primary-50' : '' }}">
                                <i class="fas fa-tachometer-alt w-4 text-center mr-2"></i>
                                System Overview
                            </a>
                        </div>
                    </div>

                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-6 mb-2">System</p>

                    <a href="{{ route('admin.settings.index') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.settings.*') ? 'text-primary-700 bg-primary-50' : '' }}">
                        <i class="fas fa-cog w-5 text-center mr-3"></i>
                        Settings
                    </a>
                </div>

                <!-- Switch to User Dashboard -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <a href="{{ route('admin.switch.to.user') }}" class="flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg font-medium transition-colors">
                        <i class="fas fa-exchange-alt w-5 text-center mr-3"></i>
                        Switch to User View
                    </a>
                </div>
            </nav>
        </div>

        <!-- User Profile Section - Fixed at bottom -->
        <div class="p-4 border-t border-gray-200 flex-shrink-0">
            <div class="relative">
                <button onclick="toggleProfileDropdown()" class="w-full flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="relative">
                        @if(auth()->user()->avatar)
                            <img src="{{ asset('public/' . auth()->user()->avatar) }}" alt="Profile" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                {{ strtoupper(substr(auth()->user()->first_name ?? auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name ?? '', 0, 1)) }}
                            </div>
                        @endif
                        <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></div>
                    </div>

                    <div class="flex-1 min-w-0 text-left">
                        <p class="text-sm font-medium text-gray-800 truncate">
                            {{ auth()->user()->first_name ? auth()->user()->first_name . ' ' . auth()->user()->last_name : auth()->user()->name }}
                        </p>
                        <p class="text-xs text-purple-600 truncate">Administrator</p>
                    </div>

                    <i class="fas fa-chevron-up text-gray-400 text-xs transition-transform duration-200" id="profileDropdownArrow"></i>
                </button>

                <!-- Dropdown Menu -->
                <div id="profileDropdown" class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-lg shadow-lg border border-gray-200 py-2 hidden">
                    <a href="{{ route('user.profile') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-user-edit w-4 mr-3 text-gray-400"></i>
                        Edit Profile
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <button onclick="confirmLogout()" class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt w-4 mr-3"></i>
                        Sign Out
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden Logout Form -->
        <form id="logoutForm" method="POST" action="{{ route('auth.logout') }}" class="hidden">
            @csrf
        </form>
    </div>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pl-16 lg:pl-8">
                <div class="flex items-center space-x-4">
                    <h2 class="text-2xl font-bold text-gray-800">@yield('title', 'Admin Dashboard')</h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        <i class="fas fa-crown mr-1"></i> Admin
                    </span>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        @yield('content')
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
        document.getElementById('profileDropdown').classList.add('hidden');
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function performLogout() {
        document.getElementById('logoutForm').submit();
    }

    document.getElementById('logoutModal').addEventListener('click', function(e) {
        if (e.target === this) closeLogoutModal();
    });

    // Reports dropdown functionality
    function toggleReportsDropdown() {
        const dropdown = document.getElementById('reportsDropdown');
        const arrow = document.getElementById('reportsDropdownArrow');
        dropdown.classList.toggle('hidden');
        arrow.classList.toggle('rotate-180');
    }

    // Auto-expand reports dropdown if on a reports page
    document.addEventListener('DOMContentLoaded', function() {
        const reportsDropdown = document.getElementById('reportsDropdown');
        const currentPath = window.location.pathname;
        if (currentPath.includes('/admin/reports/')) {
            reportsDropdown.classList.remove('hidden');
            document.getElementById('reportsDropdownArrow').classList.add('rotate-180');
        }
    });

    // Mobile sidebar
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const closeSidebarBtn = document.getElementById('close-sidebar-btn');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');

        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
            document.body.style.overflow = '';
        }

        closeSidebarBtn.addEventListener('click', closeSidebar);
        backdrop.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSidebar();
                closeLogoutModal();
            }
        });
    });
    </script>
    @stack('scripts')
</body>
</html>
