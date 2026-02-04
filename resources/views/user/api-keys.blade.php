@extends('layouts.app')

@section('title', 'API Keys Management')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Main Content -->
<div class="p-4 lg:p-8">
    <!-- Plan Limitation Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-600 text-lg"></i>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-semibold text-blue-800">Google Places API Management</h3>
                <p class="text-sm text-blue-700 mt-1">
                    Add your Google Places API key to start searching for businesses. Free plan allows 1 API key, Pro plan supports unlimited keys.
                </p>
            </div>
        </div>
    </div>

    <!-- Usage Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-primary-100 rounded-lg">
                    <i class="fas fa-chart-line text-primary-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">API Calls Today</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $apiKeys->sum('usage_count') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Keys</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $apiKeys->where('is_active', true)->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-lg">
                    <i class="fas fa-calendar-alt text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Keys</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $apiKeys->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Errors Today</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $apiKeys->sum('error_count') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- API Keys Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Google Places API Keys</h3>
                    <p class="text-sm text-gray-600 mt-1">Manage your Google Places API keys for business search</p>
                </div>
                <button id="add-api-btn" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-plus mr-2"></i>Add API Key
                </button>
            </div>
        </div>

        <div class="p-6 space-y-4">
            @forelse($apiKeys as $apiKey)
            <!-- API Key Card -->
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fab fa-google text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">{{ $apiKey->key_name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $apiKey->google_email }}</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 {{ $apiKey->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }} text-xs font-medium rounded-full">
                                {{ $apiKey->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="px-2 py-1 {{ $apiKey->is_valid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} text-xs font-medium rounded-full">
                                {{ $apiKey->is_valid ? 'Valid' : 'Invalid' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">API Key</p>
                                <div class="flex items-center space-x-2">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded font-mono">{{ $apiKey->masked_api_key }}</code>
                                    <button onclick="copyToClipboard('{{ $apiKey->api_key }}')" class="text-gray-400 hover:text-gray-600" title="Copy to clipboard">
                                        <i class="fas fa-copy text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Usage Today</p>
                                <p class="text-sm font-medium text-gray-800">{{ $apiKey->usage_count }} / {{ $apiKey->daily_limit }} calls</p>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    @php
                                        $percentage = $apiKey->daily_limit > 0 ? ($apiKey->usage_count / $apiKey->daily_limit) * 100 : 0;
                                    @endphp
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Last Used</p>
                                <p class="text-sm font-medium text-gray-800">
                                    {{ $apiKey->last_used ? $apiKey->last_used->diffForHumans() : 'Never' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex space-x-2">
                                <button onclick="testApiKey({{ $apiKey->id }})" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-medium">
                                    <i class="fas fa-vial mr-1"></i>Test API
                                </button>
                                <button onclick="editApiKey({{ $apiKey->id }}, '{{ $apiKey->key_name }}', '{{ $apiKey->api_key }}', '{{ $apiKey->google_email }}')" class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1 rounded text-xs font-medium">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <button onclick="deleteApiKey({{ $apiKey->id }})" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs font-medium">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500">Status:</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" {{ $apiKey->is_active ? 'checked' : '' }} 
                                           onchange="toggleApiKey({{ $apiKey->id }})" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <!-- Empty State -->
            <div class="text-center py-12" id="empty-state">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">No API Keys Added</h3>
                <p class="text-gray-600 mb-4">Add your Google Places API key to start searching for businesses</p>
                <button id="add-first-api-btn" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-plus mr-2"></i>Add Your First API Key
                </button>
            </div>
            @endforelse
        </div>
    </div>
</div>


<!-- Toast Container -->
<div id="toast-container" class="fixed top-4 right-4 z-40"></div>


<!-- Your existing modal HTML with some additions -->
<div id="api-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-800" id="modal-title">Add Google Places API Key</h3>
                <button id="close-modal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="api-form" class="space-y-6">
                <input type="hidden" id="api-key-id" value="">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">API Key Name *</label>
                    <input type="text" id="api-key-name" placeholder="e.g., Production API Key" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Google Account Email *</label>
                    <input type="email" id="google-email" placeholder="your.email@gmail.com" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <p class="text-xs text-gray-500 mt-1">The Google account email associated with this API key</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Google Places API Key *</label>
                    <input type="text" id="api-key" placeholder="AIzaSyDxVlabcdef123456789..." required
                           oninput="onApiKeyChange()"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <p class="text-xs text-gray-500 mt-1">Enter your Google Places API key from Google Cloud Console</p>
                </div>

                <!-- Test API Section -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-800 mb-3">Test API Key</h4>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <input type="text" id="test-query" placeholder="restaurants in Dubai" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        <div>
                            <button type="button" id="test-api-btn" onclick="testApiKeyInModal()" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium disabled:bg-gray-400 disabled:cursor-not-allowed">
                                <i class="fas fa-vial mr-2"></i>Test API Key
                            </button>
                        </div>
                    </div>
                    
                    <!-- Test Result -->
                    <div id="test-result" class="mt-4 hidden"></div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                    <button type="button" id="cancel-btn" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="save-btn" disabled 
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-2"></i>Save API Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Update your existing script section with this enhanced version -->
<script>
// Global variables
let isApiTested = false;
let isApiValid = false;
let isEditMode = false;

// Base URL function using Laravel's url() helper
function getBaseUrl() {
    let base = @json(url('/'));
    return base.endsWith('/') ? base : base + '/';
}


// API URL builder
function buildApiUrl(endpoint) {
    let base = getBaseUrl();
    return `${base}${endpoint.startsWith('/') ? endpoint.substring(1) : endpoint}`;
}



function showToast(type, title, message, duration = 5000) {
    const toastContainer = document.getElementById('toast-container');
    const toastId = 'toast-' + Date.now();

    const iconClasses = {
        success: 'fa-check-circle text-green-500',
        error: 'fa-exclamation-circle text-red-500',
        warning: 'fa-exclamation-triangle text-yellow-500',
        info: 'fa-info-circle text-blue-500'
    };

    const bgClasses = {
        success: 'bg-green-100 border-l-4 border-green-500',
        error: 'bg-red-100 border-l-4 border-red-500',
        warning: 'bg-yellow-100 border-l-4 border-yellow-500',
        info: 'bg-blue-100 border-l-4 border-blue-500'
    };

    const textClasses = {
        success: 'text-green-800',
        error: 'text-red-800',
        warning: 'text-yellow-800',
        info: 'text-blue-800'
    };

    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `transform transition-all duration-300 ease-in-out translate-x-full opacity-0`;

    toast.innerHTML = `
        <div class="max-w-sm w-full ${bgClasses[type]} rounded-md shadow-lg pointer-events-auto">
            <div class="flex p-4">
                <div class="flex-shrink-0">
                    <i class="fas ${iconClasses[type]} text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-bold ${textClasses[type]}">${title}</p>
                    <p class="text-sm ${textClasses[type]} opacity-90">${message}</p>
                </div>
                <div class="ml-3">
                    <button onclick="closeToast('${toastId}')" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    `;

    toastContainer.appendChild(toast);

    // Animate in
    setTimeout(() => {
        toast.className = `transform transition-all duration-300 ease-in-out translate-x-0 opacity-100`;
    }, 100);

    // Auto remove
    if (duration > 0) {
        setTimeout(() => {
            closeToast(toastId);
        }, duration);
    }
}


function closeToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast) {
        toast.className = `transform transition-all duration-300 ease-in-out translate-x-full opacity-0`;
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

// Modal elements
const modal = document.getElementById('api-modal');
const addApiBtn = document.getElementById('add-api-btn');
const addFirstApiBtn = document.getElementById('add-first-api-btn');
const closeModal = document.getElementById('close-modal');
const cancelBtn = document.getElementById('cancel-btn');
const modalTitle = document.getElementById('modal-title');
const testResult = document.getElementById('test-result');
const saveBtn = document.getElementById('save-btn');

// Reset form state
function resetFormState() {
    isApiTested = false;
    isApiValid = false;
    isEditMode = false;
    updateSaveButton();
}

// Update save button state
function updateSaveButton() {
    const apiKeyValue = document.getElementById('api-key').value.trim();
    
    if (isEditMode) {
        // For edit mode, enable save button if API key is not empty
        saveBtn.disabled = !apiKeyValue;
    } else {
        // For new API key, require testing and validation
        saveBtn.disabled = !isApiTested || !isApiValid || !apiKeyValue;
    }
}

// Handle API key input change
function onApiKeyChange() {
    const apiKeyValue = document.getElementById('api-key').value.trim();
    
    if (!isEditMode) {
        // Reset test state when API key changes (only for new keys)
        isApiTested = false;
        isApiValid = false;
        testResult.classList.add('hidden');
    }
    
    updateSaveButton();
}

// Open modal for adding new API key
function openAddModal() {
    modalTitle.textContent = 'Add Google Places API Key';
    document.getElementById('api-form').reset();
    document.getElementById('api-key-id').value = '';
    testResult.classList.add('hidden');
    resetFormState();
    modal.classList.remove('hidden');
}

// Close modal
function closeApiModal() {
    modal.classList.add('hidden');
    resetFormState();
}

// Event listeners for modal
if (addApiBtn) addApiBtn.addEventListener('click', openAddModal);
if (addFirstApiBtn) addFirstApiBtn.addEventListener('click', openAddModal);
closeModal.addEventListener('click', closeApiModal);
cancelBtn.addEventListener('click', closeApiModal);

// Close modal when clicking outside
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        closeApiModal();
    }
});

// Enhanced form submission
document.getElementById('api-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    // Check if API key needs testing for new additions
    if (!isEditMode && (!isApiTested || !isApiValid)) {
        showToast('warning', 'API Key Test Required', 'Please test your API key before saving.');
        return;
    }

    const formData = new FormData();
    formData.append('key_name', document.getElementById('api-key-name').value);
    formData.append('api_key', document.getElementById('api-key').value);
    formData.append('google_email', document.getElementById('google-email').value);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    const isEdit = document.getElementById('api-key-id').value;
    const url = isEdit 
        ? buildApiUrl(`/user/api-keys/${isEdit}`)
        : buildApiUrl('/user/api-keys');

    if (isEdit) {
        formData.append('_method', 'PUT');
    }
    
    // Disable save button during submission
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('success', 'Success!', result.message);
            closeApiModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', 'Error', result.message || 'Failed to save API key');
        }
    } catch (error) {
        showToast('error', 'Network Error', 'Failed to save API key. Please try again.');
        console.error(error);
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save API Key';
        updateSaveButton();
    }
});

