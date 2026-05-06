@extends('layouts.admin')

@section('title', 'All Affiliates')

@section('content')
<div class="p-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">All Affiliates</h1>
            <p class="text-gray-500 text-sm mt-1">Manage and monitor affiliate accounts</p>
        </div>
        <a href="{{ route('admin.affiliate.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 text-sm">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    <!-- Filters -->
    <form method="GET" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, code..."
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="min-w-36">
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
            <i class="fas fa-search mr-1"></i> Filter
        </button>
        <a href="{{ route('admin.affiliate.affiliates') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm rounded-lg transition-colors">Reset</a>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        @if($affiliates->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Clicks</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Referrals</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Conv.</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Earned</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($affiliates as $aff)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-medium text-gray-900">{{ $aff->name }}</p>
                                <p class="text-xs text-gray-500">{{ $aff->email }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs bg-gray-50 rounded text-gray-700">{{ $aff->referral_code }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ number_format($aff->affiliate_clicks_count) }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ number_format($aff->referred_users_count) }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ number_format($aff->affiliate_conversions_count) }}</td>
                        <td class="px-6 py-4 font-semibold text-green-700">${{ number_format($aff->affiliateEarning->total_earned ?? 0, 2) }}</td>
                        <td class="px-6 py-4">
                            @if($aff->affiliate_active)
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Active</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Disabled</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.affiliate.show', $aff) }}" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs rounded-lg transition-colors">
                                    View
                                </a>
                                <form action="{{ route('admin.affiliate.toggle', $aff) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 {{ $aff->affiliate_active ? 'bg-red-100 hover:bg-red-200 text-red-700' : 'bg-green-100 hover:bg-green-200 text-green-700' }} text-xs rounded-lg transition-colors">
                                        {{ $aff->affiliate_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $affiliates->links() }}
        </div>
        @else
        <div class="text-center py-16">
            <i class="fas fa-users text-4xl text-gray-200 mb-3"></i>
            <p class="text-gray-500">No affiliates found.</p>
        </div>
        @endif
    </div>

</div>
@endsection
