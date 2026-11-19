{{-- Homepage: trust numbers, whole sales process, 3 steps, reviews. Expects $homeReviews (array, may be empty). --}}

<!-- Trust statistics (same figures the homepage already showed) -->
<section class="bg-white border-b border-gray-100" aria-label="CustomerNearMe in numbers">
    <div class="hp-wrap py-6">
        <dl class="grid grid-cols-1 sm:grid-cols-3 max-w-4xl mx-auto divide-y sm:divide-y-0 sm:divide-x divide-gray-200">
            @foreach([['fa-users', '1,000+', 'Users'], ['fa-chart-column', '50,000+', 'Leads Generated'], ['fa-gift', '3-Day', 'Free Trial']] as [$sIcon, $sValue, $sLabel])
                <div class="flex items-center justify-center gap-3.5 py-3 sm:py-1">
                    <span class="w-11 h-11 rounded-full bg-[#FFF7F2] text-[#F97316] flex items-center justify-center flex-shrink-0"><i class="fas {{ $sIcon }}" aria-hidden="true"></i></span>
                    <div>
                        <dd class="text-[26px] font-extrabold text-[#111111] leading-none tracking-tight">{{ $sValue }}</dd>
                        <dt class="text-[13px] text-gray-500 mt-1">{{ $sLabel }}</dt>
                    </div>
                </div>
            @endforeach
        </dl>
    </div>
</section>

