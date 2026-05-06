@extends('layouts.admin')

@section('title', 'Affiliate Settings')

@section('content')
<div class="p-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Affiliate Settings</h1>
            <p class="text-gray-500 text-sm mt-1">Configure global commission rates, cookie duration, and payouts</p>
        </div>
        <a href="{{ route('admin.affiliate.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 text-sm"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
    @endif

    <div class="max-w-2xl">
        <form action="{{ route('admin.affiliate.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Commission -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-1">Commission Configuration</h2>
                <p class="text-sm text-gray-500 mb-4">Set the default commission earned per successful referral. Can be overridden per affiliate.</p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Commission Type</label>
                        <select name="commission_type" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="percent" {{ $settings['commission_type'] === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed" {{ $settings['commission_type'] === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                        </select>
                        @error('commission_type')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                        <input type="number" name="commission_value" step="0.01" min="0"
                            value="{{ old('commission_value', $settings['commission_value']) }}"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                            placeholder="e.g. 10 for 10% or $10">
                        @error('commission_value')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-3 p-3 bg-blue-50 rounded-lg text-xs text-blue-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    Example: Type = Percentage, Value = 10 → Affiliate earns 10% of each referred sale.
                </div>
            </div>

            <!-- Cookie & Delay -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-1">Tracking & Approval</h2>
                <p class="text-sm text-gray-500 mb-4">Configure how long referrals are tracked and when commissions are approved.</p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cookie Duration (days)</label>
                        <input type="number" name="cookie_days" min="1" max="365"
                            value="{{ old('cookie_days', $settings['cookie_days']) }}"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">How long the referral cookie lasts</p>
                        @error('cookie_days')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Approval Delay (days)</label>
                        <input type="number" name="approval_delay_days" min="0" max="90"
                            value="{{ old('approval_delay_days', $settings['approval_delay_days']) }}"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Days before auto-approving (0 = instant)</p>
                        @error('approval_delay_days')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Withdrawal -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-1">Withdrawal Settings</h2>
                <p class="text-sm text-gray-500 mb-4">Set the minimum payout threshold for affiliates.</p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Withdrawal ($)</label>
                    <input type="number" name="min_withdrawal" step="0.01" min="0"
                        value="{{ old('min_withdrawal', $settings['min_withdrawal']) }}"
                        class="w-full max-w-xs px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    @error('min_withdrawal')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg transition-colors">
                <i class="fas fa-save mr-2"></i>Save Settings
            </button>
        </form>
    </div>
</div>
@endsection
