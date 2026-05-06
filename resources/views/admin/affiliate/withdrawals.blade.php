@extends('layouts.admin')

@section('title', 'Withdrawal Requests')

@section('content')
<div class="p-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Withdrawal Requests</h1>
            <p class="text-gray-500 text-sm mt-1">Pending payout: <strong class="text-gray-900">${{ number_format($pendingSum, 2) }}</strong></p>
        </div>
        <a href="{{ route('admin.affiliate.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 text-sm"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4 text-red-800 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>
    @endif

    <!-- Filter -->
    <form method="GET" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex gap-3 items-end">
        <div class="min-w-36">
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg">Filter</button>
        <a href="{{ route('admin.affiliate.withdrawals') }}" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-lg">Reset</a>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        @if($withdrawals->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Details</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Requested</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($withdrawals as $wr)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $wr->user->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $wr->user->email ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900 text-base">${{ number_format($wr->amount, 2) }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $wr->method_label }}</td>
                        <td class="px-6 py-4 text-xs text-gray-600">
                            @if(is_array($wr->payment_details))
                                <p><span class="font-medium">Name:</span> {{ $wr->payment_details['account_name'] ?? '—' }}</p>
                                <p><span class="font-medium">Account:</span> {{ $wr->payment_details['account_number'] ?? '—' }}</p>
                                @if(!empty($wr->payment_details['bank_name']))
                                    <p><span class="font-medium">Bank:</span> {{ $wr->payment_details['bank_name'] }}</p>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php $statusMap = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-blue-100 text-blue-700', 'paid' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700']; @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusMap[$wr->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($wr->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ $wr->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            @if($wr->isPending())
                            <div class="flex flex-wrap gap-1">
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
                            @elseif($wr->isApproved())
                            <form action="{{ route('admin.affiliate.withdrawal.process', $wr) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="action" value="pay">
                                <button type="submit" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg">
                                    <i class="fas fa-check mr-1"></i>Mark Paid
                                </button>
                            </form>
                            @else
                            <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $withdrawals->links() }}
        </div>
        @else
        <div class="text-center py-16">
            <i class="fas fa-money-bill-transfer text-4xl text-gray-200 mb-3"></i>
            <p class="text-gray-500">No withdrawal requests found.</p>
        </div>
        @endif
    </div>
</div>
@endsection
