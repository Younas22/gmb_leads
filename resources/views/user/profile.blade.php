@extends('layouts.app')

@section('title', 'Profile - Business Search Tool')

@section('content')
        <!-- Profile Content -->
        <div class="p-4 lg:p-8">
            <!-- Profile Header -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl p-4 sm:p-6 md:p-8 mb-6 text-white">
                <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6">
                    <div class="relative">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 bg-white rounded-full flex items-center justify-center text-primary-600 text-2xl sm:text-3xl font-bold shadow-lg">
                            @if($user->avatar)
                                <img id="profileImage" src="{{ asset('public/' . $user->avatar) }}" alt="Profile" class="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 rounded-full object-cover">
                            @else
                                <img id="profileImage" src="" alt="Profile" class="w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 rounded-full object-cover hidden">
                                <span id="profileInitials">{{ strtoupper(substr($user->first_name ?? $user->name, 0, 1)) }}{{ strtoupper(substr($user->last_name ?? '', 0, 1)) }}</span>
                            @endif
                        </div>
                        <button onclick="openImageUpload()" class="absolute bottom-0 right-0 bg-orange-500 hover:bg-orange-600 text-white p-1.5 sm:p-2 rounded-full shadow-lg transition-colors">
                            <i class="fas fa-camera text-xs sm:text-sm"></i>
                        </button>
                        <input type="file" id="avatarInput" accept="image/*" class="hidden" onchange="previewImage(event)">
                    </div>

<div class="text-center md:text-left flex-1">
    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold mb-2">{{ $user->name ?? ($user->first_name . ' ' . $user->last_name) }}</h1>
    <p class="text-primary-100 text-sm sm:text-base mb-3 break-all">{{ $user->email }}</p>
    <div class="flex flex-wrap justify-center md:justify-start gap-2 sm:gap-3">
        <span class="inline-flex items-center px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-white bg-opacity-20 text-white">
            <i class="fas {{ $user->isAdmin() ? 'fa-crown' : 'fa-user' }} mr-1 sm:mr-2"></i>
            {{ $user->isAdmin() ? 'Administrator' : 'Regular User' }}
        </span>

        @if($user->email_verified)
            <span class="inline-flex items-center px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-green-500 text-white">
                <i class="fas fa-check-circle mr-1 sm:mr-2"></i>Email Verified
            </span>
        @else
            <button onclick="resendVerification()" id="resendBtn"
                    class="inline-flex items-center px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-red-500 hover:bg-red-600 text-white transition-colors">
                <i class="fas fa-envelope mr-1 sm:mr-2"></i>
                <span id="resendText">Verify Email Now</span>
            </button>
        @endif

        <span class="inline-flex items-center px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-white bg-opacity-20 text-white">
            <i class="fas {{ $user->login_type === 'google' ? 'fa-google' : 'fa-sign-in-alt' }} mr-1 sm:mr-2"></i>
            {{ $user->login_type === 'google' ? 'Google Login' : 'Email Login' }}
        </span>
        <span class="inline-flex items-center px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs font-medium
            {{ $user->status === 'active' ? 'bg-green-500' : 'bg-red-500' }} text-white">
            <i class="fas {{ $user->status === 'active' ? 'fa-check' : 'fa-times' }} mr-1 sm:mr-2"></i>
            {{ ucfirst($user->status) }}
        </span>
    </div>
