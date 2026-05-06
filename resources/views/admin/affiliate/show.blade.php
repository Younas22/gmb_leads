@extends('layouts.admin')

@section('title', 'Affiliate: ' . $user->name)

@section('content')
<div class="p-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
            <p class="text-gray-500 text-sm mt-1">Affiliate details — Code: <span class="font-mono font-semibold">{{ $user->referral_code }}</span></p>
        </div>
        <a href="{{ route('admin.affiliate.affiliates') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 text-sm"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
    @endif

    @if($fraudFlags)
    <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4 text-red-800 text-sm">
        <p class="font-semibold mb-1"><i class="fas fa-exclamation-triangle mr-2"></i>Fraud Detection Flags</p>
        @foreach($fraudFlags as $flag)
            <p class="text-xs mt-1">• {{ $flag }}</p>
        @endforeach
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Profile Card -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Profile</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Name</span><span class="font-medium">{{ $user->name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Email</span><span class="font-medium">{{ $user->email }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Ref. Code</span><span class="font-mono font-medium">{{ $user->referral_code }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status</span>
                    @if($user->affiliate_active)
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">Active</span>
                    @else
                        <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">Disabled</span>
                    @endif
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Total Clicks</span><span class="font-medium">{{ number_format($clicks) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Total Referrals</span><span class="font-medium">{{ number_format($signups) }}</span></div>
            </div>

            <div class="border-t border-gray-100 mt-4 pt-4">
                <form action="{{ route('admin.affiliate.toggle', $user) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2 {{ $user->affiliate_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white text-sm rounded-lg transition-colors">
                        {{ $user->affiliate_active ? 'Disable Affiliate' : 'Enable Affiliate' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Earnings Card -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Earnings</h2>
            @php $earning = $user->affiliateEarning; @endphp
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Total Earned</span><span class="font-bold text-gray-900">${{ number_format($earning->total_earned ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Pending</span><span class="font-semibold text-yellow-600">${{ number_format($earning->pending ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Approved</span><span class="font-semibold text-green-600">${{ number_format($earning->approved ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Withdrawn</span><span class="font-semibold text-gray-600">${{ number_format($earning->withdrawn ?? 0, 2) }}</span></div>
                <div class="flex justify-between border-t border-gray-100 pt-3">
                    <span class="text-sm font-bold text-gray-900">Available</span>
                    <span class="font-bold text-green-600">${{ number_format($earning ? $earning->available : 0, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Custom Commission -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Custom Commission</h2>
            <p class="text-xs text-gray-500 mb-3">Override the global commission rate for this affiliate.</p>
            <form action="{{ route('admin.affiliate.commission', $user) }}" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Commission Type</label>
                    <select name="custom_commission_type" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Use Global Setting</option>
                        <option value="percent" {{ $user->custom_commission_type === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed" {{ $user->custom_commission_type === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Value</label>
                    <input type="number" name="custom_commission_value" step="0.01" min="0"
                        value="{{ $user->custom_commission_value }}"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="{{ $user->custom_commission_type === 'percent' ? 'e.g. 15' : 'e.g. 5.00' }}">
                </div>
                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                    Save Commission
                </button>
            </form>
        </div>
    </div>

    <!-- Conversions -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-900">Conversions</h2>
        </div>
        @if($conversions->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Referred User</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Sale</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Commission</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Available At</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($conversions as $conv)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $conv->user->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $conv->user->email ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4">${{ number_format($conv->sale_amount, 2) }}</td>
                        <td class="px-6 py-4 font-semibold text-green-700">${{ number_format($conv->commission_amount, 2) }}</td>
                        <td class="px-6 py-4">
                            @if($conv->status === 'pending')
                                <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs">Pending</span>
                            @elseif($conv->status === 'approved')
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">Approved</span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">Rejected</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $conv->available_at ? $conv->available_at->format('M d, Y') : '—' }}</td>
                        <td class="px-6 py-4">
                            @if($conv->isPending())
                            <div class="flex gap-2">
                                <form action="{{ route('admin.affiliate.conversion.approve', $conv) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg">Approve</button>
                                </form>
                                <form action="{{ route('admin.affiliate.conversion.reject', $conv) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg">Reject</button>
                                </form>
                            </div>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-8 text-gray-400 text-sm">No conversions</div>
        @endif
    </div>

    <!-- Withdrawals -->
    @if($withdrawals->count())
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-900">Withdrawal Requests</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Amount</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Method</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Requested</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($withdrawals as $wr)
                    <tr>
                        <td class="px-6 py-4 font-bold text-gray-900">${{ number_format($wr->amount, 2) }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $wr->method_label }}</td>
                        <td class="px-6 py-4">
                            @php $statusMap = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-blue-100 text-blue-700', 'paid' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700']; @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $statusMap[$wr->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($wr->status) }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ $wr->created_at->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
