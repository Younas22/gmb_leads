@extends('layouts.app')

@section('title', 'AI Sales Profile')

@php
    $listValue = function ($field, $current) {
        $value = old($field, $current ?? []);
        return is_array($value) ? implode("\n", $value) : (string) $value;
    };
    $profileErrors = $errors->getBag('profile');
    $offerErrors = $errors->getBag('offer');
    $openFormKey = old('form_key');
    $skillMin = \App\Support\SkillCatalog::min();
    $skillMax = \App\Support\SkillCatalog::max();
    $serviceLim = \App\Models\UserOffer::limits('service');
    $typeMeta = [
        'skill'   => ['plural' => 'Skills',   'icon' => 'fa-screwdriver-wrench', 'hint' => 'What you are good at. Pick a main skill, then a sub skill: at least ' . $skillMin . ', up to ' . $skillMax . '.'],
        'service' => ['plural' => 'Services', 'icon' => 'fa-briefcase',          'hint' => 'What clients can buy from you, e.g. "Website redesign". At least ' . $serviceLim['min'] . ', up to ' . $serviceLim['max'] . '.'],
        'offer'   => ['plural' => 'Offers',   'icon' => 'fa-tag',                'hint' => 'Deals that give a lead a reason to buy now. Active offers are the ones AI uses in your messages.'],
    ];
    $usageLabels = ['website_audit' => 'AI website audits', 'email_draft' => 'AI email drafts', 'offer_builder' => 'AI offers (Offer Builder)'];

    // Tab to open first: the one holding an open add/edit form or the first profile error (a #hash in the URL wins in JS)
    $profileTabs = ['sec-business', 'sec-audience', 'sec-outreach'];
    $activeTab = 'sec-business';
    if ($openFormKey && str_starts_with($openFormKey, 'add-')) {
        $activeTab = substr($openFormKey, 4) . 's';
    } elseif ($openFormKey && str_starts_with($openFormKey, 'edit-')) {
        $editId = (int) substr($openFormKey, 5);
        foreach ($offers as $t => $list) {
            if ($list->contains('id', $editId)) { $activeTab = $t . 's'; }
        }
    } elseif ($profileErrors->any()) {
        $errKeys = implode(' ', $profileErrors->keys());
        if (preg_match('/\b(language|tone|sender_name|sender_postal_address|signature|ai_instructions)\b/', $errKeys)) $activeTab = 'sec-outreach';
        if (preg_match('/\btarget_(industries|locations)/', $errKeys)) $activeTab = 'sec-audience';
        if (preg_match('/\b(company_name|website|description|portfolio_urls)/', $errKeys)) $activeTab = 'sec-business';
    }
    $profileTabActive = in_array($activeTab, $profileTabs, true);
@endphp