// Enhanced test API key function
async function testApiKeyInModal() {
    const testBtn = document.getElementById('test-api-btn');
    const apiKey = document.getElementById('api-key').value.trim();
    const testQuery = document.getElementById('test-query').value || 'restaurants in Dubai';
    
    if (!apiKey) {
        showTestResult('error', 'Please enter an API key');
        return;
    }
    
    // Basic format validation
    if (apiKey.length < 30) {
        showTestResult('error', 'Invalid API key format', 'API key is too short');
        return;
    }
    
    testBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testing...';
    testBtn.disabled = true;

    try {
        const response = await fetch(buildApiUrl('/user/api/test-api-key'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                api_key: apiKey,
                query: testQuery
            })
        });
        
        const result = await response.json();
        
        isApiTested = true;
        isApiValid = result.success;
        
        if (result.success) {
            showTestResult('success', result.message, result.details || '');
            showToast('success', 'API Key Valid', 'Your API key is working correctly!');
        } else {
            showTestResult('error', result.message, result.details || '');
            showToast('error', 'API Key Invalid', result.message);
        }
        
        updateSaveButton();
        
    } catch (error) {
        console.error('API test error:', error);
        isApiTested = true;
        isApiValid = false;
        showTestResult('error', 'Connection failed', 'Unable to test API key. Please try again.');
        showToast('error', 'Test Failed', 'Unable to connect to test service.');
        updateSaveButton();
    } finally {
        testBtn.innerHTML = '<i class="fas fa-vial mr-2"></i>Test API Key';
        testBtn.disabled = false;
    }
}

