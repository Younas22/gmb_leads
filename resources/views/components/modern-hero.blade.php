<!-- Modern SaaS Hero Section for CustomerNearme -->
<!-- Add Roboto Font to <head> -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

<style>
    @keyframes blob-drift {
        0%, 100% { transform: translate(0, 0) scale(1); }
        25% { transform: translate(30px, -50px) scale(1.05); }
        50% { transform: translate(-20px, 20px) scale(0.95); }
        75% { transform: translate(15px, 40px) scale(1.02); }
    }
    @keyframes blob-drift-reverse {
        0%, 100% { transform: translate(0, 0) scale(1); }
        25% { transform: translate(-40px, 30px) scale(0.95); }
        50% { transform: translate(25px, -25px) scale(1.05); }
        75% { transform: translate(-10px, -40px) scale(0.98); }
    }
    .hero-blob-1 { animation: blob-drift 18s ease-in-out infinite; }
    .hero-blob-2 { animation: blob-drift-reverse 22s ease-in-out infinite; }
</style>

<!-- Hero Section -->
<section class="relative py-12 sm:py-16 bg-white overflow-hidden" style="font-family: 'Roboto', sans-serif;">

    <!-- Decorative Background (refund-style) -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute inset-0" style="background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(249,115,22,0.06) 0%, transparent 60%), radial-gradient(ellipse 60% 50% at 20% 80%, rgba(29,78,216,0.05) 0%, transparent 60%);"></div>
        <div class="hero-blob-1 absolute -top-32 -right-32 w-[500px] h-[500px] rounded-full" style="background: radial-gradient(circle, rgba(249,115,22,0.07) 0%, transparent 70%);"></div>
        <div class="hero-blob-2 absolute -bottom-40 -left-40 w-[600px] h-[600px] rounded-full" style="background: radial-gradient(circle, rgba(29,78,216,0.06) 0%, transparent 70%);"></div>
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h40v40H0z' fill='none' stroke='%23000' stroke-width='0.5'/%3E%3C/svg%3E&quot;);"></div>
    </div>

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
                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black text-gray-900 mb-4 leading-tight tracking-tight">
                    Build a Direct Client Funnel
                    <span class="relative inline-block">
                        <span class="relative z-10 text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-600">
                            Using Google Maps
                        </span>
                    </span>
                    <span class="inline-block"> 🚀</span>
                </h1>

                <!-- Subheading -->
                <p class="text-base sm:text-lg text-gray-600 mb-6 leading-relaxed max-w-2xl mx-auto">
                    Find verified local and global businesses in minutes.
                    Skip marketplaces and create your own client pipeline.
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
                <div class="flex flex-nowrap gap-2 sm:gap-4 justify-center items-center mb-3">
                    <a href="{{ route('auth.show') }}" class="inline-flex items-center justify-center w-40 sm:w-48 px-4 sm:px-6 py-2.5 sm:py-3.5 text-xs sm:text-base font-bold text-white bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg shadow-lg hover:shadow-xl hover:from-orange-600 hover:to-orange-700 transition-all duration-200">
                        Start Free Trial
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>

                    <a href="#how-it-works" class="inline-flex items-center justify-center w-40 sm:w-48 px-4 sm:px-6 py-2.5 sm:py-3.5 text-xs sm:text-base font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:border-orange-500 hover:text-orange-600 transition-all duration-200">
                        How It Works
                    </a>
                </div>

                <p class="text-sm text-gray-500 mb-8 mt-2">
                    No credit card required • 3 Days Free Trial
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
