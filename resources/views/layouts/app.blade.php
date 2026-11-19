<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ucfirst(auth()->user()->user_type) }} Dashboard - {{ \App\Models\Setting::get('site_name', config('app.name')) }}</title>

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
    @include('partials.safe-html-helpers')
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Mobile Menu Button -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button id="mobile-menu-btn" class="bg-primary-600 text-white p-2 rounded-lg shadow-lg">
            <i class="fas fa-bars text-lg"></i>
        </button>
    </div>

    <!-- Sidebar -->
        @include('layouts.sidebar')
    <!-- Main Content -->
    <div id="main-content" class="lg:ml-64 min-h-screen transition-all duration-300">
        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200">
            <div class="flex items-center justify-between gap-3 min-h-[4.5rem] px-4 lg:px-8 py-3 pl-16 lg:pl-8">
                <div class="flex items-center space-x-2 sm:space-x-4 min-w-0">
                    <!-- Fullscreen / Sidebar Toggle -->
                    <button id="sidebar-toggle-btn" onclick="toggleSidebarFullscreen()" title="Toggle Fullscreen"
                        aria-label="Toggle sidebar" class="hidden lg:inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-500 hover:text-gray-800 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <i id="sidebar-toggle-icon" class="fas fa-expand-alt text-sm"></i>
                    </button>
                    <h2 class="text-lg sm:text-2xl font-bold text-gray-900 tracking-tight truncate">Dashboard</h2>
                    @php
                        $activeSubscription = auth()->user()->activeSubscription();
                        $showCountdown = false;
                        $daysLeft = 0;
                        $endDate = null;

                        if ($activeSubscription && $activeSubscription->end_date && !$activeSubscription->is_trial) {
                            $endDate = $activeSubscription->end_date;
                            $daysLeft = now()->diffInDays($endDate, false);
                            $showCountdown = $daysLeft >= 0 && $daysLeft <= 10;
                        }
                    @endphp

                    @if($activeSubscription)
                        <span class="hidden sm:inline-flex items-center gap-1.5 h-7 px-3 rounded-full text-xs font-semibold bg-orange-50 border border-orange-200 text-orange-700"><i class="fas fa-crown text-[11px]" aria-hidden="true"></i>
                            {{ $activeSubscription->package->name ?? 'Free Plan' }}
                        </span>
                    @else
                        <span class="hidden sm:inline-flex items-center gap-1.5 h-7 px-3 rounded-full text-xs font-semibold bg-orange-50 border border-orange-200 text-orange-700"><i class="fas fa-crown text-[11px]" aria-hidden="true"></i>
                            Free Plan
                        </span>
                    @endif
                </div>

                {{-- Icons only on phones (labels from "sm" up) so the header never overflows a 360-414px screen --}}
                <div class="flex items-center space-x-2 sm:space-x-3 shrink-0">
                    <!-- Community Button -->
                    <a href="https://chat.whatsapp.com/JoXwhqeKW5sCRGuovBNQm8" target="_blank" rel="noopener noreferrer" aria-label="Community" title="Community" class="inline-flex items-center justify-center h-10 min-w-[2.5rem] px-3 sm:px-4 bg-white hover:bg-green-50 border border-green-600 text-green-700 text-sm font-semibold rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-green-700">
                        <i class="fab fa-whatsapp sm:mr-2" aria-hidden="true"></i>
                        <span class="hidden sm:inline">Community</span>
                    </a>
                    <!-- Playlist Button -->
                    <a href="{{ route('user.tutorials') }}" aria-label="Playlist" title="Playlist" class="inline-flex items-center justify-center h-10 min-w-[2.5rem] px-3 sm:px-4 bg-white hover:bg-red-50 border border-red-500 text-red-600 text-sm font-semibold rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-red-600">
                        <i class="fab fa-youtube sm:mr-2" aria-hidden="true"></i>
                        <span class="hidden sm:inline">Playlist</span>
                    </a>
                    <!-- Add Extension Button -->
                    <button type="button" onclick="openExtensionModal()" aria-label="Add Extension" title="Add Extension" class="inline-flex items-center justify-center h-10 min-w-[2.5rem] px-3 sm:px-5 bg-[#F97316] hover:bg-[#EA580C] text-white text-sm font-semibold rounded-full transition-colors shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-orange-500">
                        <i class="fas fa-puzzle-piece sm:mr-2" aria-hidden="true"></i>
                        <span class="hidden sm:inline">Add Extension</span>
                    </button>
                </div>
            </div>

            <!-- Subscription Expiry Countdown Banner -->
            @if($showCountdown)
            <div class="bg-red-50 border-t border-red-200 px-4 lg:px-8 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        <div class="bg-red-100 rounded-full p-2">
                            <i class="fas fa-clock text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-red-900">
                                Your subscription is expiring soon!
                            </p>
                            <p class="text-xs text-red-700">
                                Renew now to continue enjoying uninterrupted service
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div id="countdown-timer" class="text-right" data-end-date="{{ $endDate->toIso8601String() }}">
                            <div class="flex items-center space-x-2 bg-white rounded-lg px-4 py-2 shadow-sm">
                                <i class="fas fa-hourglass-half text-orange-600"></i>
                                <div>
                                    <div class="flex items-center space-x-1 text-lg font-bold text-gray-900">
                                        <span id="countdown-days">{{ $daysLeft }}</span>
                                        <span class="text-xs font-normal text-gray-600">days</span>
                                        <span id="countdown-hours">00</span>
                                        <span class="text-xs font-normal text-gray-600">hrs</span>
                                        <span id="countdown-minutes">00</span>
                                        <span class="text-xs font-normal text-gray-600">min</span>
                                        <span id="countdown-seconds">00</span>
                                        <span class="text-xs font-normal text-gray-600">sec</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('user.subscription') }}" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors duration-200 flex items-center space-x-2">
                            <i class="fas fa-sync-alt"></i>
                            <span>Renew Now</span>
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </header>

        <!-- ══ Affiliate Motivational Banner ══ -->
        @php
            $affEarning  = auth()->user()->affiliateEarning;
            $affAvailable = $affEarning ? $affEarning->available : 0;
            $affTotal     = $affEarning ? $affEarning->total_earned : 0;
            $affCode      = auth()->user()->referral_code;
            $affLink      = auth()->user()->getReferralLink();
        @endphp
        <div id="affiliateBanner"
             class="relative overflow-hidden mx-0"
             style="display:none;">
            <!-- Animated gradient background -->
            <div class="bg-emerald-700 px-4 lg:px-8 py-3">
                <!-- Decorative circles -->
                <div class="absolute -top-4 -right-4 w-24 h-24 bg-white opacity-5 rounded-full pointer-events-none"></div>
                <div class="absolute -bottom-6 right-32 w-16 h-16 bg-white opacity-5 rounded-full pointer-events-none"></div>
                <div class="absolute top-0 right-64 w-10 h-10 bg-white opacity-5 rounded-full pointer-events-none"></div>

                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <!-- Left: Icon + Message -->
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex-shrink-0 bg-white bg-opacity-20 rounded-full p-2">
                            <i class="fas fa-hand-holding-dollar text-white text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            @if($affTotal > 0)
                                <p class="text-white font-semibold text-sm leading-tight">
                                    💰 You've earned
                                    <span class="bg-white bg-opacity-20 px-1.5 py-0.5 rounded font-bold">${{ number_format($affTotal, 2) }}</span>
                                    so far!
                                    @if($affAvailable > 0)
                                        <span class="ml-1 text-yellow-200">${{ number_format($affAvailable, 2) }} is ready to withdraw.</span>
                                    @else
                                        <span class="ml-1 text-yellow-200">Keep referring friends to earn more!</span>
                                    @endif
                                </p>
                            @else
                                <p class="text-white font-semibold text-sm leading-tight">
                                    🚀 Refer friends &amp; earn <span class="bg-white bg-opacity-20 px-1.5 py-0.5 rounded font-bold">commission on every sale</span> — completely free!
                                </p>
                            @endif
                            <p class="text-green-100 text-xs mt-0.5 hidden sm:block">
                                Your code:
                                <span class="font-mono font-bold text-white cursor-pointer hover:text-yellow-200 transition-colors"
                                      onclick="copyAffCode(@js($affCode), @js($affLink))"
                                      title="Click to copy link">
                                    {{ $affCode }}
                                </span>
                                <span class="ml-1 opacity-70">· Click to copy your referral link</span>
                            </p>
                        </div>
                    </div>

                    <!-- Right: CTA + Dismiss -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if($affAvailable > 0)
                            <a href="{{ route('user.affiliate.withdrawals') }}"
                               class="inline-flex items-center px-4 py-2 bg-yellow-400 hover:bg-yellow-300 text-gray-900 text-sm font-bold rounded-lg transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                <i class="fas fa-money-bill-transfer mr-2"></i>
                                Withdraw ${{ number_format($affAvailable, 2) }}
                            </a>
                        @else
                            <a href="{{ route('user.affiliate.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 text-green-700 text-sm font-bold rounded-lg transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                <i class="fas fa-link mr-2"></i>
                                Get Your Link
                            </a>
                        @endif

                        <button onclick="dismissAffiliateBanner()"
                                class="flex-shrink-0 text-white hover:text-green-200 transition-colors p-1.5 rounded-lg hover:bg-white hover:bg-opacity-10"
                                title="Dismiss">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Affiliate banner disabled site-wide — kept in the DOM (display:none) rather than
        // removed, so it's easy to re-enable later; just not auto-shown on DOMContentLoaded.

        function dismissAffiliateBanner() {
            const banner = document.getElementById('affiliateBanner');
            if (banner) {
                banner.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                banner.style.opacity = '0';
                banner.style.transform = 'translateY(-10px)';
                setTimeout(() => banner.style.display = 'none', 300);
            }
        }

        function copyAffCode(code, link) {
            navigator.clipboard.writeText(link).then(() => {
                const t = document.createElement('div');
                t.className = 'fixed bottom-6 right-6 bg-gray-900 text-white px-4 py-2 rounded-lg text-sm shadow-xl z-50';
                t.innerHTML = '<i class="fas fa-check mr-2 text-green-400"></i>Referral link copied!';
                document.body.appendChild(t);
                t.style.opacity = '0';
                t.style.transition = 'opacity 0.3s';
                requestAnimationFrame(() => t.style.opacity = '1');
                setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 2500);
            });
        }
        </script>
        <!-- ══ End Affiliate Banner ══ -->

        <!-- Flash Messages -->
        @if(session('success'))
        <div id="flashMessage" class="mx-4 lg:mx-8 mt-4">
            <div class="bg-green-50 border-l-4 border-green-400 rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-green-600 hover:text-green-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div id="flashMessage" class="mx-4 lg:mx-8 mt-4">
            <div class="bg-red-50 border-l-4 border-red-400 rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        @endif

        @if(session('warning'))
        <div id="flashMessage" class="mx-4 lg:mx-8 mt-4">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-yellow-800">{{ session('warning') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-yellow-600 hover:text-yellow-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Dashboard Content -->
            @yield('content')

    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>


    <!-- Feedback Modal (manual + post-login prompt) -->
    @include('partials.feedback-modal')

