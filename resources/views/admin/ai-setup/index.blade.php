@extends('layouts.admin')

@section('title', 'AI Setup')

@section('content')
@php
    // Per-provider texts for the setup tabs
    $meta = [
        'anthropic' => ['short' => 'Claude', 'icon' => 'fa-brain', 'color' => 'orange', 'keyHint' => 'sk-ant-...', 'keyUrl' => 'https://console.anthropic.com/settings/keys', 'keyWhere' => 'console.anthropic.com → API keys', 'effort' => 'Effort', 'about' => 'Paid. Strong writing and analysis.'],
        'openai' => ['short' => 'ChatGPT', 'icon' => 'fa-comment-dots', 'color' => 'emerald', 'keyHint' => 'sk-...', 'keyUrl' => 'https://platform.openai.com/api-keys', 'keyWhere' => 'platform.openai.com → API keys', 'effort' => 'Reasoning effort', 'about' => 'Paid. OpenAI Responses API.'],
        'gemini' => ['short' => 'Gemini', 'icon' => 'fa-gem', 'color' => 'blue', 'keyHint' => 'AIza... / AQ....', 'keyUrl' => 'https://aistudio.google.com/apikey', 'keyWhere' => 'aistudio.google.com → Get API key', 'effort' => 'Thinking level', 'about' => 'Free tier on Flash / Flash-Lite models (Google projects without billing).'],
        'openrouter' => ['short' => 'OpenRouter', 'icon' => 'fa-route', 'color' => 'violet', 'keyHint' => 'sk-or-v1-...', 'keyUrl' => 'https://openrouter.ai/settings/keys', 'keyWhere' => 'openrouter.ai → Keys', 'effort' => 'Reasoning effort', 'about' => 'One key, many models. ":free" models cost $0 (50 requests/day, 1000/day after buying $10 credit once).'],
    ];
    $statusPill = fn (?string $s) => match ($s) {
        'success' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-700',
        'refused' => 'bg-amber-100 text-amber-800',
        'rejected' => 'bg-gray-100 text-gray-600',
        default => 'bg-blue-100 text-blue-700',
    };
    $errorText = [
        'billing' => 'No credit left (402)', 'rate_limited' => 'Rate / daily limit (429)', 'authentication' => 'Invalid key',
        'bad_request' => 'Request rejected (wrong model?)', 'overloaded' => 'Provider busy', 'server_error' => 'Provider error',
        'timeout' => 'Timed out', 'connection' => 'Could not connect', 'empty_response' => 'Empty answer', 'invalid_output' => 'Answer failed checks',
        'refusal' => 'Refused', 'quota_exceeded' => 'User plan quota used up', 'feature_not_in_plan' => 'Not in user plan', 'exception' => 'App error',
    ];
    $okRate = fn (array $s) => $s['calls'] ? (int) round($s['ok'] / $s['calls'] * 100) : null;
    $startTab = old('tab') ?: ($active !== 'off' && isset($providers[$active]) ? $active : 'anthropic');
    $activeTab = $active !== 'off' && isset($providers[$active]) ? $active : 'anthropic';
    $maxDaily = max(1, max(array_column($daily, 'calls')));
    $card = 'text-left bg-white border border-gray-200 rounded-2xl p-4 hover:border-primary-300 hover:shadow-sm transition';
@endphp

