<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ \App\Models\Setting::get('site_name', 'BusinessFinder') }} - Find Quality Business Leads Fast</title>

    @php
        $siteFavicon = \App\Models\Setting::get('site_favicon');
    @endphp
    @if($siteFavicon)
        <link rel="icon" type="image/png" href="{{ asset('public/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('public/assets/images/favicon.png') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
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

    <script>
        const BASE_URL = '{{ url("/") }}';
    </script>
</head>
<body class="bg-white font-inter">
    <!-- Navigation -->
    <nav id="navbar" class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-100/80 transition-all duration-300" role="navigation" aria-label="Main navigation">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-[72px]">

                <!-- Logo -->
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 flex-shrink-0 group" aria-label="CustomerNearme Home">
                    @php
                        $siteLogo = \App\Models\Setting::get('site_logo');
                        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
                    @endphp
                    @if($siteLogo)
                        <img src="{{ asset('public/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-9 w-auto object-contain">
                    @else
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-orange to-orange-600 flex items-center justify-center shadow-md shadow-orange-200/50">
                                <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-[1.15rem] font-bold text-gray-900 tracking-tight">Customer<span class="text-primary-orange">Nearme</span></span>
                        </div>
                    @endif
                </a>

                <!-- Desktop Navigation Links -->
                <div class="hidden lg:flex items-center gap-1">
                    <a href="#features" class="relative px-4 py-2 text-[0.9rem] font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition-all duration-200 group">
                        Features
                        <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-primary-orange rounded-full transition-all duration-200 group-hover:w-5"></span>
                    </a>
                    <a href="#how-it-works" class="relative px-4 py-2 text-[0.9rem] font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition-all duration-200 group">
                        How It Works
                        <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-primary-orange rounded-full transition-all duration-200 group-hover:w-5"></span>
                    </a>
                    <a href="#pricing" class="relative px-4 py-2 text-[0.9rem] font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition-all duration-200 group">
                        Pricing
                        <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-primary-orange rounded-full transition-all duration-200 group-hover:w-5"></span>
                    </a>
                    <a href="#faq" class="relative px-4 py-2 text-[0.9rem] font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition-all duration-200 group">
                        FAQ
                        <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-primary-orange rounded-full transition-all duration-200 group-hover:w-5"></span>
                    </a>
                </div>

                <!-- Desktop Right Side Actions -->
                <div class="hidden lg:flex items-center gap-3">
                    @guest
                        <a href="{{ route('auth.show') }}" class="px-4 py-2 text-[0.9rem] font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50 transition-all duration-200">
                            Login
                        </a>
                        <a href="{{ route('auth.show') }}" class="group relative inline-flex items-center gap-2 px-5 py-2.5 text-[0.9rem] font-semibold text-white bg-primary-orange rounded-full shadow-lg shadow-orange-200/50 hover:shadow-orange-300/60 hover:bg-dark-orange transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary-orange focus:ring-offset-2">
                            Start Free Trial
                            <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @endguest
                    @auth
                        <a href="{{ route('user.dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-[0.9rem] font-semibold text-white bg-primary-orange rounded-full shadow-lg shadow-orange-200/50 hover:shadow-orange-300/60 hover:bg-dark-orange transition-all duration-200 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary-orange focus:ring-offset-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            Dashboard
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" type="button" class="lg:hidden relative w-10 h-10 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-orange/50" aria-expanded="false" aria-controls="mobile-menu" aria-label="Toggle navigation menu">
                    <span class="sr-only">Open menu</span>
                    <!-- Hamburger Icon -->
                    <div class="w-5 h-4 flex flex-col justify-between" id="hamburger-icon">
                        <span class="block h-0.5 w-5 bg-gray-700 rounded-full transition-all duration-300 origin-center" id="bar-1"></span>
                        <span class="block h-0.5 w-3.5 bg-gray-700 rounded-full transition-all duration-300 ml-auto" id="bar-2"></span>
                        <span class="block h-0.5 w-5 bg-gray-700 rounded-full transition-all duration-300 origin-center" id="bar-3"></span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Panel -->
        <div id="mobile-menu" class="lg:hidden hidden" role="menu">
            <div class="bg-white border-t border-gray-100 shadow-xl shadow-gray-200/20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 space-y-1">
                    <a href="#features" class="flex items-center gap-3 px-4 py-3 text-[0.95rem] font-medium text-gray-700 hover:text-gray-900 hover:bg-orange-50 rounded-xl transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                        <svg class="w-5 h-5 text-primary-orange/70" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                        Features
                    </a>
                    <a href="#how-it-works" class="flex items-center gap-3 px-4 py-3 text-[0.95rem] font-medium text-gray-700 hover:text-gray-900 hover:bg-orange-50 rounded-xl transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                        <svg class="w-5 h-5 text-primary-orange/70" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/></svg>
                        How It Works
                    </a>
                    <a href="#pricing" class="flex items-center gap-3 px-4 py-3 text-[0.95rem] font-medium text-gray-700 hover:text-gray-900 hover:bg-orange-50 rounded-xl transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                        <svg class="w-5 h-5 text-primary-orange/70" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                        Pricing
                    </a>
                    <a href="#faq" class="flex items-center gap-3 px-4 py-3 text-[0.95rem] font-medium text-gray-700 hover:text-gray-900 hover:bg-orange-50 rounded-xl transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                        <svg class="w-5 h-5 text-primary-orange/70" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>
                        FAQ
                    </a>

                    <!-- Mobile Divider -->
                    <div class="my-3 border-t border-gray-100"></div>

                    <!-- Mobile Action Buttons -->
                    @guest
                        <a href="{{ route('auth.show') }}" class="flex items-center justify-center gap-2 px-4 py-3 text-[0.95rem] font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-xl transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                            Login
                        </a>
                        <a href="{{ route('auth.show') }}" class="flex items-center justify-center gap-2 px-4 py-3.5 text-[0.95rem] font-semibold text-white bg-primary-orange hover:bg-dark-orange rounded-xl shadow-lg shadow-orange-200/40 transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                            Start Free Trial
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @endguest
                    @auth
                        <a href="{{ route('user.dashboard') }}" class="flex items-center justify-center gap-2 px-4 py-3.5 text-[0.95rem] font-semibold text-white bg-primary-orange hover:bg-dark-orange rounded-xl shadow-lg shadow-orange-200/40 transition-all duration-200" role="menuitem" onclick="closeMobileMenu()">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            Dashboard
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Navbar Spacer (72px to match nav height) -->
    <div class="h-[72px]"></div>

    <!-- Navbar Scripts -->
    <script>
    (function() {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const navbar = document.getElementById('navbar');
        const bar1 = document.getElementById('bar-1');
        const bar2 = document.getElementById('bar-2');
        const bar3 = document.getElementById('bar-3');
        let isOpen = false;

        // Toggle mobile menu
        menuBtn.addEventListener('click', function() {
            isOpen = !isOpen;
            menuBtn.setAttribute('aria-expanded', isOpen);

            if (isOpen) {
                mobileMenu.classList.remove('hidden');
                mobileMenu.style.maxHeight = '0';
                mobileMenu.style.overflow = 'hidden';
                mobileMenu.style.transition = 'max-height 0.3s ease-out';
                requestAnimationFrame(function() {
                    mobileMenu.style.maxHeight = mobileMenu.scrollHeight + 'px';
                });
                // Animate hamburger to X
                bar1.style.transform = 'translateY(7px) rotate(45deg)';
                bar2.style.opacity = '0';
                bar2.style.transform = 'translateX(10px)';
                bar3.style.transform = 'translateY(-7px) rotate(-45deg)';
            } else {
                mobileMenu.style.maxHeight = '0';
                setTimeout(function() {
                    mobileMenu.classList.add('hidden');
                    mobileMenu.style.maxHeight = '';
                    mobileMenu.style.overflow = '';
                    mobileMenu.style.transition = '';
                }, 300);
                // Animate X back to hamburger
                bar1.style.transform = '';
                bar2.style.opacity = '1';
                bar2.style.transform = '';
                bar3.style.transform = '';
            }
        });

        // Close mobile menu function (for link clicks)
        window.closeMobileMenu = function() {
            if (isOpen) {
                isOpen = false;
                menuBtn.setAttribute('aria-expanded', 'false');
                mobileMenu.style.maxHeight = '0';
                setTimeout(function() {
                    mobileMenu.classList.add('hidden');
                    mobileMenu.style.maxHeight = '';
                    mobileMenu.style.overflow = '';
                    mobileMenu.style.transition = '';
                }, 300);
                bar1.style.transform = '';
                bar2.style.opacity = '1';
                bar2.style.transform = '';
                bar3.style.transform = '';
            }
        };

        // Navbar scroll effect — adds shadow on scroll
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            if (currentScroll > 10) {
                navbar.classList.add('shadow-sm');
                navbar.style.borderColor = 'transparent';
            } else {
                navbar.classList.remove('shadow-sm');
                navbar.style.borderColor = '';
            }
            lastScroll = currentScroll;
        }, { passive: true });

        // Close menu on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen) {
                window.closeMobileMenu();
                menuBtn.focus();
            }
        });

        // Close menu on click outside
        document.addEventListener('click', function(e) {
            if (isOpen && !mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
                window.closeMobileMenu();
            }
        });
    })();
    </script>

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

    @include('components.modern-hero')
    <!-- Hero Section -->
 

    <!-- Problem Section — Deep Pain -->
    <section class="py-20 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Heading -->
            <div class="text-center mb-14">
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Why Most Lead Generation <span class="text-red-500">Fails</span></h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">You send emails. You make calls. But clients don't respond.</p>
            </div>

            <!-- Why? -->
            <div class="text-center mb-10">
                <p class="text-xl font-semibold text-gray-800 mb-8">Why? Because you're reaching:</p>
            </div>

            <!-- Pain Cards -->
            <div class="grid sm:grid-cols-3 gap-6 mb-14">
                <!-- Card 1 — Closed Businesses -->
                <div class="bg-red-50 border border-red-100 rounded-xl p-6 text-center">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Closed Businesses</h3>
                    <p class="text-sm text-gray-500">You're pitching to businesses that shut down months ago.</p>
                </div>

                <!-- Card 2 — Wrong Phone Numbers -->
                <div class="bg-orange-50 border border-orange-100 rounded-xl p-6 text-center">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3l5 5m0-5l-5 5"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Wrong Phone Numbers</h3>
                    <p class="text-sm text-gray-500">Disconnected lines and numbers that go nowhere.</p>
                </div>

                <!-- Card 3 — Outdated Listings -->
                <div class="bg-red-50 border border-red-100 rounded-xl p-6 text-center">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Outdated Listings</h3>
                    <p class="text-sm text-gray-500">Old databases filled with data that hasn't been updated in years.</p>
                </div>
            </div>

            <!-- Solution Line -->
            <div class="text-center bg-gradient-to-r from-blue-50 to-orange-50 border border-blue-100 rounded-2xl py-8 px-6">
                <div class="flex items-center justify-center gap-2 mb-3">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-sm font-bold text-green-600 uppercase tracking-wide">The Solution</span>
                </div>
                <p class="text-xl sm:text-2xl font-bold text-gray-900">
                    {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} fixes this by pulling only <span class="text-primary-blue">active businesses</span> directly from <span class="text-primary-orange">Google Maps</span>.
                </p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Heading -->
            <div class="text-center mb-14">
                <p class="text-sm font-bold text-primary-orange uppercase tracking-widest mb-3">Features</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900">Everything You Need to Find & Manage Leads</h2>
            </div>

            <!-- Features Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Google Places Integration -->
                <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-lg hover:border-primary-orange/30 transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-orange-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 mb-1">Google Places Integration</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">Real-time verified business data pulled directly from Google Maps.</p>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-lg hover:border-primary-blue/30 transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 mb-1">Advanced Filters</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">Filter by name, phone, email, rating, reviews & location.</p>
                        </div>
                    </div>
                </div>

                <!-- Lead Management -->
                <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-lg hover:border-primary-orange/30 transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-orange-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 mb-1">Lead Management</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">Notes, tags & status tracking to keep your pipeline organized.</p>
                        </div>
                    </div>
                </div>

                <!-- Export Options -->
                <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-lg hover:border-primary-blue/30 transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 mb-1">Export Options</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">Download leads as CSV, Excel or connect via API.</p>
                        </div>
                    </div>
                </div>

                <!-- API Dashboard -->
                <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-lg hover:border-primary-orange/30 transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-orange-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 mb-1">API Dashboard</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">Monitor usage & analytics with a clean dashboard.</p>
                        </div>
                    </div>
                </div>

                <!-- Real-Time Updates -->
                <div class="bg-white rounded-xl p-6 border border-gray-100 hover:shadow-lg hover:border-primary-blue/30 transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 mb-1">Real-Time Updates</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">Always fresh data — never worry about stale leads again.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Who Is This For -->
    <section class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Heading -->
            <div class="text-center mb-14">
                <p class="text-sm font-bold text-primary-orange uppercase tracking-widest mb-3">Target Audience</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Built for People Who Need Clients</h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} is perfect for:</p>
            </div>

            <!-- Audience Grid -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 mb-12">

    <!-- Digital Marketers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Digital Marketers</h3>
    </div>

    <!-- SEO Agencies -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-blue/30 transition-all group">
        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">SEO Agencies</h3>
    </div>

    <!-- Freelancers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Freelancers</h3>
    </div>

    <!-- Cold Emailers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-blue/30 transition-all group">
        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Cold Emailers</h3>
    </div>

    <!-- Sales Teams -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Sales Teams</h3>
    </div>

    <!-- Real Estate & Insurance -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-blue/30 transition-all group">
        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Real Estate & Insurance</h3>
    </div>

    <!-- Local Service Providers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Local Service Providers</h3>
    </div>

    <!-- Startups & Entrepreneurs -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-blue/30 transition-all group">
        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2L2 7l10 5 10-5-10-5z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Startups & Entrepreneurs</h3>
    </div>

    <!-- Marketing Consultants / Agencies -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v18h14V3H5z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6v2H9V7zM9 11h6v2H9v-2z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Marketing Consultants</h3>
    </div>

    <!-- Social Media Managers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-blue/30 transition-all group">
        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-blue" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12c0 4.98 3.65 9.12 8.44 9.88v-6.99H7.9v-2.89h2.54V9.41c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.55v1.88h2.78l-.44 2.89h-2.34v6.99C18.35 21.12 22 16.98 22 12c0-5.52-4.48-10-10-10z"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Social Media Managers</h3>
    </div>

    <!-- Content Creators / Copywriters -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 19h16M4 5h16M4 12h16"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Content Creators</h3>
    </div>

    <!-- Web Developers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-blue/30 transition-all group">
        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4h16v16H4z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Web Developers</h3>
    </div>

    <!-- Mobile App Developers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4h10v16H7z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Mobile App Developers</h3>
    </div>

    <!-- Graphic Designers -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-blue/30 transition-all group">
        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2l9 7-9 7-9-7 9-7z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Graphic Designers</h3>
    </div>

    <!-- Email Marketing Specialists -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-orange/30 transition-all group">
        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l9 6 9-6v10H3V8z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Email Marketing Specialists</h3>
    </div>

    <!-- Small Business Owners -->
    <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center hover:shadow-md hover:border-primary-blue/30 transition-all group">
        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18v18H3V3z"></path>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-900">Small Business Owners</h3>
    </div>

