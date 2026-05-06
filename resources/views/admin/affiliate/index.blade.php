@extends('layouts.admin')

@section('title', 'Affiliate Management')

@section('content')
<div class="p-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Affiliate Management</h1>
            <p class="text-gray-500 text-sm mt-1">Overview of the affiliate / referral program</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.affiliate.settings') }}" class="flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-cog mr-2"></i> Settings
            </a>
            <a href="{{ route('admin.affiliate.withdrawals') }}" class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                <i class="fas fa-money-bill-transfer mr-2"></i> Payouts
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 text-sm">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Affiliates</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_affiliates']) }}</p>
            <div class="text-xs text-blue-600 mt-1"><i class="fas fa-users mr-1"></i> Active</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Total Clicks</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_clicks']) }}</p>
            <div class="text-xs text-purple-600 mt-1"><i class="fas fa-mouse-pointer mr-1"></i> All time</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Conversions</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_conversions']) }}</p>
            <div class="text-xs text-green-600 mt-1"><i class="fas fa-check mr-1"></i> Approved</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Aff. Revenue</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($stats['total_revenue'], 0) }}</p>
            <div class="text-xs text-green-600 mt-1"><i class="fas fa-chart-line mr-1"></i> From referrals</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Paid Out</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($stats['total_commissions'], 0) }}</p>
            <div class="text-xs text-yellow-600 mt-1"><i class="fas fa-hand-holding-dollar mr-1"></i> Commissions</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Pend. Payouts</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">${{ number_format($stats['pending_payouts'], 0) }}</p>
            <div class="text-xs text-red-500 mt-1"><i class="fas fa-clock mr-1"></i> Awaiting</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- Top Affiliates -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Top Affiliates</h2>
                <a href="{{ route('admin.affiliate.affiliates') }}" class="text-sm text-blue-600 hover:underline">View all</a>
            </div>
            @if($topAffiliates->count())
            <div class="divide-y divide-gray-50">
                @foreach($topAffiliates as $i => $e)
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center text-xs font-bold text-gray-500 mr-3">{{ $i + 1 }}</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $e->user->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-500">{{ $e->user->email ?? '' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-green-600">${{ number_format($e->total_earned, 2) }}</p>
                        @if($e->user && !$e->user->affiliate_active)
                            <span class="text-xs text-red-500">Disabled</span>
                        @else
                            <span class="text-xs text-gray-400">Code: {{ $e->user->referral_code ?? '—' }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="px-6 py-8 text-center text-gray-400 text-sm">No affiliates yet</div>
            @endif
        </div>

        <!-- Recent Conversions -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Recent Conversions</h2>
                <a href="{{ route('admin.affiliate.conversions') }}" class="text-sm text-blue-600 hover:underline">View all</a>
            </div>
            @if($recentConversions->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Referrer</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Commission</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentConversions as $conv)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $conv->referrer->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-green-700 font-semibold">${{ number_format($conv->commission_amount, 2) }}</td>
                            <td class="px-4 py-3">
                                @if($conv->status === 'pending')
                                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs">Pending</span>
                                @elseif($conv->status === 'approved')
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">Approved</span>
                                @else
                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">Rejected</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $conv->created_at->format('M d') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="px-6 py-8 text-center text-gray-400 text-sm">No conversions yet</div>
            @endif
        </div>
    </div>

    <!-- Pending Withdrawals -->
    @if($pendingWithdrawals->count())
    <div class="bg-white rounded-xl border border-yellow-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-yellow-100 bg-yellow-50 flex items-center justify-between">
            <h2 class="text-base font-semibold text-yellow-800">
                <i class="fas fa-clock mr-2 text-yellow-600"></i>Pending Withdrawal Requests
            </h2>
            <a href="{{ route('admin.affiliate.withdrawals') }}" class="text-sm text-blue-600 hover:underline">Manage all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">User</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Amount</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Method</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Requested</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($pendingWithdrawals as $wr)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $wr->user->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $wr->user->email ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900">${{ number_format($wr->amount, 2) }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $wr->method_label }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ $wr->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <form action="{{ route('admin.affiliate.withdrawal.process', $wr) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg">Approve</button>
                                </form>
                                <form action="{{ route('admin.affiliate.withdrawal.process', $wr) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
