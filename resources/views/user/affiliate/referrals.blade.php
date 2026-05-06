@extends('layouts.app')

@section('title', 'My Referrals')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Referrals</h1>
                <p class="text-gray-500 mt-1">Users who signed up using your referral link</p>
            </div>
            <a href="{{ route('user.affiliate.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            @if($referredUsers->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Signed Up</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Purchase Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($referredUsers as $index => $referred)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-500">{{ $referredUsers->firstItem() + $index }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold text-xs mr-3">
                                        {{ strtoupper(substr($referred->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $referred->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $referred->email }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $referred->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                @if($referred->has_paid)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <i class="fas fa-check-circle mr-1"></i> Paid
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        <i class="fas fa-clock mr-1"></i> Free
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-100">
                {{ $referredUsers->links() }}
            </div>

            @else
            <div class="text-center py-16">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users text-2xl text-purple-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">No referrals yet</h3>
                <p class="text-gray-500 mt-2 max-w-sm mx-auto">Share your referral link to start earning commissions when your friends sign up and subscribe.</p>
                <a href="{{ route('user.affiliate.index') }}" class="mt-4 inline-block px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                    Get Your Link
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
