@extends('layouts.app')

@section('title', 'Affiliate Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Affiliate Dashboard</h1>
            <p class="text-gray-500 mt-1">Earn commissions by referring new users to CustomerNearMe</p>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4 text-green-800 text-sm">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4 text-red-800 text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
        @endif

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Clicks</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($clicks) }}</p>
                <div class="flex items-center mt-2 text-blue-600 text-xs"><i class="fas fa-mouse-pointer mr-1"></i> All time</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Signups</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($signups) }}</p>
                <div class="flex items-center mt-2 text-purple-600 text-xs"><i class="fas fa-user-plus mr-1"></i> Via your link</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Users</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($converted) }}</p>
                <div class="flex items-center mt-2 text-green-600 text-xs"><i class="fas fa-check mr-1"></i> Converted</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Conv. Rate</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $conversionRate }}%</p>
                <div class="flex items-center mt-2 text-yellow-600 text-xs"><i class="fas fa-chart-line mr-1"></i> Click to paid</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</p>
                <p class="text-2xl font-bold text-yellow-600 mt-1">${{ number_format($earning->pending, 2) }}</p>
                <div class="flex items-center mt-2 text-gray-500 text-xs"><i class="fas fa-clock mr-1"></i> Under review</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Available</p>
                <p class="text-2xl font-bold text-green-600 mt-1">${{ number_format($earning->available, 2) }}</p>
                <div class="flex items-center mt-2 text-gray-500 text-xs"><i class="fas fa-wallet mr-1"></i> Ready to withdraw</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

            <!-- Referral Link Card -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-link text-blue-500 mr-2"></i>Your Referral Link
                </h2>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 flex items-center justify-between mb-4">
                    <span id="referralLinkText" class="text-sm text-gray-700 font-mono break-all">{{ $user->getReferralLink() }}</span>
                    <button onclick="copyLink('{{ $user->getReferralLink() }}')"
                        class="ml-3 flex-shrink-0 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                        <i class="fas fa-copy mr-1"></i> Copy
                    </button>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <p class="text-sm font-medium text-gray-700 mb-3">Generate Campaign Link</p>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">UTM Source (platform)</label>
                            <input id="utmSource" type="text" placeholder="facebook, twitter..." class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">UTM Campaign</label>
                            <input id="utmCampaign" type="text" placeholder="summer-promo..." class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <input id="campaignLinkOutput" type="text" readonly placeholder="Generated link will appear here..." class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 font-mono">
                        <button onclick="generateCampaignLink()" class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm rounded-lg transition-colors">
                            Generate
                        </button>
                        <button onclick="copyLink(document.getElementById('campaignLinkOutput').value)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                            Copy
                        </button>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 mt-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Share on Social</p>
                    <div class="flex gap-2">
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($user->getReferralLink()) }}&text={{ urlencode('Check out CustomerNearMe — the best tool for finding local business leads!') }}" target="_blank"
                            class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm rounded-lg transition-colors">
                            <i class="fab fa-twitter mr-1"></i> Twitter
                        </a>
                        <a href="https://wa.me/?text={{ urlencode('Check out CustomerNearMe: ' . $user->getReferralLink()) }}" target="_blank"
                            class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm rounded-lg transition-colors">
                            <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($user->getReferralLink()) }}" target="_blank"
                            class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm rounded-lg transition-colors">
                            <i class="fab fa-facebook mr-1"></i> Facebook
                        </a>
                    </div>
                </div>
            </div>

            <!-- Earnings Summary -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-wallet text-green-500 mr-2"></i>Earnings Summary
                </h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-600">Total Earned</span>
                        <span class="font-bold text-gray-900">${{ number_format($earning->total_earned, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-600">Pending</span>
                        <span class="font-semibold text-yellow-600">${{ number_format($earning->pending, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-600">Approved</span>
                        <span class="font-semibold text-green-600">${{ number_format($earning->approved, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-600">Withdrawn</span>
                        <span class="font-semibold text-gray-700">${{ number_format($earning->withdrawn, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 bg-green-50 rounded-lg px-3">
                        <span class="text-sm font-semibold text-green-800">Available</span>
                        <span class="font-bold text-green-700 text-lg">${{ number_format($earning->available, 2) }}</span>
                    </div>
                </div>

                @if($earning->available > 0)
                <a href="{{ route('user.affiliate.withdrawals') }}" class="mt-4 block w-full text-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-money-bill-transfer mr-2"></i>Request Withdrawal
                </a>
                @else
                <div class="mt-4 p-3 bg-gray-50 rounded-lg text-center">
                    <p class="text-xs text-gray-500">Earn commissions to unlock withdrawals</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Clicks — Last 30 Days</h3>
                <canvas id="clicksChart" height="200"></canvas>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Conversions — Last 30 Days</h3>
                <canvas id="conversionsChart" height="200"></canvas>
            </div>
        </div>

        <!-- Recent Conversions -->
        @if($recentConversions->count())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Recent Commissions</h2>
                <a href="{{ route('user.affiliate.earnings') }}" class="text-sm text-blue-600 hover:underline">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase border-b border-gray-100">
                            <th class="pb-3 pr-4">User</th>
                            <th class="pb-3 pr-4">Sale</th>
                            <th class="pb-3 pr-4">Commission</th>
                            <th class="pb-3 pr-4">Status</th>
                            <th class="pb-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentConversions as $conv)
                        <tr>
                            <td class="py-3 pr-4 font-medium text-gray-900">{{ $conv->user->name ?? 'N/A' }}</td>
                            <td class="py-3 pr-4 text-gray-700">${{ number_format($conv->sale_amount, 2) }}</td>
                            <td class="py-3 pr-4 font-semibold text-green-700">${{ number_format($conv->commission_amount, 2) }}</td>
                            <td class="py-3 pr-4">
                                @if($conv->status === 'pending')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Pending</span>
                                @elseif($conv->status === 'approved')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Approved</span>
                                @else
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Rejected</span>
                                @endif
                            </td>
                            <td class="py-3 text-gray-500">{{ $conv->created_at->format('M d, Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Quick Links -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <a href="{{ route('user.affiliate.referrals') }}" class="flex items-center p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
                <div class="p-2 bg-purple-100 rounded-lg group-hover:bg-purple-200 transition-colors mr-3">
                    <i class="fas fa-users text-purple-600"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">My Referrals</p>
                    <p class="text-xs text-gray-500">{{ $signups }} users</p>
                </div>
            </a>
            <a href="{{ route('user.affiliate.earnings') }}" class="flex items-center p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
                <div class="p-2 bg-green-100 rounded-lg group-hover:bg-green-200 transition-colors mr-3">
                    <i class="fas fa-dollar-sign text-green-600"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Earnings</p>
                    <p class="text-xs text-gray-500">${{ number_format($earning->total_earned, 2) }} total</p>
                </div>
            </a>
            <a href="{{ route('user.affiliate.withdrawals') }}" class="flex items-center p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
                <div class="p-2 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition-colors mr-3">
                    <i class="fas fa-bank text-blue-600"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Withdraw</p>
                    <p class="text-xs text-gray-500">${{ number_format($earning->available, 2) }} available</p>
                </div>
            </a>
            <div class="flex items-center p-4 bg-white rounded-xl border border-gray-100 shadow-sm">
                <div class="p-2 bg-orange-100 rounded-lg mr-3">
                    <i class="fas fa-tag text-orange-600"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Your Code</p>
                    <p class="text-xs text-gray-500 font-mono">{{ $user->referral_code }}</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function copyLink(url) {
    if (!url) return;
    navigator.clipboard.writeText(url).then(() => {
        showToast('Link copied!');
    });
}

function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'fixed bottom-6 right-6 bg-gray-900 text-white px-4 py-2 rounded-lg text-sm shadow-lg z-50 transition-opacity';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 500); }, 2000);
}

function generateCampaignLink() {
    const src = document.getElementById('utmSource').value.trim();
    const cmp = document.getElementById('utmCampaign').value.trim();
    fetch('{{ route("user.affiliate.generate-link") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({ utm_source: src, utm_campaign: cmp }),
    }).then(r => r.json()).then(d => {
        document.getElementById('campaignLinkOutput').value = d.url;
    });
}

// Charts
const clicksDates = @json($clicksChart->pluck('date'));
const clicksTotals = @json($clicksChart->pluck('total'));
const convDates = @json($conversionsChart->pluck('date'));
const convTotals = @json($conversionsChart->pluck('total'));

const chartDefaults = {
    type: 'bar',
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
    }
};

new Chart(document.getElementById('clicksChart'), {
    ...chartDefaults,
    data: {
        labels: clicksDates,
        datasets: [{ label: 'Clicks', data: clicksTotals, backgroundColor: '#3b82f6', borderRadius: 4 }],
    },
});

new Chart(document.getElementById('conversionsChart'), {
    ...chartDefaults,
    data: {
        labels: convDates,
        datasets: [{ label: 'Conversions', data: convTotals, backgroundColor: '#22c55e', borderRadius: 4 }],
    },
});
</script>
@endsection
