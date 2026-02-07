@extends('layouts.admin')

@section('title', 'Packages Management')

@section('content')
<main class="p-4 lg:p-8">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Packages Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage subscription packages and pricing</p>
        </div>
        <button onclick="openCreateModal()" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>
            Add Package
        </button>
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

    <!-- Packages Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Billing</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Features</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($packages as $package)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-box text-primary-600"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $package->name }}
                                        @if($package->is_popular)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                            Popular
                                        </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $package->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $package->package_for === 'user' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                <i class="fas {{ $package->package_for === 'user' ? 'fa-user' : 'fa-building' }} mr-1"></i>
                                {{ ucfirst($package->package_for) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ ucfirst($package->billing_type) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $package->currency }} {{ number_format($package->price, 2) }}</div>
                            @if($package->max_users)
                            <div class="text-xs text-gray-500">Max {{ $package->max_users }} users</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-500">{{ $package->features->count() }} features</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="toggleStatus({{ $package->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors {{ $package->status === 'active' ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}" id="status-badge-{{ $package->id }}">
                                <span class="w-2 h-2 rounded-full mr-1.5 {{ $package->status === 'active' ? 'bg-green-400' : 'bg-red-400' }}" id="status-dot-{{ $package->id }}"></span>
                                <span id="status-text-{{ $package->id }}">{{ ucfirst($package->status) }}</span>
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal({{ $package->id }})" class="text-primary-600 hover:text-primary-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmDelete({{ $package->id }}, '{{ $package->name }}')" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="bg-gray-100 rounded-full p-4 mb-4">
                                    <i class="fas fa-box-open text-gray-400 text-3xl"></i>
                                </div>
                                <p class="text-gray-500 mb-2">No packages found</p>
                                <button onclick="openCreateModal()" class="text-primary-600 hover:text-primary-700 font-medium">
                                    Create your first package
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($packages->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $packages->links() }}
        </div>
        @endif
    </div>
</main>

<!-- Create/Edit Package Modal -->
<div id="packageModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Add New Package</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form id="packageForm" method="POST" action="{{ route('admin.packages.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="p-6 space-y-6">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Package Name *</label>
                            <input type="text" name="name" id="packageName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., Pro Plan">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Package For *</label>
                            <select name="package_for" id="packageFor" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="user">User</option>
                                <option value="company">Company</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Billing Type *</label>
                            <select name="billing_type" id="billingType" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="lifetime">Lifetime</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                            <input type="number" name="price" id="packagePrice" step="0.01" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                            <select name="currency" id="packageCurrency" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                                <option value="PKR">PKR</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max Users (for company packages)</label>
                            <input type="number" name="max_users" id="maxUsers" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Leave empty for unlimited">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select name="status" id="packageStatus" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="packageDescription" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Package description..."></textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_popular" id="isPopular" value="1" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="isPopular" class="ml-2 text-sm text-gray-700">Mark as Popular</label>
                    </div>

                    <!-- Features Section -->
                    <div class="border-t pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-gray-700">Package Features</h4>
                            <button type="button" onclick="addFeature()" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                <i class="fas fa-plus mr-1"></i> Add Feature
                            </button>
                        </div>
                        <div id="featuresContainer" class="space-y-3">
                            <!-- Features will be added here dynamically -->
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Common keys: leads_limit, searches_per_day, api_keys, export_data, priority_support</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        <span id="submitBtnText">Create Package</span>
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
                    <h3 class="text-lg font-semibold text-gray-800">Delete Package</h3>
                </div>
                <p class="text-gray-600 mb-2">Are you sure you want to delete this package?</p>
                <p class="text-sm font-medium text-gray-800 mb-4" id="deletePackageName"></p>
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
let featureIndex = 0;

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Package';
    document.getElementById('submitBtnText').textContent = 'Create Package';
    document.getElementById('packageForm').action = '{{ route("admin.packages.store") }}';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('packageForm').reset();
    document.getElementById('featuresContainer').innerHTML = '';
    featureIndex = 0;
    addFeature(); // Add one empty feature row
    document.getElementById('packageModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function openEditModal(packageId) {
    fetch(`{{ url('admin/packages') }}/${packageId}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Package';
            document.getElementById('submitBtnText').textContent = 'Update Package';
            document.getElementById('packageForm').action = `{{ url('admin/packages') }}/${packageId}`;
            document.getElementById('formMethod').value = 'PUT';

            document.getElementById('packageName').value = data.name;
            document.getElementById('packageFor').value = data.package_for;
            document.getElementById('billingType').value = data.billing_type;
            document.getElementById('packagePrice').value = data.price;
            document.getElementById('packageCurrency').value = data.currency;
            document.getElementById('maxUsers').value = data.max_users || '';
            document.getElementById('packageStatus').value = data.status;
            document.getElementById('packageDescription').value = data.description || '';
            document.getElementById('isPopular').checked = data.is_popular;

            // Load features
            document.getElementById('featuresContainer').innerHTML = '';
            featureIndex = 0;
            if (data.features && data.features.length > 0) {
                data.features.forEach(feature => {
                    addFeature(feature.feature_key, feature.feature_value, feature.is_unlimited);
                });
            } else {
                addFeature();
            }

            document.getElementById('packageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
}

function closeModal() {
    document.getElementById('packageModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function addFeature(key = '', value = '', isUnlimited = false) {
    const container = document.getElementById('featuresContainer');
    const html = `
        <div class="flex items-center space-x-2" id="feature-${featureIndex}">
            <input type="text" name="features[${featureIndex}][key]" value="${key}" placeholder="Feature key" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            <input type="text" name="features[${featureIndex}][value]" value="${value}" placeholder="Value" class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            <label class="flex items-center space-x-1 text-sm text-gray-600">
                <input type="checkbox" name="features[${featureIndex}][is_unlimited]" value="1" ${isUnlimited ? 'checked' : ''} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span>Unlimited</span>
            </label>
            <button type="button" onclick="removeFeature(${featureIndex})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    featureIndex++;
}

function removeFeature(index) {
    const element = document.getElementById(`feature-${index}`);
    if (element) {
        element.remove();
    }
}

function confirmDelete(packageId, packageName) {
    document.getElementById('deletePackageName').textContent = packageName;
    document.getElementById('deleteForm').action = `{{ url('admin/packages') }}/${packageId}`;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function toggleStatus(packageId) {
    fetch(`{{ url('admin/packages') }}/${packageId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById(`status-badge-${packageId}`);
            const dot = document.getElementById(`status-dot-${packageId}`);
            const text = document.getElementById(`status-text-${packageId}`);

            if (data.status === 'active') {
                badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors bg-green-100 text-green-800 hover:bg-green-200';
                dot.className = 'w-2 h-2 rounded-full mr-1.5 bg-green-400';
                text.textContent = 'Active';
            } else {
                badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors bg-red-100 text-red-800 hover:bg-red-200';
                dot.className = 'w-2 h-2 rounded-full mr-1.5 bg-red-400';
                text.textContent = 'Inactive';
            }
        }
    });
}

// Close modals on outside click
document.getElementById('packageModal').addEventListener('click', function(e) {
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
