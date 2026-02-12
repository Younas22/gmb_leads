@extends('layouts.admin')

@section('title', 'API Usage Management')

@section('content')
<main class="p-4 lg:p-8">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">API Usage Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage API keys, track usage and costs</p>
        </div>
        <button onclick="openCreateModal()" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>
            Add API Key
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total API Calls -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total API Calls</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalCalls) }}</p>
                </div>
                <div class="bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-server text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Text Search Calls -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Text Search Calls</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalTextSearchCalls) }}</p>
                    <p class="text-xs text-green-600 mt-1">${{ number_format($totalTextSearchCost, 2) }} cost</p>
                </div>
                <div class="bg-green-100 rounded-lg p-3">
                    <i class="fas fa-search text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Details API Calls -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Details API Calls</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalDetailsCalls) }}</p>
                    <p class="text-xs text-purple-600 mt-1">${{ number_format($totalDetailsCost, 2) }} cost</p>
                </div>
                <div class="bg-purple-100 rounded-lg p-3">
                    <i class="fas fa-info-circle text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Cost -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Cost</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">${{ number_format($totalCost, 2) }}</p>
                </div>
                <div class="bg-red-100 rounded-lg p-3">
                    <i class="fas fa-dollar-sign text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- API Keys Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">API Key</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Text Search</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details API</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($apiKeys as $key)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-key text-blue-600"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $key->key_name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $key->masked_api_key }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ number_format($key->text_search_count) }} calls</div>
                            <div class="text-xs text-gray-500">${{ number_format($key->text_search_price, 4) }}/call</div>
                            <div class="text-xs text-green-600 font-medium">${{ number_format($key->text_search_total_cost, 2) }} total</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ number_format($key->details_count) }} calls</div>
                            <div class="text-xs text-gray-500">${{ number_format($key->details_price, 4) }}/call</div>
                            <div class="text-xs text-purple-600 font-medium">${{ number_format($key->details_total_cost, 2) }} total</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">${{ number_format($key->total_cost, 2) }}</div>
                            <div class="text-xs text-gray-500">{{ number_format($key->total_calls) }} total calls</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="toggleStatus({{ $key->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors {{ $key->status === 'active' ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}" id="status-badge-{{ $key->id }}">
                                <span class="w-2 h-2 rounded-full mr-1.5 {{ $key->status === 'active' ? 'bg-green-400' : 'bg-red-400' }}" id="status-dot-{{ $key->id }}"></span>
                                <span id="status-text-{{ $key->id }}">{{ ucfirst($key->status) }}</span>
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal({{ $key->id }})" class="text-primary-600 hover:text-primary-900 mr-2" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmReset({{ $key->id }}, '{{ $key->key_name }}')" class="text-orange-600 hover:text-orange-900 mr-2" title="Reset Counts">
                                <i class="fas fa-redo"></i>
                            </button>
                            <button onclick="confirmDelete({{ $key->id }}, '{{ $key->key_name }}')" class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="bg-gray-100 rounded-full p-4 mb-4">
                                    <i class="fas fa-key text-gray-400 text-3xl"></i>
                                </div>
                                <p class="text-gray-500 mb-2">No API keys found</p>
                                <button onclick="openCreateModal()" class="text-primary-600 hover:text-primary-700 font-medium">
                                    Add your first API key
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Create/Edit API Key Modal -->
<div id="apiKeyModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Add New API Key</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="apiKeyForm" method="POST" action="{{ route('admin.api-usage.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Key Name *</label>
                        <input type="text" name="key_name" id="keyName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="e.g., Google Maps API Key 1">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API Key *</label>
                        <input type="text" name="api_key" id="apiKeyInput" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono text-sm" placeholder="Enter API key">
                        <p class="text-xs text-gray-400 mt-1" id="apiKeyHint">Required when adding new key</p>
                    </div>

                    <div class="border-t pt-5">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-search text-green-600 mr-1"></i> Text Search API Pricing
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Price per Call ($) *</label>
                                <input type="number" name="text_search_price" id="textSearchPrice" step="0.0001" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="0.00">
                            </div>
                            <div id="textSearchCountField" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Call Count</label>
                                <input type="number" name="text_search_count" id="textSearchCount" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-5">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-info-circle text-purple-600 mr-1"></i> Details API Pricing
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Price per Call ($) *</label>
                                <input type="number" name="details_price" id="detailsPrice" step="0.0001" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="0.00">
                            </div>
                            <div id="detailsCountField" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Call Count</label>
                                <input type="number" name="details_count" id="detailsCount" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" value="0">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select name="status" id="keyStatus" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        <span id="submitBtnText">Add API Key</span>
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
                    <h3 class="text-lg font-semibold text-gray-800">Delete API Key</h3>
                </div>
                <p class="text-gray-600 mb-2">Are you sure you want to delete this API key?</p>
                <p class="text-sm font-medium text-gray-800 mb-4" id="deleteKeyName"></p>
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

<!-- Reset Counts Confirmation Modal -->
<div id="resetModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-orange-100 rounded-full p-3 mr-4">
                        <i class="fas fa-redo text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Reset Counts</h3>
                </div>
                <p class="text-gray-600 mb-2">Reset all API call counts and costs for this key?</p>
                <p class="text-sm font-medium text-gray-800 mb-4" id="resetKeyName"></p>
                <p class="text-sm text-orange-600 mb-6">This will set all counts and costs to zero.</p>
                <div class="flex space-x-3">
                    <button onclick="closeResetModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form id="resetForm" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                            Reset
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
let isEditMode = false;

function openCreateModal() {
    isEditMode = false;
    document.getElementById('modalTitle').textContent = 'Add New API Key';
    document.getElementById('submitBtnText').textContent = 'Add API Key';
    document.getElementById('apiKeyForm').action = '{{ route("admin.api-usage.store") }}';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('apiKeyForm').reset();
    document.getElementById('apiKeyInput').required = true;
    document.getElementById('apiKeyHint').textContent = 'Required when adding new key';
    document.getElementById('textSearchCountField').classList.add('hidden');
    document.getElementById('detailsCountField').classList.add('hidden');
    document.getElementById('apiKeyModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function openEditModal(keyId) {
    fetch(`{{ url('admin/api-usage') }}/${keyId}/edit`)
        .then(response => response.json())
        .then(data => {
            isEditMode = true;
            document.getElementById('modalTitle').textContent = 'Edit API Key';
            document.getElementById('submitBtnText').textContent = 'Update API Key';
            document.getElementById('apiKeyForm').action = `{{ url('admin/api-usage') }}/${keyId}`;
            document.getElementById('formMethod').value = 'PUT';

            document.getElementById('keyName').value = data.key_name;
            document.getElementById('apiKeyInput').value = '';
            document.getElementById('apiKeyInput').required = false;
            document.getElementById('apiKeyInput').placeholder = 'Leave empty to keep current key';
            document.getElementById('apiKeyHint').textContent = 'Leave empty to keep the current key unchanged';
            document.getElementById('textSearchPrice').value = data.text_search_price;
            document.getElementById('detailsPrice').value = data.details_price;
            document.getElementById('textSearchCount').value = data.text_search_count;
            document.getElementById('detailsCount').value = data.details_count;
            document.getElementById('keyStatus').value = data.status;

            document.getElementById('textSearchCountField').classList.remove('hidden');
            document.getElementById('detailsCountField').classList.remove('hidden');

            document.getElementById('apiKeyModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
}

function closeModal() {
    document.getElementById('apiKeyModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function confirmDelete(keyId, keyName) {
    document.getElementById('deleteKeyName').textContent = keyName;
    document.getElementById('deleteForm').action = `{{ url('admin/api-usage') }}/${keyId}`;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function confirmReset(keyId, keyName) {
    document.getElementById('resetKeyName').textContent = keyName;
    document.getElementById('resetForm').action = `{{ url('admin/api-usage') }}/${keyId}/reset-counts`;
    document.getElementById('resetModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeResetModal() {
    document.getElementById('resetModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function toggleStatus(keyId) {
    fetch(`{{ url('admin/api-usage') }}/${keyId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById(`status-badge-${keyId}`);
            const dot = document.getElementById(`status-dot-${keyId}`);
            const text = document.getElementById(`status-text-${keyId}`);

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
document.getElementById('apiKeyModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
document.getElementById('resetModal').addEventListener('click', function(e) {
    if (e.target === this) closeResetModal();
});

// Close modals on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
        closeResetModal();
    }
});
</script>
@endpush
