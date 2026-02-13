@extends('layouts.admin')

@section('title', 'Settings')

@php
use App\Models\Setting;
use App\Models\EmailTemplate;
@endphp

@section('content')
<div class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
            <p class="text-sm text-gray-500 mt-1">Manage your application configuration</p>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2.5 rounded-lg flex items-center justify-between text-sm">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-2.5 rounded-lg flex items-center justify-between text-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <!-- Tabs Navigation -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-5">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px overflow-x-auto" role="tablist">
                    <button onclick="switchTab('general')" id="tab-general" class="tab-button active px-6 py-3 text-sm font-medium border-b-2 border-primary-600 text-primary-600 whitespace-nowrap">
                        <i class="fas fa-cog mr-2"></i>General
                    </button>
                    <button onclick="switchTab('email')" id="tab-email" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </button>
                    <button onclick="switchTab('api')" id="tab-api" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fas fa-plug mr-2"></i>API
                    </button>
                    <button onclick="switchTab('oauth')" id="tab-oauth" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fab fa-google mr-2"></i>Google OAuth
                    </button>
                    <button onclick="switchTab('system')" id="tab-system" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fas fa-shield-alt mr-2"></i>System
                    </button>
                    <button onclick="switchTab('performance')" id="tab-performance" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                        <i class="fas fa-tachometer-alt mr-2"></i>Performance
                    </button>
                </nav>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content-wrapper">

            <!-- ========== GENERAL TAB ========== -->
            <div id="content-general" class="tab-content">
                <form action="{{ route('admin.settings.general.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <div class="lg:col-span-2 space-y-5">
                            <!-- Application Settings -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
                                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                        <i class="fas fa-laptop text-purple-600 mr-2 text-sm"></i>
                                        Application Settings
                                    </h2>
                                </div>
                                <div class="p-5 grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="site_name" class="block text-xs font-medium text-gray-700 mb-1.5">Site Name <span class="text-red-500">*</span></label>
                                        <input type="text" id="site_name" name="site_name"
                                               value="{{ old('site_name', Setting::get('site_name', config('app.name'))) }}"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                                    </div>
                                    <div>
                                        <label for="contact_email" class="block text-xs font-medium text-gray-700 mb-1.5">Contact Email <span class="text-red-500">*</span></label>
                                        <input type="email" id="contact_email" name="contact_email"
                                               value="{{ old('contact_email', Setting::get('contact_email')) }}"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" required>
                                    </div>
                                    <div class="col-span-2">
                                        <label for="site_description" class="block text-xs font-medium text-gray-700 mb-1.5">Site Description</label>
                                        <textarea id="site_description" name="site_description" rows="2"
                                                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">{{ old('site_description', Setting::get('site_description')) }}</textarea>
                                    </div>
                                    <div>
                                        <label for="contact_phone" class="block text-xs font-medium text-gray-700 mb-1.5">Contact Phone</label>
                                        <input type="text" id="contact_phone" name="contact_phone"
                                               value="{{ old('contact_phone', Setting::get('contact_phone')) }}"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div>
                                        <label for="support_email" class="block text-xs font-medium text-gray-700 mb-1.5">Support Email</label>
                                        <input type="email" id="support_email" name="support_email"
                                               value="{{ old('support_email', Setting::get('support_email')) }}"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Logo & Favicon -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-blue-50">
                                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                        <i class="fas fa-image text-indigo-600 mr-2 text-sm"></i>
                                        Logo & Favicon
                                    </h2>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- Logo Upload -->
                                        <div>
                                            <label for="site_logo" class="block text-xs font-medium text-gray-700 mb-1.5">Site Logo</label>
                                            @if(Setting::get('site_logo'))
                                                <div class="mb-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                    <img src="{{ asset('public/' . Setting::get('site_logo')) }}" alt="Site Logo" class="h-16 object-contain">
                                                </div>
                                            @endif
                                            <input type="file" id="site_logo" name="site_logo" accept="image/*"
                                                   class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                            <p class="mt-1 text-[10px] text-gray-500">Recommended: 200x60px, PNG or JPG</p>
                                        </div>

                                        <!-- Favicon Upload -->
                                        <div>
                                            <label for="site_favicon" class="block text-xs font-medium text-gray-700 mb-1.5">Favicon</label>
                                            @if(Setting::get('site_favicon'))
                                                <div class="mb-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                    <img src="{{ asset('public/' . Setting::get('site_favicon')) }}" alt="Favicon" class="h-8 w-8 object-contain">
                                                </div>
                                            @endif
                                            <input type="file" id="site_favicon" name="site_favicon" accept="image/x-icon,image/png"
                                                   class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                            <p class="mt-1 text-[10px] text-gray-500">Recommended: 32x32px, ICO or PNG</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Business Settings -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-amber-50">
                                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                        <i class="fas fa-briefcase text-orange-600 mr-2 text-sm"></i>
                                        Business Settings
                                    </h2>
                                </div>
                                <div class="p-5 grid grid-cols-3 gap-4">
                                    <div>
                                        <label for="default_country" class="block text-xs font-medium text-gray-700 mb-1.5">Default Country</label>
                                        <select id="default_country" name="default_country"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                            <option value="PK" {{ Setting::get('default_country') == 'PK' ? 'selected' : '' }}>Pakistan</option>
                                            <option value="US" {{ Setting::get('default_country') == 'US' ? 'selected' : '' }}>United States</option>
                                            <option value="GB" {{ Setting::get('default_country') == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                            <option value="CA" {{ Setting::get('default_country') == 'CA' ? 'selected' : '' }}>Canada</option>
                                            <option value="AU" {{ Setting::get('default_country') == 'AU' ? 'selected' : '' }}>Australia</option>
                                            <option value="IN" {{ Setting::get('default_country') == 'IN' ? 'selected' : '' }}>India</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="default_currency" class="block text-xs font-medium text-gray-700 mb-1.5">Default Currency</label>
                                        <select id="default_currency" name="default_currency"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                            <option value="PKR" {{ Setting::get('default_currency') == 'PKR' ? 'selected' : '' }}>PKR (₨)</option>
                                            <option value="USD" {{ Setting::get('default_currency') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="timezone" class="block text-xs font-medium text-gray-700 mb-1.5">Timezone</label>
                                        <select id="timezone" name="timezone"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                            <option value="Asia/Karachi" {{ Setting::get('timezone') == 'Asia/Karachi' ? 'selected' : '' }}>Pakistan (PKT)</option>
                                            <option value="UTC" {{ Setting::get('timezone', 'UTC') == 'UTC' ? 'selected' : '' }}>UTC</option>
                                            <option value="America/New_York" {{ Setting::get('timezone') == 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                                            <option value="America/Chicago" {{ Setting::get('timezone') == 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                                            <option value="America/Los_Angeles" {{ Setting::get('timezone') == 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                                            <option value="Europe/London" {{ Setting::get('timezone') == 'Europe/London' ? 'selected' : '' }}>London</option>
                                            <option value="Asia/Kolkata" {{ Setting::get('timezone') == 'Asia/Kolkata' ? 'selected' : '' }}>India</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Search Settings -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-teal-50 to-green-50">
                                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                        <i class="fas fa-search text-teal-600 mr-2 text-sm"></i>
                                        Search Settings
                                    </h2>
                                </div>
                                <div class="p-5 grid grid-cols-3 gap-4">
                                    <div>
                                        <label for="max_search_results" class="block text-xs font-medium text-gray-700 mb-1.5">Max Results Per Page</label>
                                        <input type="number" id="max_search_results" name="max_search_results"
                                               value="{{ old('max_search_results', Setting::get('max_search_results', 50)) }}"
                                               min="10" max="200"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div>
                                        <label for="default_search_radius" class="block text-xs font-medium text-gray-700 mb-1.5">Search Radius (km)</label>
                                        <input type="number" id="default_search_radius" name="default_search_radius"
                                               value="{{ old('default_search_radius', Setting::get('default_search_radius', 10)) }}"
                                               min="1" max="100"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div>
                                        <label for="max_saved_leads" class="block text-xs font-medium text-gray-700 mb-1.5">Max Saved Leads</label>
                                        <input type="number" id="max_saved_leads" name="max_saved_leads"
                                               value="{{ old('max_saved_leads', Setting::get('max_saved_leads', 1000)) }}"
                                               min="10" max="10000"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">
                                    <i class="fas fa-save mr-2"></i>Save General Settings
                                </button>
                            </div>
                        </div>

                        <!-- Sidebar for General Tab -->
                        <div class="space-y-5">
                            <!-- System Status -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-red-50 to-pink-50">
                                    <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                        <i class="fas fa-cogs text-red-600 mr-2 text-xs"></i>
                                        System Status
                                    </h2>
                                </div>
                                <div class="p-4 space-y-2.5">
                                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-200">
                                        <div>
                                            <h5 class="font-medium text-gray-900 text-xs">Registration</h5>
                                            <p class="text-[10px] text-gray-600">{{ Setting::get('allow_registration', true) ? 'Enabled' : 'Disabled' }}</p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ Setting::get('allow_registration', true) ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                                            {{ Setting::get('allow_registration', true) ? 'ON' : 'OFF' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg border border-gray-200">
                                        <div>
                                            <h5 class="font-medium text-gray-900 text-xs">Cache</h5>
                                            <p class="text-[10px] text-gray-600">{{ Setting::get('enable_cache', true) ? 'Enabled' : 'Disabled' }}</p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ Setting::get('enable_cache', true) ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-800' }}">
                                            {{ Setting::get('enable_cache', true) ? 'ON' : 'OFF' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Info -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h6 class="font-semibold text-blue-900 mb-2 flex items-center text-xs">
                                    <i class="fas fa-info-circle mr-1.5"></i>Quick Info
                                </h6>
                                <ul class="space-y-1.5 text-[11px] text-blue-800">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                        <span>Logo displayed in navigation</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                        <span>Favicon shown in browser tab</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                        <span>All settings auto-saved</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ========== EMAIL TAB ========== -->
            <div id="content-email" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div class="lg:col-span-2 space-y-5">
                        <!-- Email Configuration -->
                        <form action="{{ route('admin.settings.email.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                                    <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                        <i class="fas fa-envelope text-blue-600 mr-2 text-sm"></i>
                                        Email Configuration
                                    </h2>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div>
                                        <label for="resend_api_key" class="block text-xs font-medium text-gray-700 mb-1.5">
                                            Resend API Key <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex gap-2">
                                            <input type="password" id="resend_api_key" name="resend_api_key"
                                                   value="{{ old('resend_api_key', $settings['resend_api_key'] ?? '') }}"
                                                   placeholder="re_xxxxxxxxxxxxxxxxxxxxxxxx"
                                                   class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                            <button type="button" onclick="toggleApiKeyVisibility()"
                                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">
                                                <i class="fas fa-eye" id="toggleIcon"></i>
                                            </button>
                                            <button type="button" onclick="verifyApiKey()" id="verifyBtn"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                                <i class="fas fa-check-circle mr-1"></i>Verify
                                            </button>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Get your key from <a href="https://resend.com/api-keys" target="_blank" class="text-blue-600 hover:underline">Resend Dashboard</a>
                                        </p>
                                        <div id="verifyResult" class="mt-2"></div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="from_email" class="block text-xs font-medium text-gray-700 mb-1.5">
                                                From Email <span class="text-red-500">*</span>
                                            </label>
                                            <input type="email" id="from_email" name="from_email"
                                                   value="{{ old('from_email', $settings['from_email'] ?? '') }}"
                                                   placeholder="noreply@yourdomain.com"
                                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        </div>
                                        <div>
                                            <label for="from_name" class="block text-xs font-medium text-gray-700 mb-1.5">
                                                From Name <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="from_name" name="from_name"
                                                   value="{{ old('from_name', $settings['from_name'] ?? config('app.name')) }}"
                                                   placeholder="{{ config('app.name') }}"
                                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        </div>
                                    </div>

                                    <div class="pt-2">
                                        <button type="submit" class="px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">
                                            <i class="fas fa-save mr-1.5"></i>Save Email Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Test Email -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
                                <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-paper-plane text-green-600 mr-2 text-sm"></i>
                                    Test Email
                                </h2>
                            </div>
                            <div class="p-5">
                                <form action="{{ route('admin.settings.email.test') }}" method="POST" class="flex gap-3">
                                    @csrf
                                    <input type="email" name="test_email" placeholder="Enter email address" required
                                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-paper-plane mr-1.5"></i>Send
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Test New Feature Email -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-violet-50">
                                <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-star text-purple-600 mr-2 text-sm"></i>
                                    Test New Feature Email
                                </h2>
                            </div>
                            <div class="p-5">
                                <p class="text-xs text-gray-600 mb-3">Send a test new feature announcement email</p>
                                <form action="{{ route('admin.settings.email.test.new.feature') }}" method="POST" class="flex gap-3">
                                    @csrf
                                    <input type="email" name="test_email" placeholder="Enter email address" required
                                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-paper-plane mr-1.5"></i>Send
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Test Maintenance Email -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-amber-50">
                                <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-wrench text-orange-600 mr-2 text-sm"></i>
                                    Test Maintenance Email
                                </h2>
                            </div>
                            <div class="p-5">
                                <p class="text-xs text-gray-600 mb-3">Send a test system maintenance notification email</p>
                                <form action="{{ route('admin.settings.email.test.maintenance') }}" method="POST" class="flex gap-3">
                                    @csrf
                                    <input type="email" name="test_email" placeholder="Enter email address" required
                                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-paper-plane mr-1.5"></i>Send
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Bulk New Feature Email -->
                        <div class="bg-white rounded-lg shadow-sm border border-red-200 border-2">
                            <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-cyan-50">
                                <h2 class="text-base font-semibold text-gray-900 flex items-center justify-between">
                                    <span class="flex items-center">
                                        <i class="fas fa-users text-blue-600 mr-2 text-sm"></i>
                                        Send New Feature Email to ALL Users
                                        <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full font-medium">Bulk</span>
                                    </span>
                                    <span id="verified_users_count_feature" class="px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">
                                        <i class="fas fa-spinner fa-spin mr-1"></i>Loading...
                                    </span>
                                </h2>
                            </div>
                            <div class="p-5">
                                <div class="mb-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-xs text-yellow-800 flex items-start">
                                        <i class="fas fa-exclamation-triangle mr-2 mt-0.5"></i>
                                        <span><strong>Warning:</strong> This will send email to ALL verified users in the system!</span>
                                    </p>
                                </div>
                                <div class="space-y-3">
                                    <!-- Progress Bar -->
                                    <div id="feature_progress_container" class="hidden">
                                        <div class="mb-2">
                                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                <span id="feature_progress_text">Sending emails...</span>
                                                <span id="feature_progress_percent">0%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div id="feature_progress_bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                                            </div>
                                        </div>
                                        <div id="feature_progress_stats" class="text-xs text-gray-600"></div>
                                    </div>

                                    <div id="feature_result_message"></div>

                                    <button type="button" onclick="sendBulkEmail('feature')" id="feature_submit_btn" class="w-full px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-paper-plane mr-1.5"></i>Send to ALL Users
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Bulk Maintenance Email -->
                        <div class="bg-white rounded-lg shadow-sm border border-red-200 border-2">
                            <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-red-50 to-rose-50">
                                <h2 class="text-base font-semibold text-gray-900 flex items-center justify-between">
                                    <span class="flex items-center">
                                        <i class="fas fa-users text-red-600 mr-2 text-sm"></i>
                                        Send Maintenance Email to ALL Users
                                        <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full font-medium">Bulk</span>
                                    </span>
                                    <span id="verified_users_count_maintenance" class="px-3 py-1 bg-red-100 text-red-800 text-xs rounded-full font-medium">
                                        <i class="fas fa-spinner fa-spin mr-1"></i>Loading...
                                    </span>
                                </h2>
                            </div>
                            <div class="p-5">
                                <div class="mb-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-xs text-yellow-800 flex items-start">
                                        <i class="fas fa-exclamation-triangle mr-2 mt-0.5"></i>
                                        <span><strong>Warning:</strong> This will send email to ALL verified users in the system!</span>
                                    </p>
                                </div>
                                <div class="space-y-3">
                                    <!-- Progress Bar -->
                                    <div id="maintenance_progress_container" class="hidden">
                                        <div class="mb-2">
                                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                <span id="maintenance_progress_text">Sending emails...</span>
                                                <span id="maintenance_progress_percent">0%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div id="maintenance_progress_bar" class="bg-red-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                                            </div>
                                        </div>
                                        <div id="maintenance_progress_stats" class="text-xs text-gray-600"></div>
                                    </div>

                                    <div id="maintenance_result_message"></div>

                                    <button type="button" onclick="sendBulkEmail('maintenance')" id="maintenance_submit_btn" class="w-full px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-paper-plane mr-1.5"></i>Send to ALL Users
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Templates Sidebar -->
                    <div class="space-y-5">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-violet-50">
                                <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-file-alt text-purple-600 mr-2 text-xs"></i>
                                    Email Templates
                                </h2>
                            </div>
                            <div class="p-4">
                                <div class="space-y-2.5">
                                    @php
                                        $templateMap = [
                                            'enable_welcome_email' => ['name' => 'Welcome Email', 'desc' => 'User registration', 'slug' => 'welcome'],
                                            'enable_verify_email' => ['name' => 'Email Verification', 'desc' => 'Verify email address', 'slug' => 'verify_email'],
                                            'enable_reset_password_email' => ['name' => 'Password Reset', 'desc' => 'Reset password', 'slug' => 'reset_password'],
                                            'enable_new_feature_email' => ['name' => 'New Feature', 'desc' => 'Announcements', 'slug' => 'new_feature'],
                                            'enable_subscription_invoice_email' => ['name' => 'Invoice', 'desc' => 'Payment receipts', 'slug' => 'subscription_invoice'],
                                            'enable_subscription_start_email' => ['name' => 'Sub Start', 'desc' => 'Subscription begins', 'slug' => 'subscription_start'],
                                            'enable_subscription_end_email' => ['name' => 'Sub End', 'desc' => 'Subscription expires', 'slug' => 'subscription_end'],
                                            'enable_system_maintenance_email' => ['name' => 'Maintenance', 'desc' => 'System alerts', 'slug' => 'system_maintenance'],
                                        ];
                                    @endphp
                                    @foreach($templateMap as $settingKey => $info)
                                        @php
                                            $emailTemplate = EmailTemplate::where('slug', $info['slug'])->first();
                                        @endphp
                                        <div class="p-2.5 bg-gray-50 rounded-lg border border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h6 class="font-medium text-gray-800 text-xs">{{ $info['name'] }}</h6>
                                                    <p class="text-[10px] text-gray-500">{{ $info['desc'] }}</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    @if($emailTemplate)
                                                        <a href="{{ route('admin.settings.email-templates.edit', $emailTemplate->id) }}"
                                                           class="px-2 py-1 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded text-[10px] font-medium transition-colors"
                                                           title="Edit template content">
                                                            <i class="fas fa-pen text-[9px] mr-0.5"></i>Edit
                                                        </a>
                                                    @endif
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" class="sr-only peer email-template-toggle"
                                                               data-template-key="{{ $settingKey }}"
                                                               {{ Setting::get($settingKey, true) ? 'checked' : '' }}>
                                                        <div class="w-8 h-4 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[1px] after:left-[1px] after:bg-white after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-primary-600 relative"></div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div id="sidebar-toggle-result" class="mt-2"></div>
                            </div>
                        </div>

                        <!-- Quick Info -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h6 class="font-semibold text-blue-900 mb-2 flex items-center text-xs">
                                <i class="fas fa-info-circle mr-1.5"></i>Quick Info
                            </h6>
                            <ul class="space-y-1.5 text-[11px] text-blue-800">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span>Responsive email templates</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span>Dynamic content support</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span>Automatic delivery</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== API TAB ========== -->
            <div id="content-api" class="tab-content hidden">
                <form action="{{ route('admin.settings.api.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 max-w-4xl">
                        <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-cyan-50 to-blue-50">
                            <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-plug text-cyan-600 mr-2 text-sm"></i>
                                API Settings
                            </h2>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label for="google_maps_api_key" class="block text-xs font-medium text-gray-700 mb-1.5">Google Maps API Key</label>
                                <div class="flex gap-2">
                                    <input type="password" id="google_maps_api_key" name="google_maps_api_key"
                                           value="{{ old('google_maps_api_key', Setting::get('google_maps_api_key')) }}"
                                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <button type="button" onclick="togglePasswordVisibility('google_maps_api_key')"
                                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label for="google_places_api_key" class="block text-xs font-medium text-gray-700 mb-1.5">Google Maps API Key</label>
                                <div class="flex gap-2">
                                    <input type="password" id="google_places_api_key" name="google_places_api_key"
                                           value="{{ old('google_places_api_key', Setting::get('google_places_api_key')) }}"
                                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <button type="button" onclick="togglePasswordVisibility('google_places_api_key')"
                                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="api_rate_limit" class="block text-xs font-medium text-gray-700 mb-1.5">API Rate Limit (per min)</label>
                                    <input type="number" id="api_rate_limit" name="api_rate_limit"
                                           value="{{ old('api_rate_limit', Setting::get('api_rate_limit', 60)) }}"
                                           min="1" max="1000"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <div class="flex items-end">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" id="enable_api_logging" name="enable_api_logging" value="1"
                                               {{ Setting::get('enable_api_logging', false) ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600 relative"></div>
                                        <span class="ml-2 text-xs font-medium text-gray-700">Enable API Logging</span>
                                    </label>
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end">
                                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">
                                    <i class="fas fa-save mr-2"></i>Save API Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ========== GOOGLE OAUTH TAB ========== -->
            <div id="content-oauth" class="tab-content hidden">
                <form action="{{ route('admin.settings.oauth.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 max-w-4xl">
                        <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-red-50 to-orange-50">
                            <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                <i class="fab fa-google text-red-600 mr-2 text-sm"></i>
                                Google OAuth Configuration
                            </h2>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <p class="text-xs text-blue-800 flex items-start">
                                    <i class="fas fa-info-circle mr-2 mt-0.5"></i>
                                    <span>These settings are automatically synced with your .env file. Get your OAuth credentials from <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="underline font-medium">Google Cloud Console</a>.</span>
                                </p>
                            </div>

                            <div>
                                <label for="google_client_id" class="block text-xs font-medium text-gray-700 mb-1.5">
                                    Google Client ID <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-2">
                                    <input type="text" id="google_client_id" name="google_client_id"
                                           value="{{ old('google_client_id', Setting::get('google_client_id', env('GOOGLE_CLIENT_ID'))) }}"
                                           placeholder="291712108770-xxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com"
                                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                           required>
                                    <button type="button" onclick="copyToClipboard('google_client_id')"
                                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm"
                                            title="Copy to clipboard">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="google_client_secret" class="block text-xs font-medium text-gray-700 mb-1.5">
                                    Google Client Secret <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-2">
                                    <input type="password" id="google_client_secret" name="google_client_secret"
                                           value="{{ old('google_client_secret', Setting::get('google_client_secret', env('GOOGLE_CLIENT_SECRET'))) }}"
                                           placeholder="GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx"
                                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                           required>
                                    <button type="button" onclick="togglePasswordVisibility('google_client_secret')"
                                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" onclick="copyToClipboard('google_client_secret')"
                                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm"
                                            title="Copy to clipboard">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="google_redirect_uri" class="block text-xs font-medium text-gray-700 mb-1.5">
                                    Google Redirect URI <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-2">
                                    <input type="url" id="google_redirect_uri" name="google_redirect_uri"
                                           value="{{ old('google_redirect_uri', Setting::get('google_redirect_uri', env('GOOGLE_REDIRECT_URI'))) }}"
                                           placeholder="http://localhost/gmb_leads/auth/google/callback"
                                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                           required>
                                    <button type="button" onclick="copyToClipboard('google_redirect_uri')"
                                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm"
                                            title="Copy to clipboard">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">This URL must be added to authorized redirect URIs in Google Cloud Console</p>
                            </div>

                            <div class="pt-2 flex justify-between items-center">
                                <a href="https://console.cloud.google.com/apis/credentials" target="_blank"
                                   class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                    <i class="fas fa-external-link-alt mr-1"></i>Open Google Cloud Console
                                </a>
                                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">
                                    <i class="fas fa-save mr-2"></i>Save OAuth Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Quick Setup Guide -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-5 mt-5 max-w-4xl">
                    <h6 class="font-semibold text-blue-900 mb-3 flex items-center text-sm">
                        <i class="fas fa-book mr-2"></i>Quick Setup Guide
                    </h6>
                    <ol class="space-y-2 text-xs text-blue-800 list-decimal list-inside">
                        <li>Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="underline font-medium">Google Cloud Console</a></li>
                        <li>Create a new OAuth 2.0 Client ID or use existing one</li>
                        <li>Add the Redirect URI to authorized redirect URIs list</li>
                        <li>Copy Client ID and Client Secret from Google Console</li>
                        <li>Paste them in the fields above and click "Save OAuth Settings"</li>
                        <li>Your .env file will be automatically updated!</li>
                    </ol>
                </div>
            </div>

            <!-- ========== SYSTEM TAB ========== -->
            <div id="content-system" class="tab-content hidden">
                <form action="{{ route('admin.settings.system.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 max-w-4xl">
                        <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-red-50 to-rose-50">
                            <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-shield-alt text-red-600 mr-2 text-sm"></i>
                                System Settings
                            </h2>
                        </div>
                        <div class="p-5 space-y-4">
                            <!-- Development / Production Mode Toggle -->
                            <div class="flex items-center justify-between p-3 rounded-lg border {{ Setting::get('app_mode', 'production') === 'development' ? 'bg-orange-50 border-orange-300' : 'bg-green-50 border-green-300' }}" id="app-mode-container">
                                <div>
                                    <h5 class="font-medium text-gray-900 text-xs">Application Mode</h5>
                                    <p class="text-[10px] text-gray-600">Switch between Development and Production mode</p>
                                    <p class="text-[10px] mt-1 font-medium {{ Setting::get('app_mode', 'production') === 'development' ? 'text-orange-600' : 'text-green-600' }}" id="app-mode-status">
                                        Currently: <span class="uppercase">{{ Setting::get('app_mode', 'production') }}</span> Mode
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-[10px] font-medium {{ Setting::get('app_mode', 'production') === 'production' ? 'text-green-700' : 'text-gray-400' }}" id="prod-label">Production</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="app_mode" name="app_mode" value="development"
                                               {{ Setting::get('app_mode', 'production') === 'development' ? 'checked' : '' }}
                                               class="sr-only peer"
                                               onchange="updateModeUI(this)">
                                        <div class="w-9 h-5 bg-green-500 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-500 relative"></div>
                                    </label>
                                    <span class="text-[10px] font-medium {{ Setting::get('app_mode', 'production') === 'development' ? 'text-orange-700' : 'text-gray-400' }}" id="dev-label">Development</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                <div>
                                    <h5 class="font-medium text-gray-900 text-xs">Maintenance Mode</h5>
                                    <p class="text-[10px] text-gray-600">Restrict site access to admins only</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1"
                                           {{ Setting::get('maintenance_mode', false) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-yellow-600 relative"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <div>
                                    <h5 class="font-medium text-gray-900 text-xs">Dynamic Email Templates</h5>
                                    <p class="text-[10px] text-gray-600">Use dynamic email templates in emails</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="use_dynamic_emails" name="use_dynamic_emails" value="1"
                                           {{ Setting::get('use_dynamic_emails', false) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600 relative"></div>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div>
                                        <h5 class="font-medium text-gray-900 text-xs">User Registration</h5>
                                        <p class="text-[10px] text-gray-600">Allow new signups</p>
                                    </div> 
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="allow_registration" name="allow_registration" value="1"
                                               {{ Setting::get('allow_registration', true) ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600 relative"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div>
                                        <h5 class="font-medium text-gray-900 text-xs">Company Registration</h5>
                                        <p class="text-[10px] text-gray-600">Allow new company signups</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="allow_company_registration" name="allow_company_registration" value="1"
                                               {{ Setting::get('allow_company_registration', true) ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600 relative"></div>
                                    </label>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="session_timeout" class="block text-xs font-medium text-gray-700 mb-1.5">Session Timeout (minutes)</label>
                                    <input type="number" id="session_timeout" name="session_timeout"
                                           value="{{ old('session_timeout', Setting::get('session_timeout', 120)) }}"
                                           min="5" max="1440"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <div>
                                    <label for="cache_duration" class="block text-xs font-medium text-gray-700 mb-1.5">Cache Duration (minutes)</label>
                                    <input type="number" id="cache_duration" name="cache_duration"
                                           value="{{ old('cache_duration', Setting::get('cache_duration', 60)) }}"
                                           min="1" max="1440"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end">
                                <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">
                                    <i class="fas fa-save mr-2"></i>Save System Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ========== PERFORMANCE TAB ========== -->
            <div id="content-performance" class="tab-content hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 max-w-6xl">
                    <!-- Cache Management -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-amber-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-tachometer-alt text-orange-600 mr-2 text-xs"></i>
                                Cache Management
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-gray-600 mb-3">Clear application cache to refresh data and improve performance.</p>
                            <button type="button" onclick="clearCache()"
                                    class="w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-medium">
                                <i class="fas fa-broom mr-1.5"></i>Clear Cache
                            </button>
                            <div id="cache-result" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Database Optimization -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-database text-green-600 mr-2 text-xs"></i>
                                Database Optimization
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-gray-600 mb-3">Optimize database tables to improve query performance.</p>
                            <button type="button" onclick="optimizeDatabase()"
                                    class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-medium">
                                <i class="fas fa-database mr-1.5"></i>Optimize Database
                            </button>
                            <div id="database-result" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Performance Settings Seeder -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-cogs text-purple-600 mr-2 text-xs"></i>
                                Performance Settings
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-gray-600 mb-3">Initialize or update performance-related settings in the database.</p>
                            <button type="button" onclick="seedPerformanceSettings()"
                                    class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-medium">
                                <i class="fas fa-cogs mr-1.5"></i>Seed Performance Settings
                            </button>
                            <div id="performance-seed-result" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Run Migrations -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-blue-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-sync-alt text-indigo-600 mr-2 text-xs"></i>
                                Run Migrations
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-gray-600 mb-3">Run pending database migrations to update the database schema.</p>
                            <button type="button" onclick="runMigrations()"
                                    class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium">
                                <i class="fas fa-sync-alt mr-1.5"></i>Run Migrations
                            </button>
                            <div id="migrations-result" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Composer Install -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-box text-blue-600 mr-2 text-xs"></i>
                                Composer Install
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-gray-600 mb-3"><strong>Use when:</strong> Installing dependencies from composer.json (first setup or after git pull).</p>
                            <button type="button" onclick="runComposerCommand('install')"
                                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium">
                                <i class="fas fa-download mr-1.5"></i>Run Composer Install
                            </button>
                            <div id="composer-install-result" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Composer Update -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-violet-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-sync text-purple-600 mr-2 text-xs"></i>
                                Composer Update
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-gray-600 mb-3"><strong>Use when:</strong> Updating all packages to their latest versions according to version constraints.</p>
                            <button type="button" onclick="runComposerCommand('update')"
                                    class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-medium">
                                <i class="fas fa-sync-alt mr-1.5"></i>Run Composer Update
                            </button>
                            <div id="composer-update-result" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Composer Dump-Autoload -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-yellow-50 to-orange-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-file-code text-yellow-600 mr-2 text-xs"></i>
                                Dump Autoload
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-gray-600 mb-3"><strong>Use when:</strong> Regenerating autoload files after adding new classes or namespaces.</p>
                            <button type="button" onclick="runComposerCommand('dump-autoload')"
                                    class="w-full px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-xs font-medium">
                                <i class="fas fa-refresh mr-1.5"></i>Dump Autoload
                            </button>
                            <div id="composer-dump-autoload-result" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Composer Clear Cache -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-red-50 to-pink-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-trash text-red-600 mr-2 text-xs"></i>
                                Composer Clear Cache
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-gray-600 mb-3"><strong>Use when:</strong> Fixing composer download issues or forcing fresh package downloads.</p>
                            <button type="button" onclick="runComposerCommand('clear-cache')"
                                    class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium">
                                <i class="fas fa-eraser mr-1.5"></i>Clear Composer Cache
                            </button>
                            <div id="composer-clear-cache-result" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Composer Diagnose -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-teal-50 to-cyan-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-stethoscope text-teal-600 mr-2 text-xs"></i>
                                Composer Diagnose
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-gray-600 mb-3"><strong>Use when:</strong> Troubleshooting composer issues and checking system configuration.</p>
                            <button type="button" onclick="runComposerCommand('diagnose')"
                                    class="w-full px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-medium">
                                <i class="fas fa-check-circle mr-1.5"></i>Run Diagnose
                            </button>
                            <div id="composer-diagnose-result" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Composer Validate -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-pink-50 to-rose-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-check-double text-pink-600 mr-2 text-xs"></i>
                                Composer Validate
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-gray-600 mb-3"><strong>Use when:</strong> Validating composer.json and composer.lock file syntax and structure.</p>
                            <button type="button" onclick="runComposerCommand('validate')"
                                    class="w-full px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg text-xs font-medium">
                                <i class="fas fa-shield-alt mr-1.5"></i>Validate Composer Files
                            </button>
                            <div id="composer-validate-result" class="mt-2"></div>
                        </div>
                    </div>
                </div>

                <!-- Performance Tips -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-5 max-w-6xl">
                    <h6 class="font-semibold text-blue-900 mb-3 flex items-center text-sm">
                        <i class="fas fa-lightbulb mr-2"></i>Performance & Composer Tips
                    </h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Performance Tips -->
                        <div>
                            <h6 class="font-medium text-blue-800 mb-2 text-xs">Performance Optimization</h6>
                            <ul class="space-y-1.5 text-[11px] text-blue-800">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span>Clear cache regularly for optimal performance</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span>Optimize database monthly or after bulk operations</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span>Monitor API rate limits to avoid service disruptions</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Composer Tips -->
                        <div>
                            <h6 class="font-medium text-blue-800 mb-2 text-xs">Composer Best Practices</h6>
                            <ul class="space-y-1.5 text-[11px] text-blue-800">
                                <li class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span><strong>Install:</strong> Use after cloning project or when composer.lock exists</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span><strong>Update:</strong> Updates all dependencies to latest compatible versions</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span><strong>Dump-autoload:</strong> Run after creating new classes or files</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span><strong>Clear-cache:</strong> Fix download or installation errors</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span><strong>Diagnose:</strong> Check system health and troubleshoot issues</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                    <span><strong>Validate:</strong> Verify composer.json structure before committing</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Tab Switching Function
    function switchTab(tabName) {
        // Hide all tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Remove active state from all tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active', 'border-primary-600', 'text-primary-600');
            button.classList.add('border-transparent', 'text-gray-500');
        });

        // Show selected tab content
        document.getElementById('content-' + tabName).classList.remove('hidden');

        // Add active state to selected tab
        const activeTab = document.getElementById('tab-' + tabName);
        activeTab.classList.add('active', 'border-primary-600', 'text-primary-600');
        activeTab.classList.remove('border-transparent', 'text-gray-500');
    }

    // Update Development/Production Mode UI
    function updateModeUI(checkbox) {
        const container = document.getElementById('app-mode-container');
        const status = document.getElementById('app-mode-status');
        const prodLabel = document.getElementById('prod-label');
        const devLabel = document.getElementById('dev-label');

        if (checkbox.checked) {
            container.className = 'flex items-center justify-between p-3 rounded-lg border bg-orange-50 border-orange-300';
            status.className = 'text-[10px] mt-1 font-medium text-orange-600';
            status.innerHTML = 'Currently: <span class="uppercase">DEVELOPMENT</span> Mode';
            prodLabel.className = 'text-[10px] font-medium text-gray-400';
            devLabel.className = 'text-[10px] font-medium text-orange-700';
        } else {
            container.className = 'flex items-center justify-between p-3 rounded-lg border bg-green-50 border-green-300';
            status.className = 'text-[10px] mt-1 font-medium text-green-600';
            status.innerHTML = 'Currently: <span class="uppercase">PRODUCTION</span> Mode';
            prodLabel.className = 'text-[10px] font-medium text-green-700';
            devLabel.className = 'text-[10px] font-medium text-gray-400';
        }
    }

    // Toggle API Key Visibility
    function toggleApiKeyVisibility() {
        const input = document.getElementById('resend_api_key');
        const icon = document.getElementById('toggleIcon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Verify API Key
    function verifyApiKey() {
        const apiKey = document.getElementById('resend_api_key').value;
        const resultDiv = document.getElementById('verifyResult');
        const btn = document.getElementById('verifyBtn');

        if (!apiKey) {
            resultDiv.innerHTML = `<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-triangle mr-1"></i>Enter API key first</div>`;
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Verifying...';

        fetch('{{ route('admin.settings.email.verify') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ api_key: apiKey })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="bg-green-50 border border-green-200 text-green-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-check-circle mr-1"></i>Valid API Key</div>`;
                setTimeout(() => resultDiv.innerHTML = '', 3000);
            } else {
                resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Invalid Key</div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Error</div>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Verify';
        });
    }

    // Toggle Password Visibility for multiple fields
    function togglePasswordVisibility(fieldId) {
        const input = document.getElementById(fieldId);
        const icon = event.currentTarget.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Clear Cache
    function clearCache() {
        const resultDiv = document.getElementById('cache-result');
        resultDiv.innerHTML = `<div class="bg-blue-50 border border-blue-200 text-blue-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-spinner fa-spin mr-1"></i>Clearing...</div>`;

        fetch('{{ route('admin.settings.cache.clear') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="bg-green-50 border border-green-200 text-green-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-check-circle mr-1"></i>${data.message}</div>`;
                setTimeout(() => resultDiv.innerHTML = '', 3000);
            } else {
                resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Failed</div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Error</div>`;
        });
    }

    // Optimize Database
    function optimizeDatabase() {
        const resultDiv = document.getElementById('database-result');
        resultDiv.innerHTML = `<div class="bg-blue-50 border border-blue-200 text-blue-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-spinner fa-spin mr-1"></i>Optimizing...</div>`;

        fetch('{{ route('admin.settings.database.optimize') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="bg-green-50 border border-green-200 text-green-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-check-circle mr-1"></i>${data.message}</div>`;
                setTimeout(() => resultDiv.innerHTML = '', 3000);
            } else {
                resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Failed</div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Error</div>`;
        });
    }

    // Seed Performance Settings
    function seedPerformanceSettings() {
        const resultDiv = document.getElementById('performance-seed-result');
        resultDiv.innerHTML = `<div class="bg-blue-50 border border-blue-200 text-blue-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-spinner fa-spin mr-1"></i>Seeding settings...</div>`;

        fetch('{{ route('admin.settings.performance.seed-settings') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="bg-green-50 border border-green-200 text-green-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-check-circle mr-1"></i>${data.message}</div>`;
                setTimeout(() => resultDiv.innerHTML = '', 3000);
            } else {
                resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Failed</div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Error</div>`;
        });
    }

    // Run Migrations
    function runMigrations() {
        const resultDiv = document.getElementById('migrations-result');
        resultDiv.innerHTML = `<div class="bg-blue-50 border border-blue-200 text-blue-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-spinner fa-spin mr-1"></i>Running migrations...</div>`;

        fetch('{{ route('admin.settings.migrations.run') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let message = data.message;
                if (data.output) {
                    message += '<br><small class="text-xs">' + data.output + '</small>';
                }
                resultDiv.innerHTML = `<div class="bg-green-50 border border-green-200 text-green-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-check-circle mr-1"></i>${message}</div>`;
                setTimeout(() => resultDiv.innerHTML = '', 5000);
            } else {
                resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>${data.message}</div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Error running migrations</div>`;
        });
    }

    // Run Composer Command
    function runComposerCommand(command) {
        const resultDiv = document.getElementById(`composer-${command}-result`);
        const commandLabels = {
            'install': 'Installing dependencies',
            'update': 'Updating packages',
            'dump-autoload': 'Regenerating autoload files',
            'clear-cache': 'Clearing composer cache',
            'diagnose': 'Running diagnostics',
            'validate': 'Validating composer files'
        };

        resultDiv.innerHTML = `<div class="bg-blue-50 border border-blue-200 text-blue-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-spinner fa-spin mr-1"></i>${commandLabels[command]}...</div>`;

        fetch('{{ route('admin.settings.composer.run') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ command: command })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let message = data.message;
                if (data.output) {
                    message += `<div class="mt-2 p-2 bg-gray-900 text-green-400 rounded text-[10px] font-mono overflow-x-auto max-h-40 overflow-y-auto">${data.output}</div>`;
                }
                resultDiv.innerHTML = `<div class="bg-green-50 border border-green-200 text-green-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-check-circle mr-1"></i>${message}</div>`;
                setTimeout(() => resultDiv.innerHTML = '', 10000);
            } else {
                let errorMessage = data.message;
                if (data.output) {
                    errorMessage += `<div class="mt-2 p-2 bg-gray-900 text-red-400 rounded text-[10px] font-mono overflow-x-auto max-h-40 overflow-y-auto">${data.output}</div>`;
                }
                resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>${errorMessage}</div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Error: ${error.message}</div>`;
        });
    }

    // Auto-switch tab from URL parameter
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab && document.getElementById('content-' + tab)) {
            switchTab(tab);
        }
    });

    // Handle Email Template Toggle via AJAX
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.email-template-toggle');
        const resultDiv = document.getElementById('sidebar-toggle-result');

        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const templateKey = this.dataset.templateKey;
                const enabled = this.checked;

                resultDiv.innerHTML = `<div class="bg-blue-50 border border-blue-200 text-blue-800 px-2 py-1.5 rounded text-xs"><i class="fas fa-spinner fa-spin mr-1"></i>Updating...</div>`;

                fetch('{{ route('admin.settings.email.toggle') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        template_key: templateKey,
                        enabled: enabled
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = `<div class="bg-green-50 border border-green-200 text-green-800 px-2 py-1.5 rounded text-xs"><i class="fas fa-check-circle mr-1"></i>Updated</div>`;
                        setTimeout(() => resultDiv.innerHTML = '', 2000);
                    } else {
                        resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-2 py-1.5 rounded text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Failed</div>`;
                        toggle.checked = !enabled;
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-800 px-2 py-1.5 rounded text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Error</div>`;
                    toggle.checked = !enabled;
                });
            });
        });
    });

    // Load Verified Users Count
    function loadVerifiedUsersCount() {
        fetch('{{ route('admin.settings.email.verified.users.count') }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const countText = `<i class="fas fa-users mr-1"></i>${data.count} Verified Users`;
                    document.getElementById('verified_users_count_feature').innerHTML = countText;
                    document.getElementById('verified_users_count_maintenance').innerHTML = countText;
                } else {
                    document.getElementById('verified_users_count_feature').innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>Error';
                    document.getElementById('verified_users_count_maintenance').innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>Error';
                }
            })
            .catch(error => {
                console.error('Error loading verified users count:', error);
                document.getElementById('verified_users_count_feature').innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>Error';
                document.getElementById('verified_users_count_maintenance').innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>Error';
            });
    }

    // Send Bulk Email with Progress Bar
    function sendBulkEmail(type) {
        const emailType = type === 'feature' ? 'new feature' : 'maintenance';
        const message = `⚠️ WARNING: You are about to send ${emailType} email to ALL verified users in the system!\n\nAre you absolutely sure you want to continue?`;

        if (!confirm(message)) {
            return false;
        }

        const submitBtn = document.getElementById(`${type}_submit_btn`);
        const progressContainer = document.getElementById(`${type}_progress_container`);
        const progressBar = document.getElementById(`${type}_progress_bar`);
        const progressPercent = document.getElementById(`${type}_progress_percent`);
        const progressText = document.getElementById(`${type}_progress_text`);
        const progressStats = document.getElementById(`${type}_progress_stats`);
        const resultMessage = document.getElementById(`${type}_result_message`);

        // Disable submit button and show progress
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Initializing...';
        progressContainer.classList.remove('hidden');
        resultMessage.innerHTML = '';

        // Prepare form data with default values
        const formData = new FormData();
        if (type === 'feature') {
            formData.append('feature_title', 'New Feature Announcement');
            formData.append('feature_description', 'We have added exciting new features to improve your experience. Check them out in your dashboard!');
        } else {
            formData.append('start_time', 'Tonight at 02:00 AM');
            formData.append('end_time', 'Tonight at 05:00 AM');
            formData.append('duration', '3 hours');
            formData.append('maintenance_reason', 'System upgrade and performance improvements.');
        }

        // Send AJAX request
        const url = type === 'feature'
            ? '{{ route('admin.settings.email.bulk.new.feature') }}'
            : '{{ route('admin.settings.email.bulk.maintenance') }}';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update progress to 100%
                progressBar.style.width = '100%';
                progressPercent.textContent = '100%';
                progressText.textContent = 'Completed!';
                progressStats.innerHTML = `
                    <div class="flex items-center justify-between p-2 bg-green-50 rounded border border-green-200">
                        <span><i class="fas fa-check-circle text-green-600 mr-1"></i>Successfully sent: ${data.success_count}</span>
                        ${data.fail_count > 0 ? `<span class="text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>Failed: ${data.fail_count}</span>` : ''}
                    </div>
                `;

                resultMessage.innerHTML = `
                    <div class="bg-green-50 border border-green-200 text-green-800 px-3 py-2 rounded-lg text-xs">
                        <i class="fas fa-check-circle mr-1"></i>${data.message}
                    </div>
                `;

                // Reset after 5 seconds
                setTimeout(() => {
                    progressContainer.classList.add('hidden');
                    progressBar.style.width = '0%';
                    progressPercent.textContent = '0%';
                    progressText.textContent = 'Sending emails...';
                    progressStats.innerHTML = '';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1.5"></i>Send to ALL Users';
                }, 5000);
            } else {
                resultMessage.innerHTML = `
                    <div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs">
                        <i class="fas fa-exclamation-circle mr-1"></i>${data.message}
                    </div>
                `;
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1.5"></i>Send to ALL Users';
                progressContainer.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error sending bulk email:', error);
            resultMessage.innerHTML = `
                <div class="bg-red-50 border border-red-200 text-red-800 px-3 py-2 rounded-lg text-xs">
                    <i class="fas fa-exclamation-circle mr-1"></i>An error occurred while sending emails.
                </div>
            `;
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1.5"></i>Send to ALL Users';
            progressContainer.classList.add('hidden');
        });

        // Simulate progress (since we can't get real-time updates easily with standard JSON response)
        let simulatedProgress = 0;
        const progressInterval = setInterval(() => {
            if (simulatedProgress < 90) {
                simulatedProgress += 10;
                progressBar.style.width = simulatedProgress + '%';
                progressPercent.textContent = simulatedProgress + '%';
                progressText.textContent = `Sending emails... (${simulatedProgress}%)`;
            } else {
                clearInterval(progressInterval);
            }
        }, 500);

        return false;
    }

    // Load verified users count when email tab is accessed
    document.addEventListener('DOMContentLoaded', function() {
        loadVerifiedUsersCount();

        // Reload count when email tab is clicked
        const emailTab = document.getElementById('tab-email');
        if (emailTab) {
            emailTab.addEventListener('click', loadVerifiedUsersCount);
        }
    });

    // Copy to Clipboard Function
    function copyToClipboard(fieldId) {
        const input = document.getElementById(fieldId);
        const value = input.value;

        if (!value) {
            return;
        }

        // Create a temporary textarea element
        const tempTextarea = document.createElement('textarea');
        tempTextarea.value = value;
        document.body.appendChild(tempTextarea);
        tempTextarea.select();
        document.execCommand('copy');
        document.body.removeChild(tempTextarea);

        // Show feedback
        const button = event.currentTarget;
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check text-green-600"></i>';
        button.classList.add('bg-green-100');

        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('bg-green-100');
        }, 1500);
    }
</script>
@endsection
