@extends('layouts.admin')

@section('title', 'Custom Features')

@section('content')
@php
    $user = $subscription->user;
    $userName = $user ? (trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name) : 'Unknown user';
    $on = ['true', '1', 'yes', 'on'];

    // How a feature row (package or override) reads, in plain words
    $describe = function ($row, string $type) use ($on) {
        if (!$row) {
            return ['Not set', 'text-gray-400'];
        }
        if (!empty($row->is_locked)) {
            return ['Locked', 'text-amber-700'];
        }
        if ($row->is_unlimited || strtolower((string) $row->feature_value) === 'unlimited') {
            return [$type === 'bool' ? 'On' : 'Unlimited', 'text-green-700'];
        }
        $v = strtolower(trim((string) $row->feature_value));
        if ($type === 'bool') {
            return in_array($v, $on, true) ? ['On', 'text-green-700'] : ['Off', 'text-gray-500'];
        }

        return [$v === '' ? 'Not set' : (string) $row->feature_value, 'text-gray-900'];
    };
    $grouped = collect($rows)->groupBy('group', true);
@endphp

<div class="p-4 lg:p-8 max-w-6xl">
    <a href="{{ route('admin.subscriptions.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 mb-4">
        <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i> Back to subscriptions
    </a>

    <div class="bg-white rounded-2xl border border-gray-200 p-5 mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Custom features</h1>
            <p class="text-sm text-gray-600 mt-1">
                For <strong class="text-gray-900">{{ $userName }}</strong>@if($user) ({{ $user->email }})@endif
                · Package: <strong class="text-gray-900">{{ $subscription->package->name ?? '—' }}</strong>
            </p>
            <p class="text-xs text-gray-500 mt-2 max-w-2xl">
                Change features for this user only. "Use package" keeps the package setting. "Custom" gives this user another value.
                "Locked" shows the feature with a lock and your message in their sidebar, and they cannot use it.
                Saved per user: it stays after a renewal or a package change. Team members of a company follow the company owner.
            </p>
        </div>
        @php $customCount = collect($rows)->filter(fn ($r) => $r['override'])->count(); @endphp
        <span class="inline-flex items-center gap-1.5 h-8 px-3 rounded-full text-xs font-semibold {{ $customCount ? 'bg-orange-50 border border-orange-200 text-orange-700' : 'bg-gray-100 text-gray-600' }}">
            <i class="fas fa-sliders" aria-hidden="true"></i> {{ $customCount }} custom
        </span>
    </div>

    @if(!$installed)
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i>
            The database is not updated yet. Run <code class="font-mono">php artisan migrate</code> on the server to turn on feature locks and custom features.
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.subscriptions.features.update', $subscription) }}">
        @csrf
        @method('PUT')

        @foreach($grouped as $group => $groupRows)
            <section class="bg-white rounded-2xl border border-gray-200 mb-5 overflow-hidden">
                <h2 class="px-5 py-3 border-b border-gray-100 bg-gray-50 text-sm font-semibold text-gray-800">{{ $group }}</h2>
                <div class="divide-y divide-gray-100">
                    @foreach($groupRows as $key => $r)
                        @php
                            $o = $r['override'];
                            $mode = old("features.$key.mode", $o ? ($o->is_locked ? 'locked' : 'custom') : 'package');
                            [$pkgText, $pkgClass] = $describe($r['package'], $r['type']);
                            $val = old("features.$key.value", $o && !$o->is_locked ? (string) $o->feature_value : '');
                            $unl = old("features.$key.is_unlimited", $o && $o->is_unlimited);
                            $msg = old("features.$key.lock_message", $o->lock_message ?? '');
                        @endphp
                        <div class="px-5 py-4 grid grid-cols-1 lg:grid-cols-12 gap-3 items-start {{ $mode !== 'package' ? 'bg-orange-50/40' : '' }}" data-feature-row>
                            <div class="lg:col-span-4">
                                <p class="text-sm font-semibold text-gray-900">{{ $r['name'] }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ $key }}</p>
                                @if($r['desc'])<p class="text-xs text-gray-500 mt-0.5">{{ $r['desc'] }}</p>@endif
                            </div>
                            <div class="lg:col-span-2 text-sm">
                                <p class="text-xs text-gray-500">Package</p>
                                <p class="font-medium {{ $pkgClass }}">
                                    @if($pkgText === 'Locked')<i class="fas fa-lock text-[11px] mr-1" aria-hidden="true"></i>@endif{{ $pkgText }}
                                </p>
                                @if($r['package'] && !empty($r['package']->is_locked) && $r['package']->lock_message)
                                    <p class="text-xs text-amber-700 mt-0.5">"{{ $r['package']->lock_message }}"</p>
                                @endif
                            </div>
                            <div class="lg:col-span-6 space-y-2">
                                <label class="sr-only" for="mode-{{ $key }}">For this user: {{ $r['name'] }}</label>
                                <select id="mode-{{ $key }}" name="features[{{ $key }}][mode]" class="w-full sm:w-56 h-10 px-3 border border-gray-300 rounded-lg text-sm bg-white cursor-pointer disabled:cursor-not-allowed focus:ring-2 focus:ring-primary-500" onchange="cnmFeatureMode(this)" @disabled(!$installed)>
                                    <option value="package" @selected($mode === 'package')>Use package</option>
                                    <option value="custom" @selected($mode === 'custom')>Custom for this user</option>
                                    <option value="locked" @selected($mode === 'locked')>Locked for this user</option>
                                </select>

                                <div data-mode="custom" class="{{ $mode === 'custom' ? '' : 'hidden' }} flex flex-wrap items-center gap-3">
                                    @if($r['type'] === 'bool')
                                        <select name="features[{{ $key }}][value]" class="h-10 px-3 border border-gray-300 rounded-lg text-sm bg-white cursor-pointer disabled:cursor-not-allowed focus:ring-2 focus:ring-primary-500" aria-label="{{ $r['name'] }} value" @disabled(!$installed)>
                                            <option value="true" @selected(in_array(strtolower($val), $on, true) || $unl)>On</option>
                                            <option value="false" @selected(!in_array(strtolower($val), $on, true) && !$unl)>Off</option>
                                        </select>
                                    @else
                                        <input type="{{ $r['type'] === 'value' ? 'number' : 'text' }}" @if($r['type'] === 'value') min="0" @endif name="features[{{ $key }}][value]" value="{{ $val }}" maxlength="255"
                                               placeholder="{{ $r['type'] === 'value' ? 'e.g. 100' : 'Value' }}" aria-label="{{ $r['name'] }} value"
                                               class="w-36 h-10 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500" @disabled(!$installed)>
                                        <label class="inline-flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer select-none">
                                            <input type="checkbox" name="features[{{ $key }}][is_unlimited]" value="1" @checked($unl) class="rounded border-gray-300 text-primary-600 cursor-pointer disabled:cursor-not-allowed focus:ring-primary-500" @disabled(!$installed)>
                                            Unlimited
                                        </label>
                                    @endif
                                </div>

                                <div data-mode="locked" class="{{ $mode === 'locked' ? '' : 'hidden' }}">
                                    <input type="text" name="features[{{ $key }}][lock_message]" value="{{ $msg }}" maxlength="255"
                                           placeholder="Message this user sees, e.g. Ask support to unlock this"
                                           aria-label="{{ $r['name'] }} lock message"
                                           class="w-full h-10 px-3 border border-amber-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500" @disabled(!$installed)>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="sticky bottom-0 bg-gray-50 py-4 flex justify-end gap-3">
            <a href="{{ route('admin.subscriptions.index') }}" class="h-10 inline-flex items-center px-4 border border-gray-300 rounded-lg text-sm text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
            <button type="submit" class="h-10 inline-flex items-center px-5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold cursor-pointer disabled:bg-gray-300 disabled:cursor-not-allowed" @disabled(!$installed)>
                <i class="fas fa-save mr-2" aria-hidden="true"></i>Save custom features
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function cnmFeatureMode(select) {
    const row = select.closest('[data-feature-row]');
    row.querySelectorAll('[data-mode]').forEach(el => el.classList.toggle('hidden', el.dataset.mode !== select.value));
    row.classList.toggle('bg-orange-50/40', select.value !== 'package');
}
</script>
@endpush