</div>

                    <div class="text-center">
                        <p class="text-primary-100 text-xs sm:text-sm">Last Login</p>
                        <p class="text-white text-sm sm:text-base font-medium">
                            {{ $user->last_login ? $user->last_login->diffForHumans() : 'Never' }}
                        </p>
                    </div>
                </div>
            </div>

        <!-- Success/Error Messages -->
        <div id="messageArea">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif
        </div>


            <!-- Profile Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex overflow-x-auto space-x-4 sm:space-x-8 px-3 sm:px-6 scrollbar-hide">
                        <button class="tab-btn active py-3 sm:py-4 px-1 sm:px-2 border-b-2 border-primary-600 text-primary-600 font-medium text-xs sm:text-sm whitespace-nowrap" data-tab="personal">
                            <i class="fas fa-user mr-1 sm:mr-2"></i>Personal Info
                        </button>
                        <button class="tab-btn py-3 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-xs sm:text-sm whitespace-nowrap" data-tab="security">
                            <i class="fas fa-shield-alt mr-1 sm:mr-2"></i>Security
                        </button>
                        <button class="tab-btn py-3 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-xs sm:text-sm whitespace-nowrap" data-tab="preferences">
                            <i class="fas fa-cog mr-1 sm:mr-2"></i>Preferences
                        </button>
                        <button class="tab-btn py-3 sm:py-4 px-1 sm:px-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-xs sm:text-sm whitespace-nowrap" data-tab="activity">
                            <i class="fas fa-chart-line mr-1 sm:mr-2"></i>Activity
                        </button>
                    </nav>
                </div>

                <!-- Personal Info Tab -->
                <div id="personal-tab" class="tab-content p-4 sm:p-6">
                    <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data" class="space-y-4 sm:space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">First Name</label>
                                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                                    class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('first_name') border-red-500 @enderror">
                                @error('first_name')
                                    <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                                    class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('last_name') border-red-500 @enderror">
                                @error('last_name')
                                    <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <div class="relative">
                                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                    class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 pr-12 @error('email') border-red-500 @enderror">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    @if($user->email_verified)
                                        <i class="fas fa-check-circle text-green-500"></i>
                                    @else
                                        <i class="fas fa-times-circle text-red-500"></i>
                                    @endif
                                </div>
                            </div>
                            @if($user->email_verified)
                                <p class="text-xs sm:text-sm text-green-600 mt-1">Email verified</p>
                            @else
                                <p class="text-xs sm:text-sm text-red-600 mt-1">Email not verified</p>
                            @endif
                            @error('email')
                                <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Avatar</label>
                            <input type="file" name="avatar" accept="image/*"
                                class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('avatar') border-red-500 @enderror">
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">Upload a new profile picture (JPG, PNG, max 2MB)</p>
                            @error('avatar')
                                <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-0 sm:space-x-4">
                            <button type="button" onclick="window.location.reload()" class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 border border-gray-300 rounded-lg text-sm sm:text-base text-gray-700 hover:bg-gray-50 text-center">
                                Cancel
                            </button>
                            <button type="submit" class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm sm:text-base text-center">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Tab -->
                <div id="security-tab" class="tab-content p-4 sm:p-6 hidden">
                    <div class="space-y-4 sm:space-y-6">
                        <!-- Password Change -->
                        @if($user->login_type !== 'google')
                        <div class="bg-gray-50 rounded-lg p-4 sm:p-6">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Change Password</h3>
                            <form method="POST" action="{{ route('user.password.update') }}" class="space-y-4">
                                @csrf
                                @method('PUT')

                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                    <input type="password" name="current_password"
                                        class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('current_password') border-red-500 @enderror">
                                    @error('current_password')
                                        <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <input type="password" name="password"
                                        class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('password') border-red-500 @enderror">
                                    @error('password')
                                        <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                    <input type="password" name="password_confirmation"
                                        class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <button type="submit" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg text-sm sm:text-base">
                                    Update Password
                                </button>
                            </form>
                        </div>
                        @else
                        <div class="bg-blue-50 rounded-lg p-4 sm:p-6">
                            <div class="flex items-start sm:items-center">
                                <i class="fas fa-info-circle text-blue-500 mr-3 mt-0.5 sm:mt-0"></i>
                                <div>
                                    <h3 class="text-base sm:text-lg font-semibold text-blue-800">Google Account</h3>
                                    <p class="text-sm sm:text-base text-blue-600">You're signed in with Google. Password changes should be made through your Google account.</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Email Verification -->
                        @if(!$user->email_verified)
                        <div class="bg-yellow-50 rounded-lg p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                                <div>
                                    <h3 class="text-base sm:text-lg font-semibold text-yellow-800">Email Verification</h3>
                                    <p class="text-yellow-600 text-xs sm:text-sm">Your email address is not verified. Please verify to secure your account.</p>
                                </div>
                                <form method="POST" action="{{ route('user.verification.send') }}" class="inline w-full sm:w-auto">
                                    @csrf
                                    <button type="submit" class="w-full sm:w-auto bg-yellow-600 hover:bg-yellow-700 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm text-center">
                                        Send Verification Email
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif

                        <!-- Account Security Info -->
                        <div class="bg-gray-50 rounded-lg p-4 sm:p-6">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Account Security</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 sm:p-4 bg-white rounded-lg border">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-calendar text-gray-500 text-sm sm:text-base"></i>
                                        <div>
                                            <p class="text-sm sm:text-base font-medium text-gray-800">Account Created</p>
                                            <p class="text-xs sm:text-sm text-gray-600">{{ $user->created_at->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-3 sm:p-4 bg-white rounded-lg border">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-clock text-gray-500 text-sm sm:text-base"></i>
                                        <div>
                                            <p class="text-sm sm:text-base font-medium text-gray-800">Last Profile Update</p>
                                            <p class="text-xs sm:text-sm text-gray-600">{{ $user->updated_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preferences Tab -->
                <div id="preferences-tab" class="tab-content p-4 sm:p-6 hidden">
                    <form method="POST" action="{{ route('user.preferences.update') }}" class="space-y-4 sm:space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="bg-gray-50 rounded-lg p-4 sm:p-6">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Email Notifications</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm sm:text-base font-medium text-gray-800">Search Results</p>
                                        <p class="text-xs sm:text-sm text-gray-600">Receive notifications when new search results are available</p>
                                    </div>
                                    <input type="checkbox" name="notifications[search_results]" value="1"
                                        {{ old('notifications.search_results', true) ? 'checked' : '' }}
                                        class="w-4 h-4 sm:w-5 sm:h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 flex-shrink-0">
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm sm:text-base font-medium text-gray-800">Weekly Summary</p>
                                        <p class="text-xs sm:text-sm text-gray-600">Get a weekly summary of your search activity</p>
                                    </div>
                                    <input type="checkbox" name="notifications[weekly_summary]" value="1"
                                        {{ old('notifications.weekly_summary', false) ? 'checked' : '' }}
                                        class="w-4 h-4 sm:w-5 sm:h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 flex-shrink-0">
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm sm:text-base font-medium text-gray-800">Marketing Updates</p>
                                        <p class="text-xs sm:text-sm text-gray-600">Receive updates about new features and promotions</p>
                                    </div>
                                    <input type="checkbox" name="notifications[marketing]" value="1"
                                        {{ old('notifications.marketing', true) ? 'checked' : '' }}
                                        class="w-4 h-4 sm:w-5 sm:h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 flex-shrink-0">
                                </div>
                            </div>
                        </div>

                        <!-- <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Default Search Settings</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Location</label>
                                    <input type="text" name="default_location" value="{{ old('default_location', '') }}" 
                                        placeholder="Enter city or region" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Results Per Page</label>
                                    <select name="results_per_page" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="10" {{ old('results_per_page', 25) == 10 ? 'selected' : '' }}>10 results</option>
                                        <option value="25" {{ old('results_per_page', 25) == 25 ? 'selected' : '' }}>25 results</option>
                                        <option value="50" {{ old('results_per_page', 25) == 50 ? 'selected' : '' }}>50 results</option>
                                        <option value="100" {{ old('results_per_page', 25) == 100 ? 'selected' : '' }}>100 results</option>
                                    </select>
                                </div>
                            </div>
                        </div> -->

                        <div class="flex justify-end">
                            <button type="submit" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg text-sm sm:text-base">
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Activity Tab -->
                <div id="activity-tab" class="tab-content p-4 sm:p-6 hidden">
                    <div class="space-y-4 sm:space-y-6">
                        <!-- Activity Stats -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 sm:p-6 text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-blue-100 text-xs sm:text-sm">Total Searches</p>
                                        <p class="text-2xl sm:text-3xl font-bold">{{ $stats['total_searches'] ?? 0 }}</p>
                                    </div>
                                    <i class="fas fa-search text-xl sm:text-2xl text-blue-200"></i>
                                </div>
                            </div>
                            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 sm:p-6 text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-green-100 text-xs sm:text-sm">Leads Saved</p>
                                        <p class="text-2xl sm:text-3xl font-bold">{{ $stats['saved_leads'] ?? 0 }}</p>
                                    </div>
                                    <i class="fas fa-users text-xl sm:text-2xl text-green-200"></i>
                                </div>
                            </div>
                            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 sm:p-6 text-white sm:col-span-2 md:col-span-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-purple-100 text-xs sm:text-sm">Account Age</p>
                                        <p class="text-2xl sm:text-3xl font-bold">{{ $user->created_at->diffForHumans(null, true) }}</p>
                                    </div>
                                    <i class="fas fa-calendar text-xl sm:text-2xl text-purple-200"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-gray-50 rounded-lg p-4 sm:p-6">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Recent Saved Leads</h3>
                            @if($recentLeads && $recentLeads->count() > 0)
                                <div class="space-y-3 sm:space-y-4">
                                    @foreach($recentLeads->take(5) as $lead)
                                        <div class="flex items-center space-x-3 sm:space-x-4 p-3 sm:p-4 bg-white rounded-lg">
                                            <div class="w-8 h-8 sm:w-10 sm:h-10 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-building text-primary-600 text-sm sm:text-base"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm sm:text-base font-medium text-gray-800 truncate">{{ $lead->name }}</p>
                                                <p class="text-xs sm:text-sm text-gray-600 truncate">{{ $lead->address }} • Saved {{ $lead->created_at->diffForHumans() }}</p>
                                            </div>
                                            <div class="text-right flex-shrink-0">
                                                @if($lead->rating)
                                                    <div class="flex items-center text-yellow-500">
                                                        <i class="fas fa-star text-xs mr-1"></i>
                                                        <span class="text-xs sm:text-sm">{{ $lead->rating }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if($recentLeads->count() > 5)
                                    <div class="mt-4 text-center">
                                        <a href="{{ route('user.leads') }}" class="text-sm sm:text-base text-primary-600 hover:text-primary-700 font-medium">
                                            View All Leads <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-6 sm:py-8">
                                    <div class="text-gray-400 text-3xl sm:text-4xl mb-3">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <p class="text-sm sm:text-base text-gray-500">No saved leads yet</p>
                                    <a href="{{ route('user.dashboard') }}" class="text-sm sm:text-base text-primary-600 hover:text-primary-700 font-medium mt-2 inline-block">
                                        Start Searching for Leads
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-semibold text-red-800 mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
                </h3>
                <div class="space-y-3 sm:space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0 p-3 sm:p-4 bg-white rounded-lg border border-red-200">
                        <div>
                            <p class="text-sm sm:text-base font-medium text-gray-800">Delete Account</p>
                            <p class="text-xs sm:text-sm text-gray-600">Permanently delete your account and all associated data</p>
                        </div>
                        <button onclick="confirmAccountDeletion()" class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm text-center whitespace-nowrap">
                            Delete Account
                        </button>
                    </div>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0 p-3 sm:p-4 bg-white rounded-lg border border-red-200">
                        <div>
                            <p class="text-sm sm:text-base font-medium text-gray-800">Clear All Data</p>
                            <p class="text-xs sm:text-sm text-gray-600">Remove all search history and saved leads</p>
                        </div>
                        <button onclick="confirmDataClear()" class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm text-center whitespace-nowrap">
                            Clear Data
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <!-- Delete Account Modal -->
    <div id="deleteAccountModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-red-100 rounded-full p-2 sm:p-3 mr-3 sm:mr-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-base sm:text-xl"></i>
                        </div>
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800">Delete Account</h3>
                    </div>
                    <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">Are you sure you want to delete your account? This action cannot be undone and will permanently delete all your data.</p>
                    <form method="POST" action="{{ route('user.account.delete') }}">
                        @csrf
                        @method('DELETE')
                        <div class="mb-4">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Type "DELETE" to confirm</label>
                            <input type="text" id="deleteConfirmation" class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-lg" placeholder="DELETE">
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" onclick="closeDeleteModal()" class="flex-1 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-sm sm:text-base text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" id="confirmDeleteBtn" disabled class="flex-1 px-3 sm:px-4 py-2 bg-red-600 text-white rounded-lg text-sm sm:text-base hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                Delete Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Clear Data Modal -->
    <div id="clearDataModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-orange-100 rounded-full p-2 sm:p-3 mr-3 sm:mr-4">
                            <i class="fas fa-trash text-orange-600 text-base sm:text-xl"></i>
                        </div>
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800">Clear All Data</h3>
                    </div>
                    <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">This will permanently delete all your search history and saved leads. Your account will remain active.</p>
                    <form method="POST" action="{{ route('user.data.clear') }}">
                        @csrf
                        @method('DELETE')
                        <div class="flex space-x-3">
                            <button type="button" onclick="closeClearDataModal()" class="flex-1 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-sm sm:text-base text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 px-3 sm:px-4 py-2 bg-orange-600 text-white rounded-lg text-sm sm:text-base hover:bg-orange-700">
                                Clear Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');
                
                // Remove active class from all tabs
                tabBtns.forEach(tab => {
                    tab.classList.remove('active', 'border-primary-600', 'text-primary-600');
                    tab.classList.add('border-transparent', 'text-gray-500');
                });
                
                // Add active class to clicked tab
                btn.classList.add('active', 'border-primary-600', 'text-primary-600');
                btn.classList.remove('border-transparent', 'text-gray-500');
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show selected tab content
                document.getElementById(tabId + '-tab').classList.remove('hidden');
            });
        });

        // Profile image functions
        function openImageUpload() {
            document.getElementById('avatarInput').click();
        }

 
        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;

            // 🟢 Live preview
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profileImage').src = e.target.result;
                document.getElementById('profileImage').classList.remove('hidden');
                const initials = document.getElementById('profileInitials');
                if (initials) initials.classList.add('hidden');
            };
            reader.readAsDataURL(file);

            // 🟢 Upload via AJAX
            const formData = new FormData();
            formData.append('avatar', file);

            fetch("{{ route('user.avatar.upload') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const messageArea = document.getElementById('messageArea');
                messageArea.innerHTML = ''; // clear old messages

                if (data.success) {
                    console.log("Avatar updated:", data.avatar);
                    showMessage(messageArea, data.message || "Profile picture updated successfully!", "success");
                } else {
                    showMessage(messageArea, data.message || "Error updating profile picture.", "error");
                }
            })
            .catch(err => {
                console.error("Upload error:", err);
                const messageArea = document.getElementById('messageArea');
                showMessage(messageArea, "Something went wrong while uploading.", "error");
            });
        }

        function showMessage(container, message, type = "success") {
            const div = document.createElement("div");

            if (type === "success") {
                div.className = "bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6";
                div.innerHTML = `<i class='fas fa-check-circle mr-2'></i>${message}`;
            } else {
                div.className = "bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6";
                div.innerHTML = `<i class='fas fa-exclamation-circle mr-2'></i>${message}`;
            }

            container.appendChild(div);

            // 🕒 Auto hide after 3 seconds
            setTimeout(() => {
                div.style.transition = "opacity 0.5s ease";
                div.style.opacity = "0";
                setTimeout(() => div.remove(), 500);
            }, 3000);
        }


        // Delete account modal functions
        function confirmAccountDeletion() {
            document.getElementById('deleteAccountModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('deleteAccountModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            document.getElementById('deleteConfirmation').value = '';
            document.getElementById('confirmDeleteBtn').disabled = true;
        }

        // Enable delete button when "DELETE" is typed
        document.getElementById('deleteConfirmation').addEventListener('input', function(e) {
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            if (e.target.value === 'DELETE') {
                confirmBtn.disabled = false;
            } else {
                confirmBtn.disabled = true;
            }
        });

        // Clear data modal functions
        function confirmDataClear() {
            document.getElementById('clearDataModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeClearDataModal() {
            document.getElementById('clearDataModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modals when clicking outside
        document.getElementById('deleteAccountModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        document.getElementById('clearDataModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeClearDataModal();
            }
        });

        // Auto-hide success/error messages
        setTimeout(() => {
            const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
            alerts.forEach(alert => {
                if (alert.textContent.includes('success') || alert.textContent.includes('error')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>




<script>
function resendVerification() {
    const btn = document.getElementById('resendBtn');
    const btnText = document.getElementById('resendText');
    const messageBox = document.getElementById('messageArea');
    
    btn.disabled = true;
    btn.classList.add('opacity-75', 'cursor-not-allowed');
    btnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';

    fetch('{{ route("auth.resend.verification") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            btnText.textContent = 'Email Sent!';
            setTimeout(() => {
                btnText.textContent = 'Verify Email Now';
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }, 3000);
        } else {
            showAlert(data.message, 'error');
            btnText.textContent = 'Verify Email Now';
            btn.disabled = false;
            btn.classList.remove('opacity-75', 'cursor-not-allowed');
        }
    })
    .catch(error => {
        showAlert('Failed to send email. Please try again.', 'error');
        btnText.textContent = 'Verify Email Now';
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
    });
}

function showAlert(message, type) {
    const messageBox = document.getElementById('messageArea');
    messageBox.innerHTML = `
        <div class="p-3 rounded-lg ${
            type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
        } border ${type === 'success' ? 'border-green-300' : 'border-red-300'}">
            ${message}
        </div>
    `;

    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageBox.innerHTML = '';
    }, 5000);
}
</script>


@endsection