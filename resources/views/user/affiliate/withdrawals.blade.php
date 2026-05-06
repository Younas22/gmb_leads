@extends('layouts.app')

@section('title', 'Withdrawals')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Withdraw Earnings</h1>
                <p class="text-gray-500 mt-1">Request a payout from your available balance</p>
            </div>
            <a href="{{ route('user.affiliate.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

            <!-- Withdrawal Form -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">New Withdrawal Request</h2>
                <div class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-lg text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Available balance: <strong>${{ number_format($earning->available, 2) }}</strong>
                    &nbsp;|&nbsp; Minimum withdrawal: <strong>${{ number_format($minWithdrawal, 2) }}</strong>
                </div>

                @if($user->withdrawalRequests()->pending()->exists())
                <div class="p-4 bg-yellow-50 border border-yellow-100 rounded-lg text-sm text-yellow-700">
                    <i class="fas fa-clock mr-2"></i>You already have a pending withdrawal request. Please wait for it to be processed.
                </div>
                @elseif($earning->available < $minWithdrawal)
                <div class="p-4 bg-gray-50 border border-gray-100 rounded-lg text-sm text-gray-600">
                    <i class="fas fa-lock mr-2"></i>Your available balance (${{ number_format($earning->available, 2) }}) is below the minimum withdrawal amount (${{ number_format($minWithdrawal, 2) }}).
                </div>
                @else
                <form action="{{ route('user.affiliate.withdrawal.request') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount ($)</label>
                            <input type="number" name="amount" step="0.01" min="{{ $minWithdrawal }}" max="{{ $earning->available }}"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm @error('amount') border-red-300 @enderror"
                                value="{{ old('amount', number_format($earning->available, 2)) }}">
                            @error('amount')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                            <select name="method" id="paymentMethod" onchange="showMethodFields()"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm @error('method') border-red-300 @enderror">
                                <option value="">Select method</option>
                                <option value="bank" {{ old('method') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="jazzcash" {{ old('method') === 'jazzcash' ? 'selected' : '' }}>JazzCash</option>
                                <option value="easypaisa" {{ old('method') === 'easypaisa' ? 'selected' : '' }}>EasyPaisa</option>
                                <option value="paypal" {{ old('method') === 'paypal' ? 'selected' : '' }}>PayPal</option>
                            </select>
                            @error('method')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div id="methodFields" class="{{ old('method') ? '' : 'hidden' }} space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                            <input type="text" name="account_name" value="{{ old('account_name') }}"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm @error('account_name') border-red-300 @enderror"
                                placeholder="Your name on the account">
                            @error('account_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" id="accountNumberLabel">Account Number / Phone / Email</label>
                            <input type="text" name="account_number" value="{{ old('account_number') }}"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm @error('account_number') border-red-300 @enderror"
                                placeholder="Account/Phone/Email">
                            @error('account_number')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div id="bankNameField" class="{{ old('method') === 'bank' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                                placeholder="e.g. HBL, MCB, Meezan...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes (optional)</label>
                            <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Any additional info...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors text-sm">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Withdrawal Request
                    </button>
                </form>
                @endif
            </div>

            <!-- Balance Card -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Balance Summary</h2>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Total Earned</span>
                        <span class="font-semibold">${{ number_format($earning->total_earned, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Pending</span>
                        <span class="font-semibold text-yellow-600">${{ number_format($earning->pending, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Approved</span>
                        <span class="font-semibold text-green-600">${{ number_format($earning->approved, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Withdrawn</span>
                        <span class="font-semibold text-gray-600">${{ number_format($earning->withdrawn, 2) }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between">
                        <span class="text-sm font-bold text-gray-900">Available</span>
                        <span class="font-bold text-green-600 text-lg">${{ number_format($earning->available, 2) }}</span>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500 leading-relaxed">
                        <i class="fas fa-info-circle mr-1"></i>
                        Commissions are approved after a {{ \App\Models\Setting::get('affiliate_approval_delay_days', 7) }}-day review period to protect against refunds.
                    </p>
                </div>
            </div>
        </div>

        <!-- Withdrawal History -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Withdrawal History</h2>
            </div>

            @if($requests->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Requested</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Processed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($requests as $wr)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-900">${{ number_format($wr->amount, 2) }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $wr->method_label }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusMap = [
                                        'pending'  => 'bg-yellow-100 text-yellow-700',
                                        'approved' => 'bg-blue-100 text-blue-700',
                                        'paid'     => 'bg-green-100 text-green-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusMap[$wr->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($wr->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $wr->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $wr->processed_at ? $wr->processed_at->format('M d, Y') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $requests->links() }}
            </div>
            @else
            <div class="text-center py-12">
                <i class="fas fa-bank text-4xl text-gray-200 mb-3"></i>
                <p class="text-gray-500">No withdrawal requests yet.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function showMethodFields() {
    const method = document.getElementById('paymentMethod').value;
    const fields = document.getElementById('methodFields');
    const bankField = document.getElementById('bankNameField');
    const label = document.getElementById('accountNumberLabel');

    fields.classList.toggle('hidden', !method);
    bankField.classList.toggle('hidden', method !== 'bank');

    const labels = {
        bank: 'Account Number',
        jazzcash: 'JazzCash Number',
        easypaisa: 'EasyPaisa Number',
        paypal: 'PayPal Email',
    };
    label.textContent = labels[method] || 'Account Number / Phone / Email';
}
// Init on load if old value present
showMethodFields();
</script>
@endsection