<!-- Auto-hide Flash Messages -->
<script>
// Auto-hide flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('#flashMessage');
    flashMessages.forEach(function(message) {
        setTimeout(function() {
            message.style.transition = 'opacity 0.5s ease-out';
            message.style.opacity = '0';
            setTimeout(function() {
                message.remove();
            }, 500);
        }, 5000); // Hide after 5 seconds
    });
});
</script>

<!-- Subscription Countdown Timer -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const countdownTimer = document.getElementById('countdown-timer');

    if (!countdownTimer) return;

    const endDate = new Date(countdownTimer.getAttribute('data-end-date'));

    function updateCountdown() {
        const now = new Date();
        const timeDiff = endDate - now;

        if (timeDiff <= 0) {
            // Subscription expired
            document.getElementById('countdown-days').textContent = '0';
            document.getElementById('countdown-hours').textContent = '00';
            document.getElementById('countdown-minutes').textContent = '00';
            document.getElementById('countdown-seconds').textContent = '00';
            return;
        }

        // Calculate time components
        const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);

        // Update display with leading zeros
        document.getElementById('countdown-days').textContent = days;
        document.getElementById('countdown-hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('countdown-minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('countdown-seconds').textContent = String(seconds).padStart(2, '0');
    }

    // Update immediately and then every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
});
</script>