// Enhanced showTestResult function
function showTestResult(type, message, details = '') {
    const resultDiv = document.getElementById('test-result');
    if (!resultDiv) {
        console.error('Test result div not found');
        return;
    }
    
    const iconClass = type === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500';
    const bgClass = type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
    const textClass = type === 'success' ? 'text-green-800' : 'text-red-800';
    
    resultDiv.innerHTML = `
        <div class="p-4 rounded-lg border ${bgClass} ${textClass}">
            <div class="flex items-start">
                <i class="fas ${iconClass} mt-1 mr-3 flex-shrink-0"></i>
                <div class="flex-1">
                    <p class="font-semibold text-sm">${message}</p>
                    ${details ? `<p class="text-xs mt-1 opacity-75">${details}</p>` : ''}
                </div>
            </div>
        </div>
    `;
    
    resultDiv.classList.remove('hidden');
}

// Test existing API key
async function testApiKey(apiKeyId) {
    try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        const response = await fetch(buildApiUrl(`/user/api-keys/${apiKeyId}/test`), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('success', 'Test Successful', result.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', 'Test Failed', result.message);
        }
    } catch (error) {
        showToast('error', 'Network Error', 'Error testing API key');
        console.error(error);
    }
}

// Toggle API key status
async function toggleApiKey(apiKeyId) {
    try {
        const response = await fetch(buildApiUrl(`/user/api-keys/${apiKeyId}/toggle`), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('success', 'Status Updated', 'API key status has been updated');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', 'Update Failed', 'Failed to update API key status');
        }
    } catch (error) {
        showToast('error', 'Network Error', 'Error updating API key status');
        console.error(error);
    }
}

// Delete API key
async function deleteApiKey(apiKeyId) {
    if (!confirm('Are you sure you want to delete this API key?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        const response = await fetch(buildApiUrl(`/user/api-keys/${apiKeyId}`), {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            showToast('success', 'Deleted', 'API key has been deleted successfully');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', 'Delete Failed', 'Failed to delete API key');
        }
    } catch (error) {
        showToast('error', 'Network Error', 'Error deleting API key');
        console.error(error);
    }
}

// Edit API key
function editApiKey(apiKeyId, keyName, apiKey, googleEmail) {
    document.getElementById('api-key-id').value = apiKeyId;
    document.getElementById('api-key-name').value = keyName;
    document.getElementById('api-key').value = apiKey;
    document.getElementById('google-email').value = googleEmail;
    
    modalTitle.textContent = 'Edit Google Places API Key';
    testResult.classList.add('hidden');
    
    // Set edit mode
    isEditMode = true;
    isApiTested = true; // Consider existing API as tested
    isApiValid = true; // Assume existing API is valid unless tested again
    
    updateSaveButton();
    modal.classList.remove('hidden');
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('success', 'Copied!', 'API key copied to clipboard');
    }).catch(() => {
        showToast('error', 'Copy Failed', 'Failed to copy API key');
    });
}
</script>
@endsection