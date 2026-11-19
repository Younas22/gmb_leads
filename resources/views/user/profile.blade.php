@extends('layouts.app')

@section('title', 'Profile - Business Search Tool')

@php
    $avatarSrc = $user->avatar
        ? (str_starts_with($user->avatar, 'http') ? $user->avatar : asset('public/' . $user->avatar))
        : asset('assets/avatar/placeholder-image.jpeg');
    $isTeam = $user->isTeamMember();
    // Reopen the tab whose form came back with errors (Personal Info otherwise)
    $startTab = 'personal';
    if (!$isTeam && ($errors->has('current_password') || $errors->has('password'))) $startTab = 'security';
    if (!$isTeam && ($errors->has('results_per_page') || $errors->has('notifications') || $errors->has('default_location'))) $startTab = 'preferences';
@endphp

@section('content')
{{-- Profile Settings look: Poppins + custom controls (this page's content only) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .cnm-pf, .cnm-pf button, .cnm-pf input, .cnm-pf select, .cnm-pf textarea,
    .pf-modal, .pf-modal button, .pf-modal input { font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; }

    .pf-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15, 23, 42, .05); }
    .pf-title { font-size: 14.5px; font-weight: 600; color: #111827; }
    .pf-sub { font-size: 12px; color: #64748B; margin-top: 2px; }
    .pf-label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 600; color: #111827; }
    .pf-help { margin-top: 6px; font-size: 12px; color: #64748B; }
    .pf-error { margin-top: 6px; font-size: 12px; color: #DC2626; }

    /* Badges */
    .pf-badge { display: inline-flex; align-items: center; gap: 6px; height: 23px; padding: 0 9px; border-radius: 999px; border: 1px solid; font-size: 11px; font-weight: 500; white-space: nowrap; }
    .pf-badge.is-neutral { background: #F8FAFC; border-color: #E2E8F0; color: #334155; }
    .pf-badge.is-success { background: #F0FDF4; border-color: #BBF7D0; color: #16A34A; }
    .pf-badge.is-danger  { background: #FEF2F2; border-color: #FECACA; color: #DC2626; }
    button.pf-badge.is-danger:hover:not(:disabled) { background: #FEE2E2; }

    /* Inputs */
    .pf-input-wrap { position: relative; }
    .pf-input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 12px; pointer-events: none; width: 14px; text-align: center; }
    .pf-input { width: 100%; height: 38px; padding: 0 12px 0 34px; border: 1px solid #CBD5E1; border-radius: 9px; background: #fff; color: #111827; font-size: 13px; transition: border-color .15s, box-shadow .15s; }
    .pf-input::placeholder { color: #94A3B8; }
    .pf-input:hover { border-color: #94A3B8; }
    .pf-input:focus { outline: none; border-color: #F97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, .15); }
    .pf-input:disabled, .pf-input[readonly] { background: #F8FAFC; color: #64748B; cursor: not-allowed; }
    .pf-input.is-invalid { border-color: #DC2626; }
    .pf-input.is-invalid:focus { box-shadow: 0 0 0 4px rgba(220, 38, 38, .12); }
    .pf-input-wrap:focus-within .pf-input-icon { color: #F97316; }
    .pf-input.has-badge { padding-right: 116px; }
    .pf-input-badge { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); }

    /* Avatar upload */
    .pf-upload { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 14px; min-height: 46px; padding: 6px 12px 6px 6px; border: 1px solid #E2E8F0; border-radius: 9px; background: #F8FAFC; }
    .pf-upload.is-invalid { border-color: #DC2626; }
    .pf-upload:focus-within { border-color: #F97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, .15); }
    .pf-upload-btn { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; border-radius: 8px; border: 1px solid #CBD5E1; background: #fff; color: #111827; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all .15s; }
    .pf-upload-btn:hover { border-color: #F97316; color: #C2410C; }

    /* Buttons */
    .pf-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 36px; padding: 0 16px; border-radius: 9px; background: #F97316; color: #fff; font-size: 13px; font-weight: 600; transition: background-color .15s; }
    .pf-btn:hover { background: #EA580C; }
    .pf-btn-light { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 36px; padding: 0 16px; border-radius: 9px; border: 1px solid #E2E8F0; background: #fff; color: #334155; font-size: 13px; font-weight: 500; transition: all .15s; }
    .pf-btn-light:hover { background: #F8FAFC; border-color: #CBD5E1; }
    .pf-btn-danger { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 32px; padding: 0 12px; border-radius: 8px; border: 1px solid #DC2626; background: #fff; color: #DC2626; font-size: 12.5px; font-weight: 600; white-space: nowrap; transition: all .15s; }
    .pf-btn-danger:hover { background: #FEF2F2; }
    .pf-btn:focus-visible, .pf-btn-light:focus-visible, .pf-btn-danger:focus-visible, .pf-tab:focus-visible, .pf-cam:focus-visible { outline: 2px solid #F97316; outline-offset: 2px; }

    /* Tabs */
    .pf-tab { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 34px; padding: 0 14px; border-radius: 8px; color: #334155; font-size: 13px; font-weight: 500; white-space: nowrap; transition: all .15s; }
    .pf-tab:hover { color: #111827; background: #F8FAFC; }
    .pf-tab.active { background: #F97316; color: #fff; font-weight: 600; box-shadow: 0 2px 6px rgba(249, 115, 22, .25); }
    .pf-tab.active:hover { background: #EA580C; }

    /* Switch (a real checkbox) */
    .pf-switch { -webkit-appearance: none; appearance: none; position: relative; width: 36px; height: 20px; border-radius: 999px; background: #CBD5E1; cursor: pointer; flex-shrink: 0; transition: background-color .15s; }
    .pf-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 14px; height: 14px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .2); transition: transform .15s; }
    .pf-switch:checked { background: #F97316; }
    .pf-switch:checked::after { transform: translateX(16px); }
    .pf-switch:focus-visible { outline: 2px solid #F97316; outline-offset: 2px; }

    .pf-camera-icon { width: 34px; height: 34px; border-radius: 9px; font-size: 13px; background: #FFF7ED; color: #EA580C; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

    @media (prefers-reduced-motion: reduce) { .cnm-pf *, .pf-modal * { transition: none !important; } }
</style>

<div class="cnm-pf p-4 lg:px-6 lg:py-5">
<div class="max-w-[1040px] mx-auto space-y-3.5">

    {{-- Profile summary --}}
    <div class="rounded-[14px] border border-[#F1E4D6] bg-[#FFFAF5] p-4">
        <div class="flex flex-col md:flex-row md:items-center gap-4">
            <div class="relative flex-shrink-0 self-center md:self-auto">
                <img id="profileImage" src="{{ $avatarSrc }}" alt="Profile"
                     class="w-[72px] h-[72px] rounded-full object-cover bg-white border-[3px] border-white shadow-[0_0_0_1px_#E2E8F0]">
                <button type="button" onclick="openImageUpload()" aria-label="Change profile photo" title="Change photo"
                        class="pf-cam absolute -bottom-0.5 -right-0.5 w-7 h-7 rounded-full bg-[#F97316] hover:bg-[#EA580C] text-white border-2 border-white flex items-center justify-center shadow transition-colors">
                    <i class="fas fa-camera text-[11px]" aria-hidden="true"></i>
                </button>
                <input type="file" id="avatarInput" accept="image/*" class="hidden" onchange="previewImage(event)">
            </div>

            <div class="flex-1 min-w-0 text-center md:text-left">
                <h1 class="text-[19px] leading-tight font-bold text-[#111827] break-words">{{ $user->name ?? ($user->first_name . ' ' . $user->last_name) }}</h1>
                <p class="text-[12.5px] text-[#64748B] break-all">{{ $user->email }}</p>
                <div class="flex flex-wrap justify-center md:justify-start gap-1.5 mt-2">
                    <span class="pf-badge is-neutral">
                        <i class="fas {{ $user->isAdmin() ? 'fa-crown text-[#F97316]' : 'fa-user text-[#64748B]' }} text-[11px]" aria-hidden="true"></i>
                        {{ $user->isAdmin() ? 'Administrator' : 'Regular User' }}
                    </span>
                    @if($user->email_verified)
                        <span class="pf-badge is-success"><i class="fas fa-circle-check text-[11px]" aria-hidden="true"></i>Email Verified</span>
                    @else
                        <button type="button" onclick="resendVerification()" id="resendBtn" class="pf-badge is-danger">
                            <i class="fas fa-envelope text-[11px]" aria-hidden="true"></i>
                            <span id="resendText">Verify Email Now</span>
                        </button>
                    @endif
                    <span class="pf-badge is-neutral">
                        <i class="{{ $user->login_type === 'google' ? 'fab fa-google text-[#EA4335]' : 'fas fa-right-to-bracket text-[#64748B]' }} text-[11px]" aria-hidden="true"></i>
                        {{ $user->login_type === 'google' ? 'Google Login' : 'Email Login' }}
                    </span>
                    <span class="pf-badge {{ $user->status === 'active' ? 'is-success' : 'is-danger' }}">
                        <i class="fas {{ $user->status === 'active' ? 'fa-check' : 'fa-xmark' }} text-[11px]" aria-hidden="true"></i>
                        {{ ucfirst($user->status) }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3.5 self-stretch md:self-auto justify-center md:justify-start bg-white border border-[#E2E8F0] rounded-xl px-3.5 py-2.5 md:min-w-[180px]">
                <span class="pf-camera-icon" aria-hidden="true"><i class="far fa-calendar-check"></i></span>
                <div>
                    <p class="text-[11px] text-[#64748B]">Last Login</p>
                    <p class="text-[13px] font-semibold text-[#111827]">{{ $user->last_login ? $user->last_login->diffForHumans() : 'Never' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Messages --}}
    <div id="messageArea">
        @if(session('success'))
            <div class="bg-green-100 border border-green-300 text-green-800 px-3.5 py-2 rounded-xl text-[12.5px]">
                <i class="fas fa-check-circle mr-1.5"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-300 text-red-800 px-3.5 py-2 rounded-xl text-[12.5px]">
                <i class="fas fa-exclamation-circle mr-1.5"></i>{{ session('error') }}
            </div>
        @endif
    </div>

    {{-- Tabs --}}
    <div>
        <nav class="inline-flex max-w-full overflow-x-auto gap-1 p-1 bg-white border border-[#E2E8F0] rounded-xl" role="tablist" aria-label="Profile settings">
            <button type="button" role="tab" class="tab-btn pf-tab {{ $startTab === 'personal' ? 'active' : '' }}" data-tab="personal" aria-selected="{{ $startTab === 'personal' ? 'true' : 'false' }}">
                <i class="fas fa-user text-[13px]" aria-hidden="true"></i>Personal Info
            </button>
            @if(!$isTeam)
            <button type="button" role="tab" class="tab-btn pf-tab {{ $startTab === 'security' ? 'active' : '' }}" data-tab="security" aria-selected="{{ $startTab === 'security' ? 'true' : 'false' }}">
                <i class="fas fa-shield-halved text-[13px]" aria-hidden="true"></i>Security
            </button>
            <button type="button" role="tab" class="tab-btn pf-tab {{ $startTab === 'preferences' ? 'active' : '' }}" data-tab="preferences" aria-selected="{{ $startTab === 'preferences' ? 'true' : 'false' }}">
                <i class="fas fa-gear text-[13px]" aria-hidden="true"></i>Preferences
            </button>
            @endif
        </nav>
    </div>

    {{-- Personal Info --}}
    <div id="personal-tab" role="tabpanel" class="tab-content pf-card p-4 sm:p-5 {{ $startTab === 'personal' ? '' : 'hidden' }}">
        <div class="mb-3.5">
            <h2 class="pf-title">Personal Information</h2>
            <p class="pf-sub">Update your name, contact details and profile photo.</p>
        </div>
        <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data" class="space-y-3.5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                <div>
                    <label for="pf-first-name" class="pf-label">First Name</label>
                    <div class="pf-input-wrap">
                        <i class="fas fa-user pf-input-icon" aria-hidden="true"></i>
                        <input type="text" id="pf-first-name" name="first_name" value="{{ old('first_name', $user->first_name) }}" autocomplete="given-name"
                               @error('first_name') aria-invalid="true" @enderror
                               class="pf-input @error('first_name') is-invalid @enderror">
                    </div>
                    @error('first_name')<p class="pf-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="pf-last-name" class="pf-label">Last Name</label>
                    <div class="pf-input-wrap">
                        <i class="fas fa-user pf-input-icon" aria-hidden="true"></i>
                        <input type="text" id="pf-last-name" name="last_name" value="{{ old('last_name', $user->last_name) }}" autocomplete="family-name"
                               @error('last_name') aria-invalid="true" @enderror
                               class="pf-input @error('last_name') is-invalid @enderror">
                    </div>
                    @error('last_name')<p class="pf-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="pf-whatsapp" class="pf-label">WhatsApp Number</label>
                <div class="pf-input-wrap">
                    <i class="fab fa-whatsapp pf-input-icon !text-[16px]" aria-hidden="true"></i>
                    <input type="text" id="pf-whatsapp" name="whatsapp_number" value="{{ old('whatsapp_number', $user->whatsapp_number) }}"
                           placeholder="+923460820722" pattern="^\+923[0-9]{9}$" autocomplete="tel" aria-describedby="pf-whatsapp-help"
                           @error('whatsapp_number') aria-invalid="true" @enderror
                           class="pf-input @error('whatsapp_number') is-invalid @enderror">
                </div>
                <p id="pf-whatsapp-help" class="pf-help">Format: +92 followed by 10 digits, e.g. +923460820722</p>
                @error('whatsapp_number')<p class="pf-error">{{ $message }}</p>@enderror
            </div>

            @if(!$isTeam)
            <div>
                <label for="pf-email" class="pf-label">Email Address</label>
                <div class="pf-input-wrap">
                    <i class="fas fa-envelope pf-input-icon" aria-hidden="true"></i>
                    <input type="email" id="pf-email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email"
                           @error('email') aria-invalid="true" @enderror
                           class="pf-input has-badge @error('email') is-invalid @enderror">
                    <span class="pf-input-badge">
                        @if($user->email_verified)
                            <span class="pf-badge is-success !h-[24px]"><i class="fas fa-circle-check text-[11px]" aria-hidden="true"></i>Verified</span>
                        @else
                            <span class="pf-badge is-danger !h-[24px]"><i class="fas fa-circle-xmark text-[11px]" aria-hidden="true"></i>Not verified</span>
                        @endif
                    </span>
                </div>
                @error('email')<p class="pf-error">{{ $message }}</p>@enderror
            </div>
            @endif

            <div>
                <label for="pf-avatar" class="pf-label">Avatar</label>
                <div class="pf-upload @error('avatar') is-invalid @enderror">
                    <input type="file" id="pf-avatar" name="avatar" accept="image/*" class="sr-only" aria-describedby="pf-avatar-hint"
                           onchange="document.getElementById('pf-avatar-name').textContent = this.files[0] ? this.files[0].name : 'No file chosen'">
                    <label for="pf-avatar" class="pf-upload-btn"><i class="fas fa-cloud-arrow-up text-[#F97316]" aria-hidden="true"></i>Choose File</label>
                    <span id="pf-avatar-name" class="flex-1 min-w-[120px] text-sm text-[#64748B] truncate">No file chosen</span>
                    <span id="pf-avatar-hint" class="text-xs text-[#64748B] whitespace-nowrap">JPG, PNG, GIF or WEBP · Max 2MB</span>
                </div>
                @error('avatar')<p class="pf-error">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
                <button type="button" onclick="window.location.reload()" class="pf-btn-light">Cancel</button>
                <button type="submit" class="pf-btn"><i class="fas fa-floppy-disk" aria-hidden="true"></i>Save Changes</button>
            </div>
        </form>
    </div>

    @if(!$isTeam)
    {{-- Security --}}
    <div id="security-tab" role="tabpanel" class="tab-content space-y-3.5 {{ $startTab === 'security' ? '' : 'hidden' }}">
        @if($user->login_type !== 'google')
        <div class="pf-card p-4 sm:p-5">
            <div class="mb-3.5">
                <h2 class="pf-title">Change Password</h2>
                <p class="pf-sub">Use at least 8 characters.</p>
            </div>
            <form method="POST" action="{{ route('user.password.update') }}" class="space-y-3.5">
                @csrf
                @method('PUT')
                <div>
                    <label for="pf-current-password" class="pf-label">Current Password</label>
                    <div class="pf-input-wrap">
                        <i class="fas fa-lock pf-input-icon" aria-hidden="true"></i>
                        <input type="password" id="pf-current-password" name="current_password" autocomplete="current-password"
                               @error('current_password') aria-invalid="true" @enderror
                               class="pf-input @error('current_password') is-invalid @enderror">
                    </div>
                    @error('current_password')<p class="pf-error">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div>
                        <label for="pf-new-password" class="pf-label">New Password</label>
                        <div class="pf-input-wrap">
                            <i class="fas fa-key pf-input-icon" aria-hidden="true"></i>
                            <input type="password" id="pf-new-password" name="password" autocomplete="new-password"
                                   @error('password') aria-invalid="true" @enderror
                                   class="pf-input @error('password') is-invalid @enderror">
                        </div>
                        @error('password')<p class="pf-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="pf-confirm-password" class="pf-label">Confirm New Password</label>
                        <div class="pf-input-wrap">
                            <i class="fas fa-key pf-input-icon" aria-hidden="true"></i>
                            <input type="password" id="pf-confirm-password" name="password_confirmation" autocomplete="new-password" class="pf-input">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button type="submit" class="pf-btn w-full sm:w-auto"><i class="fas fa-lock" aria-hidden="true"></i>Update Password</button>
                </div>
            </form>
        </div>
        @else
        <div class="pf-card p-4 flex items-start gap-4">
            <span class="pf-camera-icon !bg-[#EFF6FF] !text-[#2563EB]" aria-hidden="true"><i class="fab fa-google"></i></span>
            <div>
                <h2 class="pf-title">Google Account</h2>
                <p class="pf-sub">You're signed in with Google. Password changes should be made through your Google account.</p>
            </div>
        </div>
        @endif

        @if(!$user->email_verified)
        <div class="pf-card p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 !border-[#FDE68A] !bg-[#FFFBEB]">
            <div class="flex items-start gap-4">
                <span class="pf-camera-icon !bg-white !text-[#D97706]" aria-hidden="true"><i class="fas fa-envelope"></i></span>
                <div>
                    <h2 class="pf-title">Email Verification</h2>
                    <p class="pf-sub">Your email address is not verified.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('user.verification.send') }}">
                @csrf
                <button type="submit" class="pf-btn w-full sm:w-auto"><i class="fas fa-paper-plane" aria-hidden="true"></i>Send Verification Email</button>
            </form>
        </div>
        @endif

        <div class="pf-card p-4 sm:p-5">
            <div class="mb-3">
                <h2 class="pf-title">Account Security</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center gap-4 px-3.5 py-2.5 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC]">
                    <span class="pf-camera-icon" aria-hidden="true"><i class="far fa-calendar"></i></span>
                    <div>
                        <p class="text-[11.5px] text-[#64748B]">Account Created</p>
                        <p class="text-[13px] font-semibold text-[#111827]">{{ $user->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 px-3.5 py-2.5 rounded-xl border border-[#E2E8F0] bg-[#F8FAFC]">
                    <span class="pf-camera-icon" aria-hidden="true"><i class="far fa-clock"></i></span>
                    <div>
                        <p class="text-[11.5px] text-[#64748B]">Last Profile Update</p>
                        <p class="text-[13px] font-semibold text-[#111827]">{{ $user->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Preferences --}}
    <div id="preferences-tab" role="tabpanel" class="tab-content pf-card p-4 sm:p-5 {{ $startTab === 'preferences' ? '' : 'hidden' }}">
        <div class="mb-3.5">
            <h2 class="pf-title">Email Notifications</h2>
            <p class="pf-sub">Choose which emails you want to receive.</p>
        </div>
        <form method="POST" action="{{ route('user.preferences.update') }}" class="space-y-3.5">
            @csrf
            @method('PUT')
            @if($errors->has('results_per_page') || $errors->has('notifications'))
                <p class="pf-error" role="alert">{{ $errors->first('notifications') ?: $errors->first('results_per_page') }}</p>
            @endif
            <div class="divide-y divide-[#E2E8F0] border border-[#E2E8F0] rounded-2xl">
                <label class="flex items-center justify-between gap-4 px-4 py-2.5 cursor-pointer">
                    <span>
                        <span class="block text-[13px] font-semibold text-[#111827]">Weekly Summary</span>
                        <span class="block text-[12px] text-[#64748B]">Get a weekly summary of your search activity</span>
                    </span>
                    <input type="checkbox" name="notifications[weekly_summary]" value="1"
                           {{ old('notifications.weekly_summary', false) ? 'checked' : '' }} class="pf-switch">
                </label>
                <label class="flex items-center justify-between gap-4 px-4 py-2.5 cursor-pointer">
                    <span>
                        <span class="block text-[13px] font-semibold text-[#111827]">Marketing Updates</span>
                        <span class="block text-[12px] text-[#64748B]">Receive updates about new features and promotions</span>
                    </span>
                    <input type="checkbox" name="notifications[marketing]" value="1"
                           {{ old('notifications.marketing', true) ? 'checked' : '' }} class="pf-switch">
                </label>
            </div>
            <div class="flex justify-end pt-1">
                <button type="submit" class="pf-btn w-full sm:w-auto"><i class="fas fa-floppy-disk" aria-hidden="true"></i>Save Preferences</button>
            </div>
        </form>
    </div>

    {{-- Danger Zone --}}
    <div class="rounded-[14px] border border-[#FECACA] bg-[#FEF5F5] p-4">
        <div class="grid grid-cols-1 lg:grid-cols-[230px_1fr] gap-3.5 lg:gap-8">
            <div class="flex items-start gap-3.5">
                <span class="w-8 h-8 rounded-lg bg-[#FEE2E2] text-[#DC2626] flex items-center justify-center flex-shrink-0 text-[13px]" aria-hidden="true"><i class="fas fa-triangle-exclamation"></i></span>
                <div>
                    <h2 class="text-[14.5px] font-semibold text-[#DC2626]">Danger Zone</h2>
                    <p class="text-[12px] text-[#64748B]">Permanent actions that cannot be undone.</p>
                </div>
            </div>
            <div class="bg-white border border-[#FECACA] rounded-2xl divide-y divide-[#FEE2E2]">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-2.5">
                    <div>
                        <p class="text-[13px] font-semibold text-[#111827]">Delete Account</p>
                        <p class="text-[12px] text-[#64748B]">Permanently delete your account and all associated data</p>
                    </div>
                    <button type="button" onclick="confirmAccountDeletion()" class="pf-btn-danger"><i class="fas fa-trash-can text-[12px]" aria-hidden="true"></i>Delete Account</button>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-2.5">
                    <div>
                        <p class="text-[13px] font-semibold text-[#111827]">Clear All Data</p>
                        <p class="text-[12px] text-[#64748B]">Remove all search history and saved leads</p>
                    </div>
                    <button type="button" onclick="confirmDataClear()" class="pf-btn-danger"><i class="fas fa-eraser text-[12px]" aria-hidden="true"></i>Clear Data</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
</div>

<!-- Delete Account Modal -->
<div id="deleteAccountModal" class="pf-modal fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-[20px] shadow-xl max-w-md w-full" role="dialog" aria-modal="true" aria-labelledby="deleteAccountTitle">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-11 h-11 rounded-xl bg-[#FEE2E2] text-[#DC2626] flex items-center justify-center flex-shrink-0"><i class="fas fa-triangle-exclamation"></i></span>
                    <h3 id="deleteAccountTitle" class="text-[17px] font-semibold text-[#111827]">Delete Account</h3>
                </div>
                <p class="text-[13.5px] text-[#64748B] mb-5">Are you sure? This action cannot be undone and will permanently delete all your data.</p>
                <form method="POST" action="{{ route('user.account.delete') }}">
                    @csrf
                    @method('DELETE')
                    <div class="mb-3">
                        <label for="deleteConfirmation" class="pf-label">Type "DELETE" to confirm</label>
                        <div class="pf-input-wrap">
                            <i class="fas fa-keyboard pf-input-icon" aria-hidden="true"></i>
                            <input type="text" id="deleteConfirmation" class="pf-input" placeholder="DELETE" autocomplete="off">
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeDeleteModal()" class="pf-btn-light flex-1">Cancel</button>
                        <button type="submit" id="confirmDeleteBtn" disabled class="flex-1 h-11 rounded-[11px] bg-[#DC2626] hover:bg-[#B91C1C] text-white text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed">Delete Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Clear Data Modal -->
<div id="clearDataModal" class="pf-modal fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-[20px] shadow-xl max-w-md w-full" role="dialog" aria-modal="true" aria-labelledby="clearDataTitle">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-11 h-11 rounded-xl bg-[#FFF7ED] text-[#EA580C] flex items-center justify-center flex-shrink-0"><i class="fas fa-trash"></i></span>
                    <h3 id="clearDataTitle" class="text-[17px] font-semibold text-[#111827]">Clear All Data</h3>
                </div>
                <p class="text-[13.5px] text-[#64748B] mb-5">This will permanently delete all your search history and saved leads. Your account will remain active.</p>
                <form method="POST" action="{{ route('user.data.clear') }}">
                    @csrf
                    @method('DELETE')
                    <div class="flex gap-3">
                        <button type="button" onclick="closeClearDataModal()" class="pf-btn-light flex-1">Cancel</button>
                        <button type="submit" class="flex-1 h-11 rounded-[11px] bg-[#EA580C] hover:bg-[#C2410C] text-white text-sm font-semibold">Clear Data</button>
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
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');
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
            ? "bg-green-100 border border-green-300 text-green-800 px-3.5 py-2 rounded-xl text-[12.5px]"
            : "bg-red-100 border border-red-300 text-red-800 px-3.5 py-2 rounded-xl text-[12.5px]";
        const icon = document.createElement("i");
        icon.className = "fas fa-" + (type === "success" ? "check" : "exclamation") + "-circle mr-1.5";
        div.append(icon, document.createTextNode(message));
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
        const alerts = document.querySelectorAll('#messageArea .bg-green-100, #messageArea .bg-red-100');
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
    const div = document.createElement('div');
    div.className = 'px-4 py-3 rounded-xl text-sm border ' + (type === 'success' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300');
    div.textContent = message;
    messageBox.replaceChildren(div);
    setTimeout(() => { messageBox.innerHTML = ''; }, 5000);
}
</script>
@endsection
