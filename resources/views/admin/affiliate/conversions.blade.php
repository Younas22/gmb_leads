@extends('layouts.admin')

@section('title', 'All Conversions')

@section('content')
<div class="p-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Affiliate Conversions</h1>
            <p class="text-gray-500 text-sm mt-1">All commission events across affiliates</p>
        </div>
        <a href="{{ route('admin.affiliate.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 text-sm"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
    @endif

    <form method="GET" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex gap-3 items-end">
        <div class="min-w-36">
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg">Filter</button>
        <a href="{{ route('admin.affiliate.conversions') }}" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-lg">Reset</a>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        @if($conversions->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Referrer</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Referred User</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Sale</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Rate</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Commission</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Available</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($conversions as $conv)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $conv->referrer->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500 font-mono">{{ $conv->referral_code }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $conv->user->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $conv->user->email ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 text-gray-700">${{ number_format($conv->sale_amount, 2) }}</td>
                        <td class="px-6 py-4 text-gray-600 text-xs">
                            {{ $conv->commission_type === 'percent' ? $conv->commission_rate . '%' : '$' . number_format($conv->commission_rate, 2) }}
                        </td>
                        <td class="px-6 py-4 font-bold text-green-700">${{ number_format($conv->commission_amount, 2) }}</td>
                        <td class="px-6 py-4">
                            @php $map = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700']; @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $map[$conv->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($conv->status) }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $conv->available_at ? $conv->available_at->format('M d, Y') : '—' }}</td>
                        <td class="px-6 py-4">
                            @if($conv->isPending())
                            <div class="flex gap-1">
                                <form action="{{ route('admin.affiliate.conversion.approve', $conv) }}" method="POST">
                                    @csrf
                                    <button class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg">Approve</button>
                                </form>
                                <form action="{{ route('admin.affiliate.conversion.reject', $conv) }}" method="POST">
                                    @csrf
                                    <button class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg">Reject</button>
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
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $conversions->links() }}
        </div>
        @else
        <div class="text-center py-16">
            <i class="fas fa-exchange-alt text-4xl text-gray-200 mb-3"></i>
            <p class="text-gray-500">No conversions found.</p>
        </div>
        @endif
    </div>
</div>
@endsection
