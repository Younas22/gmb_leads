@extends('layouts.app')

@section('title', 'API Keys Management')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">

<!-- Custom Choices.js Styling -->
<style>
    .choices__inner {
        background-color: #fff;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        min-height: 42px;
        font-size: 0.875rem;
    }

    .choices__inner:focus,
    .choices.is-focused .choices__inner {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .choices__list--multiple .choices__item {
        background-color: #4f46e5;
        border: 1px solid #4338ca;
        border-radius: 0.375rem;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        margin-bottom: 0.25rem;
    }

    .choices__list--multiple .choices__item.is-highlighted {
        background-color: #4338ca;
        border-color: #3730a3;
    }

    .choices__button {
        border-left: 1px solid #6366f1;
        padding: 0 0.5rem;
        margin-left: 0.25rem;
    }

    .choices__list--dropdown {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        margin-top: 0.25rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        z-index: 100;
    }

    .choices__list--dropdown .choices__item--selectable {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }

    .choices__list--dropdown .choices__item--selectable.is-highlighted {
        background-color: #eef2ff;
        color: #4f46e5;
    }

    .choices__input {
        font-size: 0.875rem;
        padding: 0.25rem 0;
    }

    .choices__placeholder {
        opacity: 0.5;
    }

    @media (max-width: 640px) {
        .choices__inner {
            font-size: 0.75rem;
            padding: 0.375rem 0.5rem;
        }

        .choices__list--multiple .choices__item {
            font-size: 0.6875rem;
            padding: 0.1875rem 0.375rem;
        }

        .choices__list--dropdown .choices__item--selectable {
            padding: 0.375rem 0.5rem;
            font-size: 0.75rem;
        }
    }
</style>

<!-- Main Content -->
<div class="p-4 lg:p-8">
    <!-- Plan Limitation Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 sm:p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-600 text-base sm:text-lg"></i>
            </div>
            <div class="ml-2 sm:ml-3 flex-1">
                <h3 class="text-xs sm:text-sm font-semibold text-blue-800">Google Places API Management</h3>
                <p class="text-xs sm:text-sm text-blue-700 mt-1">
                    Add your Google Places API key to start searching for businesses.
                    @if($apiLimit == -1)
                        Your plan supports <strong>unlimited</strong> API keys.
                    @else
                        Your plan allows <strong>{{ $apiLimit }}</strong> API key(s).
                        @if($remainingSlots === 0)
                            <span class="text-red-600 font-semibold">Limit reached!</span>
                        @else
                            Remaining: <strong>{{ $remainingSlots }}</strong>
                        @endif
                    @endif
                </p>
                <p class="text-xs sm:text-sm text-blue-700 mt-2">
                    <i class="fas fa-external-link-alt mr-1"></i>
                    Don't have an API key?
                    <a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank" class="font-semibold underline hover:text-blue-900 break-all">
                        Get your Google Places API key here
                    </a>
                </p>
                <p class="text-xs text-blue-600 mt-2">
                    <i class="fas fa-lock mr-1"></i> Note: Once API key is verified, it cannot be edited or deleted for security reasons.
                </p>
            </div>
        </div>
    </div>

    <!-- Usage Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 bg-primary-100 rounded-lg">
                    <i class="fas fa-chart-line text-primary-600 text-base sm:text-xl"></i>
                </div>
                <div class="ml-3 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">API Calls Today</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ $apiKeys->sum('usage_count') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-base sm:text-xl"></i>
                </div>
                <div class="ml-3 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Active Keys</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ $apiKeys->where('is_active', true)->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 bg-orange-100 rounded-lg">
                    <i class="fas fa-calendar-alt text-orange-600 text-base sm:text-xl"></i>
                </div>
                <div class="ml-3 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Total Keys</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ $apiKeys->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-exclamation-circle text-red-600 text-base sm:text-xl"></i>
                </div>
                <div class="ml-3 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Errors Today</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ $apiKeys->sum('error_count') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- API Keys Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                <div class="w-full sm:w-auto">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Google Places API Keys</h3>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1">Manage your Google Places API keys for business search</p>
                </div>
                @if($canAddMore)
                <button id="add-api-btn" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white px-3 sm:px-4 py-2 rounded-lg text-sm sm:text-base font-medium text-center">
                    <i class="fas fa-plus mr-1 sm:mr-2 text-xs"></i>Add API Key
                </button>
                @else
                <button disabled class="w-full sm:w-auto bg-gray-400 cursor-not-allowed text-white px-3 sm:px-4 py-2 rounded-lg text-sm sm:text-base font-medium text-center" title="API key limit reached">
                    <i class="fas fa-lock mr-1 sm:mr-2 text-xs"></i>Limit Reached
                </button>
                @endif
            </div>
        </div>

        <div class="p-6 space-y-4">
            @forelse($apiKeys as $apiKey)
            <!-- API Key Card -->
            <div class="border border-gray-200 rounded-lg p-3 sm:p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:space-x-3 mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fab fa-google text-blue-600 text-sm sm:text-base"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm sm:text-base font-semibold text-gray-800">{{ $apiKey->key_name }}</h4>
                                    <p class="text-xs sm:text-sm text-gray-600 break-all">{{ $apiKey->google_email }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2 ml-9 sm:ml-0">
                                <span class="px-2 py-0.5 sm:py-1 {{ $apiKey->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }} text-xs font-medium rounded-full whitespace-nowrap">
                                    {{ $apiKey->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="px-2 py-0.5 sm:py-1 {{ $apiKey->is_valid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} text-xs font-medium rounded-full whitespace-nowrap">
                                    {{ $apiKey->is_valid ? 'Valid' : 'Invalid' }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">API Key</p>
                                <div class="flex items-center space-x-2">
                                    <code class="text-xs sm:text-sm bg-gray-100 px-2 py-1 rounded font-mono break-all">{{ $apiKey->masked_api_key }}</code>
                                    <button onclick="copyToClipboard('{{ $apiKey->api_key }}')" class="text-gray-400 hover:text-gray-600 flex-shrink-0" title="Copy to clipboard">
                                        <i class="fas fa-copy text-xs sm:text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Usage Today</p>
                                <p class="text-xs sm:text-sm font-medium text-gray-800">{{ $apiKey->usage_count }} / {{ $apiKey->daily_limit }} calls</p>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 sm:h-2 mt-1">
                                    @php
                                        $percentage = $apiKey->daily_limit > 0 ? ($apiKey->usage_count / $apiKey->daily_limit) * 100 : 0;
                                    @endphp
                                    <div class="bg-blue-600 h-1.5 sm:h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Last Used</p>
                                <p class="text-xs sm:text-sm font-medium text-gray-800">
                                    {{ $apiKey->last_used ? $apiKey->last_used->diffForHumans() : 'Never' }}
                                </p>
                            </div>
                        </div>

                        @if($user->isCompany() && $apiKey->assignedUsers->count() > 0)
                        <div class="mb-4 pb-4 border-b border-gray-200">
                            <p class="text-xs text-gray-500 mb-2">Assigned to Users:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($apiKey->assignedUsers as $assignedUser)
                                <span class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-full">
                                    <i class="fas fa-user mr-1"></i>
                                    {{ $assignedUser->first_name }} {{ $assignedUser->last_name }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                            <div class="flex flex-wrap gap-2">
                                <button onclick="testApiKey({{ $apiKey->id }})" class="bg-green-600 hover:bg-green-700 text-white px-2 sm:px-3 py-1 sm:py-1.5 rounded text-xs font-medium whitespace-nowrap">
                                    <i class="fas fa-vial mr-1"></i>Test API
                                </button>
                                @if(!$apiKey->is_valid)
                                {{-- Only show Edit/Delete if key is NOT verified --}}
                                <button onclick='editApiKey({{ $apiKey->id }}, "{{ $apiKey->key_name }}", "{{ $apiKey->api_key }}", "{{ $apiKey->google_email }}", {{ json_encode($apiKey->assignedUsers->pluck("id")->toArray()) }})' class="bg-primary-600 hover:bg-primary-700 text-white px-2 sm:px-3 py-1 sm:py-1.5 rounded text-xs font-medium whitespace-nowrap">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                                <button onclick="deleteApiKey({{ $apiKey->id }})" class="bg-red-600 hover:bg-red-700 text-white px-2 sm:px-3 py-1 sm:py-1.5 rounded text-xs font-medium whitespace-nowrap">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                                @else
                                {{-- Show locked indicator for verified keys --}}
                                <span class="bg-gray-200 text-gray-600 px-2 sm:px-3 py-1 sm:py-1.5 rounded text-xs font-medium whitespace-nowrap" title="Verified key - cannot be modified">
                                    <i class="fas fa-lock mr-1"></i>Locked
                                </span>
                                @endif
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500">Status:</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" {{ $apiKey->is_active ? 'checked' : '' }}
                                           onchange="toggleApiKey({{ $apiKey->id }})" class="sr-only peer">
                                    <div class="w-9 h-5 sm:w-11 sm:h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 sm:after:h-5 sm:after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <!-- Empty State -->
            <div class="text-center py-12 px-4" id="empty-state">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-2">No API Keys Added</h3>
                <p class="text-sm sm:text-base text-gray-600 mb-4">Add your Google Places API key to start searching for businesses</p>
                @if($canAddMore)
                <button id="add-first-api-btn" class="bg-primary-600 hover:bg-primary-700 text-white px-4 sm:px-6 py-2 rounded-lg text-sm sm:text-base font-medium">
                    <i class="fas fa-plus mr-1 sm:mr-2 text-xs"></i>Add Your First API Key
                </button>
                @else
                <button disabled class="bg-gray-400 cursor-not-allowed text-white px-4 sm:px-6 py-2 rounded-lg text-sm sm:text-base font-medium">
                    <i class="fas fa-lock mr-1 sm:mr-2 text-xs"></i>API Key Limit Reached
                </button>
                <p class="text-xs sm:text-sm text-red-600 mt-2">Please upgrade your package to add API keys.</p>
                @endif
            </div>
            @endforelse
        </div>
    </div>
</div>


<!-- Toast Container -->
<div id="toast-container" class="fixed top-4 right-4 z-40"></div>


<!-- Your existing modal HTML with some additions -->
<div id="api-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-4 sm:top-20 mx-auto p-4 sm:p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white my-4 sm:my-0">
        <div class="mt-2 sm:mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <h3 class="text-base sm:text-lg font-semibold text-gray-800" id="modal-title">Add Google Places API Key</h3>
                <button id="close-modal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="api-form" class="space-y-4 sm:space-y-6">
                <input type="hidden" id="api-key-id" value="">

                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">API Key Name *</label>
                    <input type="text" id="api-key-name" placeholder="e.g., Production API Key" required
                           class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Google Account Email *</label>
                    <input type="email" id="google-email" placeholder="your.email@gmail.com" required
                           class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <p class="text-xs text-gray-500 mt-1">The Google account email associated with this API key</p>
                </div>

                @if($user->isCompany() && $teamMembers->count() > 0)
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-users mr-1"></i>Assign to Team Members
                    </label>
                    <select id="assigned-users" multiple>
                        @foreach($teamMembers as $member)
                        <option value="{{ $member->id }}">
                            {{ $member->first_name }} {{ $member->last_name }} ({{ $member->email }})
                        </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>Select team members who can use this API key
                    </p>
                </div>
                @endif

                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Google Places API Key *</label>
                    <input type="text" id="api-key" placeholder="AIzaSyDxVlabcdef123456789..." required
                           oninput="onApiKeyChange()"
                           class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <p class="text-xs text-gray-500 mt-1">
                        Enter your Google Places API key from Google Cloud Console
                        <a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium ml-1">
                            <i class="fas fa-external-link-alt"></i> Get API Key
                        </a>
                    </p>
                </div>

                <!-- Test API Section -->
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                    <h4 class="text-xs sm:text-sm font-semibold text-gray-800 mb-3">Test API Key</h4>
                    <div class="grid grid-cols-1 gap-3 sm:gap-4">
                        <div>
                            <input type="text" id="test-query" placeholder="restaurants in Dubai"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-xs sm:text-sm">
                        </div>
                        <div>
                            <button type="button" id="test-api-btn" onclick="testApiKeyInModal()"
                                    class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium disabled:bg-gray-400 disabled:cursor-not-allowed">
                                <i class="fas fa-vial mr-1 sm:mr-2"></i>Test API Key
                            </button>
                        </div>
                    </div>

                    <!-- Test Result -->
                    <div id="test-result" class="mt-3 sm:mt-4 hidden"></div>
                </div>

                <!-- Modal Actions -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2 sm:gap-3 sm:space-x-3 pt-4 sm:pt-6 border-t border-gray-200">
                    <button type="button" id="cancel-btn" class="px-3 sm:px-4 py-2 bg-gray-600 text-white rounded-lg text-sm sm:text-base hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="save-btn" disabled
                            class="px-3 sm:px-4 py-2 bg-primary-600 text-white rounded-lg text-sm sm:text-base hover:bg-primary-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-1 sm:mr-2"></i>Save API Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Choices.js Script -->
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>

<!-- Update your existing script section with this enhanced version -->
<script>
// Global variables
let isApiTested = false;
let isApiValid = false;
let isEditMode = false;
const canAddMore = {{ $canAddMore ? 'true' : 'false' }};
const apiLimit = {{ $apiLimit }};
const remainingSlots = '{{ $remainingSlots }}';
let choicesInstance = null;

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

// Initialize Choices.js for multi-select
function initializeChoices() {
    const assignedUsersSelect = document.getElementById('assigned-users');
    if (assignedUsersSelect && !choicesInstance) {
        choicesInstance = new Choices(assignedUsersSelect, {
            removeItemButton: true,
            searchEnabled: true,
            searchPlaceholderValue: 'Search team members...',
            placeholder: true,
            placeholderValue: 'Select team members',
            noResultsText: 'No team members found',
            itemSelectText: 'Click to select',
            classNames: {
                containerOuter: 'choices',
                containerInner: 'choices__inner',
                input: 'choices__input',
                inputCloned: 'choices__input--cloned',
                list: 'choices__list',
                listItems: 'choices__list--multiple',
                listSingle: 'choices__list--single',
                listDropdown: 'choices__list--dropdown',
                item: 'choices__item',
                itemSelectable: 'choices__item--selectable',
                itemDisabled: 'choices__item--disabled',
                itemChoice: 'choices__item--choice',
                placeholder: 'choices__placeholder',
                group: 'choices__group',
                groupHeading: 'choices__heading',
                button: 'choices__button',
                activeState: 'is-active',
                focusState: 'is-focused',
                openState: 'is-open',
                disabledState: 'is-disabled',
                highlightedState: 'is-highlighted',
                selectedState: 'is-selected',
                flippedState: 'is-flipped',
                loadingState: 'is-loading',
                noResults: 'has-no-results',
                noChoices: 'has-no-choices'
            }
        });
    }
}

// Destroy Choices.js instance
function destroyChoices() {
    if (choicesInstance) {
        choicesInstance.destroy();
        choicesInstance = null;
    }
}

// Open modal for adding new API key
function openAddModal() {
    modalTitle.textContent = 'Add Google Places API Key';
    document.getElementById('api-form').reset();
    document.getElementById('api-key-id').value = '';
    testResult.classList.add('hidden');
    resetFormState();
    modal.classList.remove('hidden');

    // Initialize Choices.js after modal is shown
    setTimeout(() => {
        initializeChoices();
    }, 100);
}

// Close modal
function closeApiModal() {
    modal.classList.add('hidden');
    destroyChoices();
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

    // Add assigned users if element exists
    const assignedUsersSelect = document.getElementById('assigned-users');
    if (assignedUsersSelect) {
        const selectedUsers = Array.from(assignedUsersSelect.selectedOptions).map(option => option.value);
        selectedUsers.forEach((userId, index) => {
            formData.append(`assigned_users[${index}]`, userId);
        });
    }

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
function editApiKey(apiKeyId, keyName, apiKey, googleEmail, assignedUserIds = []) {
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

    // Initialize Choices.js and set selected values
    setTimeout(() => {
        initializeChoices();

        // Set assigned users if element exists
        const assignedUsersSelect = document.getElementById('assigned-users');
        if (assignedUsersSelect && choicesInstance && assignedUserIds && assignedUserIds.length > 0) {
            // Set values using Choices.js API
            choicesInstance.setChoiceByValue(assignedUserIds.map(id => String(id)));
        }
    }, 100);
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