<!-- Fullscreen / Sidebar Toggle -->
<script>
let sidebarHidden = false;

function toggleSidebarFullscreen() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const icon = document.getElementById('sidebar-toggle-icon');

    sidebarHidden = !sidebarHidden;

    if (sidebarHidden) {
        sidebar.style.transform = 'translateX(-100%)';
        mainContent.classList.remove('lg:ml-64');
        mainContent.classList.add('ml-0');
        icon.classList.remove('fa-expand-alt');
        icon.classList.add('fa-compress-alt');
    } else {
        sidebar.style.transform = '';
        mainContent.classList.add('lg:ml-64');
        mainContent.classList.remove('ml-0');
        icon.classList.remove('fa-compress-alt');
        icon.classList.add('fa-expand-alt');
    }
}
</script>

<!-- JavaScript for Mobile Menu Toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    
    // Open sidebar
    mobileMenuBtn.addEventListener('click', function() {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    });
    
    // Close sidebar function
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
        document.body.style.overflow = ''; // Restore scroll
    }
    
    // Close button click
    closeSidebarBtn.addEventListener('click', closeSidebar);
    
    // Backdrop click
    backdrop.addEventListener('click', closeSidebar);
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
            closeSidebar();
        }
    });
});
</script>

<!-- Extension Install Modal -->
<div id="extensionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 flex-shrink-0">
                <div class="flex items-center">
                    <div class="bg-orange-100 rounded-full p-2 mr-3">
                        <i class="fas fa-puzzle-piece text-orange-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Install Browser Extension</h3>
                        <p class="text-xs text-gray-500">CustomerNearme Chrome Extension</p>
                    </div>
                </div>
                <button onclick="closeExtensionModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Video Guide Button -->
            <div class="px-6 py-3 border-b border-gray-100">
                <a href="{{ route('user.tutorials') }}#add-extension" onclick="closeExtensionModal()"
                   style="display:flex;align-items:center;justify-content:center;width:100%;padding:10px 16px;background-color:#4f46e5;color:#fff;font-size:14px;font-weight:500;border-radius:8px;text-decoration:none;">
                    <i class="fas fa-play-circle" style="margin-right:8px;"></i>
                    Video Guide — Watch Add Extension Tutorial
                </a>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1">
                <!-- Step 1 -->
                <div class="flex items-start mb-5">
                    <div class="flex-shrink-0 w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-4">1</div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800 mb-2">Download the Extension ZIP</p>
                        <a href="{{ asset('public/downloads/Customernearme_Extension.zip') }}" download
                           class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            Download Extension (.zip)
                        </a>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="flex items-start mb-5">
                    <div class="flex-shrink-0 w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-4">2</div>
                    <div>
                        <p class="font-medium text-gray-800 mb-1">Extract the ZIP File</p>
                        <p class="text-sm text-gray-600">Right-click the downloaded ZIP and select <strong>Extract All</strong> to unzip it into a folder.</p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="flex items-start mb-5">
                    <div class="flex-shrink-0 w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-4">3</div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800 mb-2">Open Chrome Extensions Page</p>
                        
                        <button onclick="openChromeExtensions()" id="extOpenBtn" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fab fa-chrome mr-2"></i>
                            <span id="extBtnText">Copy chrome://extensions</span>
                        </button>
                        <div id="copyFallback" class="hidden mt-3 bg-green-50 border border-green-200 rounded-lg p-3">
                            <p class="text-sm font-semibold text-green-800 mb-1">
                                <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                URL Copied!
                            </p>
                            <ol class="text-xs text-green-700 space-y-1 list-decimal list-inside">
                                <li>Open Chrome browser</li>
                                <li>Click on the address bar (or press <kbd class="bg-green-100 px-1 rounded border border-green-300 font-mono">Ctrl+L</kbd>)</li>
                                <li>Paste <kbd class="bg-green-100 px-1 rounded border border-green-300 font-mono">Ctrl+V</kbd> then press <kbd class="bg-green-100 px-1 rounded border border-green-300 font-mono">Enter</kbd></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="flex items-start mb-5">
                    <div class="flex-shrink-0 w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-4">4</div>
                    <div>
                        <p class="font-medium text-gray-800 mb-1">Enable Developer Mode</p>
                        <p class="text-sm text-gray-600">On the Extensions page, toggle <strong>"Developer mode"</strong> ON (top-right corner).</p>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="flex items-start mb-5">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-4">5</div>
                    <div>
                        <p class="font-medium text-gray-800 mb-1">Load the Extension</p>
                        <p class="text-sm text-gray-600">Click <strong>"Load unpacked"</strong> and select the extracted folder. The extension will be installed!</p>
                    </div>
                </div>

                <!-- Guide Image -->
                <div class="mt-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-image text-orange-500 mr-2"></i>Visual Guide — Steps 4 & 5
                    </p>
                    <div class="rounded-lg overflow-hidden border border-gray-200 cursor-zoom-in" onclick="openImageLightbox(this.querySelector('img').src)">
                        <img src="{{ asset('public/assets/images/extension.png') }}" alt="Extension install guide" class="w-full h-auto object-contain hover:opacity-90 transition-opacity">
                    </div>
                    <p class="text-xs text-gray-400 mt-1 text-center"><i class="fas fa-search-plus mr-1"></i>Click image to view full size</p>
                </div>

            </div>

            <!-- Footer -->
            <div class="px-6 pb-6 pt-3 flex-shrink-0">
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2 flex-shrink-0"></i>
                    <p class="text-xs text-blue-700">Supported browsers: Chrome, Brave, and Edge. Firefox is not supported.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Lightbox -->
