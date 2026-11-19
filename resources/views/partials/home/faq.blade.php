{{-- Homepage FAQ accordion. Expects $homeFaqs (list of [question, answerHtml]) from resources/data/home-faqs.php. --}}
<section id="faq" class="hp-sec bg-white" aria-labelledby="faq-heading">
    <div class="hp-wrap">
        <div class="max-w-[850px] mx-auto">
            <div class="text-center mb-8">
                <h2 id="faq-heading" class="hp-h2">Frequently Asked <span class="hp-mark">Questions</span></h2>
                <p class="hp-lead mt-4">Everything you need to know about {{ \App\Models\Setting::get('site_name', 'CustomerNearMe') }}.</p>
            </div>

            <div class="space-y-2.5" id="faq-accordion">
                @foreach($homeFaqs as $i => [$question, $answer])
                    <div class="hp-faq rounded-[14px] border border-[#E5E7EB] bg-white transition-colors">
                        <h3>
                            <button type="button" id="faq-q-{{ $i }}" aria-expanded="false" aria-controls="faq-a-{{ $i }}" onclick="hpToggleFaq(this)"
                                    class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left rounded-[14px] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F97316]">
                                <span class="text-[15px] font-semibold text-[#111827]">{!! $question !!}</span>
                                <span class="hp-faq-ic w-7 h-7 rounded-full bg-[#FFF7F2] text-[#F97316] flex items-center justify-center flex-shrink-0 transition-transform" aria-hidden="true"><i class="fas fa-plus text-[11px]"></i></span>
                            </button>
                        </h3>
                        <div id="faq-a-{{ $i }}" role="region" aria-labelledby="faq-q-{{ $i }}" hidden>
                            <div class="px-5 pb-4 -mt-1 text-[14px] leading-relaxed text-[#64748B]">{!! $answer !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-center text-[14px] text-[#64748B] mt-7">
                Still have a question?
                <a href="mailto:info@customernearme.com" class="font-semibold text-[#EA580C] hover:underline">Email us</a>
                or
                <a href="https://wa.me/923460820722" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#EA580C] hover:underline">chat on WhatsApp</a>.
            </p>
        </div>
    </div>
</section>
<style>
    .hp-faq.is-open { border-color: #FDBA74; box-shadow: 0 6px 18px -12px rgba(249, 115, 22, .45); }
    .hp-faq.is-open .hp-faq-ic { transform: rotate(45deg); background: #F97316; color: #fff; }
</style>
<script>
    function hpToggleFaq(btn) {
        const item = btn.closest('.hp-faq');
        const panel = document.getElementById(btn.getAttribute('aria-controls'));
        const open = btn.getAttribute('aria-expanded') !== 'true';
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.hidden = !open;
        item.classList.toggle('is-open', open);
    }
</script>
