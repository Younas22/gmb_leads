<!-- Modern SaaS Hero Section for CustomerNearme -->
<!-- Add Roboto Font to <head> -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    @keyframes pulse-shadow {
        0%, 100% { box-shadow: 0 0 20px rgba(249, 115, 22, 0.3); }
        50% { box-shadow: 0 0 40px rgba(249, 115, 22, 0.5); }
    }

    .badge-hover:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .hero-float {
        animation: float 3s ease-in-out infinite;
    }
</style>

<!-- Hero Section -->
<section class="relative pt-10 pb-12 bg-gradient-to-br from-white via-orange-50/30 to-blue-50/40 overflow-hidden" style="font-family: 'Roboto', sans-serif;">

    <!-- Background Decorative Elements -->
    <div class="absolute top-0 left-0 w-64 h-64 bg-gradient-to-br from-blue-400/10 to-transparent rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-0 w-64 h-64 bg-gradient-to-tl from-orange-400/10 to-transparent rounded-full blur-3xl"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center">

            <!-- Content -->
            <div class="text-center max-w-4xl mx-auto">

                <!-- Micro Badges Above Heading -->
                <div class="flex flex-wrap justify-center gap-2 mb-3">
                    <span class="inline-flex items-center gap-1.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm">
                        Direct Client Hunting 🚀
                    </span>
                    <span class="inline-flex items-center gap-1.5 bg-blue-600 text-white px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm">
                        No Fiverr, No Upwork Needed
                    </span>
                </div>

                <!-- Main Heading -->
                <h1 class="text-3xl sm:text-3xl lg:text-6xl font-black text-gray-900 mb-4 leading-tight">
                    Still Struggling to Find Clients Because You're Approaching
                    <span class="relative inline-block">
                        <span class="relative z-10 text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-600">
                            Outdated & Dead Leads?
                        </span>
                        <span class="absolute bottom-1 left-0 w-full h-4 bg-orange-200 -z-0"></span>
                    </span>
                </h1>

                <!-- Subheading -->
                <p class="text-sm sm:text-base lg:text-lg text-gray-600 mb-4 leading-relaxed max-w-3xl mx-auto">
                    <strong class="text-gray-900">{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}</strong> helps you discover hundreds of active businesses in minutes — not outdated lists, not fake data, only <strong class="text-gray-900">real Google Maps businesses</strong>.
                </p>

                <p class="text-sm sm:text-base text-red-600 font-semibold mb-5 max-w-3xl mx-auto">
                    Stop wasting time messaging businesses that no longer exist or never reply.
                </p>

                <!-- Feature Micro-Highlights -->
                <div class="flex flex-row flex-wrap justify-center gap-1.5 sm:gap-2 mb-5">
                    <div class="inline-flex items-center gap-1 sm:gap-1.5 bg-white px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg shadow-sm border border-gray-200">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-orange-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-[10px] sm:text-sm font-medium text-gray-700">Active Businesses Only</span>
                    </div>

                    <div class="inline-flex items-center gap-1 sm:gap-1.5 bg-white px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg shadow-sm border border-gray-200">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-[10px] sm:text-sm font-medium text-gray-700">Real Google Maps Data</span>
                    </div>

                    <div class="inline-flex items-center gap-1 sm:gap-1.5 bg-white px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg shadow-sm border border-gray-200">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-orange-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z"/>
                            <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z"/>
                        </svg>
                        <span class="text-[10px] sm:text-sm font-medium text-gray-700">Saved Forever</span>
                    </div>
                </div>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center mb-3">
                    <a href="{{ route('auth.show') }}" class="group relative inline-flex items-center justify-center px-6 py-3 text-sm sm:text-base font-bold text-white bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <span class="absolute inset-0 bg-gradient-to-r from-blue-600 to-blue-700 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                        <span class="relative flex items-center gap-2">
                            Start Free Trial — No Credit Card
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </span>
                    </a>

                    <a href="#features" class="group inline-flex items-center justify-center px-6 py-3 text-sm sm:text-base font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:border-blue-600 hover:text-blue-600 transition-all duration-300 shadow-sm hover:shadow-md">
                        <span class="flex items-center gap-2">
                            See How It Works
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                    </a>
                </div>

                <p class="text-xs sm:text-sm text-gray-500 italic mb-8">
                    ✨ No credit card required. Cancel anytime. Start in 60 seconds.
                </p>
            </div>

            <!-- Hero Image/Dashboard Mockup -->
            <div class="max-w-4xl mx-auto mt-6">
                <div class="relative">

                    <!-- Glowing Background -->
                    <div class="absolute -inset-2 bg-gradient-to-r from-blue-500/15 to-orange-500/15 rounded-xl blur-2xl opacity-40"></div>

                    <!-- Main Image Container -->
                    <div class="relative bg-white rounded-xl shadow-xl p-2 sm:p-3 border border-gray-200">

                        <!-- Feature Badges on Image -->
                        <!-- Top Right -->
                        <div class="absolute -top-2 -right-2 sm:-top-3 sm:-right-3 z-20">
                            <span class="inline-flex items-center gap-1 bg-white text-orange-600 px-2 sm:px-2.5 py-1 rounded-lg text-xs font-bold shadow-lg border border-orange-200 badge-hover transition-all duration-300">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                <span class="hidden sm:inline">Export CSV/Excel</span>
                                <span class="sm:hidden">Export</span>
                            </span>
                        </div>

                        <!-- Top Left -->
                        <div class="absolute -top-2 -left-2 sm:-top-3 sm:-left-3 z-20">
                            <span class="inline-flex items-center gap-1 bg-white text-blue-600 px-2 sm:px-2.5 py-1 rounded-lg text-xs font-bold shadow-lg border border-blue-200 badge-hover transition-all duration-300">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                </svg>
                                <span class="hidden sm:inline">Advanced Search</span>
                                <span class="sm:hidden">Search</span>
                            </span>
                        </div>

                        <!-- Bottom Left -->
                        <div class="absolute -bottom-2 -left-2 sm:-bottom-3 sm:-left-3 z-20">
                            <span class="inline-flex items-center gap-1 bg-white text-orange-600 px-2 sm:px-2.5 py-1 rounded-lg text-xs font-bold shadow-lg border border-orange-200 badge-hover transition-all duration-300">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="hidden sm:inline">Active Leads Data</span>
                                <span class="sm:hidden">Active</span>
                            </span>
                        </div>

                        <!-- Bottom Right -->
                        <div class="absolute -bottom-2 -right-2 sm:-bottom-3 sm:-right-3 z-20">
                            <span class="inline-flex items-center gap-1 bg-white text-blue-600 px-2 sm:px-2.5 py-1 rounded-lg text-xs font-bold shadow-lg border border-blue-200 badge-hover transition-all duration-300">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z"/>
                                    <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z"/>
                                </svg>
                                <span class="hidden sm:inline">Save Forever</span>
                                <span class="sm:hidden">Save</span>
                            </span>
                        </div>

                        <!-- Dashboard Screenshot -->
                        <div class="relative rounded-lg overflow-hidden shadow-md">
                            <img src="{{ asset('public/images/hero/hero.png') }}"
                                 alt="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} Dashboard — Real-Time Google Maps Business Leads"
                                 class="w-full h-auto rounded-xl"
                                 loading="eager">

                            <!-- Overlay gradient for premium look -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/5 to-transparent pointer-events-none rounded-lg"></div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>