<!-- Your whole sales process -->
<section id="features" class="hp-sec bg-white" aria-labelledby="features-heading">
    <div class="hp-wrap">
        <div class="text-center max-w-2xl mx-auto mb-9">
            <h2 id="features-heading" class="hp-h2">Your Whole Sales Process, in <span class="hp-mark">One Place</span></h2>
            <p class="hp-lead mt-4">No more jumping between a scraper, ChatGPT, a spreadsheet and your inbox.</p>
        </div>
        <div class="grid grid-cols-1 min-[480px]:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @foreach([
                ['fa-magnifying-glass', 'Find Leads', 'Get verified business leads from Google Maps.'],
                ['fa-wand-magic-sparkles', 'AI Analysis', 'Know what each business needs in seconds.', 'ai'],
                ['fa-envelope', 'AI Email Drafts', 'Personal, unique emails for every lead.', 'ai'],
                ['fa-chart-line', 'Send & Track', 'Send from Gmail, track clicks and replies.', 'email'],
                ['fa-bolt', 'Smart Follow-ups', 'Get reminders and close more clients.', 'email'],
            ] as $fRow)
                @php [$fIcon, $fTitle, $fText] = $fRow; $fSoon = (($fRow[3] ?? null) === 'ai' && !empty($aiSoon)) || (($fRow[3] ?? null) === 'email' && !empty($emailSoon)); @endphp
                <div class="hp-card p-5 text-center relative">
                    @if($fSoon)<span class="absolute top-3 right-3 px-2 py-0.5 rounded-full bg-[#111827] text-white text-[10px] font-semibold uppercase tracking-wide">Coming soon</span>@endif
                    <span class="hp-icon mx-auto mb-3.5"><i class="fas {{ $fIcon }}" aria-hidden="true"></i></span>
                    <h3 class="text-[16px] font-semibold text-[#111111]">{{ $fTitle }}</h3>
                    <p class="text-[13.5px] text-gray-500 mt-1.5 leading-relaxed">{{ $fText }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- From lead to reply in 3 steps (this is the AI workflow) -->
<section id="ai-tools" class="hp-sec bg-white !pt-0" aria-labelledby="steps-heading">
    <span id="how-it-works" class="block relative -top-4" aria-hidden="true"></span>
    <div class="hp-wrap">
        <div class="text-center max-w-2xl mx-auto mb-9">
            @if(!empty($aiSoon))
                <span class="inline-flex items-center gap-1.5 h-7 px-3 rounded-full bg-[#111827] text-white text-[12px] font-semibold mb-3"><i class="far fa-clock text-[11px] text-[#FDBA74]" aria-hidden="true"></i> Coming soon</span>
            @endif
            <h2 id="steps-heading" class="hp-h2">From Lead to Reply in <span class="hp-mark">3 Simple Steps</span></h2>
            @if(!empty($aiSoon))
                <p class="hp-lead mt-4">Our AI outreach assistant is almost ready. Here is how it will work.</p>
            @endif
        </div>
        <ol class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
            @foreach([
                ['fa-window-maximize', 'AI reads their website', 'It checks what they do well and what they are missing.'],
                ['fa-pen-to-square', 'AI writes a personal email', 'Built from those findings and your business profile.'],
                ['fa-paper-plane', 'You approve, it sends', 'One click sends it from your own Gmail. Replies come to you.'],
            ] as $i => [$stIcon, $stTitle, $stText])
                <li class="relative hp-card p-5 flex items-start gap-4">
                    <span class="w-10 h-10 rounded-full bg-[#F97316] text-white text-[16px] font-bold flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                    <div class="min-w-0">
                        <h3 class="text-[16px] font-semibold text-[#111111] flex items-center gap-2"><i class="fas {{ $stIcon }} text-[#F97316] text-[14px]" aria-hidden="true"></i>{{ $stTitle }}</h3>
                        <p class="text-[13.5px] text-gray-500 mt-1 leading-relaxed">{{ $stText }}</p>
                    </div>
                    @if($i < 2)
                        <span class="hp-step-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>

<!-- Reviews: real reviews from $homeReviews; on local/staging only, labelled sample reviews preview the layout ($reviewsAreSamples) -->
@if(!empty($homeReviews))
@php
    $rvSamples = !empty($reviewsAreSamples);
    $rvTotal = count($homeReviews);
    $rvStep = 6;
@endphp
<section id="reviews" class="hp-sec bg-[#FFF7F2]" aria-labelledby="reviews-heading" @if($rvSamples) data-sample-reviews="1" @endif>
    <div class="hp-wrap">
        <div class="text-center max-w-2xl mx-auto mb-8">
            @if($rvSamples)
                <!-- <span class="inline-flex items-center gap-1.5 h-7 px-3 rounded-full bg-white border border-[#FED7AA] text-[12px] font-semibold text-[#C2410C] mb-3">
                    <i class="fas fa-circle-info text-[10px]" aria-hidden="true"></i> Example reviews · not real customers
                </span> -->
                <h2 id="reviews-heading" class="hp-h2"><span class="hp-mark">Customer</span> Feedback</h2>
                <p class="hp-lead mt-4">How people can use {{ \App\Models\Setting::get('site_name', 'CustomerNearMe') }}.</p>
            @else
                <h2 id="reviews-heading" class="hp-h2">What <span class="hp-mark">Our Users</span> Say</h2>
                <p class="hp-lead mt-4">Real experiences and feedback from people using {{ \App\Models\Setting::get('site_name', 'CustomerNearMe') }}.</p>
            @endif
        </div>

        <div id="hp-review-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-5">
            @foreach($homeReviews as $ri => $review)
                @php
                    $rvStars = max(1, min(5, (int) ($review['stars'] ?? 5)));
                    $rvInitials = collect(preg_split('/\s+/', trim($review['name'])))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                @endphp
                <figure class="hp-review hp-card p-5 flex flex-col {{ $ri >= $rvStep ? 'hidden' : '' }}" data-review-index="{{ $ri }}">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex gap-0.5 text-[#F97316] text-[13px]" role="img" aria-label="{{ $rvStars }} out of 5 stars">
                            @for($s = 1; $s <= 5; $s++)<i class="{{ $s <= $rvStars ? 'fas' : 'far' }} fa-star" aria-hidden="true"></i>@endfor
                        </div>
                        @if($rvSamples)
                            <span class="text-[10.5px] font-semibold uppercase tracking-wide text-[#94A3B8]"></span>
                        @elseif(!empty($review['verified']))
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#16A34A]"><i class="fas fa-circle-check" aria-hidden="true"></i>Verified</span>
                        @endif
                    </div>
                    <blockquote class="text-[14px] text-[#111827] leading-relaxed mt-3 flex-1">“{{ $review['text'] }}”</blockquote>
                    <figcaption class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-100">
                        @if(!empty($review['avatar']) && !$rvSamples)
                            <img src="{{ asset($review['avatar']) }}" alt="" width="40" height="40" loading="lazy" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <span class="w-10 h-10 rounded-full bg-[#F97316] text-white font-semibold text-[13px] flex items-center justify-center flex-shrink-0" aria-hidden="true">{{ $rvInitials }}</span>
                        @endif
                        <span class="min-w-0">
                            <span class="block text-[14px] font-semibold text-[#111827]">{{ $review['name'] }}</span>
                            @if(!empty($review['role']))<span class="block text-[12.5px] text-[#64748B]">{{ $review['role'] }}</span>@endif
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>

        @if($rvTotal > $rvStep)
            <div class="flex flex-col items-center gap-2 mt-7">
                <button type="button" id="hp-reviews-more" class="hp-btn-outline" aria-controls="hp-review-grid" onclick="hpMoreReviews()">
                    <i class="fas fa-plus text-xs" aria-hidden="true"></i> Load More Reviews
                </button>
                <p id="hp-reviews-count" class="text-[13px] text-[#64748B]" aria-live="polite">Showing {{ $rvStep }} of {{ $rvTotal }}</p>
            </div>
            <script>
                function hpMoreReviews() {
                    const hidden = [...document.querySelectorAll('#hp-review-grid .hp-review.hidden')];
                    const next = hidden.slice(0, {{ $rvStep }});
                    next.forEach(card => card.classList.remove('hidden'));
                    const shown = document.querySelectorAll('#hp-review-grid .hp-review:not(.hidden)').length;
                    document.getElementById('hp-reviews-count').textContent = 'Showing ' + shown + ' of {{ $rvTotal }}';
                    if (shown >= {{ $rvTotal }}) {
                        const btn = document.getElementById('hp-reviews-more');
                        btn.remove();
                        if (next[0]) { next[0].setAttribute('tabindex', '-1'); next[0].focus(); }
                    } else if (next[0]) {
                        next[0].setAttribute('tabindex', '-1');
                        next[0].focus({ preventScroll: true });
                    }
                }
            </script>
        @endif
    </div>
</section>
@endif
