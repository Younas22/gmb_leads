@extends('layouts.app')

@section('title', 'Profile - Business Search Tool')

@section('content')
<div class="p-4 lg:p-6">

    <!-- Profile Header -->
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl p-4 mb-4 text-white">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
            <div class="relative flex-shrink-0">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-primary-600 text-xl font-bold shadow">
                    @php
                        $avatarSrc = $user->avatar
                            ? (str_starts_with($user->avatar, 'http') ? $user->avatar : asset('public/' . $user->avatar))
                            : asset('assets/avatar/placeholder-image.jpeg');
                    @endphp
                    <img id="profileImage" src="{{ $avatarSrc }}" alt="Profile" class="w-16 h-16 rounded-full object-cover">
                </div>
                <button onclick="openImageUpload()" class="absolute bottom-0 right-0 bg-orange-500 hover:bg-orange-600 text-white p-1 rounded-full shadow transition-colors">
                    <i class="fas fa-camera text-xs"></i>
                </button>
                <input type="file" id="avatarInput" accept="image/*" class="hidden" onchange="previewImage(event)">
            </div>

            <div class="flex-1 text-center sm:text-left">
                <h1 class="text-lg font-bold mb-0.5">{{ $user->name ?? ($user->first_name . ' ' . $user->last_name) }}</h1>
                <p class="text-primary-100 text-xs mb-2 break-all">{{ $user->email }}</p>
                <div class="flex flex-wrap justify-center sm:justify-start gap-1.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white bg-opacity-20 text-white">
                        <i class="fas {{ $user->isAdmin() ? 'fa-crown' : 'fa-user' }} mr-1"></i>
                        {{ $user->isAdmin() ? 'Administrator' : 'Regular User' }}
                    </span>
                    @if($user->email_verified)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-500 text-white">
                            <i class="fas fa-check-circle mr-1"></i>Email Verified
                        </span>
                    @else
                        <button onclick="resendVerification()" id="resendBtn"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-500 hover:bg-red-600 text-white transition-colors">
                            <i class="fas fa-envelope mr-1"></i>
                            <span id="resendText">Verify Email Now</span>
                        </button>
                    @endif
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white bg-opacity-20 text-white">
                        <i class="{{ $user->login_type === 'google' ? 'fab fa-google' : 'fas fa-sign-in-alt' }} mr-1"></i>
                        {{ $user->login_type === 'google' ? 'Google Login' : 'Email Login' }}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $user->status === 'active' ? 'bg-green-500' : 'bg-red-500' }} text-white">
                        <i class="fas {{ $user->status === 'active' ? 'fa-check' : 'fa-times' }} mr-1"></i>
                        {{ ucfirst($user->status) }}
                    </span>
                </div>
            </div>

            <div class="text-center flex-shrink-0">
                <p class="text-primary-100 text-xs">Last Login</p>
                <p class="text-white text-xs font-medium">{{ $user->last_login ? $user->last_login->diffForHumans() : 'Never' }}</p>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <div id="messageArea">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded text-sm mb-4">
                <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm mb-4">
                <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
            </div>
        @endif
    </div>

    <!-- Profile Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4">
        <div class="border-b border-gray-200">
            <nav class="flex overflow-x-auto gap-1 px-4 scrollbar-hide">
                <button class="tab-btn active py-2.5 px-3 border-b-2 border-primary-600 text-primary-600 font-medium text-xs whitespace-nowrap" data-tab="personal">
                    <i class="fas fa-user mr-1"></i>Personal Info
                </button>
                @if(!$user->isTeamMember())
                <button class="tab-btn py-2.5 px-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-xs whitespace-nowrap" data-tab="security">
                    <i class="fas fa-shield-alt mr-1"></i>Security
                </button>
                <button class="tab-btn py-2.5 px-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-xs whitespace-nowrap" data-tab="preferences">
                    <i class="fas fa-cog mr-1"></i>Preferences
                </button>
                @endif
            </nav>
        </div>

        <!-- Personal Info Tab -->
        <div id="personal-tab" class="tab-content p-4">
            <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500 focus:border-primary-500 @error('first_name') border-red-500 @enderror">
                        @error('first_name')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500 focus:border-primary-500 @error('last_name') border-red-500 @enderror">
                        @error('last_name')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if(!$user->isTeamMember())
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500 focus:border-primary-500 pr-8 @error('email') border-red-500 @enderror">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-2.5">
                            @if($user->email_verified)
                                <i class="fas fa-check-circle text-green-500 text-xs"></i>
                            @else
                                <i class="fas fa-times-circle text-red-500 text-xs"></i>
                            @endif
                        </div>
                    </div>
                    @if($user->email_verified)
                        <p class="text-xs text-green-600 mt-0.5">Email verified</p>
                    @else
                        <p class="text-xs text-red-600 mt-0.5">Email not verified</p>
                    @endif
                    @error('email')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
                </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Avatar</label>
                    <input type="file" name="avatar" accept="image/*"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg @error('avatar') border-red-500 @enderror">
                    <p class="text-xs text-gray-500 mt-0.5">JPG, PNG — max 2MB</p>
                    @error('avatar')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" onclick="window.location.reload()" class="px-4 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Security Tab -->
        @if(!$user->isTeamMember())
        <div id="security-tab" class="tab-content p-4 hidden">
            <div class="space-y-3">
                @if($user->login_type !== 'google')
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Change Password</h3>
                    <form method="POST" action="{{ route('user.password.update') }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Current Password</label>
                            <input type="password" name="current_password"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500 @error('current_password') border-red-500 @enderror">
                            @error('current_password')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="password"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500 @error('password') border-red-500 @enderror">
                            @error('password')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password" name="password_confirmation"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500">
                        </div>
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-1.5 rounded-lg text-xs">
                            Update Password
                        </button>
                    </form>
                </div>
                @else
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mr-2 mt-0.5"></i>
                        <div>
                            <h3 class="text-sm font-semibold text-blue-800">Google Account</h3>
                            <p class="text-xs text-blue-600 mt-0.5">You're signed in with Google. Password changes should be made through your Google account.</p>
                        </div>
                    </div>
                </div>
                @endif

                @if(!$user->email_verified)
                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-yellow-800">Email Verification</h3>
                            <p class="text-yellow-600 text-xs mt-0.5">Your email address is not verified.</p>
                        </div>
                        <form method="POST" action="{{ route('user.verification.send') }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1.5 rounded-lg text-xs whitespace-nowrap">
                                Send Verification Email
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Account Security</h3>
                    <div class="space-y-2">
                        <div class="flex items-center p-2.5 bg-white rounded-lg border">
                            <i class="fas fa-calendar text-gray-400 text-xs mr-2.5"></i>
                            <div>
                                <p class="text-xs font-medium text-gray-800">Account Created</p>
                                <p class="text-xs text-gray-500">{{ $user->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center p-2.5 bg-white rounded-lg border">
                            <i class="fas fa-clock text-gray-400 text-xs mr-2.5"></i>
                            <div>
                                <p class="text-xs font-medium text-gray-800">Last Profile Update</p>
                                <p class="text-xs text-gray-500">{{ $user->updated_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Preferences Tab -->
        @if(!$user->isTeamMember())
        <div id="preferences-tab" class="tab-content p-4 hidden">
            <form method="POST" action="{{ route('user.preferences.update') }}" class="space-y-3">
                @csrf
                @method('PUT')
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Email Notifications</h3>
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-medium text-gray-800">Weekly Summary</p>
                                <p class="text-xs text-gray-500">Get a weekly summary of your search activity</p>
                            </div>
                            <input type="checkbox" name="notifications[weekly_summary]" value="1"
                                {{ old('notifications.weekly_summary', false) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500 flex-shrink-0">
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-medium text-gray-800">Marketing Updates</p>
                                <p class="text-xs text-gray-500">Receive updates about new features and promotions</p>
                            </div>
                            <input type="checkbox" name="notifications[marketing]" value="1"
                                {{ old('notifications.marketing', true) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500 flex-shrink-0">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-1.5 rounded-lg text-xs">
                        Save Preferences
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>

    <!-- Danger Zone -->
    @if(!$user->isTeamMember())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <h3 class="text-sm font-semibold text-red-800 mb-3">
            <i class="fas fa-exclamation-triangle mr-1"></i>Danger Zone
        </h3>
        <div class="space-y-2">
            <div class="flex items-center justify-between gap-3 p-3 bg-white rounded-lg border border-red-200">
                <div>
                    <p class="text-xs font-medium text-gray-800">Delete Account</p>
                    <p class="text-xs text-gray-500">Permanently delete your account and all associated data</p>
                </div>
                <button onclick="confirmAccountDeletion()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs whitespace-nowrap">
                    Delete Account
                </button>
            </div>
            <div class="flex items-center justify-between gap-3 p-3 bg-white rounded-lg border border-red-200">
                <div>
                    <p class="text-xs font-medium text-gray-800">Clear All Data</p>
                    <p class="text-xs text-gray-500">Remove all search history and saved leads</p>
                </div>
                <button onclick="confirmDataClear()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs whitespace-nowrap">
                    Clear Data
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Delete Account Modal -->
<div id="deleteAccountModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-4">
                <div class="flex items-center mb-3">
                    <div class="bg-red-100 rounded-full p-2 mr-3">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800">Delete Account</h3>
                </div>
                <p class="text-xs text-gray-600 mb-4">Are you sure? This action cannot be undone and will permanently delete all your data.</p>
                <form method="POST" action="{{ route('user.account.delete') }}">
                    @csrf
                    @method('DELETE')
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Type "DELETE" to confirm</label>
                        <input type="text" id="deleteConfirmation" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg" placeholder="DELETE">
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeDeleteModal()" class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" id="confirmDeleteBtn" disabled class="flex-1 px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">Delete Account</button>
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
            <div class="p-4">
                <div class="flex items-center mb-3">
                    <div class="bg-orange-100 rounded-full p-2 mr-3">
                        <i class="fas fa-trash text-orange-600"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800">Clear All Data</h3>
                </div>
                <p class="text-xs text-gray-600 mb-4">This will permanently delete all your search history and saved leads. Your account will remain active.</p>
                <form method="POST" action="{{ route('user.data.clear') }}">
                    @csrf
                    @method('DELETE')
                    <div class="flex gap-2">
                        <button type="button" onclick="closeClearDataModal()" class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="flex-1 px-3 py-1.5 bg-orange-600 text-white rounded-lg text-xs hover:bg-orange-700">Clear Data</button>
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
            tabBtns.forEach(tab => {
                tab.classList.remove('active', 'border-primary-600', 'text-primary-600');
                tab.classList.add('border-transparent', 'text-gray-500');
            });
            btn.classList.add('active', 'border-primary-600', 'text-primary-600');
            btn.classList.remove('border-transparent', 'text-gray-500');
            tabContents.forEach(content => content.classList.add('hidden'));
            document.getElementById(tabId + '-tab').classList.remove('hidden');
        });
    });

    function openImageUpload() {
        document.getElementById('avatarInput').click();
    }

    function previewImage(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profileImage').src = e.target.result;
        };
        reader.readAsDataURL(file);

        const formData = new FormData();
        formData.append('avatar', file);
        fetch("{{ route('user.avatar.upload') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            const messageArea = document.getElementById('messageArea');
            messageArea.innerHTML = '';
            showMessage(messageArea, data.message || (data.success ? "Profile picture updated!" : "Error updating picture."), data.success ? "success" : "error");
        })
        .catch(() => {
            showMessage(document.getElementById('messageArea'), "Something went wrong while uploading.", "error");
        });
    }

    function showMessage(container, message, type = "success") {
        const div = document.createElement("div");
        div.className = type === "success"
            ? "bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded text-sm mb-4"
            : "bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm mb-4";
        div.innerHTML = `<i class='fas fa-${type === "success" ? "check" : "exclamation"}-circle mr-1'></i>${message}`;
        container.appendChild(div);
        setTimeout(() => {
            div.style.transition = "opacity 0.5s ease";
            div.style.opacity = "0";
            setTimeout(() => div.remove(), 500);
        }, 3000);
    }

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

    document.getElementById('deleteConfirmation').addEventListener('input', function(e) {
        document.getElementById('confirmDeleteBtn').disabled = e.target.value !== 'DELETE';
    });

    function confirmDataClear() {
        document.getElementById('clearDataModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeClearDataModal() {
        document.getElementById('clearDataModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    document.getElementById('deleteAccountModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });

    document.getElementById('clearDataModal').addEventListener('click', function(e) {
        if (e.target === this) closeClearDataModal();
    });

    setTimeout(() => {
        const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>

<script>
function resendVerification() {
    const btn = document.getElementById('resendBtn');
    const btnText = document.getElementById('resendText');
    btn.disabled = true;
    btn.classList.add('opacity-75', 'cursor-not-allowed');
    btnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Sending...';

    fetch('{{ route("auth.resend.verification") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
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
    .catch(() => {
        showAlert('Failed to send email. Please try again.', 'error');
        btnText.textContent = 'Verify Email Now';
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
    });
}

function showAlert(message, type) {
    const messageBox = document.getElementById('messageArea');
    messageBox.innerHTML = `
        <div class="px-3 py-2 rounded text-sm mb-4 border ${type === 'success' ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400'}">
            ${message}
        </div>`;
    setTimeout(() => { messageBox.innerHTML = ''; }, 5000);
}
</script>
@endsection
