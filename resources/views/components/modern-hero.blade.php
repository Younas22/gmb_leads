<!-- Modern SaaS Hero Section for CustomerNearme -->
<!-- Add Roboto Font to <head> -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    @keyframes float-1 {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }

    @keyframes float-2 {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-30px) rotate(-5deg); }
    }

    @keyframes float-3 {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-25px) rotate(3deg); }
    }

    @keyframes float-4 {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-15px) rotate(-3deg); }
    }

    .floating-emoji {
        position: absolute;
        font-size: 2rem;
        opacity: 0.15;
        pointer-events: none;
        user-select: none;
    }
</style>

<!-- Hero Section -->
<section class="relative py-12 sm:py-16 bg-gradient-to-br from-white via-orange-50/30 to-blue-50/40 overflow-hidden" style="font-family: 'Roboto', sans-serif;">

    <!-- Background Decorative Elements -->
    <div class="absolute top-0 left-0 w-64 h-64 bg-gradient-to-br from-blue-400/10 to-transparent rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-0 w-64 h-64 bg-gradient-to-tl from-orange-400/10 to-transparent rounded-full blur-3xl"></div>

    <!-- Floating Emoji Background -->
    <div class="floating-emoji" style="top: 10%; left: 5%; animation: float-1 6s ease-in-out infinite;">💼</div>
    <div class="floating-emoji" style="top: 20%; right: 8%; animation: float-2 7s ease-in-out infinite;">💰</div>
    <div class="floating-emoji" style="top: 60%; left: 10%; animation: float-3 5s ease-in-out infinite;">💼</div>
    <div class="floating-emoji" style="top: 70%; right: 15%; animation: float-4 8s ease-in-out infinite;">💰</div>
    <div class="floating-emoji" style="top: 40%; left: 3%; animation: float-1 7s ease-in-out infinite 0.5s;">💰</div>
    <div class="floating-emoji" style="top: 50%; right: 5%; animation: float-2 6s ease-in-out infinite 1s;">💼</div>
    <div class="floating-emoji" style="top: 30%; right: 20%; animation: float-3 6.5s ease-in-out infinite 0.8s;">💼</div>
    <div class="floating-emoji" style="top: 80%; left: 8%; animation: float-4 7.5s ease-in-out infinite 1.2s;">💰</div>
    <div class="floating-emoji" style="top: 15%; left: 18%; animation: float-2 5.5s ease-in-out infinite 0.3s;">💰</div>
    <div class="floating-emoji" style="top: 85%; right: 12%; animation: float-1 8.5s ease-in-out infinite 1.5s;">💼</div>
    <div class="floating-emoji" style="top: 25%; left: 25%; animation: float-3 7s ease-in-out infinite 0.6s;">💰</div>
    <div class="floating-emoji" style="top: 65%; right: 25%; animation: float-4 6s ease-in-out infinite 1.8s;">💼</div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center">

            <!-- Content -->
            <div class="text-center max-w-4xl mx-auto">

                <!-- Micro Badge Above Heading -->
                <div class="flex justify-center mb-4">
                    <span class="inline-flex items-center gap-1.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white px-4 py-1.5 rounded-full text-xs font-semibold shadow-sm">
                        Freelancers' ultimate tool to find active clients fast
                    </span>
                </div>

                <!-- Main Heading -->
                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black text-gray-900 mb-4 leading-tight">
                    Direct Client Hunting,
                    <span class="relative inline-block">
                        <span class="relative z-10 text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-600">
                            Done Right
                        </span>
                    </span>
                    <span class="inline-block">🚀</span>
                </h1>

                <!-- Subheading -->
                <p class="text-base sm:text-lg text-gray-600 mb-6 leading-relaxed max-w-2xl mx-auto">
                    Stop wasting time on dead leads or marketplaces. Discover real, active businesses from Google Maps and pitch clients before anyone else.
                </p>

                <!-- Feature Highlights -->
                <!-- <div class="flex flex-wrap justify-center gap-3 sm:gap-4 mb-6">
                    <div class="group flex items-center gap-2.5 bg-gradient-to-br from-white to-green-50/50 px-4 py-2.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-green-100 hover:border-green-300 hover:-translate-y-0.5">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-800 text-sm">Active Businesses Only</span>
                    </div>
                    <div class="group flex items-center gap-2.5 bg-gradient-to-br from-white to-blue-50/50 px-4 py-2.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-blue-100 hover:border-blue-300 hover:-translate-y-0.5">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-800 text-sm">Live Google Maps Data</span>
                    </div>
                    <div class="group flex items-center gap-2.5 bg-gradient-to-br from-white to-orange-50/50 px-4 py-2.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-orange-100 hover:border-orange-300 hover:-translate-y-0.5">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-800 text-sm">Saved Forever</span>
                    </div>
                </div> -->

                <!-- CTA Buttons -->
                <div class="flex flex-wrap gap-3 sm:gap-4 justify-center items-center mb-3">
                    <a href="{{ route('auth.show') }}" class="inline-flex items-center justify-center w-[180px] sm:w-[200px] px-6 py-3.5 text-sm sm:text-base font-bold text-white bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg shadow-lg hover:shadow-xl hover:from-orange-600 hover:to-orange-700 transition-all duration-200">
                        Start Free Trial
                        <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>

                    <a href="#how-it-works" class="inline-flex items-center justify-center w-[180px] sm:w-[200px] px-6 py-3.5 text-sm sm:text-base font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:border-orange-500 hover:text-orange-600 transition-all duration-200">
                        How It Works
                    </a>
                </div>

                <p class="text-sm text-gray-500 mb-8 mt-2">
                    No credit card required • 100% Free trial
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
