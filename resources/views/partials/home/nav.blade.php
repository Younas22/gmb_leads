{{-- Homepage header. Ids (navbar, mobile-menu-btn, mobile-menu, bar-1..3) are used by the navbar script in home.blade.php. --}}
@php
    $siteLogo = \App\Models\Setting::get('site_logo');
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $hpLinks = [
        ['#features', 'Features', false],
        ['#ai-tools', 'AI Tools', true],
        ['#how-it-works', 'How It Works', false],
        ['#pricing', 'Pricing', false],
    ];
    if (!empty($homeReviews)) { $hpLinks[] = ['#reviews', 'Reviews', false]; }
    $hpLinks[] = ['#faq', 'FAQ', false];
@endphp
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 transition-all duration-300" role="navigation" aria-label="Main navigation">
    <div class="hp-wrap">
        <div class="flex items-center justify-between gap-4 h-[72px]">

            <!-- Logo -->
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 flex-shrink-0" aria-label="{{ $siteName }} home">
                @if($siteLogo)
                    <img src="{{ asset('public/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-9 w-auto object-contain">
                @else
                    <span class="w-9 h-9 rounded-xl bg-[#F97316] flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-location-dot text-white" aria-hidden="true"></i>
                    </span>
                    <span class="leading-tight">
                        <span class="block text-[17px] font-bold text-[#111111] tracking-tight">Customer<span class="text-[#F97316]">NearMe</span></span>
                        <span class="block text-[11px] text-gray-500">Verified GMB Leads Instantly</span>
                    </span>
                @endif
            </a>

            <!-- Desktop links -->
            <div class="hidden lg:flex items-center gap-0.5">
                @foreach($hpLinks as [$href, $label, $isNew])
                    <a href="{{ $href }}" class="hp-nav-link">{{ $label }}@if($isNew)<span class="hp-new {{ !empty($aiSoon) ? '!bg-[#111827]' : '' }}">{{ !empty($aiSoon) ? 'Soon' : 'New' }}</span>@endif</a>
                @endforeach
            </div>

            <!-- Desktop actions -->
            <div class="hidden lg:flex items-center gap-2.5">
                @guest
                    <a href="{{ route('auth.show') }}" class="hp-btn-outline hp-sm !text-[#111111]">Login</a>
                    <a href="{{ route('auth.show') }}#signup" class="hp-btn hp-sm">Get Started Free <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i></a>
                @endguest
                @auth
                    <a href="{{ route('user.dashboard') }}" class="hp-btn hp-sm"><i class="fas fa-table-cells-large text-xs" aria-hidden="true"></i> Dashboard</a>
                @endauth
            </div>

            <!-- Mobile menu button -->
            <button id="mobile-menu-btn" type="button" class="lg:hidden relative w-10 h-10 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F97316]" aria-expanded="false" aria-controls="mobile-menu" aria-label="Toggle navigation menu">
                <span class="sr-only">Open menu</span>
                <div class="w-5 h-4 flex flex-col justify-between" id="hamburger-icon">
                    <span class="block h-0.5 w-5 bg-gray-700 rounded-full transition-all duration-300 origin-center" id="bar-1"></span>
                    <span class="block h-0.5 w-3.5 bg-gray-700 rounded-full transition-all duration-300 ml-auto" id="bar-2"></span>
                    <span class="block h-0.5 w-5 bg-gray-700 rounded-full transition-all duration-300 origin-center" id="bar-3"></span>
                </div>
            </button>
        </div>
    </div>

    <!-- Mobile menu panel -->
    <div id="mobile-menu" class="lg:hidden hidden" role="menu">
        <div class="bg-white border-t border-gray-100 shadow-lg">
            <div class="hp-wrap py-3 space-y-0.5">
                @foreach($hpLinks as [$href, $label, $isNew])
                    <a href="{{ $href }}" class="flex items-center gap-2 px-3 py-2.5 text-[15px] font-medium text-gray-800 hover:bg-[#FFF7F2] rounded-xl" role="menuitem" onclick="closeMobileMenu()">
                        {{ $label }}@if($isNew)<span class="hp-new {{ !empty($aiSoon) ? '!bg-[#111827]' : '' }}">{{ !empty($aiSoon) ? 'Soon' : 'New' }}</span>@endif
                    </a>
                @endforeach
                <div class="pt-3 mt-2 border-t border-gray-100 grid grid-cols-2 gap-2">
                    @guest
                        <a href="{{ route('auth.show') }}" class="hp-btn-outline hp-sm !text-[#111111]" role="menuitem" onclick="closeMobileMenu()">Login</a>
                        <a href="{{ route('auth.show') }}#signup" class="hp-btn hp-sm" role="menuitem" onclick="closeMobileMenu()">Get Started Free</a>
                    @endguest
                    @auth
                        <a href="{{ route('user.dashboard') }}" class="hp-btn hp-sm col-span-2" role="menuitem" onclick="closeMobileMenu()">Dashboard</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</nav>
