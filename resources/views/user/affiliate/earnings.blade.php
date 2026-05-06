@extends('layouts.app')

@section('title', 'My Earnings')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Commission Earnings</h1>
                <p class="text-gray-500 mt-1">Detailed breakdown of all your affiliate commissions</p>
            </div>
            <a href="{{ route('user.affiliate.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
        </div>

        <!-- Summary Bar -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Total Earned</p>
                <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($earning->total_earned, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Pending</p>
                <p class="text-xl font-bold text-yellow-600 mt-1">${{ number_format($earning->pending, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Available</p>
                <p class="text-xl font-bold text-green-600 mt-1">${{ number_format($earning->available, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Withdrawn</p>
                <p class="text-xl font-bold text-gray-500 mt-1">${{ number_format($earning->withdrawn, 2) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Commission History</h2>
                @if($earning->available > 0)
                <a href="{{ route('user.affiliate.withdrawals') }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors">
                    <i class="fas fa-money-bill-transfer mr-1"></i> Withdraw ${{ number_format($earning->available, 2) }}
                </a>
                @endif
            </div>

            @if($conversions->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Referred User</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sale Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Commission Rate</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Commission</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Available</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($conversions as $conv)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $conv->user->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500">{{ $conv->user->email ?? '' }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700">${{ number_format($conv->sale_amount, 2) }}</td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $conv->commission_type === 'percent' ? $conv->commission_rate . '%' : '$' . number_format($conv->commission_rate, 2) }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-green-700">${{ number_format($conv->commission_amount, 2) }}</td>
                            <td class="px-6 py-4">
                                @if($conv->status === 'pending')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Pending</span>
                                @elseif($conv->status === 'approved')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Approved</span>
                                @else
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Rejected</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">{{ $conv->available_at ? $conv->available_at->format('M d, Y') : '—' }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $conv->created_at->format('M d, Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $conversions->links() }}
            </div>
            @else
            <div class="text-center py-16">
                <i class="fas fa-dollar-sign text-4xl text-gray-300 mb-3"></i>
                <h3 class="text-lg font-semibold text-gray-700">No commissions yet</h3>
                <p class="text-gray-500 mt-1">Commissions appear after your referrals make a purchase.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