<div id="imageLightbox" class="fixed inset-0 bg-black bg-opacity-90 z-[60] hidden flex items-center justify-center p-4" onclick="closeImageLightbox()">
    <button onclick="closeImageLightbox()" class="absolute top-4 right-4 text-red-500 hover:text-red-400 transition-colors z-10">
        <i class="fas fa-times text-3xl"></i>
    </button>
    <img id="lightboxImg" src="" alt="Extension Guide" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl">
</div>

<script>
function openExtensionModal() {
    document.getElementById('extensionModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeExtensionModal() {
    document.getElementById('extensionModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
function openImageLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('imageLightbox').classList.remove('hidden');
}
function closeImageLightbox() {
    document.getElementById('imageLightbox').classList.add('hidden');
}
function openChromeExtensions() {
    const url = 'chrome://extensions';
    const btn = document.getElementById('extOpenBtn');
    const btnText = document.getElementById('extBtnText');
    const fallback = document.getElementById('copyFallback');

    navigator.clipboard.writeText(url).then(function() {
        btnText.textContent = 'Copied!';
        btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        btn.classList.add('bg-green-600', 'hover:bg-green-700');
        fallback.classList.remove('hidden');
        setTimeout(function() {
            btnText.textContent = 'Copy chrome://extensions';
            btn.classList.remove('bg-green-600', 'hover:bg-green-700');
            btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }, 3000);
    }).catch(function() {
        fallback.classList.remove('hidden');
    });
}
document.getElementById('extensionModal').addEventListener('click', function(e) {
    if (e.target === this) closeExtensionModal();
});
</script>

    @stack('scripts')
</body>
</html>