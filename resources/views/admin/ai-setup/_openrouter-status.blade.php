{{-- OpenRouter live key status. $k = OpenRouterAiClient::keyInfo() (null = no key / unreachable), $today = this app's OpenRouter stats today --}}
<div class="pt-4 border-t border-gray-100" data-testid="openrouter-status">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-semibold text-gray-900">Free models: limits &amp; usage <span class="text-[11px] font-normal text-gray-500">(live from OpenRouter, refreshed every minute)</span></h3>
        <a href="https://openrouter.ai/activity" target="_blank" rel="noopener" class="text-xs text-primary-600 underline">OpenRouter activity →</a>
    </div>

    @if($k === null)
        <p class="mt-2 text-xs text-gray-500">Save a key to see the live free-request allowance here.</p>
    @elseif(isset($k['error']))
        <p class="mt-2 text-xs bg-red-50 border border-red-200 text-red-700 rounded-lg px-3 py-2" role="alert">OpenRouter rejected the saved key ({{ $k['error'] === 'authentication' ? 'invalid or disabled key' : $k['error'] }}).</p>
    @else
        @php
            $pct = $k['free_limit'] ? min(100, (int) round(($k['free_used'] ?? 0) / $k['free_limit'] * 100)) : 0;
            $barColor = $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-amber-500' : 'bg-green-500');
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3 text-sm">
            <div class="border border-gray-200 rounded-xl p-3">
                <p class="text-xs text-gray-500">Free requests today</p>
                <p class="text-lg font-semibold text-gray-900">{{ $k['free_used'] ?? '?' }} <span class="text-sm font-normal text-gray-500">/ {{ $k['free_limit'] ?? '?' }}</span></p>
                <div class="h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden"><div class="h-full {{ $barColor }}" style="width: {{ $pct }}%"></div></div>
                <p class="text-[11px] text-gray-500 mt-1"><strong>{{ $k['free_remaining'] ?? '?' }}</strong> left · max 20 per minute</p>
            </div>
            <div class="border border-gray-200 rounded-xl p-3">
                <p class="text-xs text-gray-500">Sent by this app today</p>
                <p class="text-lg font-semibold text-gray-900">{{ $today['calls'] }}</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ $today['ok'] }} ok · {{ $today['failed'] }} failed · {{ number_format($today['tin'] + $today['tout']) }} tokens</p>
            </div>
            <div class="border border-gray-200 rounded-xl p-3">
                <p class="text-xs text-gray-500">Credit spent (paid models)</p>
                <p class="text-lg font-semibold text-gray-900">${{ number_format($k['usage'], 4) }}@if($k['limit'] !== null) <span class="text-sm font-normal text-gray-500">/ ${{ number_format($k['limit'], 2) }} key limit</span>@endif</p>
                <p class="text-[11px] text-gray-500 mt-1">Today ${{ number_format($k['usage_daily'], 4) }} · this month ${{ number_format($k['usage_monthly'], 4) }}</p>
            </div>
        </div>

        @if($k['is_free_tier'])
            <p class="mt-3 text-xs bg-blue-50 border border-blue-200 text-blue-800 rounded-lg px-3 py-2">No credit bought on this account yet → <strong>{{ $k['free_limit'] ?? 50 }} free requests per day</strong>. Buying $10 of credit once raises the free-model limit to <strong>1,000 per day</strong> (free models still cost $0).</p>
        @endif
        @if(($k['free_remaining'] ?? 1) === 0)
            <p class="mt-2 text-xs bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-3 py-2" role="alert">Today's free requests are used up. AI requests will fail with "busy" (429) until the daily reset.</p>
        @endif
    @endif

    <ul class="text-[11px] text-gray-500 mt-3 space-y-1 list-disc list-inside">
        <li>Only models whose id ends in <span class="font-mono">:free</span> (or <span class="font-mono">openrouter/free</span>, which picks an available free model per request) are free. Any other model is charged to the OpenRouter credit.</li>
        <li>For JSON features (analysis, drafts, offers) requests are only sent to models that support structured output. The model that actually answered is shown in the Usage tab.</li>
        <li>Free models are slower and less reliable than paid ones, and their providers may log prompts. Check this is acceptable for lead data.</li>
        <li>One app request can use 2 free requests when an answer fails our checks and is retried.</li>
    </ul>
</div>
