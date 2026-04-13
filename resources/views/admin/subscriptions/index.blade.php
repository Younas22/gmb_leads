@extends('layouts.admin')

@section('title', 'Subscriptions Management')

@section('content')
<main class="p-4 lg:p-8">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Subscriptions Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage user subscriptions and payments</p>
        </div>
        <button onclick="openCreateModal()" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>
            Add Subscription
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-credit-card text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</p>
                </div>
                <div class="bg-green-100 rounded-lg p-3">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Expired</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['expired'] }}</p>
                </div>
                <div class="bg-yellow-100 rounded-lg p-3">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Cancelled</p>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['cancelled'] }}</p>
                </div>
                <div class="bg-red-100 rounded-lg p-3">
                    <i class="fas fa-times-circle text-red-600"></i>
                </div>
            </div>
        </div>
        <!-- Pending Card -->
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['pending'] }}</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-hourglass-half text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Revenue</p>
                    <p class="text-2xl font-bold text-primary-600">PKR {{ number_format($stats['total_revenue'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-primary-100 rounded-lg p-3">
                    <i class="fas fa-rupee-sign text-primary-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        {{ session('error') }}
    </div>
    @endif

    <!-- Subscriptions Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @php
                                    $subAvatar = ($subscription->user && $subscription->user->avatar)
                                        ? (str_starts_with($subscription->user->avatar, 'http') ? $subscription->user->avatar : asset('public/' . $subscription->user->avatar))
                                        : asset('assets/avatar/placeholder-image.jpeg');
                                @endphp
                                <img src="{{ $subAvatar }}" alt="User" class="w-10 h-10 rounded-full object-cover">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $subscription->user ? ($subscription->user->first_name ? $subscription->user->first_name . ' ' . $subscription->user->last_name : $subscription->user->name) : 'Deleted User' }}
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $subscription->user->email ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-box text-orange-600 text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $subscription->package->name ?? 'Deleted Package' }} ({{ $subscription->package->billing_type }})</div>
                                    <div class="text-xs text-gray-500">{{ $subscription->package ? $subscription->package->currency . ' ' . number_format($subscription->package->price, 2) : 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($subscription->paymentMethod)
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas {{ $subscription->paymentMethod->icon ?? 'fa-money-bill' }} text-green-600 text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $subscription->paymentMethod->name }}</div>
                                    @if($subscription->amount_paid)
                                    <div class="text-xs text-green-600">PKR {{ number_format($subscription->amount_paid, 0) }}</div>
                                    @endif
                                </div>
                            </div>
                            @php $screenshotPath = $subscription->payments->where('screenshot', '!=', null)->first()?->screenshot; @endphp
                            @if($screenshotPath)
                            <a href="{{ asset('public/' . $screenshotPath) }}" target="_blank" class="mt-1.5 text-xs text-blue-600 hover:underline flex items-center">
                                <i class="fas fa-image mr-1"></i> View Screenshot
                            </a>
                            @endif
                            @else
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-question text-gray-400 text-sm"></i>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm text-gray-500">
                                        @if($subscription->is_trial)
                                            <span class="text-blue-600">Trial</span>
                                        @else
                                            No payment
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $subscription->start_date ? $subscription->start_date->format('M d, Y') : 'N/A' }}</div>
                            <div class="text-xs text-gray-500">
                                @if($subscription->end_date)
                                    to {{ $subscription->end_date->format('M d, Y') }}
                                @else
                                    <span class="text-green-600">Lifetime</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="toggleStatus({{ $subscription->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors
                                {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800 hover:bg-green-200' : '' }}
                                {{ $subscription->status === 'expired' ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : '' }}
                                {{ $subscription->status === 'cancelled' ? 'bg-red-100 text-red-800 hover:bg-red-200' : '' }}
                                {{ $subscription->status === 'pending' ? 'bg-blue-100 text-blue-800 hover:bg-blue-200' : '' }}"
                                id="status-badge-{{ $subscription->id }}">
                                <span class="w-2 h-2 rounded-full mr-1.5
                                    {{ $subscription->status === 'active' ? 'bg-green-400' : '' }}
                                    {{ $subscription->status === 'expired' ? 'bg-yellow-400' : '' }}
                                    {{ $subscription->status === 'cancelled' ? 'bg-red-400' : '' }}
                                    {{ $subscription->status === 'pending' ? 'bg-blue-400' : '' }}"
                                    id="status-dot-{{ $subscription->id }}"></span>
                                <span id="status-text-{{ $subscription->id }}">{{ ucfirst($subscription->status) }}</span>
                            </button>
                            @if($subscription->auto_renew)
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-sync-alt mr-1 text-xs"></i>Auto
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal({{ $subscription->id }})" class="text-primary-600 hover:text-primary-900 mr-3" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmDelete({{ $subscription->id }}, '{{ $subscription->user ? addslashes($subscription->user->name) : 'Unknown' }}')" class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="bg-gray-100 rounded-full p-4 mb-4">
                                    <i class="fas fa-credit-card text-gray-400 text-3xl"></i>
                                </div>
                                <p class="text-gray-500 mb-2">No subscriptions found</p>
                                <button onclick="openCreateModal()" class="text-primary-600 hover:text-primary-700 font-medium">
                                    Create your first subscription
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($subscriptions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $subscriptions->links() }}
        </div>
        @endif
    </div>
