@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')
@include('user._ui-kit')
<style>
    .db-step { display: flex; align-items: center; gap: 10px; padding: 9px 11px; background: #fff; border: 1px solid #E2E8F0; border-radius: 11px; min-width: 0; }
    .db-step-n { width: 26px; height: 26px; border-radius: 8px; background: #FFF7ED; color: #EA580C; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .db-step.is-ai .db-step-n { background: #111827; color: #FDBA74; }
    .db-tile { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #E2E8F0; border-radius: 11px; background: #fff; transition: border-color .15s, background-color .15s; min-width: 0; }
    .db-tile:hover { border-color: #FDBA74; background: #FFFBF7; }
    .db-tile-i { width: 32px; height: 32px; border-radius: 9px; background: #FFF7ED; color: #EA580C; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px; }
    .db-tile:focus-visible { outline: 2px solid #F97316; outline-offset: 2px; }
    .db-kpi { position: relative; overflow: hidden; background: #fff; border: 1px solid #E2E8F0; border-radius: 14px; padding: 12px 14px; display: flex; align-items: center; gap: 12px; min-width: 0; }
    .db-kpi-i { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 15px; }
    .db-kpi-v { font-size: 22px; font-weight: 700; line-height: 1.1; color: #111827; }
    .db-kpi-l { font-size: 12px; color: #64748B; }
    .db-kpi-bar { position: absolute; left: 0; bottom: 0; height: 3px; }
    .db-row { display: flex; align-items: center; gap: 10px; padding: 9px 16px; border-bottom: 1px solid #F1F5F9; min-width: 0; }
    .db-row:last-child { border-bottom: 0; }
    .db-link { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 10px; color: #334155; font-size: 13px; font-weight: 500; transition: background-color .15s, color .15s; }
    .db-link:hover { background: #FFF7ED; color: #C2410C; }
    .db-link:focus-visible { outline: 2px solid #F97316; outline-offset: 1px; }
    .db-link i.lead { width: 30px; height: 30px; border-radius: 9px; background: #F8FAFC; color: #EA580C; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }

    /* Screenshot layout */
    .dbx-banner { background: #FFF5EE; border: 1px solid #FBD9C4; border-radius: 24px; padding: 28px; position: relative; overflow: hidden; }
    @media (min-width: 1024px) { .dbx-banner { padding: 32px 36px; } }
    .dbx-step { position: relative; display: flex; align-items: flex-start; gap: 12px; background: #fff; border: 1px solid #EEE7E2; border-radius: 16px; padding: 14px; box-shadow: 0 2px 6px -4px rgba(17, 24, 39, .12); min-width: 0; }
    .dbx-step-i { width: 42px; height: 42px; border-radius: 12px; background: #FFF1E7; color: #F97316; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 17px; }
    .dbx-arrow { display: none; }
    @media (min-width: 1280px) { .dbx-arrow { display: flex; position: absolute; right: -15px; top: 50%; transform: translateY(-50%); width: 18px; color: #F97316; font-size: 13px; justify-content: center; z-index: 1; } }
    .dbx-card { background: #fff; border: 1px solid #ECEEF1; border-radius: 20px; box-shadow: 0 2px 8px -5px rgba(17, 24, 39, .12); }
    .dbx-kpi { display: flex; align-items: center; gap: 16px; padding: 18px 20px; }
    .dbx-kpi-i { width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 21px; }
    .dbx-kpi-v { font-size: 26px; font-weight: 700; line-height: 1.1; color: #111827; }
    .dbx-kpi-l { font-size: 14px; color: #475569; margin-top: 2px; }
    .dbx-quick { display: flex; align-items: center; gap: 14px; padding: 16px 18px; transition: border-color .15s, box-shadow .15s; }
    .dbx-quick:hover { border-color: #FDBA74; box-shadow: 0 6px 16px -10px rgba(249, 115, 22, .45); }
    .dbx-quick:focus-visible { outline: 2px solid #F97316; outline-offset: 2px; }
    .dbx-go { width: 34px; height: 34px; border-radius: 50%; border: 1px solid #E5E7EB; display: flex; align-items: center; justify-content: center; color: #111827; font-size: 12px; margin-left: auto; flex-shrink: 0; transition: background-color .15s, color .15s, border-color .15s; }
    .dbx-quick:hover .dbx-go { background: #F97316; border-color: #F97316; color: #fff; }
    .dbx-soon { display: inline-flex; align-items: center; height: 20px; padding: 0 8px; border-radius: 999px; background: #F1F5F9; color: #475569; font-size: 10.5px; font-weight: 600; text-transform: lowercase; }
    .dbx-pill { display: inline-flex; align-items: center; gap: 8px; height: 42px; padding: 0 20px; border-radius: 999px; font-size: 14px; font-weight: 600; white-space: nowrap; }
</style>

<main class="cnm-ui ui-page !max-w-[1440px] space-y-5">
@include('user.welcome-tutorial-modal')

{{-- Phase 4: follow-up reminders (reminders only; nothing is sent automatically) --}}
@if(\App\Services\PlanFeatures::enabled(auth()->user(), 'email_followups_enabled'))
    @php
        $fuBase = \App\Models\EmailFollowUp::ownedBy(auth()->user()->getAccountOwner())->open();
        $fuOverdue = (clone $fuBase)->where('due_date', '<', today()->toDateString())->count();
        $fuToday = (clone $fuBase)->where('due_date', today()->toDateString())->count();
    @endphp
    @if($fuOverdue + $fuToday > 0)
        <div class="ui-note is-brand items-center justify-between flex-wrap" data-testid="dashboard-follow-ups">
            <div>
                <i class="fas fa-bell text-orange-500 mr-1"></i>
                {{-- Keep Blade directives on their own lines: an if/endif glued to a word compiled only half and broke the page layout --}}
                <strong>{{ $fuToday }}</strong> follow-up{{ $fuToday === 1 ? '' : 's' }} due today
                @if($fuOverdue)
                    and <strong class="text-red-600">{{ $fuOverdue }}</strong> overdue
                @endif
                <span class="text-slate-500">· a quick check-in is often what turns a lead into a client.</span>
            </div>
            <a href="{{ route('user.email-follow-ups.index') }}" class="ui-btn ui-sm">View follow-ups</a>
        </div>
    @endif
@endif

<!-- Email Verification Notice -->
@if(!auth()->user()->email_verified)
<div class="ui-note is-info flex-wrap items-center">
    <i class="fas fa-envelope text-blue-600" aria-hidden="true"></i>
    <div class="flex-1 min-w-[220px]">
        <p class="font-semibold">Please Verify Your Email</p>
        <p class="text-[12px] mt-0.5">We sent a link to <strong>{{ auth()->user()->email }}</strong>. Verify it to keep your account secure and receive important updates.</p>
    </div>
    <button onclick="resendVerification()" id="resendBtn" class="ui-btn-dark ui-sm">
        <i class="fas fa-paper-plane"></i>
        Resend Verification Email
    </button>
</div>

<script>
function resendVerification() {
    const btn = document.getElementById('resendBtn');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    fetch('{{ route("auth.resend.verification") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Email Sent!';
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 3000);
        } else {
            alert(data.message || 'Failed to send verification email');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending verification email');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>
@endif

<!-- Pending Subscription Warning -->
@if(auth()->user()->hasRestrictedAccess())
<div class="ui-note is-warn flex-wrap items-center">
    <i class="fas fa-clock text-amber-600" aria-hidden="true"></i>
    <div class="flex-1 min-w-[220px]">
        <p class="font-semibold">Subscription Pending Activation</p>
        <p class="text-[12px] mt-0.5">Your payment is in and our team is reviewing it. Every feature unlocks as soon as your plan is activated.</p>
    </div>
    <a href="{{ route('user.subscription') }}" class="ui-btn-light ui-sm">View Subscription Status</a>
</div>
@endif

<!-- Warning flash message from middleware -->
@if(session('warning'))
<div class="ui-note is-brand">
    <i class="fas fa-exclamation-circle text-orange-500"></i>
    <div>{{ session('warning') }}</div>
</div>
@endif

@php
    $dlData = $usageData['daily_leads'];
    $totalLeads = max(0, (int) $stats['total_leads']);
    $pct = fn ($n) => $totalLeads > 0 ? min(100, (int) round($n / $totalLeads * 100)) : 0;
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $restricted = auth()->user()->hasRestrictedAccess();
    $statusMeta = [
        'not_contacted' => ['Pending', 'bg-amber-50 text-amber-700 border-amber-200'],
        'contacted' => ['Contacted', 'bg-blue-50 text-blue-700 border-blue-200'],
        'responded' => ['Responded', 'bg-teal-50 text-teal-700 border-teal-200'],
        'follow_up' => ['Follow Up', 'bg-purple-50 text-purple-700 border-purple-200'],
        'converted' => ['Converted', 'bg-green-50 text-green-700 border-green-200'],
        'closed' => ['Closed', 'bg-slate-100 text-slate-600 border-slate-200'],
    ];
@endphp
@if($currentPlan)
@php
    // The rows that apply to this user: admin "Custom features" first, else the package; locked = not included
    $featureRows  = app(\App\Services\FeatureOverrides::class);
    $pfRow        = function (string $key) use ($featureRows, $currentPlan) {
        $row = $featureRows->row($currentPlan['subscription'], $key);
        return ($row && empty($row->is_locked)) ? $row : null;
    };
    $dl           = $usageData['daily_leads'];
    $dlFeature    = $pfRow('daily_leads_limit');
    $exportLeads  = $pfRow('export_leads');
    $maxDevices   = $pfRow('max_devices');
    $prioritySupp = $pfRow('priority_support');
    $pfLock       = fn (string $key) => \App\Services\FeatureOverrides::lockMessageOf($featureRows->row($currentPlan['subscription'], $key));
    $dlLock       = $pfLock('daily_leads_limit');
    $exportLock   = $pfLock('export_leads');
    $devicesLock  = $pfLock('max_devices');
    $supportLock  = $pfLock('priority_support');
@endphp
@else
@php $dlLock = $exportLock = $devicesLock = $supportLock = null; $exportLeads = $maxDevices = $prioritySupp = null; @endphp
@endif


@php
    $emailLock = \App\Services\PlanFeatures::lockMessage(auth()->user(), 'gmail_sending_enabled');
    $illustration = file_exists(public_path('assets/images/dashboard-illustration.png'));
@endphp

<!-- Welcome banner + how it works -->
<section class="dbx-banner" aria-labelledby="dashboard-welcome-title">
    <div class="flex items-center gap-6">
        <div class="min-w-0 flex-1">
            <h1 id="dashboard-welcome-title" class="text-[28px] sm:text-[36px] font-extrabold leading-tight tracking-[-0.02em] text-[#111827]">Welcome back, <span class="text-[#F97316]">{{ $user->first_name }}!</span></h1>
            <p class="mt-1.5 text-[15px] sm:text-[16px] text-[#475569]">
                @if($dlData['is_unlimited'])
                    {{ $ai ? 'You have unlimited leads today. Find them, let AI qualify them, and start the conversation.' : 'You have unlimited leads today. Find them and start the conversation.' }}
                @elseif($dlData['has_plan'])
                    You have {{ number_format($dlData['remaining']) }} leads remaining today out of {{ number_format($dlData['limit']) }}.
                @else
                    Start searching for businesses to build your lead database.
                @endif
            </p>
    <ol class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 xl:gap-7">
        @foreach([
            ['fa-puzzle-piece', '1. Add Extension', 'Install the Chrome Extension and pull businesses from Google Maps', null],
            ['fa-search', '2. Find & Save Leads', 'Search by keyword & location, then save contacts, ratings & websites', null],
            ['fa-wand-magic-sparkles', '3. Analyze with AI', 'Get insights and a personalized email', $aiLock],
            ['fa-paper-plane', '4. Send & Follow Up', 'Approve drafts, send from your own inbox and track every reply', $emailLock],
        ] as $si => [$sIcon, $sTitle, $sText, $sLock])
            <li class="dbx-step">
                <span class="dbx-step-i" aria-hidden="true"><i class="fas {{ $sIcon }}"></i></span>
                <span class="min-w-0">
                    <span class="flex flex-wrap items-center gap-1.5 text-[14px] font-semibold text-[#111827]">{{ $sTitle }}@if($sLock)<span class="dbx-soon" title="{{ $sLock }}">soon</span>@endif</span>
                    <span class="block text-[12px] text-[#64748B] leading-snug mt-0.5">{{ $sText }}</span>
                </span>
                @if($si < 3)<span class="dbx-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>@endif
            </li>
        @endforeach
    </ol>
        </div>
        @if($illustration)
            <img src="{{ asset('public/assets/images/dashboard-illustration.png') }}" alt="" width="290" height="230"
                 class="hidden 2xl:block w-[250px] h-auto flex-shrink-0" style="mix-blend-mode: multiply;">
        @endif
    </div>
</section>

<!-- Lead pipeline -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 xl:gap-4">
    @foreach([
        ['Total Leads', $stats['total_leads'], 'fa-users', 'bg-[#FFF1E7] text-[#F97316]'],
        ['Contacted', $stats['contacted_leads'], 'fa-phone', 'bg-[#FFF1E7] text-[#F97316]'],
        ['Pending', $stats['pending_leads'], 'fa-hourglass-half', 'bg-[#FFF1E7] text-[#F97316]'],
        ['Converted', $stats['converted_leads'], 'fa-circle-check', 'bg-green-50 text-[#16A34A]'],
    ] as [$kLabel, $kValue, $kIcon, $kTint])
        <div class="dbx-card dbx-kpi">
            <span class="dbx-kpi-i {{ $kTint }}" aria-hidden="true"><i class="fas {{ $kIcon }}"></i></span>
            <div class="min-w-0">
                <p class="dbx-kpi-v">{{ number_format($kValue) }}</p>
                <p class="dbx-kpi-l">{{ $kLabel }}</p>
            </div>
        </div>
    @endforeach
</div>

<!-- AI Sales Assistant -->
@if($ai)
@php
    $aiLabels = ['website_audit' => ['Website audits', 'fa-magnifying-glass-chart'], 'email_draft' => ['Email drafts', 'fa-envelope-open-text'], 'offer_builder' => ['AI offers', 'fa-gift']];
@endphp
<section class="ui-card overflow-hidden" aria-labelledby="dashboard-ai-title" data-testid="dashboard-ai">
    <div class="ui-card-h !bg-[#111827] !border-[#111827]">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-white/10 text-[#FDBA74] flex items-center justify-center flex-shrink-0"><i class="fas fa-wand-magic-sparkles text-sm"></i></span>
            <div class="min-w-0">
                <h2 id="dashboard-ai-title" class="text-[14px] font-semibold text-white">AI Sales Assistant</h2>
                <p class="text-[11.5px] text-slate-300">Your AI researches each lead, picks the right offer and writes the first email. You stay in control: nothing is sent without your approval.</p>
            </div>
        </div>
        @if($ai['available'])
            <span class="ui-badge !bg-white/10 !border-white/20 !text-green-300"><span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>AI ready</span>
        @else
            <span class="ui-badge !bg-white/10 !border-white/20 !text-slate-300">AI not configured yet</span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
        {{-- 1. Profile readiness --}}
        <div class="p-4">
            <p class="ui-stat-l"><i class="fas fa-user-tie"></i>AI Sales Profile</p>
            <div class="flex items-baseline gap-1.5 mt-1">
                <span class="text-[22px] font-bold text-gray-900">{{ (int) $ai['completeness'] }}%</span>
                <span class="text-[12px] text-slate-500">complete</span>
            </div>
            <div class="ui-bar"><span style="width: {{ (int) $ai['completeness'] }}%"></span></div>
            <p class="text-[11.5px] text-slate-500 mt-2 leading-snug">
                @if($ai['completeness'] >= 100)
                    The AI knows your business, services and voice. Every email is on-brand.
                @else
                    The more the AI knows about what you sell, the sharper and more personal each email gets.
                @endif
            </p>
            <a href="{{ route('user.ai-profile.index') }}" class="ui-btn-light ui-sm mt-2.5">{{ $ai['completeness'] >= 100 ? 'Review profile' : 'Complete profile' }} <i class="fas fa-arrow-right text-[10px]"></i></a>
        </div>

        {{-- 2. Usage this month --}}
        <div class="p-4">
            <p class="ui-stat-l"><i class="fas fa-gauge-high"></i>AI usage · {{ now()->format('F') }}</p>
            <div class="space-y-2.5 mt-2">
                @foreach($ai['usage'] as $feature => $u)
                    @php
                        [$uLabel, $uIcon] = $aiLabels[$feature] ?? [$feature, 'fa-circle'];
                        $uPct = $u['limit'] > 0 ? min(100, (int) round($u['used'] / $u['limit'] * 100)) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between gap-2 text-[12px]">
                            <span class="text-slate-700"><i class="fas {{ $uIcon }} text-slate-400 w-4"></i>{{ $uLabel }}</span>
                            <span class="font-semibold text-gray-900 whitespace-nowrap">
                                @if(!empty($u['lock']))
                                    @include('user._lock-badge', ['message' => $u['lock'], 'compact' => true])
                                @elseif($u['limit'] === -1)
                                    {{ number_format($u['used']) }} <span class="font-normal text-slate-500">/ unlimited</span>
                                @elseif($u['limit'] === 0)
                                    <span class="font-normal text-slate-400">Not in your plan</span>
                                @else
                                    {{ number_format($u['used']) }} <span class="font-normal text-slate-500">/ {{ number_format($u['limit']) }}</span>
                                @endif
                            </span>
                        </div>
                        @if(!empty($u['lock']))
                            <p class="text-[11px] text-slate-500 mt-0.5">{{ $u['lock'] }}</p>
                        @elseif($u['limit'] > 0)
                            <div class="ui-bar !mt-1"><span class="{{ $uPct >= 85 ? 'is-hot' : '' }}" style="width: {{ $uPct }}%"></span></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 3. Work waiting for you --}}
        <div class="p-4">
            <p class="ui-stat-l"><i class="fas fa-inbox"></i>Ready for you</p>
            <div class="grid grid-cols-3 gap-2 mt-2">
                <div class="rounded-lg bg-[#FFF7ED] border border-[#FED7AA] px-2.5 py-2">
                    <p class="text-[18px] font-bold text-[#C2410C] leading-tight">{{ number_format($ai['drafts_to_review']) }}</p>
                    <p class="text-[10.5px] text-[#9A3412]">Drafts to review</p>
                </div>
                <div class="rounded-lg bg-slate-50 border border-slate-200 px-2.5 py-2">
                    <p class="text-[18px] font-bold text-gray-900 leading-tight">{{ number_format($ai['approved']) }}</p>
                    <p class="text-[10.5px] text-slate-500">Approved</p>
                </div>
                <div class="rounded-lg bg-slate-50 border border-slate-200 px-2.5 py-2">
                    <p class="text-[18px] font-bold text-gray-900 leading-tight">{{ number_format($ai['analyses']) }}</p>
                    <p class="text-[10.5px] text-slate-500">Leads analyzed</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 mt-3">
                @if($ai['drafts_to_review'] > 0)
                    <a href="{{ route('user.ai-outreach.index', ['review' => 'draft']) }}" class="ui-btn ui-sm"><i class="fas fa-pen-to-square"></i> Review drafts</a>
                @else
                    <a href="{{ route('user.leads') }}" class="ui-btn ui-sm"><i class="fas fa-wand-magic-sparkles"></i> Analyze leads with AI</a>
                @endif
                <a href="{{ route('user.ai-outreach.index') }}" class="ui-btn-light ui-sm">Open AI Outreach</a>
            </div>
        </div>
    </div>

    {{-- Every AI feature, one click away --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 p-3 bg-slate-50 border-t border-slate-100">
        <a href="{{ route('user.ai-outreach.index') }}" class="db-tile">
            <span class="db-tile-i"><i class="fas fa-magnifying-glass-chart"></i></span>
            <span class="min-w-0"><span class="block text-[12.5px] font-semibold text-gray-900">AI Website Audit</span><span class="block text-[11px] text-slate-500 truncate">Spot what each business is missing</span></span>
        </a>
        <a href="{{ route('user.ai-outreach.index') }}" class="db-tile">
            <span class="db-tile-i"><i class="fas fa-envelope-open-text"></i></span>
            <span class="min-w-0"><span class="block text-[12.5px] font-semibold text-gray-900">AI Email Drafts</span><span class="block text-[11px] text-slate-500 truncate">Personal first emails, in your voice</span></span>
        </a>
        @if($ai['offers_lock'])
            <div class="db-tile !bg-slate-50 cursor-not-allowed" title="{{ $ai['offers_lock'] }}" data-testid="locked-tile">
                <span class="db-tile-i !bg-slate-100 !text-slate-400"><i class="fas fa-lock"></i></span>
                <span class="min-w-0"><span class="block text-[12.5px] font-semibold text-slate-500">AI Offer Builder</span><span class="block text-[11px] text-slate-500 truncate">{{ $ai['offers_lock'] }}</span></span>
            </div>
        @else
            <a href="{{ route('user.ai-profile.index') }}#offers" class="db-tile">
                <span class="db-tile-i"><i class="fas fa-gift"></i></span>
                <span class="min-w-0"><span class="block text-[12.5px] font-semibold text-gray-900">AI Offer Builder</span><span class="block text-[11px] text-slate-500 truncate">Irresistible offers from your business details</span></span>
            </a>
        @endif
        @if($ai['email_on'])
            <a href="{{ route('user.email-sending.index') }}" class="db-tile">
                <span class="db-tile-i"><i class="fas fa-paper-plane"></i></span>
                <span class="min-w-0"><span class="block text-[12.5px] font-semibold text-gray-900">Send &amp; Track</span><span class="block text-[11px] text-slate-500 truncate">Send approved drafts, track replies</span></span>
            </a>
        @elseif($ai['email_lock'])
            <div class="db-tile !bg-slate-50 cursor-not-allowed" title="{{ $ai['email_lock'] }}" data-testid="locked-tile">
                <span class="db-tile-i !bg-slate-100 !text-slate-400"><i class="fas fa-lock"></i></span>
                <span class="min-w-0"><span class="block text-[12.5px] font-semibold text-slate-500">Send &amp; Track</span><span class="block text-[11px] text-slate-500 truncate">{{ $ai['email_lock'] }}</span></span>
            </div>
        @else
            <a href="{{ route('user.ai-profile.index') }}" class="db-tile">
                <span class="db-tile-i"><i class="fas fa-user-tie"></i></span>
                <span class="min-w-0"><span class="block text-[12.5px] font-semibold text-gray-900">Train your AI</span><span class="block text-[11px] text-slate-500 truncate">Services, audience and tone of voice</span></span>
            </a>
        @endif
    </div>
</section>
@elseif($aiLock)
<section class="dbx-card !bg-[#FFFAF6] !border-[#FBD9C4] px-5 py-5 sm:px-7 flex flex-col sm:flex-row sm:items-center gap-4" data-testid="dashboard-ai-locked" aria-labelledby="dashboard-ai-locked-title">
    <span class="w-14 h-14 rounded-2xl bg-[#FFF1E7] text-[#F97316] flex items-center justify-center flex-shrink-0 text-[22px]" aria-hidden="true"><i class="fas fa-wand-magic-sparkles"></i></span>
    <div class="min-w-0 flex-1">
        <h2 id="dashboard-ai-locked-title" class="flex flex-wrap items-center gap-2.5 text-[19px] font-bold text-[#111827]">
            AI Sales Assistant @include('user._lock-badge', ['message' => $aiLock, 'compact' => true])
        </h2>
        <p class="text-[14px] text-[#475569] mt-0.5">Let AI analyze businesses, write personalized emails and help you get replies.</p>
        <p class="text-[12px] text-[#64748B] mt-0.5">{{ $aiLock }}</p>
    </div>
    <span class="dbx-pill bg-[#F97316] text-white self-start sm:self-center" role="status">Coming Soon</span>
</section>
@endif

@unless($restricted)
<!-- Quick actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 xl:gap-4">
    @foreach([
        [route('user.search'), 'fa-search', 'Find Leads', 'Search for new businesses'],
        [route('user.leads'), 'fa-bookmark', 'My Leads', 'View your saved leads'],
        [route('user.lead-center.index'), 'fa-bullseye', 'Lead Center', 'Organise, import and prioritise'],
    ] as [$qUrl, $qIcon, $qTitle, $qText])
        <a href="{{ $qUrl }}" class="dbx-card dbx-quick">
            <span class="dbx-kpi-i !w-12 !h-12 !text-[19px] bg-[#FFF1E7] text-[#F97316]" aria-hidden="true"><i class="fas {{ $qIcon }}"></i></span>
            <span class="min-w-0">
                <span class="block text-[16px] font-semibold text-[#111827]">{{ $qTitle }}</span>
                <span class="block text-[13px] text-[#64748B]">{{ $qText }}</span>
            </span>
            <span class="dbx-go" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
        </a>
    @endforeach
</div>
@endunless

@if($currentPlan)
<!-- Current plan -->
<section class="dbx-card !bg-[#FFFAF6] !border-[#FBD9C4] px-5 py-5 sm:px-7" aria-labelledby="dashboard-plan-title">
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        <span class="w-14 h-14 rounded-2xl bg-[#FFF1E7] text-[#F97316] flex items-center justify-center flex-shrink-0 text-[22px]" aria-hidden="true"><i class="fas fa-crown"></i></span>
        <div class="min-w-0 flex-1">
            <h2 id="dashboard-plan-title" class="text-[19px] font-bold text-[#111827]">{{ $currentPlan['package']->name }}</h2>
            <p class="text-[14px] text-[#475569] mt-0.5">
                @if($currentPlan['end_date'])
                    Expires: <span class="{{ $currentPlan['end_date']->isPast() ? 'text-red-600 font-medium' : 'text-[#F97316] font-medium' }}">{{ $currentPlan['end_date']->format('d M Y') }}</span>
                    @if(!$currentPlan['end_date']->isPast())
                        ({{ $currentPlan['end_date']->diffForHumans() }})
                    @endif
                @else
                    <span class="text-green-700"><i class="fas fa-infinity mr-1" aria-hidden="true"></i>No expiry</span>
                @endif
            </p>
        </div>
        <a href="{{ route('user.subscription') }}" class="dbx-pill bg-white border border-[#F97316] text-[#111827] hover:bg-[#FFF7F2] self-start sm:self-center">View Plan <i class="fas fa-arrow-right text-[#F97316] text-[12px]" aria-hidden="true"></i></a>
    </div>

    <!-- What this plan includes (admin locks show as Locked) -->
    <dl class="grid grid-cols-2 lg:grid-cols-4 gap-2 mt-4 pt-4 border-t border-[#F6E3D6] text-[12.5px]">
        <div class="bg-white rounded-xl border border-[#F1E6DE] px-3 py-2">
            <dt class="text-[#64748B]"><i class="fas fa-map-marker-alt text-[#F97316] mr-1"></i>Daily Leads Limit</dt>
            <dd class="font-semibold text-[#111827] mt-0.5">
                @if($dlLock)
                    @include('user._lock-badge', ['message' => $dlLock, 'compact' => true])
                @elseif($dlData['is_unlimited'])
                    Unlimited
                @else
                    {{ number_format($dlData['used']) }} <span class="font-normal text-[#64748B]">/ {{ number_format($dlData['limit']) }} today</span>
                @endif
            </dd>
        </div>
        <div class="bg-white rounded-xl border border-[#F1E6DE] px-3 py-2">
            <dt class="text-[#64748B]"><i class="fas fa-download text-[#F97316] mr-1"></i>Export Leads</dt>
            <dd class="font-semibold text-[#111827] mt-0.5">
                @if($exportLock)
                    @include('user._lock-badge', ['message' => $exportLock])
                @elseif($exportLeads && $exportLeads->feature_value === 'unlimited')
                    Unlimited
                @elseif($exportLeads && $exportLeads->feature_value !== 'false')
                    {{ number_format((int)$exportLeads->feature_value) }} <span class="font-normal text-[#64748B]">/mo</span>
                @else
                    <span class="font-normal text-slate-400">Not included</span>
                @endif
            </dd>
        </div>
        <div class="bg-white rounded-xl border border-[#F1E6DE] px-3 py-2">
            <dt class="text-[#64748B]"><i class="fas fa-laptop text-[#F97316] mr-1"></i>Devices Access</dt>
            <dd class="font-semibold text-[#111827] mt-0.5">
                @if($devicesLock)
                    @include('user._lock-badge', ['message' => $devicesLock, 'compact' => true])
                @elseif($maxDevices)
                    {{ $myDevices->count() }} <span class="font-normal text-[#64748B]">/ {{ ($maxDevices->is_unlimited || $maxDevices->feature_value === 'unlimited') ? '∞' : $maxDevices->feature_value }} {{ (int)$maxDevices->feature_value === 1 ? 'Device' : 'Devices' }}</span>
                @else
                    <span class="font-normal text-slate-400">—</span>
                @endif
            </dd>
        </div>
        <div class="bg-white rounded-xl border border-[#F1E6DE] px-3 py-2">
            <dt class="text-[#64748B]"><i class="fas fa-headset text-[#F97316] mr-1"></i>Priority Support</dt>
            <dd class="mt-0.5">
                @if($supportLock)
                    @include('user._lock-badge', ['message' => $supportLock, 'compact' => true])
                @elseif($prioritySupp && $prioritySupp->feature_value === 'true')
                    <span class="ui-badge is-ok"><i class="fas fa-check text-[9px]"></i>Included</span>
                @else
                    <span class="ui-badge"><i class="fas fa-xmark text-[9px]"></i>Not included</span>
                @endif
            </dd>
        </div>
    </dl>
</section>
@endif

@if($stats['total_leads'] == 0 && !$restricted)
<!-- First steps for new users -->
<section class="ui-card p-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-start gap-3 min-w-0">
        <span class="ui-head-icon !w-9 !h-9" aria-hidden="true"><i class="fas fa-rocket"></i></span>
        <div class="min-w-0">
            <h3 class="text-[14px] font-semibold text-gray-900">Get Started with Lead Generation</h3>
            <p class="text-[12.5px] text-slate-500 mt-0.5">Run your first search, like "dentists in Dubai" or "travel agencies in Lahore".</p>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('user.tutorials') }}" class="ui-btn-light ui-sm"><i class="fas fa-play-circle text-red-600" aria-hidden="true"></i>Playlist</a>
        <a href="https://chat.whatsapp.com/JoXwhqeKW5sCRGuovBNQm8" target="_blank" rel="noopener noreferrer" class="ui-btn-light ui-sm"><i class="fab fa-whatsapp text-green-700" aria-hidden="true"></i>Community</a>
        <a href="{{ route('user.search') }}" class="ui-btn ui-sm"><i class="fas fa-search" aria-hidden="true"></i>Start First Search</a>
    </div>
</section>
@endif
</main>
@endsection
