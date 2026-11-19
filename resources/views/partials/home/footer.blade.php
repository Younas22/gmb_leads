{{-- Homepage footer (dark): short pitch + contact, social icons, big full-width wordmark, legal bar. --}}
@php
    $ftName = \App\Models\Setting::get('site_name', 'CustomerNearMe');
    $ftSocial = [
        ['https://www.facebook.com/CustomerNearme', 'fab fa-facebook-f', 'Facebook'],
        ['https://www.linkedin.com/company/customernearme', 'fab fa-linkedin-in', 'LinkedIn'],
        ['https://www.youtube.com/@CustomerNearme', 'fab fa-youtube', 'YouTube'],
        ['https://x.com/CustomerNearme', 'x-logo', 'X (Twitter)'],
        ['https://www.instagram.com/customernearme', 'fab fa-instagram', 'Instagram'],
        ['https://chat.whatsapp.com/JoXwhqeKW5sCRGuovBNQm8', 'fab fa-whatsapp', 'WhatsApp community'],
    ];
    $ftLegal = [
        ['Privacy Policy', route('privacy.policy')],
        ['Terms of Service', route('terms')],
        ['Refund Policy', route('refund.policy')],
    ];
@endphp
<footer id="contact" class="bg-[#18181B] text-[#A1A1AA] overflow-hidden" aria-labelledby="footer-heading">
    <h2 id="footer-heading" class="sr-only">Footer</h2>

    <div class="hp-wrap pt-14 pb-10">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
            <!-- Pitch + contact -->
            <div class="max-w-xl">
                <p class="text-[22px] sm:text-[26px] font-bold leading-snug text-white tracking-tight">
                    Find local clients. <span class="text-[#F97316]">Start real conversations.</span>
                </p>
                <p class="text-[14px] leading-relaxed mt-3">
                    {{ $ftName }} helps businesses discover potential clients, organise leads, and streamline personalised outreach.
                </p>
                <div class="flex flex-wrap gap-2.5 mt-6">
                    <a href="mailto:info@customernearme.com" class="inline-flex items-center gap-2.5 h-11 px-4 rounded-full bg-white/5 border border-white/10 text-[14px] text-[#E4E4E7] hover:bg-white/10 hover:text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F97316]">
                        <i class="fas fa-envelope text-[#F97316]" aria-hidden="true"></i>info@customernearme.com
                    </a>
                    <a href="https://wa.me/923460820722" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2.5 h-11 px-4 rounded-full bg-white/5 border border-white/10 text-[14px] text-[#E4E4E7] hover:bg-white/10 hover:text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F97316]">
                        <i class="fab fa-whatsapp text-[#F97316]" aria-hidden="true"></i>+92 346 0820722
                    </a>
                </div>
            </div>

            <!-- Social -->
            <div>
                <p class="text-[12px] font-semibold uppercase tracking-wider text-[#71717A] mb-3 lg:text-right">Follow us</p>
                <ul class="flex flex-wrap gap-2.5">
                    @foreach($ftSocial as [$sUrl, $sIcon, $sLabel])
                        <li>
                            <a href="{{ $sUrl }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $ftName }} on {{ $sLabel }}"
                               class="w-11 h-11 rounded-full bg-white/5 border border-white/10 text-[#D4D4D8] hover:bg-[#F97316] hover:border-[#F97316] hover:text-white flex items-center justify-center transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F97316]">
                                @if($sIcon === 'x-logo')
                                    {{-- X logo as SVG: the fa-x-twitter icon is not in Font Awesome 6.4.0 --}}
                                    <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                @else
                                    <i class="{{ $sIcon }} text-[15px]" aria-hidden="true"></i>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Big wordmark across the full width of the screen -->
    <div class="border-t border-white/10 pt-6 sm:pt-8 select-none" aria-hidden="true">
        <p id="ft-wordmark" class="text-center font-extrabold leading-[0.85] tracking-[-0.045em] whitespace-nowrap" style="font-size: 11vw;">
            <span class="text-[#F97316]">Customer</span><span class="text-white">NearMe</span>
        </p>
    </div>

    <!-- Legal bar -->
    <div class="hp-wrap py-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-[13px]">
            <p>&copy; {{ date('Y') }} {{ $ftName }}. All rights reserved.</p>
            <ul class="flex flex-wrap justify-center gap-x-5 gap-y-1">
                @foreach($ftLegal as [$lLabel, $lUrl])
                    <li><a href="{{ $lUrl }}" class="hover:text-white transition-colors focus:outline-none focus-visible:text-white focus-visible:underline">{{ $lLabel }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    <script>
        // Scale the wordmark so it spans ~96% of the screen width, whatever the font metrics
        (function () {
            const el = document.getElementById('ft-wordmark');
            if (!el) return;
            const fit = () => {
                el.style.fontSize = '100px';
                const inner = el.scrollWidth, room = el.parentElement.clientWidth * 0.96;
                if (inner > 0) el.style.fontSize = Math.floor(100 * room / inner) + 'px';
            };
            (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(fit);
            window.addEventListener('resize', fit, { passive: true });
        })();
    </script>
</footer>