</div>


            <!-- Closing Line -->
            <div class="text-center">
                <p class="text-xl sm:text-2xl font-bold text-gray-900">
                    If your income depends on clients — <span class="text-primary-orange">this tool is for you.</span>
                </p>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Heading -->
            <div class="text-center mb-16">
                <p class="text-sm font-bold text-primary-orange uppercase tracking-widest mb-3">Simple 4-Step Process</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900">
                    How {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} Finds Clients for You
                </h2>
            </div>

            <!-- Steps Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 relative">

                <!-- Connector line (desktop only) -->
                <div class="hidden lg:block absolute top-10 left-[12.5%] right-[12.5%] h-0.5 bg-gradient-to-r from-primary-orange via-primary-blue to-primary-orange opacity-20"></div>

                <!-- Step 1 — Search -->
                <div class="relative text-center group">
                    <div class="w-20 h-20 bg-orange-50 border-2 border-primary-orange rounded-2xl flex items-center justify-center mx-auto mb-5 transition-transform group-hover:scale-110">
                        <svg class="w-9 h-9 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <span class="inline-block bg-primary-orange text-white text-xs font-bold px-3 py-1 rounded-full mb-3">Step 1</span>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Search Businesses</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Enter location, category, or keywords to find businesses worldwide.</p>
                </div>

                <!-- Step 2 — Review -->
                <div class="relative text-center group">
                    <div class="w-20 h-20 bg-blue-50 border-2 border-primary-blue rounded-2xl flex items-center justify-center mx-auto mb-5 transition-transform group-hover:scale-110">
                        <svg class="w-9 h-9 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                    <span class="inline-block bg-primary-blue text-white text-xs font-bold px-3 py-1 rounded-full mb-3">Step 2</span>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Review Real Profiles</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">See phone numbers, ratings, reviews & last activity.</p>
                </div>

                <!-- Step 3 — Save -->
                <div class="relative text-center group">
                    <div class="w-20 h-20 bg-orange-50 border-2 border-primary-orange rounded-2xl flex items-center justify-center mx-auto mb-5 transition-transform group-hover:scale-110">
                        <svg class="w-9 h-9 text-primary-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                        </svg>
                    </div>
                    <span class="inline-block bg-primary-orange text-white text-xs font-bold px-3 py-1 rounded-full mb-3">Step 3</span>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Save & Organize Leads</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Your data is always saved — tag & manage easily.</p>
                </div>

                <!-- Step 4 — Export -->
                <div class="relative text-center group">
                    <div class="w-20 h-20 bg-blue-50 border-2 border-primary-blue rounded-2xl flex items-center justify-center mx-auto mb-5 transition-transform group-hover:scale-110">
                        <svg class="w-9 h-9 text-primary-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <span class="inline-block bg-primary-blue text-white text-xs font-bold px-3 py-1 rounded-full mb-3">Step 4</span>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Export & Contact</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Download leads and start outreach instantly.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Data Trust Section -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <p class="text-sm font-bold text-green-600 uppercase tracking-widest mb-3">Data You Can Trust</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900">100% Real & Reliable Data</h2>
            </div>

            <div class="grid sm:grid-cols-2 gap-5 max-w-2xl mx-auto">
                <!-- Trust Item 1 -->
                <div class="flex items-center gap-4 bg-green-50 border border-green-100 rounded-xl px-5 py-4">
                    <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">Data directly from Google Maps</span>
                </div>

                <!-- Trust Item 2 -->
                <div class="flex items-center gap-4 bg-green-50 border border-green-100 rounded-xl px-5 py-4">
                    <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">No fake scraping</span>
                </div>

                <!-- Trust Item 3 -->
                <div class="flex items-center gap-4 bg-green-50 border border-green-100 rounded-xl px-5 py-4">
                    <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">Active businesses only</span>
                </div>

                <!-- Trust Item 4 -->
                <div class="flex items-center gap-4 bg-green-50 border border-green-100 rounded-xl px-5 py-4">
                    <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-800">Saved permanently in your account</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <p class="text-sm font-bold text-primary-orange uppercase tracking-widest mb-3">Pricing</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Flexible Pricing for Every Business</h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">Free Trial &middot; Monthly &middot; Yearly &middot; Lifetime — pick what works for you.</p>
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
                    'future_updates'        => 'Future Updates',
                    'advance_filter'        => 'Advanced Filters',
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
                    'sla_guarantee', 'custom_integrations', 'onboarding_training','future_updates','advance_filter'
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
                                data-package-price="{{ \App\Services\CurrencyHelper::convert((float)$package->price, $currency) }}"
                                data-currency-symbol="{{ $currency['symbol'] }}"
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
    <section class="py-20 bg-gray-50" style="display: none;">
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
    <section id="faq" class="py-20 bg-white" style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center mb-14">
                <p class="text-sm font-bold uppercase tracking-widest mb-3" style="color: rgb(249, 115, 22);">Support</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Frequently Asked Questions</h2>
                <p class="text-lg text-gray-500 max-w-xl mx-auto">Everything you need to know about {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} before getting started.</p>
            </div>

            <!-- FAQ Accordion -->
            <div class="space-y-3" id="faq-accordion">

                <!-- FAQ 1 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Where does the business data come from?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Every lead on {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} is sourced directly from the <strong class="text-gray-800">Google Places API</strong> — the same data that powers Google Maps. This means you get real business names, verified phone numbers, actual addresses, ratings, reviews, and website URLs. There is no scraping, no recycled databases, and no fabricated information. What you see on Google Maps is exactly what you get.
                        </div>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Is it legal and safe to use this data?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Yes. {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} retrieves publicly available business information through the official <strong class="text-gray-800">Google Places API</strong>, which is fully authorized by Google for commercial use. We do not access any private or restricted data. Every piece of information we display is already public on Google Maps — we simply organize it for your outreach workflow.
                        </div>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">How fresh and up-to-date is the data?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            The data is pulled in <strong class="text-gray-800">real-time</strong> from Google every time you run a search. Unlike static lead databases that go stale within weeks, our results reflect the current state of Google Maps at the moment of your query. If a business is active on Google Maps right now, it will appear in your results. This ensures you never waste time on closed or outdated listings.
                        </div>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Do I need my own Google Places API key?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
    Yes. You’ll use your own Google Places API key. Google offers free monthly credits, and CustomerNearme helps you utilize that data efficiently without scraping.
                    </div>
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">What information do I get for each business?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Each lead includes the <strong class="text-gray-800">business name, phone number, full address, Google Maps link, website URL, star rating, total review count, and business category</strong>. Depending on the business listing, you may also see operating hours and additional contact details. This gives you everything you need to qualify and reach out to prospects without any additional research.
                        </div>
                    </div>
                </div>

                <!-- FAQ 6 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Can I export my leads?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Yes. You can export your saved leads in <strong class="text-gray-800">CSV and Excel</strong> formats with a single click. Exported files are clean, organized, and ready to import directly into your CRM, email marketing platform, or outreach tools. Export functionality is available across all paid plans, making it easy to integrate {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} into your existing workflow.
                        </div>
                    </div>
                </div>

                <!-- FAQ 7 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Is there a free trial available?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Absolutely. {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} offers a <strong class="text-gray-800">free plan</strong> that lets you test the platform with real searches — no credit card required. You can explore the dashboard, run searches, view full lead profiles, and experience the data quality firsthand. When you are ready for higher volume and advanced features, you can upgrade at any time.
                        </div>
                    </div>
                </div>

                <!-- FAQ 8 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Are my saved leads stored permanently?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Yes. Every lead you save is <strong class="text-gray-800">permanently stored</strong> in your account. Unlike tools that delete data after a session or limit your history, {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} keeps all your saved leads accessible at any time. You can revisit, filter, tag, and export them whenever you need — your data is always there for you.
                        </div>
                    </div>
                </div>

                <!-- FAQ 9 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Who is this tool designed for?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} is built for anyone who needs a reliable pipeline of business leads. This includes <strong class="text-gray-800">digital marketers, SEO agencies, freelancers, cold emailers, sales teams, real estate agents, insurance agents, and local service providers</strong>. If your income depends on reaching out to businesses, this tool will save you hours of manual prospecting and deliver higher-quality contacts.
                        </div>
                    </div>
                </div>

                <!-- FAQ 10 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">What pricing plans are available?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            We offer flexible plans to fit every stage of your business — including <strong class="text-gray-800">Free, Monthly, Yearly, and Lifetime</strong> options. Each paid plan unlocks higher search volumes, more lead exports, and premium features like bulk export and priority support. You can start free and upgrade as your needs grow. View our <a href="#pricing" class="font-semibold hover:underline" style="color: rgb(249, 115, 22);">pricing section</a> for full details.
                        </div>
                    </div>
                </div>

                <!-- FAQ 11 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">Can I cancel my subscription at any time?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Yes. There are <strong class="text-gray-800">no long-term contracts and no cancellation fees</strong>. You can cancel your subscription whenever you want, and your access will remain active until the end of your current billing period. Your saved leads will continue to be available in your account even after cancellation.
                        </div>
                    </div>
                </div>

                <!-- FAQ 12 -->
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 hover:border-orange-200">
                    <button onclick="toggleFAQ(this)" class="w-full flex items-center justify-between px-6 py-5 text-left bg-white hover:bg-gray-50 transition-colors duration-200">
                        <span class="text-base font-semibold text-gray-900 pr-4">How do I get started?</span>
                        <span class="faq-icon flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300" style="background-color: rgba(249, 115, 22, 0.1);">
                            <svg class="w-4 h-4 transition-transform duration-300" style="color: rgb(249, 115, 22);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </button>
                    <div class="faq-content" style="max-height: 0; overflow: hidden; transition: max-height 0.35s ease, padding 0.35s ease;">
                        <div class="px-6 pb-5 text-gray-600 leading-relaxed text-[15px]">
                            Getting started takes less than a minute. <strong class="text-gray-800">Create a free account</strong>, enter a location and business category, and run your first search. You will immediately see real, verified business leads from Google Maps. No setup, no API keys, no technical knowledge required — just sign up and start finding clients.
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom CTA -->
            <div class="text-center mt-12 pt-8 border-t border-gray-100">
                <p class="text-gray-500 mb-4">Still have questions?</p>
                <a href="#contact" class="inline-flex items-center gap-2 font-semibold transition-colors duration-200 hover:underline" style="color: rgb(249, 115, 22);">
                    Contact our support team
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-24 bg-gradient-to-br from-gray-900 via-gray-900 to-gray-800 relative overflow-hidden">
        <!-- Background accent -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-primary-orange/10 rounded-full blur-3xl"></div>

        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white mb-5 leading-tight">
                Stop Chasing Dead Leads.<br class="hidden sm:block">
                <span class="text-primary-orange">Start Closing Real Clients.</span>
            </h2>
            <p class="text-lg text-gray-400 mb-10 max-w-xl mx-auto">
                Launch your lead generation the smart way with {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}.
            </p>
            <a href="{{ route('auth.show') }}" class="inline-block bg-primary-orange hover:bg-dark-orange text-white px-10 py-4 rounded-xl text-lg font-bold transition-all hover:scale-105 shadow-lg shadow-orange-500/20">
                Get Started Now
            </a>
            <p class="text-sm text-gray-500 mt-4">No credit card required. Cancel anytime.</p>
        </div>
    </section>

    {{-- Payment Modal --}}
    @include('partials.payment-modal')

    <!-- Footer -->
    <style>
        @keyframes footer-float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(1deg); }
        }
        @keyframes footer-pulse-ring {
            0% { transform: scale(1); opacity: 0.4; }
            100% { transform: scale(1.8); opacity: 0; }
        }
        @keyframes footer-gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .footer-glow-text {
            background: linear-gradient(135deg, #f97316 0%, #fb923c 40%, #f97316 60%, #ea580c 100%);
            background-size: 200% 200%;
            animation: footer-gradient-shift 4s ease infinite;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .footer-social-icon {
            position: relative;
            overflow: hidden;
        }
        .footer-social-icon::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            opacity: 0;
            transition: opacity 0.3s ease;
            background: linear-gradient(135deg, rgba(249,115,22,0.15), rgba(29,78,216,0.1));
        }
        .footer-social-icon:hover::before {
            opacity: 1;
        }
        .footer-link-pill {
            position: relative;
            overflow: hidden;
        }
        .footer-link-pill::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #f97316, #1d4ed8);
            border-radius: 2px;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        .footer-link-pill:hover::after {
            width: 60%;
        }
        .footer-whatsapp-card {
            background: linear-gradient(135deg, rgba(34,197,94,0.08) 0%, rgba(22,163,74,0.04) 100%);
        }
        .footer-whatsapp-card:hover {
            background: linear-gradient(135deg, rgba(34,197,94,0.14) 0%, rgba(22,163,74,0.08) 100%);
        }
    </style>

    <footer id="contact" class="relative overflow-hidden" style="background: linear-gradient(180deg, #0a0d1a 0%, #0f1225 40%, #0a0d1a 100%);">

        <!-- Decorative orbs -->
        <div class="absolute top-20 left-[10%] w-72 h-72 rounded-full opacity-30 blur-[100px]" style="background: radial-gradient(circle, rgba(249,115,22,0.2) 0%, transparent 70%);"></div>
        <div class="absolute bottom-20 right-[10%] w-80 h-80 rounded-full opacity-20 blur-[120px]" style="background: radial-gradient(circle, rgba(29,78,216,0.2) 0%, transparent 70%);"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full border border-white/[0.02]"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[900px] h-[900px] rounded-full border border-white/[0.015]" style="animation: footer-float 12s ease-in-out infinite;"></div>

        <!-- Grid texture overlay -->
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>

        <div class="relative z-10">

            <!-- Hero brand area -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 sm:pt-24 pb-16">
                <div class="text-center mb-16">
                    <!-- Logo icon with glow -->
                    <div class="inline-flex items-center justify-center mb-6 relative">
                        <div class="absolute inset-0 w-16 h-16 rounded-2xl bg-primary-orange/20 blur-xl m-auto"></div>
                        <div class="relative w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-orange via-orange-500 to-orange-600 flex items-center justify-center shadow-2xl shadow-orange-500/30">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Large brand name -->
                    <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight mb-4">
                        <span class="text-white">{Customer}</span><span class="footer-glow-text">NearMe</span>
                    </h2>
                    <p class="text-gray-400 text-base sm:text-lg max-w-md mx-auto leading-relaxed">
                        Find real, active business leads in minutes.
                    </p>
                </div>

                <!-- Main content: 3-column asymmetric grid -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-12 md:gap-8 lg:gap-12">

                    <!-- Col 1: Navigation as pills -->
                    <div class="md:col-span-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-primary-orange/80 mb-5">Navigate</p>
                        <div class="flex flex-wrap gap-2.5 mb-7">
                            <a href="#features" class="footer-link-pill px-5 py-2.5 text-[13px] font-medium text-gray-300 bg-white/[0.04] border border-white/[0.07] rounded-full hover:text-white hover:border-primary-orange/30 hover:bg-white/[0.08] transition-all duration-300">Features</a>
                            <a href="#how-it-works" class="footer-link-pill px-5 py-2.5 text-[13px] font-medium text-gray-300 bg-white/[0.04] border border-white/[0.07] rounded-full hover:text-white hover:border-primary-orange/30 hover:bg-white/[0.08] transition-all duration-300">How It Works</a>
                            <a href="#pricing" class="footer-link-pill px-5 py-2.5 text-[13px] font-medium text-gray-300 bg-white/[0.04] border border-white/[0.07] rounded-full hover:text-white hover:border-primary-orange/30 hover:bg-white/[0.08] transition-all duration-300">Pricing</a>
                            <a href="#faq" class="footer-link-pill px-5 py-2.5 text-[13px] font-medium text-gray-300 bg-white/[0.04] border border-white/[0.07] rounded-full hover:text-white hover:border-primary-orange/30 hover:bg-white/[0.08] transition-all duration-300">FAQ</a>
                            <a href="{{ route('auth.show') }}" class="footer-link-pill px-5 py-2.5 text-[13px] font-medium text-gray-300 bg-white/[0.04] border border-white/[0.07] rounded-full hover:text-white hover:border-primary-orange/30 hover:bg-white/[0.08] transition-all duration-300">Login</a>
                        </div>

                        <!-- CTA button -->
                        <a href="{{ route('auth.show') }}" class="group inline-flex items-center gap-2.5 px-7 py-3.5 rounded-full text-sm font-bold text-white shadow-xl shadow-orange-500/20 hover:shadow-orange-500/35 hover:scale-[1.03] active:scale-[0.98] transition-all duration-300" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 50%, #f97316 100%); background-size: 200% 200%; animation: footer-gradient-shift 3s ease infinite;">
                            Start Free Trial
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Col 2: Contact cards -->
                    <div class="md:col-span-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-primary-orange/80 mb-5">Get in Touch</p>

                        <!-- Email card -->
                        <a href="mailto:info@customernearme.com" class="group flex items-center gap-4 p-4 rounded-2xl bg-white/[0.03] border border-white/[0.06] hover:bg-white/[0.06] hover:border-primary-orange/20 transition-all duration-300 mb-3">
                            <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-gradient-to-br from-primary-orange/15 to-orange-600/10 flex items-center justify-center text-primary-orange group-hover:from-primary-orange/25 group-hover:to-orange-600/15 group-hover:scale-105 transition-all duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-gray-500 uppercase tracking-wider mb-0.5">Email us</p>
                                <p class="text-sm text-gray-200 group-hover:text-primary-orange transition-colors duration-300 truncate">info@customernearme.com</p>
                            </div>
                        </a>

                        <!-- WhatsApp card — prominent -->
                        <a href="https://wa.me/923460820722" target="_blank" rel="noopener noreferrer" class="footer-whatsapp-card group flex items-center gap-4 p-4 rounded-2xl border border-green-500/10 hover:border-green-500/25 transition-all duration-300">
                            <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-green-500/15 flex items-center justify-center text-green-400 group-hover:bg-green-500/25 group-hover:scale-105 transition-all duration-300 relative">
                                <svg class="w-5 h-5 relative z-10" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] text-green-400/60 uppercase tracking-wider mb-0.5">Chat on WhatsApp</p>
                                <p class="text-sm text-gray-200 group-hover:text-green-400 transition-colors duration-300">+92 346 0820722</p>
                            </div>
                            <div class="flex-shrink-0 relative">
                                <span class="flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-400"></span>
                                </span>
                            </div>
                        </a>
                    </div>

                    <!-- Col 3: Social icons + extras (right-aligned on desktop) -->
                    <div class="md:col-span-4 flex flex-col md:items-end">
                        <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-primary-orange/80 mb-5">Follow Us</p>
                        <div class="flex flex-wrap items-center gap-3 mb-8">

                            <!-- Facebook -->
                            <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="footer-social-icon group w-[52px] h-[52px] rounded-2xl bg-white/[0.04] border border-white/[0.07] flex items-center justify-center hover:border-blue-500/30 transition-all duration-300 hover:scale-105">
                                <svg class="w-[22px] h-[22px] text-gray-400 group-hover:text-blue-400 transition-all duration-300 relative z-10" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>

                            <!-- X (Twitter) -->
                            <a href="#" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)" class="footer-social-icon group w-[52px] h-[52px] rounded-2xl bg-white/[0.04] border border-white/[0.07] flex items-center justify-center hover:border-white/20 transition-all duration-300 hover:scale-105">
                                <svg class="w-[20px] h-[20px] text-gray-400 group-hover:text-white transition-all duration-300 relative z-10" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                            </a>

                            <!-- Instagram -->
                            <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="footer-social-icon group w-[52px] h-[52px] rounded-2xl bg-white/[0.04] border border-white/[0.07] flex items-center justify-center hover:border-pink-500/30 transition-all duration-300 hover:scale-105">
                                <svg class="w-[22px] h-[22px] text-gray-400 group-hover:text-pink-400 transition-all duration-300 relative z-10" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                </svg>
                            </a>

                            <!-- YouTube -->
                            <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="footer-social-icon group w-[52px] h-[52px] rounded-2xl bg-white/[0.04] border border-white/[0.07] flex items-center justify-center hover:border-red-500/30 transition-all duration-300 hover:scale-105">
                                <svg class="w-[22px] h-[22px] text-gray-400 group-hover:text-red-400 transition-all duration-300 relative z-10" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </a>

                            <!-- WhatsApp -->
                            <a href="https://wa.me/923460820722" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="footer-social-icon group w-[52px] h-[52px] rounded-2xl bg-green-500/[0.08] border border-green-500/[0.12] flex items-center justify-center hover:border-green-400/30 transition-all duration-300 hover:scale-105">
                                <svg class="w-[22px] h-[22px] text-green-400 group-hover:text-green-300 transition-all duration-300 relative z-10" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </a>
                        </div>

                        <!-- Micro trust badge -->
                        <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-white/[0.03] border border-white/[0.05]">
                            <div class="flex -space-x-1">
                                <div class="w-2 h-2 rounded-full bg-green-400"></div>
                                <div class="w-2 h-2 rounded-full bg-primary-orange"></div>
                                <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                            </div>
                            <span class="text-[11px] text-gray-500 font-medium">Trusted by 1,000+ marketers</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Bottom bar -->
            <div class="relative">
                <!-- Gradient divider line -->
                <div class="h-px" style="background: linear-gradient(90deg, transparent 0%, rgba(249,115,22,0.3) 30%, rgba(29,78,216,0.2) 70%, transparent 100%);"></div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-6 mb-3">
                        <a href="{{ route('terms') }}" class="text-[12px] text-gray-500 hover:text-primary-orange transition-colors">Terms of Service</a>
                        <a href="{{ route('privacy.policy') }}" class="text-[12px] text-gray-500 hover:text-primary-orange transition-colors">Privacy Policy</a>
                        <a href="{{ route('refund.policy') }}" class="text-[12px] text-gray-500 hover:text-primary-orange transition-colors">Refund Policy</a>
                    </div>
                    <p class="text-[13px] text-gray-500 text-center">
                        &copy; <script>document.write(new Date().getFullYear())</script> CustomerNearme. All rights reserved.
                    </p>
                </div>
            </div>

        </div>
    </footer>

    <script>
        // FAQ Toggle Function
        function toggleFAQ(button) {
            var faqItem = button.closest('.faq-item');
            var content = button.nextElementSibling;
            var icon = button.querySelector('svg');
            var isOpen = content.style.maxHeight && content.style.maxHeight !== '0px';

            // Close all other FAQ items
            document.querySelectorAll('.faq-item').forEach(function(item) {
                if (item !== faqItem) {
                    var c = item.querySelector('.faq-content');
                    var i = item.querySelector('.faq-icon svg');
                    if (c) { c.style.maxHeight = '0px'; }
                    if (i) { i.style.transform = 'rotate(0deg)'; }
                    item.classList.remove('border-orange-300', 'shadow-sm');
                }
            });

            // Toggle current item
            if (isOpen) {
                content.style.maxHeight = '0px';
                icon.style.transform = 'rotate(0deg)';
                faqItem.classList.remove('border-orange-300', 'shadow-sm');
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
                icon.style.transform = 'rotate(180deg)';
                faqItem.classList.add('border-orange-300', 'shadow-sm');
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