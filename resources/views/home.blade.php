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

    <!-- Hero Section -->
    <section class="pt-16 pb-20 bg-gradient-to-b from-gray-50 to-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-4xl mx-auto">
                <!-- Pain Point Heading -->
                <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold text-gray-900 mb-6 leading-tight">
                    Still Struggling to Find Clients Because You're Approaching
                    <span class="text-primary-orange"> Outdated & Dead Leads?</span>
                </h1>

                <!-- Sub Heading -->
                <p class="text-lg sm:text-xl text-gray-600 mb-4 max-w-3xl mx-auto leading-relaxed">
                    {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} helps you discover hundreds of active businesses in minutes — not outdated lists, not fake data, only <strong class="text-gray-800">real Google Maps businesses</strong>.
                </p>

                <!-- Emotional Line -->
                <p class="text-base sm:text-lg text-red-500 font-medium mb-8">
                    Stop wasting time messaging businesses that no longer exist or never reply.
                </p>

                <!-- Promise Bullets -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-8 mb-10">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-700 font-medium">Find active businesses only</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-700 font-medium">Real-time data from Google Maps</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-700 font-medium">Leads saved forever — use anytime</span>
                    </div>
                </div>

                <!-- CTA -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-3">
                    <a href="{{ route('auth.show') }}" class="bg-primary-orange hover:bg-dark-orange text-white px-8 py-4 rounded-xl text-lg font-bold transition-all hover:scale-105 shadow-lg shadow-orange-200">
                        Start Free Trial — No Credit Card
                    </a>
                    <a href="#features" class="border-2 border-gray-300 text-gray-700 hover:border-primary-blue hover:text-primary-blue px-8 py-4 rounded-xl text-lg font-semibold transition-all">
                        See How It Works
                    </a>
                </div>
                <p class="text-sm text-gray-400 mb-12">No credit card required. Cancel anytime.</p>

                <!-- Hero Image / Dashboard Preview -->
                <div class="relative max-w-5xl mx-auto">
                    <div class="absolute -inset-4 bg-gradient-to-r from-blue-500/20 to-orange-500/20 rounded-2xl blur-2xl"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl p-2 sm:p-4 border border-gray-100">
                        <img src="{{ asset('public/images/hero/hero.png') }}" alt="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} Dashboard — Real Google Maps Business Leads" class="w-full rounded-xl">
                    </div>
                </div>
            </div>
        </div>
    </section>

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