@section('content')
{{-- AI Sales Profile look: Poppins + custom controls (this page only) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body.bg-gray-50 { background-color: #f8fafc; }
    .cnm-asp, .cnm-asp button, .cnm-asp input, .cnm-asp select, .cnm-asp textarea,
    #sidebar, #main-content > header { font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; }

    /* Cards and section headings */
    .asp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; }
    .asp-head { display: flex; align-items: flex-start; gap: 10px; padding: 14px 16px 0; }
    @media (min-width: 640px) { .asp-head { padding: 14px 18px 0; } }
    .asp-head-icon { width: 32px; height: 32px; border-radius: 9px; font-size: 13px; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .asp-body { padding: 14px 16px 16px; }
    @media (min-width: 640px) { .asp-body { padding: 14px 18px 18px; } }
    .asp-label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 600; color: #111827; }
    .asp-label .opt { font-weight: 400; color: #64748b; }
    .asp-help { margin-top: 6px; font-size: 12px; color: #64748b; }

    /* Fields */
    .asp-field { width: 100%; height: 38px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 9px; background-color: #fff; color: #111827; font-size: 13px; transition: border-color .15s, box-shadow .15s, background-color .15s; }
    .asp-field::placeholder { color: #94a3b8; }
    .asp-field:hover { border-color: #b6c2d2; }
    .asp-field:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, .15); }
    .asp-field:disabled { background-color: #f8fafc; color: #64748b; cursor: not-allowed; }
    .asp-field.is-sm { height: 34px; font-size: 12.5px; padding: 0 10px; }
    .asp-field.is-invalid { border-color: #dc2626; }
    textarea.asp-field { height: auto; min-height: 76px; padding: 8px 12px; line-height: 1.5; resize: vertical; }
    textarea.asp-field.is-sm { min-height: 64px; padding: 7px 10px; }
    textarea.asp-field.is-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12.5px; }
    select.asp-field { -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 32px; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center; background-size: 13px; }
    .asp-field.has-icon { padding-left: 32px; }
    .asp-check { width: 16px; height: 16px; accent-color: #f97316; flex-shrink: 0; cursor: pointer; }

    /* Buttons */
    .asp-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; padding: 0 16px; border-radius: 9px; background: #f97316; color: #fff; font-size: 13px; font-weight: 600; transition: background-color .15s; }
    .asp-btn:hover { background: #ea580c; }
    .asp-btn:focus-visible, .asp-act:focus-visible, .asp-sec:focus-visible { outline: 2px solid #f97316; outline-offset: 2px; }
    .asp-sec { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 34px; padding: 0 12px; border-radius: 9px; border: 1px solid #e2e8f0; background: #fff; color: #334155; font-size: 12.5px; font-weight: 500; transition: all .15s; }
    .asp-sec:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; color: #111827; }
    .asp-sec:disabled { opacity: .45; cursor: not-allowed; }
    .asp-act { display: inline-flex; align-items: center; justify-content: center; gap: 5px; height: 30px; min-width: 30px; padding: 0 10px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #334155; font-size: 12px; font-weight: 500; transition: all .15s; }
    .asp-act:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; }
    .asp-act.is-orange { border-color: #fdba74; background: #fff7ed; color: #c2410c; }
    .asp-act.is-orange:hover:not(:disabled) { background: #ffedd5; }
    .asp-act.is-danger { color: #dc2626; padding: 0; }
    .asp-act.is-danger:hover:not(:disabled) { background: #fef2f2; border-color: #fecaca; }
    .asp-act:disabled { opacity: .4; cursor: not-allowed; }

    /* Badges and pickers */
    .asp-badge { display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 8px; border: 1px solid; font-size: 11px; font-weight: 600; }
    .asp-badge.is-on { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
    .asp-badge.is-off { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
    .asp-list { max-height: 12rem; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }
    .asp-list .cnm-opt { display: flex; align-items: center; gap: 8px; padding: 6px 10px; font-size: 12.5px; color: #1f2937; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
    .asp-list .cnm-opt:last-child { border-bottom: 0; }
    .asp-list .cnm-opt:hover { background: #fff7ed; }
    .asp-list .cnm-opt.hidden { display: none; }
    .asp-chips { display: flex; flex-wrap: wrap; gap: 5px; min-height: 40px; padding: 6px; border: 1px dashed #dce3ed; border-radius: 12px; background: #f8fafc; }
    .asp-chips.is-invalid { border-color: #dc2626; border-style: solid; }

    /* Section navigation */
    .asp-nav a { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; border-radius: 8px; font-size: 12.5px; font-weight: 500; color: #475569; white-space: nowrap; border: 1px solid transparent; transition: all .15s; }
    .asp-nav a:hover { color: #111827; background: #fff; border-color: #e2e8f0; }
    .asp-nav a[aria-selected="true"] { color: #c2410c; background: #fff; border-color: #fdba74; font-weight: 600; }
    .asp-nav a:focus-visible { outline: 2px solid #f97316; outline-offset: 2px; }

    .ob-cat { height: 30px; padding: 0 11px; border-radius: 8px; font-size: 12px; font-weight: 600; color: #475569; white-space: nowrap; transition: all .15s; }
    .ob-cat:hover { color: #111827; }
    .ob-cat.is-on { background: #fff; color: #c2410c; box-shadow: 0 1px 2px rgba(15, 23, 42, .1); }
    .ob-cat:focus-visible { outline: 2px solid #f97316; outline-offset: 1px; }

    @media (prefers-reduced-motion: reduce) { .cnm-asp * { transition: none !important; scroll-behavior: auto !important; } }
    html { scroll-behavior: smooth; }
</style>

<div class="p-4 lg:px-6 lg:py-5 cnm-asp">
<div class="max-w-[1040px] mx-auto">

    {{-- Header: title, explanation, completeness --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3 mb-3.5">
        <div class="min-w-0">
            <h1 class="text-[19px] font-bold text-gray-900 tracking-tight">My AI Sales Profile</h1>
            <p class="text-[12.5px] text-slate-500 mt-0.5 max-w-2xl leading-relaxed">
                Tell the AI who you are, what you sell and who you want to reach. It will use this to match your
                services to leads and write personalised outreach in your voice.
            </p>
        </div>
        <div class="w-full lg:w-72 flex-shrink-0 bg-white border border-slate-200 rounded-xl px-3.5 py-2.5">
            <div class="flex items-center justify-between gap-3 text-sm">
                <span class="font-medium text-slate-600">Profile completeness</span>
                <span class="font-bold text-gray-900" data-testid="completeness">{{ $completeness }}%</span>
            </div>
            <div class="mt-1.5 h-1.5 bg-slate-100 rounded-full overflow-hidden" role="progressbar" aria-label="Profile completeness" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) $completeness }}">
                <div class="h-full bg-orange-500 rounded-full" style="width: {{ (int) $completeness }}%"></div>
            </div>
            <div class="mt-2">
                @if($aiAvailable)
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-lg bg-green-50 text-green-700 border border-green-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> AI ready
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 border border-slate-200" title="AI features will turn on once the administrator configures them.">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> AI not configured yet
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if(!$canEdit)
        <div class="mb-5 bg-orange-50 border border-orange-200 rounded-xl px-4 py-3 text-sm text-gray-800 flex items-start gap-3">
            <i class="fas fa-lock text-orange-500 mt-0.5" aria-hidden="true"></i>
            <span>
                @if($isTeamMember)
                    This is your company's AI Sales Profile. Only the company account owner can change it.
                @else
                    You can view this profile but not change it from this account.
                @endif
            </span>
        </div>
    @endif

    {{-- Tabs: one section at a time. Panels are only hidden, never removed, so unsaved values stay. --}}
    @php
        $tabs = [
            'sec-business' => ['fa-building', 'Business Details'],
            'sec-audience' => ['fa-bullseye', 'Target Audience'],
            'sec-outreach' => ['fa-paper-plane', 'Outreach Preferences'],
        ];
        foreach ($offerTypes as $type => $label) { $tabs[$type . 's'] = [$typeMeta[$type]['icon'], $typeMeta[$type]['plural']]; }
    @endphp
    <nav class="asp-nav sticky top-0 z-20 -mx-4 lg:-mx-8 pl-16 pr-4 lg:px-8 py-2 mb-3.5 bg-slate-50 border-b border-slate-200" aria-label="Profile sections">
        <div class="flex items-center gap-1 overflow-x-auto max-w-[1040px] mx-auto" role="tablist">
            @foreach($tabs as $tabId => [$tabIcon, $tabLabel])
                <a href="#{{ $tabId }}" role="tab" id="tab-{{ $tabId }}" aria-controls="{{ $tabId }}"
                   aria-selected="{{ $activeTab === $tabId ? 'true' : 'false' }}" tabindex="{{ $activeTab === $tabId ? '0' : '-1' }}"><i class="fas {{ $tabIcon }} text-xs" aria-hidden="true"></i>{{ $tabLabel }}</a>
            @endforeach
        </div>
    </nav>

    <div class="space-y-3.5">

        {{-- How the AI uses this + usage (compact, can be folded away) --}}
        <details class="asp-card group" open>
            <summary class="list-none cursor-pointer flex flex-wrap items-center justify-between gap-3 px-4 sm:px-5 py-2.5">
                <span class="flex items-center gap-2.5 text-sm font-semibold text-gray-900">
                    <i class="fas fa-wand-magic-sparkles text-orange-500" aria-hidden="true"></i> How the AI uses this
                </span>
                <span class="flex items-center gap-2 text-xs text-slate-500">
                    AI usage this month
                    <i class="fas fa-chevron-down text-[10px] transition-transform group-open:rotate-180" aria-hidden="true"></i>
                </span>
            </summary>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 sm:px-5 pb-3.5 pt-0 border-t border-slate-100 text-[12.5px]">
                <ul class="space-y-1.5 text-[12.5px] text-slate-600 pt-3">
                    <li class="flex gap-2.5"><i class="fas fa-check text-orange-500 mt-1 text-xs" aria-hidden="true"></i><span>Matches your <strong class="text-gray-900">active</strong> services and offers to each lead.</span></li>
                    <li class="flex gap-2.5"><i class="fas fa-check text-orange-500 mt-1 text-xs" aria-hidden="true"></i><span>Writes in your chosen language and tone, signed with your name and signature.</span></li>
                    <li class="flex gap-2.5"><i class="fas fa-check text-orange-500 mt-1 text-xs" aria-hidden="true"></i><span>Disabled items are kept but never suggested.</span></li>
                    <li class="flex gap-2.5"><i class="fas fa-shield-halved text-orange-500 mt-1 text-xs" aria-hidden="true"></i><span>Every AI email is a draft — nothing is sent without your approval.</span></li>
                </ul>
                <div class="pt-3 md:pl-4 md:border-l border-slate-100">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">AI usage this month</h3>
                    <div class="space-y-1.5">
                        @foreach($aiUsage as $feature => $usage)
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-slate-700">{{ $usageLabels[$feature] ?? $feature }}</span>
                                <span class="font-semibold text-gray-900 whitespace-nowrap">
                                    @if($usage['limit'] === -1)
                                        {{ $usage['used'] }} <span class="text-slate-500 font-normal">/ unlimited</span>
                                    @elseif($usage['limit'] === 0)
                                        <span class="text-slate-500 font-normal">Not in your plan</span>
                                    @else
                                        {{ $usage['used'] }} <span class="text-slate-500 font-normal">/ {{ $usage['limit'] }}</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-slate-500 mt-3">Website audits and email drafts are coming soon.</p>
                </div>
            </div>
        </details>

        {{-- Profile form: Business Details, Target Audience, Outreach Preferences (one save, as before) --}}
        <form method="POST" action="{{ route('user.ai-profile.update') }}" id="asp-profile-form" class="space-y-3.5 {{ $profileTabActive ? '' : 'hidden' }}" novalidate>
            @csrf
            @method('PUT')

            @if($profileErrors->any())
                <div class="bg-red-50 border border-red-200 rounded-2xl px-5 py-4" role="alert">
                    <p class="text-sm font-semibold text-red-800 mb-1">Please fix the highlighted fields.</p>
                    <ul class="text-sm text-red-700 list-disc pl-5 space-y-0.5">
                        @foreach($profileErrors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <fieldset @disabled(!$canEdit) class="min-w-0 space-y-3.5 disabled:opacity-90">

                {{-- Business Details --}}
                <section id="sec-business" role="tabpanel" class="asp-tab asp-card {{ $activeTab === 'sec-business' ? '' : 'hidden' }}" aria-labelledby="sec-business-title">
                    <div class="asp-head">
                        <span class="asp-head-icon" aria-hidden="true"><i class="fas fa-building"></i></span>
                        <h2 id="sec-business-title" class="text-[14.5px] font-semibold text-gray-900 pt-1">Business Details</h2>
                    </div>
                    <div class="asp-body grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="company_name" class="asp-label">Business or brand name</label>
                            <input id="company_name" name="company_name" type="text" maxlength="150"
                                   value="{{ old('company_name', $profile->company_name) }}"
                                   placeholder="e.g. Pixel Growth Studio"
                                   @if($profileErrors->has('company_name')) aria-invalid="true" @endif
                                   class="asp-field {{ $profileErrors->has('company_name') ? 'is-invalid' : '' }}">
                        </div>
                        <div>
                            <label for="website" class="asp-label">Website</label>
                            <input id="website" name="website" type="url" maxlength="500"
                                   value="{{ old('website', $profile->website) }}"
                                   placeholder="https://yourbusiness.com"
                                   @if($profileErrors->has('website')) aria-invalid="true" aria-describedby="website-error" @endif
                                   class="asp-field {{ $profileErrors->has('website') ? 'is-invalid' : '' }}">
                            @if($profileErrors->has('website'))<p id="website-error" class="text-xs text-red-600 mt-1.5">{{ $profileErrors->first('website') }}</p>@endif
                        </div>
                        <div class="sm:col-span-2">
                            <label for="description" class="asp-label">What does your business do?</label>
                            <textarea id="description" name="description" rows="4" maxlength="2000"
                                      placeholder="Two or three sentences about who you help and the results you get them."
                                      @if($profileErrors->has('description')) aria-invalid="true" @endif
                                      class="asp-field {{ $profileErrors->has('description') ? 'is-invalid' : '' }}">{{ old('description', $profile->description) }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="portfolio_urls" class="asp-label">Portfolio links <span class="opt">(one per line, up to 10)</span></label>
                            <textarea id="portfolio_urls" name="portfolio_urls" rows="3"
                                      placeholder="https://yourbusiness.com/case-study&#10;https://dribbble.com/you"
                                      @if($profileErrors->has('portfolio_urls') || $profileErrors->has('portfolio_urls.*')) aria-invalid="true" @endif
                                      class="asp-field is-mono {{ $profileErrors->has('portfolio_urls') || $profileErrors->has('portfolio_urls.*') ? 'is-invalid' : '' }}">{{ $listValue('portfolio_urls', $profile->portfolio_urls) }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Target Audience --}}
                <section id="sec-audience" role="tabpanel" class="asp-tab asp-card {{ $activeTab === 'sec-audience' ? '' : 'hidden' }}" aria-labelledby="sec-audience-title">
                    <div class="asp-head">
                        <span class="asp-head-icon" aria-hidden="true"><i class="fas fa-bullseye"></i></span>
                        <h2 id="sec-audience-title" class="text-[14.5px] font-semibold text-gray-900 pt-1">Target Audience</h2>
                    </div>
                    <div class="asp-body grid grid-cols-1 lg:grid-cols-2 gap-3">
                        {{-- Target industries: pick from the Find Leads niches (multi-select) → added to the box below --}}
                        <div data-testid="industry-picker" class="min-w-0 rounded-xl border border-slate-200 p-3">
                            <label for="target_industries" class="asp-label">Target industries</label>
                            <div class="relative">
                                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none" aria-hidden="true"></i>
                                <input type="search" id="niche-filter" placeholder="Search niches (e.g. dentist)…" autocomplete="off" aria-label="Search niches"
                                       oninput="cnmFilterList('niche-list', this.value)"
                                       class="asp-field is-sm has-icon">
                            </div>
                            <div id="niche-list" class="asp-list mt-2">
                                @foreach($nicheOptions as $n)
                                    <label class="cnm-opt" data-name="{{ mb_strtolower($n) }}">
                                        <input type="checkbox" class="cnm-pick asp-check" data-target="target_industries" value="{{ str_replace(',', ' -', $n) }}" onchange="cnmPick(this)"> {{ $n }}
                                    </label>
                                @endforeach
                            </div>
                            <div class="flex items-center justify-between mt-3 mb-1.5">
                                <span class="text-xs font-semibold text-slate-600">Selected</span>
                                <span class="text-xs text-slate-500"><span id="target_industries-count">0</span> / 20</span>
                            </div>
                            <div id="target_industries-chips" class="asp-chips {{ $profileErrors->has('target_industries') || $profileErrors->has('target_industries.*') ? 'is-invalid' : '' }}" data-testid="target_industries-chips"></div>
                            <div class="flex gap-2 mt-2">
                                <input type="text" id="target_industries-new" maxlength="150" placeholder="Add your own industry…" autocomplete="off" aria-label="Add your own industry"
                                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); cnmAddOwn('target_industries'); }"
                                       class="asp-field is-sm flex-1 min-w-0">
                                <button type="button" onclick="cnmAddOwn('target_industries')" class="asp-sec !h-[34px] !px-3"><i class="fas fa-plus text-xs" aria-hidden="true"></i>Add</button>
                            </div>
                            <textarea id="target_industries" name="target_industries" rows="5" hidden oninput="cnmSync('target_industries')"
                                      placeholder="Tick niches above, or type one per line"
                                      class="asp-field {{ $profileErrors->has('target_industries') || $profileErrors->has('target_industries.*') ? 'is-invalid' : '' }}">{{ $listValue('target_industries', $profile->target_industries) }}</textarea>
                            <p id="target_industries-msg" class="text-xs text-gray-500 mt-1">Up to 20. Click × to remove one.</p>
                        </div>

                        {{-- Target locations: Country → State → Cities (same data as Find Leads) → added to the box below --}}
                        <div data-testid="location-picker" class="min-w-0 rounded-xl border border-slate-200 p-3">
                            <label for="target_locations" class="asp-label">Target locations</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <select id="loc-country" onchange="cnmLoadStates(this.value)" aria-label="Country" class="asp-field is-sm">
                                    <option value="">Country…</option>
                                    @foreach($countryOptions as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                                </select>
                                <select id="loc-state" onchange="cnmLoadCities(this.value)" disabled aria-label="State" class="asp-field is-sm">
                                    <option value="">State / region…</option>
                                </select>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <button type="button" id="add-country" disabled onclick="cnmAddPlace('country')" class="asp-sec"><i class="fas fa-plus text-xs" aria-hidden="true"></i> Whole country</button>
                                <button type="button" id="add-state" disabled onclick="cnmAddPlace('state')" class="asp-sec"><i class="fas fa-plus text-xs" aria-hidden="true"></i> Whole state</button>
                            </div>
                            <div id="city-box" class="hidden mt-3">
                                <div class="relative">
                                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none" aria-hidden="true"></i>
                                    <input type="search" id="city-filter" placeholder="Search cities…" autocomplete="off" aria-label="Search cities" oninput="cnmFilterList('city-list', this.value)"
                                           class="asp-field is-sm has-icon">
                                </div>
                                <div id="city-list" class="asp-list mt-2 !max-h-52"></div>
                            </div>
                            <div class="flex items-center justify-between mt-3 mb-1.5">
                                <span class="text-xs font-semibold text-slate-600">Selected</span>
                                <span class="text-xs text-slate-500"><span id="target_locations-count">0</span> / 20</span>
                            </div>
                            <div id="target_locations-chips" class="asp-chips {{ $profileErrors->has('target_locations') || $profileErrors->has('target_locations.*') ? 'is-invalid' : '' }}" data-testid="target_locations-chips"></div>
                            <div class="flex gap-2 mt-2">
                                <input type="text" id="target_locations-new" maxlength="150" placeholder="Add your own location…" autocomplete="off" aria-label="Add your own location"
                                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); cnmAddOwn('target_locations'); }"
                                       class="asp-field is-sm flex-1 min-w-0">
                                <button type="button" onclick="cnmAddOwn('target_locations')" class="asp-sec !h-[34px] !px-3"><i class="fas fa-plus text-xs" aria-hidden="true"></i>Add</button>
                            </div>
                            <textarea id="target_locations" name="target_locations" rows="5" hidden oninput="cnmSync('target_locations')"
                                      placeholder="Pick above, or type one per line (e.g. Dubai (United Arab Emirates))"
                                      class="asp-field {{ $profileErrors->has('target_locations') || $profileErrors->has('target_locations.*') ? 'is-invalid' : '' }}">{{ $listValue('target_locations', $profile->target_locations) }}</textarea>
                            <p id="target_locations-msg" class="text-xs text-gray-500 mt-1">Cities, regions or countries, up to 20. Click × to remove one.</p>
                        </div>
                    </div>
                </section>

                {{-- Outreach Preferences --}}
                <section id="sec-outreach" role="tabpanel" class="asp-tab asp-card {{ $activeTab === 'sec-outreach' ? '' : 'hidden' }}" aria-labelledby="sec-outreach-title">
                    <div class="asp-head">
                        <span class="asp-head-icon" aria-hidden="true"><i class="fas fa-paper-plane"></i></span>
                        <h2 id="sec-outreach-title" class="text-[14.5px] font-semibold text-gray-900 pt-1">Outreach Preferences</h2>
                    </div>
                    <div class="asp-body grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="language" class="asp-label">Email language</label>
                            <select id="language" name="language" class="asp-field">
                                @foreach($languages as $code => $label)
                                    <option value="{{ $code }}" @selected(old('language', $profile->language) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="tone" class="asp-label">Email tone</label>
                            <select id="tone" name="tone" class="asp-field">
                                @foreach($tones as $code => $label)
                                    <option value="{{ $code }}" @selected(old('tone', $profile->tone) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="sender_name" class="asp-label">Sender name</label>
                            <input id="sender_name" name="sender_name" type="text" maxlength="120"
                                   value="{{ old('sender_name', $profile->sender_name) }}" placeholder="e.g. Ayesha Khan"
                                   @if($profileErrors->has('sender_name')) aria-invalid="true" @endif
                                   class="asp-field {{ $profileErrors->has('sender_name') ? 'is-invalid' : '' }}">
                        </div>
                        <div>
                            <label for="sender_postal_address" class="asp-label">Business postal address</label>
                            <input id="sender_postal_address" name="sender_postal_address" type="text" maxlength="500"
                                   value="{{ old('sender_postal_address', $profile->sender_postal_address) }}" placeholder="Street, city, country"
                                   aria-describedby="postal-help" @if($profileErrors->has('sender_postal_address')) aria-invalid="true" @endif
                                   class="asp-field {{ $profileErrors->has('sender_postal_address') ? 'is-invalid' : '' }}">
                            <p id="postal-help" class="asp-help">Required by anti-spam laws in many countries for business email.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="signature" class="asp-label">Email signature</label>
                            <textarea id="signature" name="signature" rows="3" maxlength="1000"
                                      placeholder="Ayesha Khan&#10;Founder, Pixel Growth Studio&#10;+92 300 0000000"
                                      @if($profileErrors->has('signature')) aria-invalid="true" @endif
                                      class="asp-field {{ $profileErrors->has('signature') ? 'is-invalid' : '' }}">{{ old('signature', $profile->signature) }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="ai_instructions" class="asp-label">Extra instructions for the AI <span class="opt">(optional)</span></label>
                            <textarea id="ai_instructions" name="ai_instructions" rows="3" maxlength="2000"
                                      placeholder="e.g. Never promise rankings. Mention our 30-day money-back guarantee. Keep emails under 120 words."
                                      @if($profileErrors->has('ai_instructions')) aria-invalid="true" @endif
                                      class="asp-field {{ $profileErrors->has('ai_instructions') ? 'is-invalid' : '' }}">{{ old('ai_instructions', $profile->ai_instructions) }}</textarea>
                        </div>
                    </div>
                </section>
            </fieldset>

            @if($canEdit)
                {{-- The one Save button for the Business, Audience and Outreach tabs --}}
                <div class="sticky bottom-0 z-10 bg-white border border-slate-200 rounded-xl px-4 py-2.5 shadow-[0_-8px_24px_-16px_rgba(15,23,42,0.25)] flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-xs text-slate-500">
                        @if($profile->exists && $profile->updated_at)
                            <i class="fas fa-clock mr-1" aria-hidden="true"></i>Last saved {{ $profile->updated_at->diffForHumans() }}
                        @else
                            Not saved yet
                        @endif
                        <span class="block mt-0.5">Saves Business Details, Target Audience and Outreach Preferences together.</span>
                    </p>
                    <button type="submit" class="asp-btn w-full sm:w-auto">
                        <i class="fas fa-save" aria-hidden="true"></i> Save profile
                    </button>
                </div>
            @endif
        </form>

        {{-- Skills / Services / Offers --}}
        @foreach($offerTypes as $type => $label)
            @php
                $items = $offers->get($type, collect());
                $meta = $typeMeta[$type];
                $addKey = 'add-' . $type;
                $isSkillType = $type === 'skill';
                // Skills 3–10, services 1–5 (UserOffer::limits); offers have no minimum
                $lim = \App\Models\UserOffer::limits($type);
                $limited = $lim['min'] > 0;
                $noun = strtolower($label);
                $minNoun = $lim['min'] === 1 ? $noun : $noun . 's';
                $activeCount = $items->where('is_active', true)->count();
                $atMax = $limited && $items->count() >= $lim['max'];
                $atMinTotal = $limited && $items->count() <= $lim['min'];
                $atMinActive = $limited && $activeCount <= $lim['min'];
            @endphp
            <section id="{{ $type }}s" role="tabpanel" class="asp-tab asp-card cnm-offers {{ $activeTab === $type . 's' ? '' : 'hidden' }}" aria-labelledby="{{ $type }}s-title"
                     data-noun="{{ $noun }}" data-min="{{ $lim['min'] }}" data-max="{{ $limited ? $lim['max'] : 0 }}">
                <div class="asp-head pb-3 border-b border-slate-100">
                    <span class="asp-head-icon" aria-hidden="true"><i class="fas {{ $meta['icon'] }}"></i></span>
                    <div class="min-w-0">
                        <h2 id="{{ $type }}s-title" class="text-lg font-semibold text-gray-900 flex flex-wrap items-center gap-2 pt-1.5">
                            {{ $meta['plural'] }}
                            <span class="text-xs font-semibold text-slate-600 bg-slate-100 rounded-lg px-2 py-0.5" @if($limited) data-testid="{{ $type }}-count" @endif><span class="cnm-count">{{ $items->count() }}</span>{{ $limited ? ' / ' . $lim['max'] : '' }}</span>
                        </h2>
                        <p class="text-[12px] text-slate-500 mt-0.5">{{ $meta['hint'] }}</p>
                        @if($limited)
                            <p class="cnm-min-warning text-xs font-medium text-red-600 mt-1.5 {{ $activeCount < $lim['min'] ? '' : 'hidden' }}" @if($activeCount < $lim['min']) data-testid="{{ $type }}-min-warning" @endif><i class="fas fa-circle-exclamation mr-1"></i>Add at least {{ $lim['min'] }} active {{ $minNoun }} so the AI knows what you {{ $isSkillType ? 'do' : 'sell' }} (<span class="cnm-active">{{ $activeCount }}</span> so far).</p>
                        @endif
                    </div>
                </div>

                {{-- Offers: pick an offer type → short form (pre-filled) → Generate with AI or Save without AI --}}
                @if($type === 'offer' && $canEdit && $offerCategories->isNotEmpty())
                    <div class="px-4 sm:px-5 py-4 border-b border-slate-100" id="ob-picker" data-testid="offer-type-picker">
                        {{-- Why these offer types: Alex Hormozi's frameworks (inspiration only, no endorsement) --}}
                        <div class="flex flex-col sm:flex-row gap-3 bg-slate-50 border border-slate-200 rounded-xl p-3 mb-4" data-testid="hormozi-intro">
                            <img src="{{ asset('public/assets/images/alex-hormozi.jpg') }}" alt="Alex Hormozi" width="64" height="64" loading="lazy"
                                 class="w-12 h-12 rounded-lg object-cover object-top bg-white flex-shrink-0">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">Alex Hormozi
                                    <span class="font-normal text-slate-500">· Entrepreneur and author of <span class="italic">$100M Offers</span> and <span class="italic">$100M Money Models</span></span>
                                </p>
                                <blockquote class="mt-1 text-[13px] font-semibold text-orange-700">
                                    “Make people an offer so good they would feel stupid saying no.”
                                </blockquote>
                                <p class="mt-1 text-[12px] text-slate-600 leading-relaxed">
                                    <span class="font-semibold text-gray-900">Why we use these offers:</span>
                                    most leads don't say no to you, they say no to a weak offer. These 16 offer types are built on his proven
                                    frameworks to get new customers (Attraction), raise each sale (Upsell), save a "no" (Downsell) and keep
                                    customers paying (Continuity). Pick one, and AI turns it into a complete offer for your leads.
                                </p>
                                <p class="mt-1.5 text-[11px] text-slate-500">Inspired by his published frameworks. CustomerNearMe is not affiliated with or endorsed by Alex Hormozi or Acquisition.com.</p>
                            </div>
                        </div>

                        <p class="text-sm font-semibold text-gray-900 mb-1"><i class="fas fa-gift text-orange-500 mr-1"></i> Create an offer</p>
                        <p class="text-xs text-slate-500 mb-3">Choose an offer type. We fill in the details from your profile; AI writes the offer.</p>
                        <div class="max-w-full overflow-x-auto">
                            <div class="inline-flex gap-1 p-1 bg-slate-100 rounded-xl" role="tablist" aria-label="Offer categories">
                                @foreach($offerCategories as $cat)
                                    <button type="button" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-cat="{{ $cat->slug }}" onclick="obPickCat('{{ $cat->slug }}')"
                                            class="ob-cat {{ $loop->first ? 'is-on' : '' }}">
                                        <i class="fas {{ $cat->icon }} mr-1.5"></i>{{ $cat->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        @foreach($offerCategories as $cat)
                            <div class="ob-cat-panel mt-4 {{ $loop->first ? '' : 'hidden' }}" data-cat="{{ $cat->slug }}" role="tabpanel">
                                <p class="text-[13px] text-slate-600 mb-3">{{ $cat->purpose }}</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2">
                                    @foreach($cat->strategies as $s)
                                        <button type="button" onclick="obOpenQuick('{{ $s->slug }}', this)" data-strategy="{{ $s->slug }}"
                                                class="ob-strategy-btn text-left bg-white border border-slate-200 rounded-lg px-3 py-2 hover:border-orange-300 hover:bg-orange-50/40 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500">
                                            <span class="block text-[13px] font-semibold text-gray-900">{{ $s->name }}</span>
                                            <span class="block text-[11.5px] text-slate-500 mt-0.5 leading-snug"><span class="font-medium text-slate-600">Use when:</span> {{ ($s->when_to_use ?? [''])[0] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <div id="ob-quick-panel" class="hidden mt-4 border border-orange-200 rounded-xl p-3.5 sm:p-4 bg-white relative scroll-mt-24">
                            <button type="button" onclick="obCloseQuick()" class="absolute top-3 right-3 w-9 h-9 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close"><i class="fas fa-xmark"></i></button>
                            <div id="ob-quick-body"></div>
                        </div>
                    </div>
                @endif

                <div class="divide-y divide-slate-100">
                    @foreach($items as $item)
                        @php $editKey = 'edit-' . $item->id; @endphp
                        <div class="px-4 sm:px-5 py-2.5 cnm-row" data-offer-id="{{ (int) $item->id }}" data-active="{{ $item->is_active ? 1 : 0 }}">
                            <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @php
                                            $mainName = $isSkillType ? \App\Support\SkillCatalog::mainOf($item->title) : null;
                                            $link = $type === 'offer' ? ($offerLinks[$item->id] ?? null) : null;
                                            $hl = fn ($t) => $link ? \App\Services\OfferBuilder\InputHighlighter::html($t, $link['chips']) : e($t);
                                        @endphp
                                        <h3 class="cnm-title text-[13.5px] font-semibold text-gray-900 break-words {{ $item->is_active ? '' : 'line-through text-gray-400' }}">{{ $item->title }}</h3>
                                        <span class="cnm-badge asp-badge {{ $item->is_active ? 'is-on' : 'is-off' }}">{{ $item->is_active ? 'Active' : 'Disabled' }}</span>
                                    </div>
                                    @if($mainName || ($link && $link['strategy']) || $item->price_hint)
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                            @if($mainName)
                                                <span class="text-[11px] font-medium px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 border border-slate-200">{{ $mainName }}</span>
                                            @endif
                                            @if($link && $link['strategy'])
                                                <span class="text-[11px] font-medium px-2 py-0.5 rounded-lg bg-white text-slate-600 border border-slate-200"><i class="fas fa-gift text-orange-500 mr-1"></i>{{ $link['strategy'] }}</span>
                                            @endif
                                            @if($item->price_hint)
                                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-lg bg-orange-50 text-orange-700 border border-orange-200">{!! $hl($item->price_hint) !!}</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if($item->description)
                                        <p class="text-[12.5px] text-slate-600 mt-1 whitespace-pre-line break-words leading-relaxed">{!! $hl($item->description) !!}</p>
                                    @endif
                                    @if($item->ideal_for)
                                        <p class="text-xs text-slate-500 mt-1.5"><span class="font-semibold text-slate-700">Ideal for:</span> {!! $hl($item->ideal_for) !!}</p>
                                    @endif
                                    @if($link && $link['chips'] !== [])
                                        <div class="mt-2">@include('user.offer-builder._chips', ['chips' => $link['chips'], 'caption' => 'What you entered (same colours in the text):'])</div>
                                    @endif
                                </div>

                                @if($canEdit)
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <button type="button" onclick="cnmToggleEdit({{ (int) $item->id }})" class="asp-act">
                                            <i class="fas fa-pen text-[11px]" aria-hidden="true"></i>Edit
                                        </button>
                                        <form method="POST" action="{{ route('user.ai-profile.offers.toggle', $item->id) }}" class="cnm-toggle">
                                            @csrf @method('PATCH')
                                            <button type="submit" data-role="toggle" @disabled($item->is_active && $atMinActive) @if($item->is_active && $atMinActive) title="You need at least {{ $lim['min'] }} active {{ $minNoun }}" @endif
                                                    class="asp-act {{ $item->is_active ? '' : 'is-orange' }}">
                                                {{ $item->is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('user.ai-profile.offers.destroy', $item->id) }}" class="cnm-del"
                                              data-confirm="Remove this {{ strtolower($label) }}?">
                                            @csrf @method('DELETE')
                                            <button type="submit" data-role="delete" @disabled($atMinTotal) title="{{ $atMinTotal ? 'You need at least ' . $lim['min'] . ' ' . $minNoun : 'Remove' }}"
                                                    class="asp-act is-danger">
                                                <i class="fas fa-trash-alt text-xs"></i><span class="sr-only">Remove</span>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            @if($canEdit)
                                <form id="edit-form-{{ (int) $item->id }}" method="POST"
                                      action="{{ route('user.ai-profile.offers.update', $item->id) }}"
                                      class="{{ $openFormKey === $editKey ? '' : 'hidden' }} mt-3 bg-slate-50 border border-slate-200 rounded-xl p-3 sm:p-4">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="form_key" value="{{ $editKey }}">
                                    <input type="hidden" name="type" value="{{ $type }}">
                                    @include('user.ai-sales-profile._offer-fields', [
                                        'type' => $type,
                                        'prefix' => 'e' . $item->id,
                                        'values' => $openFormKey === $editKey ? old() : $item->only(['title', 'description', 'price_hint', 'ideal_for', 'is_active']),
                                        'errorsBag' => $openFormKey === $editKey ? $offerErrors : null,
                                    ])
                                    <div class="flex justify-end gap-2 mt-4">
                                        <button type="button" onclick="cnmToggleEdit({{ (int) $item->id }})" class="asp-sec !h-11">Cancel</button>
                                        <button type="submit" class="asp-btn !h-11">Save changes</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                        <div class="cnm-empty p-5 text-center {{ $items->isEmpty() ? '' : 'hidden' }}">
                            <div class="w-12 h-12 mx-auto rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center mb-3">
                                <i class="fas {{ $meta['icon'] }}"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">No {{ strtolower($meta['plural']) }} yet</p>
                            <p class="text-sm text-slate-500 mt-1">{{ $canEdit ? 'Add your first one below so the AI can recommend it to the right leads.' : 'The account owner has not added any yet.' }}</p>
                        </div>
                </div>

                @if($canEdit && $limited)
                    <p class="cnm-max border-t border-slate-100 px-4 sm:px-5 py-2.5 text-[12.5px] text-slate-500 {{ $atMax ? '' : 'hidden' }}" @if($atMax) data-testid="{{ $type }}-max-reached" @endif>
                        <i class="fas fa-circle-info mr-1 text-orange-500"></i> You have reached the maximum of {{ $lim['max'] }} {{ $noun }}s. Remove one to add another.
                    </p>
                @endif
                @if($canEdit)
                    <details class="cnm-add border-t border-gray-100 group {{ $atMax ? 'hidden' : '' }}" @if($openFormKey === $addKey) open @endif>
                        <summary class="list-none cursor-pointer px-4 sm:px-5 py-2.5 text-[13px] font-semibold text-orange-600 hover:bg-orange-50 rounded-b-2xl flex items-center gap-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-orange-500">
                            <span class="w-6 h-6 rounded-md bg-orange-50 border border-orange-200 flex items-center justify-center" aria-hidden="true"><i class="fas fa-plus text-xs transition-transform group-open:rotate-45"></i></span>
                            {{ $type === 'offer' ? 'Custom offer (write your own)' : 'Add ' . strtolower($label) }}
                        </summary>
                        <form method="POST" action="{{ route('user.ai-profile.offers.store') }}" class="px-4 sm:px-5 pb-4">
                            @csrf
                            <input type="hidden" name="form_key" value="{{ $addKey }}">
                            <input type="hidden" name="type" value="{{ $type }}">
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 sm:p-4">
                                @include('user.ai-sales-profile._offer-fields', [
                                    'type' => $type,
                                    'prefix' => 'a' . $type,
                                    'values' => $openFormKey === $addKey ? old() : ['is_active' => true],
                                    'errorsBag' => $openFormKey === $addKey ? $offerErrors : null,
                                ])
                                <div class="flex justify-end mt-4">
                                    <button type="submit" class="asp-btn !h-11">
                                        Add {{ strtolower($label) }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </details>
                @endif
            </section>
        @endforeach
    </div>
</div>
</div>

<script>
    // Skills: main skill → sub skill (no empty option, first sub skill is selected)
    const CNM_SKILLS = @json(\App\Support\SkillCatalog::all());
    function cnmSkillSubs(main) {
        const sub = document.getElementById(main.dataset.sub);
        sub.innerHTML = '';
        (CNM_SKILLS[main.value] || []).forEach(s => sub.add(new Option(s, s)));
    }

    // ── Offers: offer type → short form → Generate with AI / Save without AI ──
    const OB = {
        form: @json(route('user.offer-builder.quick-form')),
        save: @json(route('user.offer-builder.quick-save')),
        store: @json(route('user.offer-builder.offers.store')),
        prompt: @json(route('user.offer-builder.prompt')),
        csrf: document.querySelector('meta[name="csrf-token"]').content,
    };
    function obPickCat(slug) {
        document.querySelectorAll('.ob-cat').forEach(b => {
            const on = b.dataset.cat === slug;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.querySelectorAll('.ob-cat-panel').forEach(p => p.classList.toggle('hidden', p.dataset.cat !== slug));
    }
    function obCloseQuick() {
        document.getElementById('ob-quick-panel').classList.add('hidden');
        document.querySelectorAll('.ob-strategy-btn').forEach(b => b.classList.remove('border-orange-500', 'ring-2', 'ring-orange-200'));
    }
    async function obOpenQuick(slug, btn) {
        const panel = document.getElementById('ob-quick-panel'), body = document.getElementById('ob-quick-body');
        document.querySelectorAll('.ob-strategy-btn').forEach(b => b.classList.remove('border-orange-500', 'ring-2', 'ring-orange-200'));
        btn.classList.add('border-orange-500', 'ring-2', 'ring-orange-200');
        body.innerHTML = '<p class="text-sm text-gray-500 py-6 text-center"><i class="fas fa-spinner fa-spin mr-1"></i> Loading…</p>';
        panel.classList.remove('hidden');
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        try {
            const res = await fetch(OB.form + '?strategy=' + encodeURIComponent(slug), { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
            if (!res.ok) throw new Error();
            body.innerHTML = await res.text();
        } catch {
            body.innerHTML = '<p class="text-sm text-red-600 py-6 text-center">Could not load this offer type. Please try again.</p>';
        }
    }
    async function obSend(url, body) {
        const res = await fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': OB.csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: body ? JSON.stringify(body) : undefined,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.ok === false) {
            const first = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(first || data.message || 'Something went wrong. Please try again.');
        }
        return data;
    }
    function obQuickData(root) {
        return {
            strategy: root.dataset.strategy,
            title: root.querySelector('.ob-title')?.value.trim() || '',
            inputs: obValues(root),
            additional_instructions: root.querySelector('.ob-additional')?.value.trim() || '',
        };
    }
    function obQuickStatus(root, text, bad) {
        const el = root.querySelector('.ob-q-status');
        el.innerHTML = '';
        el.className = 'ob-q-status text-sm mt-2 ' + (bad ? 'text-red-600' : 'text-gray-700');
        el.textContent = text;
    }
    function obQuickBusy(root, on) {
        root.querySelectorAll('.ob-q-generate, .ob-q-save').forEach(b => b.disabled = on);
    }
    document.addEventListener('click', async e => {
        const gen = e.target.closest('.ob-q-generate'), save = e.target.closest('.ob-q-save');
        if (!gen && !save) return;
        const root = e.target.closest('.ob-quick');
        obQuickBusy(root, true);
        try {
            if (save) {
                const r = await obSend(OB.save, obQuickData(root));
                obQuickStatus(root, r.message);
                location.hash = 'offers';
                location.reload();
                return;
            }
            const draft = await obSend(OB.store, obQuickData(root));
            if (draft.missing && draft.missing.length) throw new Error('Please fill in: ' + draft.missing.join(', ') + '.');
            const g = await obSend(draft.generate_url);
            obQuickStatus(root, 'AI is writing your offer and messages… this usually takes 1 to 2 minutes. You can stay here.');
            root.querySelector('.ob-q-status').insertAdjacentHTML('afterbegin', '<i class="fas fa-spinner fa-spin mr-1 text-orange-500"></i>');
            const started = Date.now();
            const tick = async () => {
                let d = {};
                try { d = await (await fetch(g.status_url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })).json(); } catch {}
                if (d.status === 'completed') { location.hash = 'offers'; location.reload(); return; }
                if (d.status === 'failed') { obQuickBusy(root, false); obQuickStatus(root, d.message || 'The AI could not create this offer. Please try again.', true); return; }
                if (Date.now() - started > 60000) obQuickStatus(root, 'Still working… the AI queue may be busy. The offer will appear in your list when it is ready.');
                setTimeout(tick, 4000);
            };
            setTimeout(tick, 3000);
        } catch (err) {
            obQuickBusy(root, false);
            obQuickStatus(root, err.message, true);
        }
    });

    // Offer type form: examples, prompt preview + copy
    document.addEventListener('click', async e => {
        const ex = e.target.closest('.ob-q-examples'), pr = e.target.closest('.ob-q-prompt'), cp = e.target.closest('.ob-q-prompt-copy');
        if (!ex && !pr && !cp) return;
        const root = e.target.closest('.ob-quick');
        if (ex) {
            const box = root.querySelector('.ob-q-examples-box');
            box.classList.toggle('hidden');
            ex.setAttribute('aria-expanded', box.classList.contains('hidden') ? 'false' : 'true');
            return;
        }
        if (cp) { obCopyString(root.querySelector('.ob-q-prompt-text').textContent, cp); return; }
        const box = root.querySelector('.ob-q-prompt-box'), pre = root.querySelector('.ob-q-prompt-text');
        if (!box.classList.contains('hidden')) { box.classList.add('hidden'); pr.setAttribute('aria-expanded', 'false'); return; }
        pr.disabled = true;
        try {
            const d = await obSend(OB.prompt, obQuickData(root));
            pre.textContent = d.prompt;
            box.classList.remove('hidden');
            pr.setAttribute('aria-expanded', 'true');
        } catch (err) { obQuickStatus(root, err.message, true); }
        finally { pr.disabled = false; }
    });

    // Enable / Disable without reloading the page
    document.addEventListener('submit', async e => {
        const form = e.target.closest('form.cnm-toggle');
        if (!form) return;
        e.preventDefault();
        const row = form.closest('.cnm-row'), sec = form.closest('.cnm-offers'), btn = form.querySelector('button');
        btn.disabled = true;
        try {
            const res = await fetch(form.action, {
                method: 'POST', body: new FormData(form), credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || 'Could not change it. Please try again.');
            const on = !!data.is_active;
            row.dataset.active = on ? '1' : '0';
            row.querySelector('.cnm-title').classList.toggle('line-through', !on);
            row.querySelector('.cnm-title').classList.toggle('text-gray-400', !on);
            const badge = row.querySelector('.cnm-badge');
            badge.textContent = on ? 'Active' : 'Disabled';
            badge.className = 'cnm-badge asp-badge ' + (on ? 'is-on' : 'is-off');
            btn.textContent = on ? 'Disable' : 'Enable';
            btn.className = 'asp-act' + (on ? '' : ' is-orange');
            btn.title = '';
            btn.disabled = false;
            cnmRefreshSection(sec);
            cnmToast(on ? 'Enabled. AI can use it.' : 'Disabled. AI will not use it.');
        } catch (err) {
            btn.disabled = false;
            cnmRefreshSection(sec);
            cnmToast(err.message, true);
        }
    });

    function cnmToggleEdit(id) {
        const form = document.getElementById('edit-form-' + parseInt(id, 10));
        if (form) form.classList.toggle('hidden');
    }

    // ── Target pickers: ticked options become lines in the textarea below (the textarea is what is saved) ──
    const CNM_MAX = 20;
    const cnmClean = v => String(v).replace(/,/g, ' -').replace(/\s+/g, ' ').trim(); // the form also splits on commas
    function cnmItems(id) {
        return document.getElementById(id).value.split(/[\r\n,]+/).map(s => s.trim()).filter(Boolean);
    }
    function cnmWrite(id, items) {
        document.getElementById(id).value = items.join('\n');
        cnmSync(id);
    }
    function cnmMsg(id, text, bad) {
        const m = document.getElementById(id + '-msg');
        if (!m) return;
        if (!m.dataset.base) m.dataset.base = m.textContent;
        m.textContent = text || m.dataset.base;
        m.className = 'text-xs mt-1 ' + (bad ? 'text-red-600' : 'text-gray-500');
    }
    function cnmAdd(id, value) {
        value = cnmClean(value);
        const items = cnmItems(id);
        if (!value || items.some(i => i.toLowerCase() === value.toLowerCase())) return true;
        if (items.length >= CNM_MAX) { cnmMsg(id, 'You can add up to ' + CNM_MAX + '. Remove one first.', true); return false; }
        items.push(value);
        cnmWrite(id, items);
        return true;
    }
    function cnmRemove(id, value) {
        value = cnmClean(value).toLowerCase();
        cnmWrite(id, cnmItems(id).filter(i => i.toLowerCase() !== value));
    }
    function cnmPick(box) {
        if (box.checked) { if (!cnmAdd(box.dataset.target, box.value)) box.checked = false; }
        else cnmRemove(box.dataset.target, box.value);
    }
    // Own entry typed in the small box under the chips
    function cnmAddOwn(id) {
        const input = document.getElementById(id + '-new');
        if (input.value.trim() && cnmAdd(id, input.value)) input.value = '';
        input.focus();
    }
    // Selected values as chips; × removes one (and unticks it above)
    function cnmChips(id) {
        const box = document.getElementById(id + '-chips');
        if (!box) return;
        box.replaceChildren();
        const items = cnmItems(id);
        if (!items.length) {
            const p = document.createElement('span');
            p.className = 'text-xs text-slate-400 px-1 py-1.5';
            p.textContent = 'Nothing selected yet';
            box.append(p);
            return;
        }
        items.forEach(v => {
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1 max-w-full pl-3 pr-1 py-1 rounded-lg bg-white border border-orange-200 text-[13px] font-medium text-orange-800';
            chip.title = v;
            const t = document.createElement('span');
            t.className = 'truncate';
            t.textContent = v;
            const x = document.createElement('button');
            x.type = 'button';
            x.className = 'w-6 h-6 flex-shrink-0 rounded-md flex items-center justify-center text-orange-500 hover:bg-orange-100 hover:text-orange-800';
            x.setAttribute('aria-label', 'Remove ' + v);
            x.innerHTML = '<i class="fas fa-xmark text-[11px]"></i>';
            x.onclick = () => cnmRemove(id, v);
            chip.append(t, x);
            box.append(chip);
        });
    }
    // Keep ticks, chips and the counter in step with the (hidden) box
    function cnmSync(id) {
        cnmChips(id);
        const set = new Set(cnmItems(id).map(i => i.toLowerCase()));
        document.querySelectorAll('.cnm-pick[data-target="' + id + '"]').forEach(b => b.checked = set.has(cnmClean(b.value).toLowerCase()));
        const c = document.getElementById(id + '-count');
        if (c) { c.textContent = set.size; c.parentElement.className = 'text-xs ' + (set.size > CNM_MAX ? 'text-red-600 font-semibold' : 'text-gray-500'); }
        cnmMsg(id, set.size > CNM_MAX ? 'Too many: keep at most ' + CNM_MAX + '.' : '', set.size > CNM_MAX);
    }
    function cnmFilterList(listId, q) {
        q = q.trim().toLowerCase();
        document.querySelectorAll('#' + listId + ' .cnm-opt').forEach(el => el.classList.toggle('hidden', q !== '' && !el.dataset.name.includes(q)));
    }

    const cnmStatesUrl = @json(route('user.api.states', ['country' => '__ID__']));
    const cnmCitiesUrl = @json(route('user.api.cities', ['state' => '__ID__']));
    const cnmText = id => { const s = document.getElementById(id); return s.value ? s.options[s.selectedIndex].text.trim() : ''; };
    function cnmLoadStates(countryId) {
        const st = document.getElementById('loc-state');
        st.innerHTML = '<option value="">State / region…</option>'; st.disabled = true;
        document.getElementById('city-box').classList.add('hidden');
        document.getElementById('add-country').disabled = !countryId;
        document.getElementById('add-state').disabled = true;
        if (!countryId) return;
        fetch(cnmStatesUrl.replace('__ID__', encodeURIComponent(countryId)), { headers: { Accept: 'application/json' } })
            .then(r => r.json()).then(list => {
                list.forEach(s => st.add(new Option(s.name, s.id)));
                st.disabled = list.length === 0;
            });
    }
    function cnmLoadCities(stateId) {
        const box = document.getElementById('city-box'), list = document.getElementById('city-list');
        document.getElementById('add-state').disabled = !stateId;
        list.innerHTML = ''; document.getElementById('city-filter').value = '';
        if (!stateId) { box.classList.add('hidden'); return; }
        list.innerHTML = '<p class="px-4 py-3 text-xs text-slate-500"><i class="fas fa-spinner fa-spin mr-1"></i>Loading…</p>'; box.classList.remove('hidden');
        fetch(cnmCitiesUrl.replace('__ID__', encodeURIComponent(stateId)), { headers: { Accept: 'application/json' } })
            .then(r => r.json()).then(cities => {
                const state = cnmText('loc-state'), country = cnmText('loc-country');
                list.innerHTML = cities.length ? '' : '<p class="px-3 py-2 text-xs text-gray-500">No cities listed for this state.</p>';
                cities.forEach(c => {
                    const label = document.createElement('label');
                    label.className = 'cnm-opt';
                    label.dataset.name = c.name.toLowerCase();
                    const cb = document.createElement('input');
                    cb.type = 'checkbox'; cb.className = 'cnm-pick asp-check'; cb.dataset.target = 'target_locations';
                    cb.value = cnmClean(c.name + ' (' + state + ' ' + country + ')');
                    cb.onchange = () => cnmPick(cb);
                    label.append(cb, document.createTextNode(' ' + c.name));
                    list.append(label);
                });
                cnmSync('target_locations');
            });
    }
    function cnmAddPlace(level) {
        const country = cnmText('loc-country'), state = cnmText('loc-state');
        if (level === 'country' && country) cnmAdd('target_locations', country);
        if (level === 'state' && state) cnmAdd('target_locations', state + ' (' + country + ')');
    }
    document.addEventListener('DOMContentLoaded', () => { cnmSync('target_industries'); cnmSync('target_locations'); });

    // Tabs: show one section; the profile form (with its Save bar) shows for its three tabs
    (() => {
        const nav = document.querySelector('.asp-nav');
        const tabs = [...nav.querySelectorAll('[role="tab"]')];
        const ids = tabs.map(t => t.getAttribute('aria-controls'));
        const profileIds = ['sec-business', 'sec-audience', 'sec-outreach'];
        const form = document.getElementById('asp-profile-form');
        function show(id, focus) {
            if (!ids.includes(id)) return false;
            tabs.forEach(t => {
                const on = t.getAttribute('aria-controls') === id;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                if (on && focus) t.focus();
                if (on) t.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            });
            ids.forEach(p => document.getElementById(p)?.classList.toggle('hidden', p !== id));
            form?.classList.toggle('hidden', !profileIds.includes(id));
            // If the tabs are stuck at the top, bring the new section's start into view
            if (nav.getBoundingClientRect().top <= 0) window.scrollTo({ top: window.scrollY + nav.nextElementSibling.getBoundingClientRect().top - nav.offsetHeight - 16 });
            return true;
        }
        tabs.forEach((t, i) => {
            t.addEventListener('click', e => {
                e.preventDefault();
                const id = t.getAttribute('aria-controls');
                show(id);
                history.replaceState(null, '', '#' + id);
            });
            t.addEventListener('keydown', e => {
                const step = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
                if (!step) return;
                e.preventDefault();
                const next = tabs[(i + step + tabs.length) % tabs.length];
                show(next.getAttribute('aria-controls'), true);
                history.replaceState(null, '', '#' + next.getAttribute('aria-controls'));
            });
        });
        const fromHash = () => show(location.hash.slice(1));
        window.addEventListener('hashchange', fromHash);
        // Server already opened the tab with an error or open form; a #hash (e.g. after creating an offer) overrides it
        @if(!$openFormKey && !$profileErrors->any())
            if (fromHash()) requestAnimationFrame(() => window.scrollTo({ top: 0 }));
        @endif
    })();

    // ── Skills / services / offers: delete without reloading the page ──
    function cnmToast(text, bad) {
        const t = document.createElement('div');
        t.setAttribute('role', 'status');
        t.className = 'fixed bottom-4 right-4 z-50 max-w-sm px-4 py-3 rounded-xl shadow-lg text-sm font-medium border '
            + (bad ? 'bg-red-50 border-red-200 text-red-800' : 'bg-green-50 border-green-200 text-green-800');
        t.textContent = text;
        document.body.append(t);
        setTimeout(() => t.remove(), 3000);
    }
    // Re-apply count, min/max rules and empty state after a row is removed
    function cnmRefreshSection(sec) {
        const rows = sec.querySelectorAll('.cnm-row');
        const min = +sec.dataset.min, max = +sec.dataset.max, noun = sec.dataset.noun;
        const active = [...rows].filter(r => r.dataset.active === '1').length;
        const need = min === 1 ? noun : noun + 's';
        sec.querySelector('.cnm-count').textContent = rows.length;
        sec.querySelector('.cnm-empty')?.classList.toggle('hidden', rows.length > 0);
        const warn = sec.querySelector('.cnm-min-warning');
        if (warn) { warn.classList.toggle('hidden', active >= min); warn.querySelector('.cnm-active').textContent = active; }
        if (max > 0) {
            sec.querySelector('.cnm-max')?.classList.toggle('hidden', rows.length < max);
            sec.querySelector('.cnm-add')?.classList.toggle('hidden', rows.length >= max);
        }
        if (min > 0) {
            sec.querySelectorAll('[data-role="delete"]').forEach(b => {
                b.disabled = rows.length <= min;
                b.title = b.disabled ? 'You need at least ' + min + ' ' + need : 'Remove';
            });
            sec.querySelectorAll('.cnm-row[data-active="1"] [data-role="toggle"]').forEach(b => {
                b.disabled = active <= min;
                b.title = b.disabled ? 'You need at least ' + min + ' active ' + need : '';
            });
        }
    }
    document.addEventListener('submit', async e => {
        const form = e.target.closest('form.cnm-del');
        if (!form) return;
        e.preventDefault();
        if (!confirm(form.dataset.confirm)) return;
        const row = form.closest('.cnm-row'), sec = form.closest('.cnm-offers'), btn = form.querySelector('button');
        btn.disabled = true;
        row.style.opacity = '.4';
        try {
            const res = await fetch(form.action, {
                method: 'POST', body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || 'Could not remove it. Please try again.');
            row.remove();
            cnmRefreshSection(sec);
            cnmToast('Removed.');
        } catch (err) {
            row.style.opacity = '';
            btn.disabled = false;
            cnmRefreshSection(sec);
            cnmToast(err.message, true);
        }
    });
</script>
@include('user.offer-builder._fields-scripts')
@include('user.offer-builder._result-scripts')
@endsection