<div class="p-4 lg:p-8 max-w-6xl mx-auto space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">AI Setup</h1>
            <p class="text-sm text-gray-600 mt-1">One provider handles every AI feature (website analysis, email drafts, offers, campaigns). Set it up in its tab, make it active, and watch usage here.</p>
        </div>
        <button type="button" data-tab-link="usage" class="text-sm px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50"><i class="fas fa-chart-bar mr-1"></i> Usage details</button>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm" role="alert">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @unless($masterSwitch)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm">AI is switched off on the server (<code>AI_ENABLED=false</code> in .env). Nothing below takes effect until it is turned on there.</div>
    @endunless

    {{-- Summary cards: click for details --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3" data-testid="ai-summary">
        <button type="button" data-tab-link="{{ $activeTab }}" class="{{ $card }}">
            <p class="text-xs text-gray-500">Active AI</p>
            @if($active !== 'off' && isset($providers[$active]))
                <p class="text-lg font-semibold text-gray-900 mt-1"><i class="fas {{ $meta[$active]['icon'] }} text-{{ $meta[$active]['color'] }}-500 mr-1"></i>{{ $meta[$active]['short'] }}</p>
                <p class="text-[11px] text-gray-500 font-mono truncate">{{ $providers[$active]['model'] }}</p>
            @else
                <p class="text-lg font-semibold text-gray-500 mt-1">Off</p>
                <p class="text-[11px] text-gray-500">AI features are disabled</p>
            @endif
        </button>
        <button type="button" data-tab-link="usage" class="{{ $card }}">
            <p class="text-xs text-gray-500">Requests today</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($totals['today']['calls']) }}</p>
            <p class="text-[11px]"><span class="text-green-700">{{ $totals['today']['ok'] }} ok</span> · <span class="{{ $totals['today']['failed'] ? 'text-red-600' : 'text-gray-500' }}">{{ $totals['today']['failed'] }} failed</span></p>
        </button>
        <button type="button" data-tab-link="usage" class="{{ $card }}">
            <p class="text-xs text-gray-500">Tokens today</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($totals['today']['tin'] + $totals['today']['tout']) }}</p>
            <p class="text-[11px] text-gray-500">{{ number_format($totals['today']['tin']) }} in / {{ number_format($totals['today']['tout']) }} out</p>
        </button>
        @if(isset($openRouterKey['free_limit']))
            <button type="button" data-tab-link="openrouter" class="{{ $card }}">
                <p class="text-xs text-gray-500">OpenRouter free left today</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $openRouterKey['free_remaining'] }} <span class="text-sm font-normal text-gray-500">/ {{ $openRouterKey['free_limit'] }}</span></p>
                <p class="text-[11px] text-gray-500">{{ $openRouterKey['free_used'] }} used · live from OpenRouter</p>
            </button>
        @else
            <button type="button" data-tab-link="usage" class="{{ $card }}">
                <p class="text-xs text-gray-500">Last 30 days</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($totals['month']['calls']) }}</p>
                <p class="text-[11px] text-gray-500">{{ $okRate($totals['month']) === null ? 'no requests' : $okRate($totals['month']) . '% successful' }}</p>
            </button>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.ai-setup.update') }}" autocomplete="off" id="ai-setup-form">
        @csrf @method('PUT')
        <input type="hidden" name="tab" id="ai-tab-input" value="{{ $startTab }}">

        {{-- Active provider switch --}}
        <section class="bg-white border border-gray-200 rounded-2xl p-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-gray-900 mr-1">Active provider:</span>
                @foreach(array_merge(\App\Services\Ai\AiSettings::PROVIDERS, ['off' => 'Off']) as $key => $label)
                    @php $checked = old('active_provider', $active) === $key; $ready = $key === 'off' || $providers[$key]['key_source']; @endphp
                    <label class="ai-pill inline-flex items-center gap-2 border rounded-full px-3 py-1.5 text-sm cursor-pointer {{ $checked ? 'border-primary-500 bg-primary-50 text-primary-800 font-semibold' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                        <input type="radio" name="active_provider" value="{{ $key }}" class="sr-only" @checked($checked)>
                        @if($key !== 'off')<i class="fas {{ $meta[$key]['icon'] }} text-{{ $meta[$key]['color'] }}-500"></i>@endif
                        {{ $key === 'off' ? 'Off (no AI)' : $meta[$key]['short'] }}
                        @unless($ready)<span class="text-[10px] text-gray-400">(no key)</span>@endunless
                    </label>
                @endforeach
                <button class="ml-auto px-4 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-semibold"><i class="fas fa-save mr-1"></i>Save</button>
            </div>
        </section>

        {{-- Tabs --}}
        <div class="mt-6 border-b border-gray-200 flex gap-1 overflow-x-auto" role="tablist">
            @foreach($providers as $key => $p)
                <button type="button" role="tab" data-tab="{{ $key }}" class="ai-tab whitespace-nowrap px-4 py-2.5 text-sm font-medium border-b-2 -mb-px border-transparent text-gray-600 hover:text-gray-900">
                    <i class="fas {{ $meta[$key]['icon'] }} text-{{ $meta[$key]['color'] }}-500 mr-1"></i>{{ $meta[$key]['short'] }}
                    @if($active === $key)<span class="ml-1 inline-block w-2 h-2 rounded-full bg-green-500" title="Active"></span>
                    @elseif(!$p['key_source'])<span class="ml-1 text-[10px] text-gray-400">setup</span>@endif
                </button>
            @endforeach
            <button type="button" role="tab" data-tab="usage" class="ai-tab whitespace-nowrap px-4 py-2.5 text-sm font-medium border-b-2 -mb-px border-transparent text-gray-600 hover:text-gray-900"><i class="fas fa-chart-bar text-gray-500 mr-1"></i>Usage</button>
        </div>

        {{-- One panel per provider --}}
        @foreach($providers as $key => $p)
            @php $m = $meta[$key]; @endphp
            <section class="ai-panel hidden bg-white border border-t-0 border-gray-200 rounded-b-2xl p-5 space-y-5" data-panel="{{ $key }}" data-testid="provider-{{ $key }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $p['label'] }}</h2>
                        <p class="text-xs text-gray-500">{{ $m['about'] }}</p>
                    </div>
                    <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $active === $key ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $active === $key ? '● Active: handles all AI requests' : 'Inactive' }}</span>
                </div>

                {{-- Mini usage --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div class="bg-gray-50 rounded-xl p-3"><p class="text-[11px] text-gray-500">Today</p><p class="font-semibold text-gray-900">{{ $p['today']['calls'] }} requests</p><p class="text-[11px] text-gray-500">{{ $p['today']['ok'] }} ok · {{ $p['today']['failed'] }} failed</p></div>
                    <div class="bg-gray-50 rounded-xl p-3"><p class="text-[11px] text-gray-500">Last 30 days</p><p class="font-semibold text-gray-900">{{ $p['usage']['calls'] }} requests</p><p class="text-[11px] text-gray-500">{{ $okRate($p['usage']) === null ? '—' : $okRate($p['usage']) . '% successful' }}</p></div>
                    <div class="bg-gray-50 rounded-xl p-3"><p class="text-[11px] text-gray-500">Tokens (30 days)</p><p class="font-semibold text-gray-900">{{ number_format($p['usage']['tin'] + $p['usage']['tout']) }}</p><p class="text-[11px] text-gray-500">{{ number_format($p['usage']['tin']) }} in / {{ number_format($p['usage']['tout']) }} out</p></div>
                    <div class="bg-gray-50 rounded-xl p-3"><p class="text-[11px] text-gray-500">Last request</p>
                        @if($p['last_call'])
                            <p><span class="text-[11px] px-2 py-0.5 rounded-full {{ $statusPill($p['last_call']->status) }}">{{ $p['last_call']->status }}</span> <span class="text-[11px] text-gray-500">{{ $p['last_call']->created_at?->diffForHumans() }}</span></p>
                            <p class="text-[11px] text-gray-500 truncate">{{ $p['last_call']->error_type ? ($errorText[$p['last_call']->error_type] ?? $p['last_call']->error_type) : $p['last_call']->model }}</p>
                        @else
                            <p class="font-semibold text-gray-400">None yet</p>
                        @endif
                    </div>
                </div>

                {{-- Step 1: key --}}
                <div class="flex gap-3">
                    <span class="flex-none w-7 h-7 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">1</span>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-900 mb-1" for="{{ $key }}_api_key">API key</label>
                        <input type="password" id="{{ $key }}_api_key" name="{{ $key }}[api_key]" autocomplete="new-password"
                               placeholder="{{ $p['key_source'] ? 'Saved. Leave empty to keep the current key' : $m['keyHint'] }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm">
                        <p class="text-xs text-gray-500 mt-1">
                            @if($p['key_source'] === 'admin') <i class="fas fa-lock text-green-600"></i> Saved key ending in <strong>{{ $p['key_last4'] }}</strong> (stored encrypted).
                            @elseif($p['key_source'] === 'env') Using the key from the server .env (ending in <strong>{{ $p['key_last4'] }}</strong>). A key saved here replaces it.
                            @else No key yet.
                            @endif
                            Get one at <a href="{{ $m['keyUrl'] }}" target="_blank" rel="noopener" class="text-primary-600 underline">{{ $m['keyWhere'] }}</a>.
                        </p>
                        @if($p['key_source'] === 'admin')
                            <label class="inline-flex items-center gap-2 text-xs text-gray-700 mt-1"><input type="checkbox" name="{{ $key }}[remove_key]" value="1"> Remove the saved key</label>
                        @endif
                    </div>
                </div>

                {{-- Step 2: model --}}
                <div class="flex gap-3">
                    <span class="flex-none w-7 h-7 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">2</span>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-900 mb-1" for="{{ $key }}_model">Model</label>
                        <input type="text" id="{{ $key }}_model" name="{{ $key }}[model]" list="{{ $key }}_models" value="{{ old($key . '.model', $p['admin']['model']) }}"
                               placeholder="{{ $p['model'] }} (default)" class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm">
                        <datalist id="{{ $key }}_models">@foreach(\App\Services\Ai\AiSettings::MODEL_SUGGESTIONS[$key] as $mm)<option value="{{ $mm }}">@endforeach</datalist>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @foreach(\App\Services\Ai\AiSettings::MODEL_SUGGESTIONS[$key] as $mm)
                                <button type="button" data-fill="{{ $key }}_model" data-value="{{ $mm }}" class="text-[11px] font-mono px-2 py-1 rounded-md border {{ $p['model'] === $mm ? 'border-primary-400 bg-primary-50 text-primary-800' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">{{ $mm }}@if($mm === 'openrouter/free') <span class="text-green-700">· auto-picks a free model</span>@endif</button>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-1">In use: <span class="font-mono">{{ $p['model'] ?: '—' }}</span>
                            @if($key === 'openrouter') · All free models: <a href="https://openrouter.ai/models?output_modalities=text&max_price=0" target="_blank" rel="noopener" class="text-primary-600 underline">openrouter.ai/models</a> (ids ending in <span class="font-mono">:free</span>)@endif
                        </p>
                    </div>
                </div>

                {{-- Step 3: save, activate, test --}}
                <div class="flex gap-3">
                    <span class="flex-none w-7 h-7 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">3</span>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">Save, make active, test</p>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <button type="submit" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-800 hover:bg-gray-50"><i class="fas fa-save mr-1"></i> Save</button>
                            @if($active !== $key)
                                <button type="submit" name="activate" value="{{ $key }}" class="px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold"><i class="fas fa-bolt mr-1"></i> Save &amp; make active</button>
                            @endif
                            <button type="button" onclick="aiTest('{{ $key }}', this)" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-800 hover:bg-gray-50"><i class="fas fa-plug mr-1"></i> Test saved settings</button>
                        </div>
                        <p id="ai-test-{{ $key }}" class="text-xs mt-2" role="status"></p>
                    </div>
                </div>

                {{-- Advanced --}}
                <details class="border border-gray-200 rounded-xl">
                    <summary class="cursor-pointer px-4 py-2.5 text-sm font-medium text-gray-700">Advanced: {{ strtolower($m['effort']) }} &amp; output limit</summary>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 pt-1 text-sm">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1" for="{{ $key }}_effort">{{ $m['effort'] }}</label>
                            <select id="{{ $key }}_effort" name="{{ $key }}[effort]" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                                <option value="">Default ({{ $p['effort'] ?: 'model default' }})</option>
                                @foreach(\App\Services\Ai\AiSettings::EFFORTS[$key] as $e)
                                    <option value="{{ $e }}" @selected(old($key . '.effort', $p['admin']['effort']) === $e)>{{ $e }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Higher = more careful but slower (and costlier on paid models).</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1" for="{{ $key }}_max_tokens">Max output tokens</label>
                            <input type="number" min="256" max="128000" id="{{ $key }}_max_tokens" name="{{ $key }}[max_tokens]" value="{{ old($key . '.max_tokens', $p['admin']['max_tokens']) }}"
                                   placeholder="{{ $p['max_tokens'] }} (default)" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Hard cap per request incl. reasoning. Features ask for up to {{ number_format((int) config('ai.analysis.max_tokens.analysis', 16000)) }}; too low cuts answers off. Empty = no extra cap.</p>
                        </div>
                    </div>
                </details>

                @if($key === 'gemini')
                    @include('admin.ai-setup._gemini-free-tier', ['g' => $geminiFree])
                @elseif($key === 'openrouter')
                    @include('admin.ai-setup._openrouter-status', ['k' => $openRouterKey, 'today' => $p['today']])
                @endif
            </section>
        @endforeach
    </form>

    {{-- Usage --}}
    <section class="ai-panel hidden space-y-5" data-panel="usage" data-testid="ai-usage">
        <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <h2 class="text-base font-semibold text-gray-900">Requests per day (last 14 days)</h2>
            <div class="flex items-end gap-1.5 h-36 mt-4">
                @foreach($daily as $d => $v)
                    <div class="flex-1 flex flex-col items-center justify-end h-full" title="{{ \Illuminate\Support\Carbon::parse($d)->format('M j') }}: {{ $v['calls'] }} requests, {{ $v['ok'] }} ok">
                        <span class="text-[10px] text-gray-500">{{ $v['calls'] ?: '' }}</span>
                        <div class="w-full rounded-t bg-red-300 relative" style="height: {{ (int) round($v['calls'] / $maxDaily * 100) }}%">
                            <div class="absolute bottom-0 inset-x-0 rounded-t bg-primary-500" style="height: {{ $v['calls'] ? (int) round($v['ok'] / $v['calls'] * 100) : 0 }}%"></div>
                        </div>
                        <span class="text-[10px] text-gray-400 mt-1">{{ \Illuminate\Support\Carbon::parse($d)->format('j') }}</span>
                    </div>
                @endforeach
            </div>
            <p class="text-[11px] text-gray-500 mt-2"><span class="inline-block w-2 h-2 bg-primary-500 rounded-sm"></span> successful · <span class="inline-block w-2 h-2 bg-red-300 rounded-sm"></span> failed / rejected</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-5 overflow-x-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-3">By provider &amp; feature (last 30 days)</h2>
            @if($byFeature->isEmpty())
                <p class="text-sm text-gray-500">No AI requests in the last 30 days.</p>
            @else
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs text-gray-500 border-b"><th class="py-2 pr-3">Provider</th><th class="pr-3">Feature</th><th class="pr-3 text-right">Requests</th><th class="pr-3 text-right">Successful</th><th class="pr-3 text-right">Tokens in</th><th class="pr-3 text-right">Tokens out</th><th class="text-right">Avg time</th></tr></thead>
                    <tbody>
                        @foreach($byFeature as $r)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 pr-3">{{ $meta[$r->provider]['short'] ?? $r->provider }}</td>
                                <td class="pr-3">{{ str_replace('_', ' ', $r->feature) }}</td>
                                <td class="pr-3 text-right">{{ number_format($r->calls) }}</td>
                                <td class="pr-3 text-right">{{ (int) $r->ok }} <span class="text-[11px] text-gray-400">({{ $r->calls ? (int) round($r->ok / $r->calls * 100) : 0 }}%)</span></td>
                                <td class="pr-3 text-right">{{ number_format($r->tin) }}</td>
                                <td class="pr-3 text-right">{{ number_format($r->tout) }}</td>
                                <td class="text-right">{{ $r->avg_ms ? number_format($r->avg_ms / 1000, 1) . 's' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-5 overflow-x-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-1">Latest 50 requests</h2>
            <p class="text-xs text-gray-500 mb-3">What was run (feature), for whom, which model answered and what happened. Prompts and answers are never stored.</p>
            @if($recent->isEmpty())
                <p class="text-sm text-gray-500">Nothing yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs text-gray-500 border-b"><th class="py-2 pr-3">When</th><th class="pr-3">User</th><th class="pr-3">Feature</th><th class="pr-3">Provider / model</th><th class="pr-3">Result</th><th class="pr-3 text-right">Tokens in / out</th><th class="text-right">Time</th></tr></thead>
                    <tbody>
                        @foreach($recent as $r)
                            <tr class="border-b border-gray-100 align-top">
                                <td class="py-2 pr-3 whitespace-nowrap text-xs text-gray-600" title="{{ $r->created_at }}">{{ $r->created_at?->diffForHumans() }}</td>
                                <td class="pr-3 text-xs">{{ $r->user?->name ?? '—' }}</td>
                                <td class="pr-3 text-xs">{{ str_replace('_', ' ', $r->feature) }}</td>
                                <td class="pr-3 text-xs"><span class="font-medium">{{ $meta[$r->provider]['short'] ?? $r->provider }}</span><br><span class="font-mono text-[11px] text-gray-500">{{ $r->model }}</span></td>
                                <td class="pr-3"><span class="text-[11px] px-2 py-0.5 rounded-full {{ $statusPill($r->status) }}">{{ $r->status }}</span>
                                    @if($r->error_type)<br><span class="text-[11px] text-gray-500">{{ $errorText[$r->error_type] ?? $r->error_type }}</span>@endif</td>
                                <td class="pr-3 text-right text-xs whitespace-nowrap">{{ number_format($r->input_tokens) }} / {{ number_format($r->output_tokens) }}</td>
                                <td class="text-right text-xs">{{ $r->duration_ms ? number_format($r->duration_ms / 1000, 1) . 's' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</div>

<script>
(function () {
    const tabs = document.querySelectorAll('.ai-tab');
    const panels = document.querySelectorAll('.ai-panel');
    const input = document.getElementById('ai-tab-input');
    function show(name) {
        if (!document.querySelector('[data-panel="' + name + '"]')) return;
        panels.forEach(p => p.classList.toggle('hidden', p.dataset.panel !== name));
        tabs.forEach(t => {
            const on = t.dataset.tab === name;
            t.classList.toggle('border-primary-600', on); t.classList.toggle('text-primary-700', on);
            t.classList.toggle('border-transparent', !on); t.classList.toggle('text-gray-600', !on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        if (name !== 'usage') input.value = name;
        history.replaceState(null, '', '#' + name);
    }
    tabs.forEach(t => t.addEventListener('click', () => show(t.dataset.tab)));
    document.querySelectorAll('[data-tab-link]').forEach(b => b.addEventListener('click', () => {
        show(b.dataset.tabLink);
        document.querySelector('[role=tablist]').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
    // Model quick-pick chips
    document.querySelectorAll('[data-fill]').forEach(b => b.addEventListener('click', () => {
        document.getElementById(b.dataset.fill).value = b.dataset.value;
    }));
    // Active provider pills
    const pillOn = ['border-primary-500', 'bg-primary-50', 'text-primary-800', 'font-semibold'];
    document.querySelectorAll('.ai-pill input').forEach(r => r.addEventListener('change', () => {
        document.querySelectorAll('.ai-pill').forEach(l => l.classList.remove(...pillOn));
        r.parentElement.classList.add(...pillOn);
    }));
    show(location.hash.slice(1) || @json($startTab));
})();

function aiTest(provider, btn) {
    const out = document.getElementById('ai-test-' + provider);
    btn.disabled = true; out.className = 'text-xs mt-2 text-gray-500'; out.textContent = 'Testing…';
    fetch(@json(url('/admin/ai-setup/test')) + '/' + provider, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
    }).then(r => r.json()).then(d => {
        out.className = 'text-xs mt-2 ' + (d.ok ? 'text-green-700' : 'text-red-600');
        out.textContent = (d.ok ? '✓ ' : '✗ ') + d.message;
    }).catch(() => { out.className = 'text-xs mt-2 text-red-600'; out.textContent = 'Request failed.'; })
      .finally(() => { btn.disabled = false; });
}
</script>
@endsection
