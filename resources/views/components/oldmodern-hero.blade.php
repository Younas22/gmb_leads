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