{{-- Homepage hero: copy left, illustrative product preview right. The preview is a static drawing, not live data. --}}
<section class="bg-white border-b border-gray-100" aria-labelledby="hero-heading">
    <div class="hp-wrap pt-10 pb-12 lg:pt-14 lg:pb-16">
        <div class="grid lg:grid-cols-[48fr_52fr] gap-10 lg:gap-12 items-center">

            <!-- Copy -->
            <div class="text-center lg:text-left">
                <a href="#ai-tools" class="inline-flex items-center gap-2 h-8 px-3.5 rounded-full bg-[#FAFAFA] border border-[#FED7AA] text-[12.5px] font-medium text-[#111111] hover:border-[#F97316] transition-colors mb-5">
                    <i class="fas fa-wand-magic-sparkles text-[#F97316] text-[11px]" aria-hidden="true"></i>
                    {{ !empty($aiSoon) ? 'Coming soon: AI that writes your outreach emails' : 'AI now writes your outreach emails' }}
                </a>

                <h1 id="hero-heading" class="text-[36px] sm:text-[46px] xl:text-[54px] font-extrabold text-[#111111] leading-[1.08] tracking-[-0.025em] mb-5">
                    Build a Direct Client<br class="hidden sm:block"> Funnel Using<br>
                    <span class="hp-mark">Google Maps</span>
                </h1>

                <p class="hp-lead !text-[15.5px] max-w-[520px] mx-auto lg:mx-0 mb-7">
                    @if(!empty($aiSoon))
                        Find verified businesses on Google Maps, save and organise them, and reach out from your own inbox to
                        <strong class="font-semibold text-[#111111]">turn leads into paying clients.</strong>
                        AI research and personal email drafts are coming soon.
                    @else
                        Find verified businesses on Google Maps, let AI study each one, write a personal email in your voice, and help you
                        <strong class="font-semibold text-[#111111]">turn leads into paying clients.</strong>
                    @endif
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start mb-5">
                    <a href="{{ route('auth.show') }}#signup" class="hp-btn">Get Started Now <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i></a>
                    <button type="button" onclick="hpOpenDemo()" class="hp-btn-ghost" aria-haspopup="dialog">
                        <span class="w-6 h-6 rounded-full bg-[#F97316] text-white flex items-center justify-center"><i class="fas fa-play text-[9px] ml-px" aria-hidden="true"></i></span>
                        Watch Demo
                    </button>
                </div>

                <ul class="flex flex-wrap justify-center lg:justify-start gap-x-6 gap-y-1.5 text-[13px] text-gray-600">
                    <li class="inline-flex items-center gap-1.5"><i class="fas fa-circle-check text-[#F97316]" aria-hidden="true"></i>No credit card required</li>
                    <li class="inline-flex items-center gap-1.5"><i class="fas fa-circle-check text-[#F97316]" aria-hidden="true"></i>3-Day free trial</li>
                </ul>
            </div>

            <!-- Illustrative product preview -->
            <div class="relative w-full max-w-[600px] mx-auto pb-10 sm:pb-8" role="img" aria-label="Illustration: searching Google Maps for dentists and opening a business card with its contact details">
                <div class="rounded-2xl border-[1.5px] border-[#111111] bg-white overflow-hidden shadow-[0_18px_40px_-18px_rgba(17,17,17,.35)]">
                    <!-- browser bar -->
                    <div class="flex items-center gap-3 px-3.5 py-2.5 border-b border-gray-200 bg-[#FAFAFA]">
                        <span class="flex gap-1.5" aria-hidden="true">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#FF5F57]"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-[#FEBC2E]"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-[#28C840]"></span>
                        </span>
                        <span class="hidden sm:flex items-center gap-1.5 text-[11px] font-bold text-[#111111]">
                            <i class="fas fa-location-dot text-[#F97316]" aria-hidden="true"></i>Customer<span class="text-[#F97316] -ml-1.5">NearMe</span>
                        </span>
                        <span class="flex-1 flex items-center gap-2 h-7 px-2.5 rounded-lg bg-white border border-gray-200 min-w-0">
                            <i class="fas fa-magnifying-glass text-[10px] text-[#F97316]" aria-hidden="true"></i>
                            <span class="text-[11px] text-gray-600 truncate">dentist in New York</span>
                        </span>
                    </div>

                    <div class="flex h-[230px] sm:h-[270px]">
                        <!-- results -->
                        <div class="w-[42%] border-r border-gray-200 overflow-hidden">
                            <div class="px-3 py-2 text-[10.5px] font-semibold text-gray-500 border-b border-gray-100">Results · example</div>
                            @foreach([['Bright Smile Dental', '4.9', '3,210'], ['City Dental Care', '4.7', '1,842'], ['Park Ave Dentistry', '4.8', '967'], ['Smile Studio NYC', '4.6', '1,105']] as $i => [$rName, $rRate, $rRev])
                                <div class="px-3 py-2 border-b border-gray-100 {{ $i === 0 ? 'bg-[#FFF7F2]' : '' }}">
                                    <div class="text-[11px] font-semibold text-[#111111] truncate">{{ $rName }}</div>
                                    <div class="text-[10px] text-gray-500"><i class="fas fa-star text-amber-400" aria-hidden="true"></i> {{ $rRate }} ({{ $rRev }}) · Dentist</div>
                                </div>
                            @endforeach
                        </div>
                        <!-- map -->
                        <div class="hp-map flex-1" aria-hidden="true">
                            <span class="hp-road left-0 right-0 top-[30%] h-2"></span>
                            <span class="hp-road left-0 right-0 top-[68%] h-1.5"></span>
                            <span class="hp-road top-0 bottom-0 left-[35%] w-2"></span>
                            <span class="hp-road top-0 bottom-0 left-[74%] w-1.5"></span>
                            <span class="absolute top-[8%] left-[6%] w-[22%] h-[17%] rounded-md bg-[#D9F2E1]"></span>
                            <span class="absolute bottom-[6%] right-[4%] w-[18%] h-[22%] rounded-lg bg-[#D6E6F7]"></span>
                            <i class="fas fa-location-dot hp-pin absolute top-[18%] left-[22%] text-lg"></i>
                            <i class="fas fa-location-dot hp-pin absolute top-[42%] left-[52%] text-2xl"></i>
                            <i class="fas fa-location-dot hp-pin absolute top-[62%] left-[16%] text-base"></i>
                            <i class="fas fa-location-dot hp-pin absolute top-[22%] left-[82%] text-base"></i>
                        </div>
                    </div>
                </div>

                <!-- floating business card -->
                <div class="absolute bottom-0 right-2 sm:-right-3 w-[230px] bg-white rounded-xl border border-gray-200 shadow-[0_12px_28px_-10px_rgba(17,17,17,.3)] p-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-lg bg-[#FFF7F2] text-[#F97316] flex items-center justify-center flex-shrink-0"><i class="fas fa-tooth text-sm" aria-hidden="true"></i></span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1 text-[12px] font-bold text-[#111111] truncate">Bright Smile Dental <i class="fas fa-circle-check text-[#16A34A] text-[10px]" aria-hidden="true"></i></div>
                            <div class="text-[10.5px] text-gray-500"><i class="fas fa-star text-amber-400" aria-hidden="true"></i> 4.9 · 3,210 reviews</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 mt-2.5" aria-hidden="true">
                        @foreach(['fa-phone', 'fa-envelope', 'fa-globe', 'fa-location-dot', 'fa-share-nodes'] as $ci)
                            <span class="w-7 h-7 rounded-lg bg-[#FFF7F2] text-[#F97316] flex items-center justify-center text-[11px]"><i class="fas {{ $ci }}"></i></span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Demo video (opens from "Watch Demo"; the video only loads when opened) -->
<div id="hp-demo" class="fixed inset-0 z-[60] hidden bg-black/70" role="dialog" aria-modal="true" aria-label="Product demo video">
    <div class="flex items-center justify-center min-h-full p-4" onclick="if (event.target === this) hpCloseDemo()">
        <div class="relative w-full max-w-4xl">
            <button type="button" onclick="hpCloseDemo()" class="absolute -top-11 right-0 w-9 h-9 rounded-full bg-white/15 hover:bg-white/25 text-white" aria-label="Close video"><i class="fas fa-xmark"></i></button>
            <div class="relative rounded-2xl overflow-hidden bg-black aspect-video">
                <iframe id="hp-demo-frame" class="absolute inset-0 w-full h-full" title="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }} product demo"
                        data-src="https://www.youtube.com/embed/YwUx_F4iE_M?rel=0&modestbranding=1&autoplay=1"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
    </div>
</div>
<script>
    function hpOpenDemo() {
        const m = document.getElementById('hp-demo'), f = document.getElementById('hp-demo-frame');
        f.src = f.dataset.src;
        m.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        m.querySelector('button').focus();
    }
    function hpCloseDemo() {
        document.getElementById('hp-demo').classList.add('hidden');
        document.getElementById('hp-demo-frame').src = '';
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !document.getElementById('hp-demo').classList.contains('hidden')) hpCloseDemo(); });
</script>
