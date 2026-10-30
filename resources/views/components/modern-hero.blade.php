<!-- Hero Section (two-column: copy left, product visualization right) -->
<style>
    /* Hero product visualization — schematic map, not a live screenshot */
    .hero-map {
        background-color: #f3f4f6;
        background-image:
            linear-gradient(90deg, rgba(255,255,255,.9) 6px, transparent 6px),
            linear-gradient(rgba(255,255,255,.9) 6px, transparent 6px),
            linear-gradient(90deg, rgba(209,213,219,.5) 1px, transparent 1px),
            linear-gradient(rgba(209,213,219,.5) 1px, transparent 1px);
        background-size: 120px 120px, 120px 120px, 24px 24px, 24px 24px;
        background-position: 40px 0, 0 60px, 0 0, 0 0;
    }
    .hero-map-park { background: #dcfce7; }
    .hero-map-water { background: #dbeafe; }
    .hero-pin { color: #f97316; filter: drop-shadow(0 2px 3px rgba(17,17,17,.25)); }
    .hero-dots {
        background-image: radial-gradient(rgba(249,115,22,.35) 1.5px, transparent 1.5px);
        background-size: 14px 14px;
    }
</style>

<section class="relative bg-white overflow-hidden border-b border-gray-100" aria-labelledby="hero-heading">
    <div class="hero-dots absolute top-10 right-0 w-40 h-40 opacity-60 pointer-events-none hidden lg:block" aria-hidden="true"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-10 items-center">

            <!-- Left: copy -->
            <div class="text-center lg:text-left">
                <span class="inline-flex items-center gap-2 bg-orange-50 text-orange-700 border border-orange-200 px-3 py-1.5 rounded-full text-xs font-semibold mb-5">
                    <i class="fas fa-location-dot text-orange-500" aria-hidden="true"></i>
                    Find Real Clients in Your Local Area
                </span>

                <h1 id="hero-heading" class="text-4xl sm:text-5xl lg:text-[3.25rem] font-extrabold text-[#111111] leading-[1.1] tracking-tight mb-5">
                    Build a Direct Client Funnel Using <span class="text-primary-orange">Google Maps</span>
                </h1>

                <p class="text-base sm:text-lg text-gray-600 leading-relaxed max-w-xl mx-auto lg:mx-0 mb-8">
                    Find verified local and global businesses from Google Maps, extract accurate contacts and turn them into real paying clients.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start mb-6">
                    <a href="{{ route('auth.show') }}"
                       class="inline-flex items-center justify-center gap-2 px-6 py-3.5 text-base font-semibold text-white bg-primary-orange hover:bg-dark-orange rounded-lg shadow-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-orange focus-visible:ring-offset-2">
                        Get Started Now
                        <i class="fas fa-arrow-right text-sm" aria-hidden="true"></i>
                    </a>
                    <a href="#demo"
                       class="inline-flex items-center justify-center gap-2 px-6 py-3.5 text-base font-semibold text-[#111111] bg-white border-2 border-[#111111] hover:bg-gray-50 rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#111111] focus-visible:ring-offset-2">
                        <i class="fas fa-circle-play text-primary-orange" aria-hidden="true"></i>
                        Watch Demo
                    </a>
                </div>

                <p class="text-sm text-gray-500 mb-6 inline-flex items-center gap-1.5">
                    <i class="fas fa-circle-check text-primary-orange" aria-hidden="true"></i>No credit card required
                </p>

                <dl class="grid grid-cols-3 max-w-md mx-auto lg:mx-0 border border-gray-200 rounded-xl divide-x divide-gray-200 bg-white">
                    <div class="px-3 py-3 text-center lg:text-left">
                        <dt class="sr-only">Users</dt>
                        <dd class="text-xl sm:text-2xl font-extrabold text-[#111111] leading-none">1,000+</dd>
                        <dd class="text-[11px] sm:text-xs text-gray-500 mt-1">Users</dd>
                    </div>
                    <div class="px-3 py-3 text-center lg:text-left">
                        <dt class="sr-only">Leads generated</dt>
                        <dd class="text-xl sm:text-2xl font-extrabold text-[#111111] leading-none">50,000+</dd>
                        <dd class="text-[11px] sm:text-xs text-gray-500 mt-1">Leads Generated</dd>
                    </div>
                    <div class="px-3 py-3 text-center lg:text-left">
                        <dt class="sr-only">Free trial</dt>
                        <dd class="text-xl sm:text-2xl font-extrabold text-primary-orange leading-none">3-Day</dd>
                        <dd class="text-[11px] sm:text-xs text-gray-500 mt-1">Free Trial</dd>
                    </div>
                </dl>
            </div>

            <!-- Right: product visualization (illustrative) -->
            <div class="relative mx-auto w-full max-w-xl" role="img" aria-label="Illustration of searching Google Maps for businesses and viewing their contact details">

                <!-- decorative arrows -->
                <svg class="absolute -left-8 top-10 w-16 h-16 text-orange-300 hidden lg:block" fill="none" viewBox="0 0 64 64" aria-hidden="true">
                    <path d="M4 50 C 20 20, 40 14, 56 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="4 6"/>
                    <path d="M48 10 L57 18 L47 25" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

                <!-- Laptop screen -->
                <div class="relative bg-[#111111] rounded-t-2xl p-2.5 shadow-2xl">
                    <div class="bg-white rounded-lg overflow-hidden">
                        <!-- browser bar -->
                        <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border-b border-gray-200">
                            <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>
                            <div class="ml-2 flex-1 flex items-center gap-2 bg-white border border-gray-200 rounded-md px-2 py-1">
                                <i class="fas fa-magnifying-glass text-[10px] text-primary-orange" aria-hidden="true"></i>
                                <span class="text-[10px] text-gray-500 truncate">dentist in New York</span>
                            </div>
                        </div>

                        <div class="flex h-56 sm:h-64">
                            <!-- results sidebar -->
                            <div class="w-[38%] border-r border-gray-200 bg-white overflow-hidden">
                                <div class="px-2.5 py-2 text-[10px] font-semibold text-gray-500 border-b border-gray-100">Results</div>
                                @foreach([['Bright Smile Dental', '4.9'], ['City Dental Care', '4.7'], ['Park Ave Dentistry', '4.8'], ['Smile Studio NYC', '4.6']] as $i => $r)
                                    <div class="px-2.5 py-2 border-b border-gray-100 {{ $i === 0 ? 'bg-orange-50' : '' }}">
                                        <div class="text-[10px] font-semibold text-[#111111] truncate">{{ $r[0] }}</div>
                                        <div class="flex items-center gap-1 text-[9px] text-gray-500">
                                            <i class="fas fa-star text-amber-400" aria-hidden="true"></i>{{ $r[1] }} · Dentist
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- map -->
                            <div class="relative flex-1 hero-map">
                                <div class="hero-map-park absolute top-6 left-8 w-16 h-10 rounded-md"></div>
                                <div class="hero-map-water absolute bottom-4 right-3 w-20 h-12 rounded-lg"></div>
                                <i class="fas fa-location-dot hero-pin absolute top-[22%] left-[28%] text-xl" aria-hidden="true"></i>
                                <i class="fas fa-location-dot hero-pin absolute top-[48%] left-[58%] text-2xl" aria-hidden="true"></i>
                                <i class="fas fa-location-dot hero-pin absolute top-[66%] left-[22%] text-lg" aria-hidden="true"></i>
                                <i class="fas fa-location-dot hero-pin absolute top-[30%] left-[76%] text-lg" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Laptop base -->
                <div class="relative mx-auto h-3 w-[108%] -ml-[4%] bg-gray-300 rounded-b-xl shadow-md">
                    <div class="absolute left-1/2 -translate-x-1/2 top-0 w-20 h-1.5 bg-gray-400 rounded-b-md"></div>
                </div>

                <!-- Floating business card -->
                <div class="absolute -bottom-6 right-2 sm:-right-4 w-56 bg-white rounded-xl border border-gray-200 shadow-xl p-3.5">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-orange-50 border border-orange-100 flex items-center justify-center">
                            <i class="fas fa-store text-primary-orange text-xs" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-[#111111] truncate">Bright Smile Dental</div>
                            <div class="text-[10px] text-gray-500"><i class="fas fa-star text-amber-400" aria-hidden="true"></i> 4.9 · 3,210 reviews</div>
                        </div>
                    </div>
                    <ul class="grid grid-cols-2 gap-x-2 gap-y-1 text-[10px] text-gray-600">
                        <li class="flex items-center gap-1.5"><i class="fas fa-location-dot w-3 text-primary-orange" aria-hidden="true"></i>Location</li>
                        <li class="flex items-center gap-1.5"><i class="fas fa-phone w-3 text-primary-orange" aria-hidden="true"></i>Phone</li>
                        <li class="flex items-center gap-1.5"><i class="fas fa-envelope w-3 text-primary-orange" aria-hidden="true"></i>Email</li>
                        <li class="flex items-center gap-1.5"><i class="fas fa-globe w-3 text-primary-orange" aria-hidden="true"></i>Website</li>
                        <li class="flex items-center gap-1.5"><i class="fas fa-star w-3 text-primary-orange" aria-hidden="true"></i>Reviews</li>
                        <li class="flex items-center gap-1.5"><i class="fas fa-share-nodes w-3 text-primary-orange" aria-hidden="true"></i>Social media</li>
                    </ul>
                </div>

                <!-- Floating search chip -->
                <div class="absolute -top-4 left-4 sm:-left-4 hidden sm:flex items-center gap-2 bg-white border border-gray-200 rounded-lg shadow-lg px-3 py-2">
                    <span class="w-6 h-6 rounded-md bg-primary-orange flex items-center justify-center">
                        <i class="fas fa-file-export text-white text-[10px]" aria-hidden="true"></i>
                    </span>
                    <span class="text-xs font-semibold text-[#111111]">Export to CSV / Excel</span>
                </div>
            </div>

        </div>
    </div>
</section>