</main>

<!-- Create/Edit Subscription Modal -->
<div id="subscriptionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 sticky top-0 bg-white">
                <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Add New Subscription</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form id="subscriptionForm" method="POST" action="{{ route('admin.subscriptions.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="p-6 space-y-4">
                    <!-- User & Package Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">User *</label>
                            <select name="user_id" id="userId" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Select User</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->first_name ? $user->first_name . ' ' . $user->last_name : $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Package *</label>
                            <select name="package_id" id="packageId" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Select Package</option>
                                @foreach($packages as $package)
                                <option value="{{ $package->id }}" data-price="{{ $package->price }}">{{ $package->name }} ({{ $package->currency }} {{ number_format($package->price, 2) }} / {{ $package->billing_type }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Payment Method & Amount -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                            <select name="payment_method_id" id="paymentMethodId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">No Payment</option>
                                @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">
                                    {{ $method->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount Paid (PKR)</label>
                            <input type="number" name="amount_paid" id="amountPaid" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="0.00">
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                            <input type="date" name="start_date" id="startDate" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date" id="endDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <p class="text-xs text-gray-500 mt-1">Leave empty for lifetime</p>
                        </div>
                    </div>

                    <!-- Status & Options -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="flex items-center pt-6">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_trial" id="isTrial" value="1" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">Trial Period</span>
                            </label>
                        </div>

                        <div class="flex items-center pt-6">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="auto_renew" id="autoRenew" value="1" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">Auto Renew</span>
                            </label>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" id="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Optional notes about this subscription..."></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200 sticky bottom-0 bg-white">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        <span id="submitBtnText">Create Subscription</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-red-100 rounded-full p-3 mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Delete Subscription</h3>
                </div>
                <p class="text-gray-600 mb-2">Are you sure you want to delete subscription for:</p>
                <p class="text-sm font-medium text-gray-800 mb-4" id="deleteUserName"></p>
                <p class="text-sm text-red-600 mb-6">This action cannot be undone.</p>
                <div class="flex space-x-3">
                    <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form id="deleteForm" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Subscription';
    document.getElementById('submitBtnText').textContent = 'Create Subscription';
    document.getElementById('subscriptionForm').action = '{{ route("admin.subscriptions.store") }}';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('subscriptionForm').reset();
    document.getElementById('startDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('subscriptionModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function openEditModal(subscriptionId) {
    fetch(`{{ url('admin/subscriptions') }}/${subscriptionId}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Subscription';
            document.getElementById('submitBtnText').textContent = 'Update Subscription';
            document.getElementById('subscriptionForm').action = `{{ url('admin/subscriptions') }}/${subscriptionId}`;
            document.getElementById('formMethod').value = 'PUT';

            document.getElementById('userId').value = data.user_id;
            document.getElementById('packageId').value = data.package_id;
            document.getElementById('paymentMethodId').value = data.payment_method_id || '';
            document.getElementById('amountPaid').value = data.amount_paid || '';
            document.getElementById('startDate').value = data.start_date ? data.start_date.split('T')[0] : '';
            document.getElementById('endDate').value = data.end_date ? data.end_date.split('T')[0] : '';
            document.getElementById('status').value = data.status;
            document.getElementById('isTrial').checked = data.is_trial;
            document.getElementById('autoRenew').checked = data.auto_renew;
            document.getElementById('notes').value = data.notes || '';

            document.getElementById('subscriptionModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
}

function closeModal() {
    document.getElementById('subscriptionModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function confirmDelete(subscriptionId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteForm').action = `{{ url('admin/subscriptions') }}/${subscriptionId}`;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function toggleStatus(subscriptionId) {
    fetch(`{{ url('admin/subscriptions') }}/${subscriptionId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById(`status-badge-${subscriptionId}`);
            const dot = document.getElementById(`status-dot-${subscriptionId}`);
            const text = document.getElementById(`status-text-${subscriptionId}`);

            // Reset classes
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors';
            dot.className = 'w-2 h-2 rounded-full mr-1.5';

            if (data.status === 'active') {
                badge.classList.add('bg-green-100', 'text-green-800', 'hover:bg-green-200');
                dot.classList.add('bg-green-400');
            } else if (data.status === 'cancelled') {
                badge.classList.add('bg-red-100', 'text-red-800', 'hover:bg-red-200');
                dot.classList.add('bg-red-400');
            }
            text.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);

            // Show success message with end date info
            if (data.status === 'active' && data.end_date) {
                alert('Subscription activated! End date: ' + data.end_date + '\nPayment status updated to completed.');
            }
        }
    });
}

// Auto-fill amount when package is selected
document.getElementById('packageId').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = selectedOption.dataset.price;
    if (price) {
        document.getElementById('amountPaid').value = price;
    }
});

// Close modals on outside click
document.getElementById('subscriptionModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

// Close modals on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
    }
});
</script>
@endpush
