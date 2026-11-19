{{-- Gemini free-tier limits and today's usage. $g = AiSetupController::geminiFreeUsage() --}}
@php
    $pct = fn (int $used, ?int $limit) => $limit ? min(100, (int) round($used / $limit * 100)) : 0;
    $bar = fn (int $p) => $p >= 90 ? 'bg-red-500' : ($p >= 70 ? 'bg-amber-500' : 'bg-green-500');
    $dayPct = $pct($g['today'], $g['rpd']);
    $minPct = $pct($g['minute'], $g['rpm']);
@endphp
<div class="mt-5 pt-4 border-t border-gray-100" data-testid="gemini-free-tier">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-semibold text-gray-900">Free tier: limits &amp; usage</h3>
        <span class="text-[11px] text-gray-500">Model <span class="font-mono">{{ $g['model'] ?: '—' }}</span> · daily quota resets {{ $g['resets_at']->format('M j, H:i') }} ({{ config('app.timezone') }}) = midnight Pacific</span>
    </div>

    @if($g['rpd'] === 0)
        <p class="mt-2 text-xs bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-3 py-2">This model has <strong>no free tier</strong>; every request is billed. Pick e.g. <span class="font-mono">gemini-3.5-flash-lite</span> for free use.</p>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3 text-sm">
        <div class="border border-gray-200 rounded-xl p-3">
            <p class="text-xs text-gray-500">Requests today</p>
            <p class="text-lg font-semibold text-gray-900">{{ number_format($g['today']) }} <span class="text-sm font-normal text-gray-500">/ {{ $g['rpd'] === null ? '?' : number_format($g['rpd']) }}</span></p>
            @if($g['rpd'])
                <div class="h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden"><div class="h-full {{ $bar($dayPct) }}" style="width: {{ $dayPct }}%"></div></div>
                <p class="text-[11px] text-gray-500 mt-1">{{ number_format(max(0, $g['rpd'] - $g['today'])) }} left today · {{ $g['today_ok'] }} successful</p>
            @else
                <p class="text-[11px] text-gray-500 mt-1">{{ $g['today_ok'] }} successful</p>
            @endif
        </div>
        <div class="border border-gray-200 rounded-xl p-3">
            <p class="text-xs text-gray-500">Last 60 seconds</p>
            <p class="text-lg font-semibold text-gray-900">{{ $g['minute'] }} <span class="text-sm font-normal text-gray-500">/ {{ $g['rpm'] === null ? '?' : $g['rpm'] }} per min</span></p>
            @if($g['rpm'])
                <div class="h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden"><div class="h-full {{ $bar($minPct) }}" style="width: {{ $minPct }}%"></div></div>
            @endif
            <p class="text-[11px] text-gray-500 mt-1">Above this Google answers "busy" (429); jobs retry later.</p>
        </div>
        <div class="border border-gray-200 rounded-xl p-3">
            <p class="text-xs text-gray-500">Tokens today</p>
            <p class="text-lg font-semibold text-gray-900">{{ number_format($g['today_tokens_in'] + $g['today_tokens_out']) }}</p>
            <p class="text-[11px] text-gray-500 mt-1">{{ number_format($g['today_tokens_in']) }} input / {{ number_format($g['today_tokens_out']) }} output (incl. thinking)</p>
        </div>
    </div>

    @if($g['last_error'] === 'billing')
        <p class="mt-3 text-xs bg-red-50 border border-red-200 text-red-700 rounded-lg px-3 py-2" role="alert">Google rejected today's requests with <strong>402: prepayment credits depleted</strong>. This key's project is linked to a <em>paid</em> billing account with $0 balance, so the free tier does not apply. Either add credit in AI Studio → Billing, or unlink billing from the project (or use a key from a project without billing) to use the free tier.</p>
    @elseif($g['last_error'] === 'rate_limited')
        <p class="mt-3 text-xs bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-3 py-2">Google answered "rate limited" (429) today: the per-minute or daily free quota was reached. Daily quota comes back at midnight Pacific.</p>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 text-sm">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1" for="gemini_free_rpm">Free requests per minute (RPM)</label>
            <input type="number" min="0" id="gemini_free_rpm" name="gemini[free_rpm]" value="{{ old('gemini.free_rpm', $g['admin_rpm']) }}"
                   placeholder="{{ $g['source'] === 'admin' || $g['rpm'] === null ? 'unknown' : $g['rpm'] . ' (approx. default)' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1" for="gemini_free_rpd">Free requests per day (RPD)</label>
            <input type="number" min="0" id="gemini_free_rpd" name="gemini[free_rpd]" value="{{ old('gemini.free_rpd', $g['admin_rpd']) }}"
                   placeholder="{{ $g['source'] === 'admin' || $g['rpd'] === null ? 'unknown' : $g['rpd'] . ' (approx. default)' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
    </div>

    <ul class="text-[11px] text-gray-500 mt-3 space-y-1 list-disc list-inside">
        <li>Google no longer publishes fixed free-tier numbers; your project's exact limits are at <a href="https://aistudio.google.com/rate-limit" target="_blank" rel="noopener" class="text-primary-600 underline">AI Studio → Rate limits</a> (usage: <a href="https://aistudio.google.com/usage" target="_blank" rel="noopener" class="text-primary-600 underline">AI Studio → Usage</a>). Copy them into the fields above; empty = approximate values (Flash-Lite ≈ 15/min, 500/day; Flash ≈ 5/min, 20/day; Gemini 4 Carbon/Argon/Neon and 3.1 Pro: no free tier).</li>
        <li>Limits are <strong>per Google project</strong>, not per key, and the free tier only applies to projects <strong>without a linked billing account</strong>.</li>
        <li>On the free tier Google may use the prompts (lead websites, offers) to improve its products. Use a paid project if that is not acceptable.</li>
        <li>Counts here are requests this app sent with Gemini as provider; an answer that fails our checks is retried once and may use 2 requests. "Test saved settings" is not counted.</li>
    </ul>
</div>
