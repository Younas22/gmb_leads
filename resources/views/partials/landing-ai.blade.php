{{-- Landing page: AI sales assistant (AI Sales Profile, website analysis, email drafts, Offer Builder, Gmail, campaigns) --}}
@php
    $aiSiteName = \App\Models\Setting::get('site_name', 'CustomerNearme');

    $aiSteps = [
        ['icon' => 'fa-magnifying-glass-chart', 'title' => 'AI reads their website', 'desc' => 'It checks each lead\'s site and lists what they do well and what they are missing.'],
        ['icon' => 'fa-pen-nib',                'title' => 'AI writes a personal email', 'desc' => 'Built from those findings and your own business profile. No templates, no "Dear Sir".'],
        ['icon' => 'fa-paper-plane',            'title' => 'You approve, it sends', 'desc' => 'One click sends it from your own Gmail. Replies come straight to your inbox.'],
    ];

    $aiFeatures = [
        ['icon' => 'fa-id-card',          'tag' => 'Set up once',  'title' => 'AI Sales Profile',
         'desc' => 'Tell the AI what you sell, who you sell to and why you are different. It uses this in every analysis, email and offer, so everything sounds like you.'],
        ['icon' => 'fa-globe',            'tag' => 'Per lead',     'title' => 'AI Website Analysis',
         'desc' => 'Know what a business needs before you write a word. Strengths, gaps and verified findings for every lead, in seconds.'],
        ['icon' => 'fa-envelope-open-text','tag' => 'Per lead',    'title' => 'AI Email Drafts',
         'desc' => 'A different email for every lead, based on their own website. Each draft shows exactly which findings it used, so nothing is made up.'],
        ['icon' => 'fa-gift',             'tag' => '16 strategies','title' => 'AI Offer Builder',
         'desc' => 'Build offers that are hard to say no to: attraction, upsell, downsell and continuity. Each one is written from your own business details.'],
        ['icon' => 'fa-chart-line',       'tag' => 'Gmail',        'title' => 'Send, Track & Follow Up',
         'desc' => 'Send from your own Gmail, see which links leads clicked, track replies in one dashboard and get reminded when it is time to follow up.'],
        ['icon' => 'fa-bullhorn',         'tag' => 'Campaigns',    'title' => 'Email Campaigns',
         'desc' => 'Send a campaign to a whole lead folder from your own domain. "Write with AI" drafts the subject and message for you.'],
    ];
@endphp

<section id="ai-tools" class="relative overflow-hidden bg-[#111111] py-20 sm:py-24" aria-labelledby="ai-tools-heading">
    <div class="hero-dots absolute top-10 right-6 w-40 h-40 opacity-30 pointer-events-none hidden lg:block" aria-hidden="true"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="text-center max-w-3xl mx-auto mb-14">
            <span class="inline-flex items-center gap-2 bg-primary-orange/15 text-orange-300 border border-primary-orange/30 px-3 py-1.5 rounded-full text-xs font-semibold mb-5">
                <span class="px-1.5 py-0.5 rounded bg-primary-orange text-white text-[10px] font-bold uppercase tracking-wider">New</span>
                AI Sales Assistant
            </span>
            <h2 id="ai-tools-heading" class="text-3xl sm:text-4xl md:text-[2.75rem] font-extrabold text-white leading-[1.15] tracking-tight mb-5">
                You Find the Lead.<br class="hidden sm:block">
                <span class="text-primary-orange">AI Writes the Email That Wins It.</span>
            </h2>
            <p class="text-base sm:text-lg text-gray-400 leading-relaxed">
                A list of leads does not pay the bills. Conversations do. {{ $aiSiteName }} now studies every business you find, writes a personal email in your voice and sends it from your own Gmail. You stay in control. The AI does the hours of research and writing.
            </p>
        </div>

        {{-- Workflow: steps + product mock --}}
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-14 items-center mb-20">

            {{-- Steps --}}
            <div class="order-2 lg:order-1">
                <p class="text-xs font-bold text-primary-orange uppercase tracking-widest mb-6">From lead to reply in 3 steps</p>
                <ol class="space-y-6">
                    @foreach($aiSteps as $i => $step)
                        <li class="flex items-start gap-4">
                            <span class="flex-shrink-0 w-11 h-11 rounded-xl bg-primary-orange/15 border border-primary-orange/30 flex items-center justify-center">
                                <i class="fas {{ $step['icon'] }} text-primary-orange" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h3 class="text-lg font-bold text-white mb-1">
                                    <span class="text-gray-500 font-semibold mr-1">{{ $i + 1 }}.</span>{{ $step['title'] }}
                                </h3>
                                <p class="text-sm text-gray-400 leading-relaxed">{{ $step['desc'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-8 grid grid-cols-3 border border-white/10 rounded-xl divide-x divide-white/10">
                    <div class="px-3 py-3 text-center">
                        <div class="text-lg sm:text-xl font-extrabold text-white leading-none">Minutes</div>
                        <div class="text-[11px] sm:text-xs text-gray-500 mt-1">not hours per lead</div>
                    </div>
                    <div class="px-3 py-3 text-center">
                        <div class="text-lg sm:text-xl font-extrabold text-white leading-none">1 to 1</div>
                        <div class="text-[11px] sm:text-xs text-gray-500 mt-1">a unique email each</div>
                    </div>
                    <div class="px-3 py-3 text-center">
                        <div class="text-lg sm:text-xl font-extrabold text-primary-orange leading-none">100%</div>
                        <div class="text-[11px] sm:text-xs text-gray-500 mt-1">approved by you</div>
                    </div>
                </div>
            </div>

            {{-- Product mock (illustrative) --}}
            <div class="order-1 lg:order-2 relative mx-auto w-full max-w-xl" role="img" aria-label="Illustration: AI analyses a dentist's website and writes a personal outreach email ready to send from Gmail">
                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                    {{-- lead header --}}
                    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-100 bg-gray-50">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-orange-50 border border-orange-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-tooth text-primary-orange text-xs" aria-hidden="true"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-[#111111] truncate">Bright Smile Dental</div>
                                <div class="text-[10px] text-gray-500 truncate">brightsmile-dental.com</div>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-green-700 bg-green-50 border border-green-200 rounded-full px-2 py-0.5 flex-shrink-0">
                            <i class="fas fa-circle-check" aria-hidden="true"></i> Website analysed
                        </span>
                    </div>

                    {{-- findings --}}
                    <div class="grid grid-cols-2 gap-px bg-gray-100">
                        <div class="bg-white p-3.5">
                            <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Strengths</div>
                            <ul class="space-y-1.5 text-[11px] text-gray-700">
                                <li class="flex gap-1.5"><i class="fas fa-plus text-green-600 text-[9px] mt-1" aria-hidden="true"></i>4.9 stars from 3,210 reviews</li>
                                <li class="flex gap-1.5"><i class="fas fa-plus text-green-600 text-[9px] mt-1" aria-hidden="true"></i>Clear list of services</li>
                            </ul>
                        </div>
                        <div class="bg-white p-3.5">
                            <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Likely needs</div>
                            <ul class="space-y-1.5 text-[11px] text-gray-700">
                                <li class="flex gap-1.5"><i class="fas fa-arrow-right text-primary-orange text-[9px] mt-1" aria-hidden="true"></i>No booking button on mobile</li>
                                <li class="flex gap-1.5"><i class="fas fa-arrow-right text-primary-orange text-[9px] mt-1" aria-hidden="true"></i>Reviews not shown on site</li>
                            </ul>
                        </div>
                    </div>

                    {{-- draft --}}
                    <div class="p-4 border-t border-gray-100">
                        <div class="flex items-center gap-1.5 text-[10px] font-bold text-primary-orange uppercase tracking-wider mb-2">
                            <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i> AI draft
                        </div>
                        <div class="text-[11px] text-gray-500 mb-1"><span class="font-semibold text-gray-700">Subject:</span> 3,210 happy patients, and one missing button</div>
                        <p class="text-[11px] text-gray-700 leading-relaxed">
                            Hi there, 4.9 stars from 3,210 reviews is rare. Patients clearly love your team. On my phone, though, I could not find a way to book. We add a one-tap booking button and show your best reviews on the homepage...
                        </p>
                        <div class="mt-3 flex items-center justify-between gap-2">
                            <span class="text-[10px] text-gray-400 truncate"><i class="fas fa-link mr-1" aria-hidden="true"></i>Based on 3 verified findings</span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-white bg-primary-orange rounded-md px-3 py-1.5 flex-shrink-0">
                                <i class="fab fa-google text-[10px]" aria-hidden="true"></i> Approve &amp; send
                            </span>
                        </div>
                    </div>
                </div>

                {{-- floating chips --}}
                <div class="absolute -top-4 -left-2 sm:-left-5 hidden sm:flex items-center gap-2 bg-white border border-gray-200 rounded-lg shadow-lg px-3 py-2">
                    <span class="w-6 h-6 rounded-md bg-primary-orange flex items-center justify-center">
                        <i class="fas fa-wand-magic-sparkles text-white text-[10px]" aria-hidden="true"></i>
                    </span>
                    <span class="text-xs font-semibold text-[#111111]">Written in your voice</span>
                </div>
                <div class="absolute -bottom-5 -right-2 sm:-right-5 hidden sm:flex items-center gap-2 bg-white border border-gray-200 rounded-lg shadow-lg px-3 py-2">
                    <span class="w-6 h-6 rounded-md bg-green-500 flex items-center justify-center">
                        <i class="fas fa-reply text-white text-[10px]" aria-hidden="true"></i>
                    </span>
                    <span class="text-xs font-semibold text-[#111111]">Reply received</span>
                </div>
            </div>
        </div>

        {{-- Feature grid --}}
        <div class="text-center mb-10">
            <h3 class="text-2xl md:text-3xl font-extrabold text-white mb-3">Your Whole Sales Process, in One Place</h3>
            <p class="text-base text-gray-400 max-w-2xl mx-auto">No more jumping between a scraper, ChatGPT, a spreadsheet and your inbox.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($aiFeatures as $feature)
                <div class="group bg-white/[0.04] border border-white/10 rounded-2xl p-6 hover:border-primary-orange/50 hover:bg-white/[0.06] transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <span class="w-11 h-11 rounded-xl bg-primary-orange/15 border border-primary-orange/30 flex items-center justify-center group-hover:bg-primary-orange transition-colors">
                            <i class="fas {{ $feature['icon'] }} text-primary-orange group-hover:text-white transition-colors" aria-hidden="true"></i>
                        </span>
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 border border-white/10 rounded-full px-2.5 py-1">{{ $feature['tag'] }}</span>
                    </div>
                    <h4 class="text-lg font-bold text-white mb-2">{{ $feature['title'] }}</h4>
                    <p class="text-sm text-gray-400 leading-relaxed">{{ $feature['desc'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- CTA --}}
        <div class="mt-14 flex flex-col sm:flex-row items-center justify-center gap-4 text-center">
            <a href="{{ route('auth.show') }}"
               class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-7 py-3.5 text-base font-semibold text-white bg-primary-orange hover:bg-dark-orange rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-orange focus-visible:ring-offset-2 focus-visible:ring-offset-[#111111]">
                Start Using AI Outreach
                <i class="fas fa-arrow-right text-sm" aria-hidden="true"></i>
            </a>
            <p class="text-sm text-gray-400 inline-flex items-center gap-1.5">
                <i class="fas fa-shield-halved text-primary-orange" aria-hidden="true"></i>
                Nothing is sent until you approve it.
            </p>
        </div>
    </div>
</section>
