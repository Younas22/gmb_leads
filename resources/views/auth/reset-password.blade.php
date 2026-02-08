<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - CustomerNearme</title>

    @php
        $siteFavicon = \App\Models\Setting::get('site_favicon');
    @endphp
    @if($siteFavicon)
        <link rel="icon" type="image/png" href="{{ asset('public/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('public/assets/images/favicon.png') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        'primary-orange': '#f97316',
                        'dark-orange': '#ea580c',
                        'primary-blue': '#3b82f6',
                        'dark-blue': '#1d4ed8',
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        },
                        orange: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c'
                        }
                    }
                }
            }
        }
    </script>

    <script>
        const BASE_URL = '{{ url("/") }}';
    </script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }

        /* Animated gradient background blobs */
        @keyframes blob-drift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -50px) scale(1.05); }
            50% { transform: translate(-20px, 20px) scale(0.95); }
            75% { transform: translate(15px, 40px) scale(1.02); }
        }
        @keyframes blob-drift-reverse {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(-40px, 30px) scale(0.95); }
            50% { transform: translate(25px, -25px) scale(1.05); }
            75% { transform: translate(-10px, -40px) scale(0.98); }
        }
        .bg-blob-1 {
            animation: blob-drift 18s ease-in-out infinite;
        }
        .bg-blob-2 {
            animation: blob-drift-reverse 22s ease-in-out infinite;
        }
        .bg-blob-3 {
            animation: blob-drift 25s ease-in-out infinite reverse;
        }

        /* Card entrance animation */
        @keyframes card-enter {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card-animate {
            animation: card-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Input focus glow */
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15), 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border-color: #f97316;
        }

        /* Button gradient animation */
        .btn-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #ea580c 0%, #1d4ed8 100%);
            opacity: 0;
            transition: opacity 0.35s ease;
        }
        .btn-primary:hover::before {
            opacity: 1;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px -5px rgba(249, 115, 22, 0.4);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .btn-primary span {
            position: relative;
            z-index: 1;
        }

        /* Password toggle button */
        .pwd-toggle {
            transition: color 0.2s ease;
        }
        .pwd-toggle:hover {
            color: #f97316;
        }

        /* Alert slide-in */
        @keyframes alert-slide {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .animate-alert {
            animation: alert-slide 0.3s ease-out;
        }

        /* Password strength bar animation */
        .strength-bar {
            transition: width 0.4s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.3s ease;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    </style>
</head>

<body class="min-h-screen bg-white font-inter overflow-x-hidden">

    <!-- ===== Decorative Background ===== -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <!-- Gradient mesh -->
        <div class="absolute inset-0" style="background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(249,115,22,0.06) 0%, transparent 60%), radial-gradient(ellipse 60% 50% at 80% 80%, rgba(29,78,216,0.05) 0%, transparent 60%);"></div>
        <!-- Floating blobs -->
        <div class="bg-blob-1 absolute -top-32 -right-32 w-[500px] h-[500px] rounded-full" style="background: radial-gradient(circle, rgba(249,115,22,0.07) 0%, transparent 70%);"></div>
        <div class="bg-blob-2 absolute -bottom-40 -left-40 w-[600px] h-[600px] rounded-full" style="background: radial-gradient(circle, rgba(29,78,216,0.06) 0%, transparent 70%);"></div>
        <div class="bg-blob-3 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] rounded-full" style="background: radial-gradient(circle, rgba(249,115,22,0.04) 0%, transparent 70%);"></div>
        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h40v40H0z' fill='none' stroke='%23000' stroke-width='0.5'/%3E%3C/svg%3E&quot;);"></div>
    </div>

    <!-- ===== Alert Messages ===== -->
    <div id="alertContainer" class="fixed top-4 right-4 z-[50] space-y-3 max-w-sm w-full"></div>

    <!-- ===== Main Content ===== -->
    <div class="relative z-10 min-h-screen flex flex-col">

        <!-- ===== Header / Logo ===== -->
        <header class="py-6 px-4 sm:px-8">
            <div class="max-w-md mx-auto flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 group">
                    @php
                        $siteLogo = \App\Models\Setting::get('site_logo');
                        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
                    @endphp
                    @if($siteLogo)
                        <img src="{{ asset('public/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-9 w-auto object-contain">
                    @else
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-orange to-orange-600 flex items-center justify-center shadow-lg shadow-orange-200/50">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-lg font-bold text-gray-900 tracking-tight">Customer<span class="text-primary-orange">Nearme</span></span>
                        </div>
                    @endif
                </a>
            </div>
        </header>

        <!-- ===== Reset Password Form Section ===== -->
        <main class="flex-1 flex items-center justify-center px-4 sm:px-8 py-6 sm:py-10">
            <div class="w-full max-w-md">

                <!-- Page Header -->
                <div class="text-center mb-8 card-animate">
                    <!-- Icon -->
                    <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-primary-orange to-orange-600 rounded-2xl shadow-xl shadow-orange-200/50 mb-5">
                        <i class="fas fa-key text-white text-2xl sm:text-3xl"></i>
                    </div>

                    <!-- Title -->
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mb-3">
                        Reset Your Password
                    </h1>
                    <p class="text-base text-gray-500">
                        Create a strong new password for your account
                    </p>
                </div>

                <!-- Reset Form Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-200/50 overflow-hidden card-animate">
                    <!-- Card header accent -->
                    <div class="h-1.5 w-full bg-gradient-to-r from-primary-orange via-orange-400 to-primary-orange"></div>

                    <div class="p-6 sm:p-8">
                        <form id="resetPasswordForm" class="space-y-5">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="email" value="{{ $email }}">

                            <!-- Email (Read-only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400 text-sm"></i>
                                    </div>
                                    <input type="email" value="{{ $email }}" readonly
                                           class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 text-gray-600 cursor-not-allowed">
                                </div>
                            </div>

                            <!-- New Password -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400 text-sm"></i>
                                    </div>
                                    <input type="password" name="password" id="newPassword" required
                                           class="input-glow block w-full pl-10 pr-11 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-gray-50/50 hover:bg-white"
                                           placeholder="Enter new password">
                                    <button type="button" onclick="togglePassword('newPassword', 'newPasswordIcon')"
                                            class="pwd-toggle absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400">
                                        <i class="fas fa-eye text-sm" id="newPasswordIcon"></i>
                                    </button>
                                </div>
                                <span class="text-red-500 text-xs mt-1 hidden" id="passwordError"></span>
                                <p class="mt-1.5 text-xs text-gray-500">Must be at least 8 characters long</p>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400 text-sm"></i>
                                    </div>
                                    <input type="password" name="password_confirmation" id="confirmPassword" required
                                           class="input-glow block w-full pl-10 pr-11 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-gray-50/50 hover:bg-white"
                                           placeholder="Confirm new password">
                                    <button type="button" onclick="togglePassword('confirmPassword', 'confirmPasswordIcon')"
                                            class="pwd-toggle absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400">
                                        <i class="fas fa-eye text-sm" id="confirmPasswordIcon"></i>
                                    </button>
                                </div>
                                <span class="text-red-500 text-xs mt-1 hidden" id="passwordConfirmationError"></span>
                            </div>

                            <!-- Password Strength Indicator -->
                            <div class="pt-2">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-gray-600">Password strength:</span>
                                    <span id="passwordStrengthText" class="text-xs font-medium text-gray-500">Not entered</span>
                                </div>
                                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div id="passwordStrength" class="strength-bar h-full bg-gray-300 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" id="resetButton" class="btn-primary w-full text-white font-semibold py-3.5 px-4 rounded-xl mt-6 shadow-lg">
                                <span id="resetButtonText" class="flex items-center justify-center gap-2">
                                    <i class="fas fa-check-circle text-sm"></i>
                                    Reset Password
                                </span>
                            </button>

                            <!-- Back to Login -->
                            <div class="text-center pt-2">
                                <a href="{{ route('auth.show') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                    <i class="fas fa-arrow-left text-xs"></i>
                                    Back to Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Tips -->
                <div class="mt-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 rounded-xl p-5 card-animate">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-gray-900 mb-2.5">Password Security Tips</h3>
                            <ul class="space-y-2 text-xs text-gray-600">
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check text-green-500 mt-0.5 flex-shrink-0"></i>
                                    <span>Use a unique password you don't use elsewhere</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check text-green-500 mt-0.5 flex-shrink-0"></i>
                                    <span>Include uppercase, lowercase, numbers & symbols</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check text-green-500 mt-0.5 flex-shrink-0"></i>
                                    <span>Avoid personal information like names or dates</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="fas fa-check text-green-500 mt-0.5 flex-shrink-0"></i>
                                    <span>Consider using a password manager</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- ===== Footer ===== -->
        <footer class="py-6 px-4 sm:px-8">
            <div class="max-w-md mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-gray-400">
                    &copy; {{ date('Y') }} CustomerNearme. All rights reserved.
                </p>
                <div class="flex items-center gap-4">
                    <a href="https://wa.me/923460820722" target="_blank" rel="noopener noreferrer" class="text-xs text-green-600 hover:text-green-700 transition-colors flex items-center gap-1">
                        <i class="fab fa-whatsapp"></i>
                        Support
                    </a>
                </div>
            </div>
        </footer>
    </div>

    <!-- ===== JavaScript ===== -->
    <script>
        // CSRF setup for AJAX
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // ========== Password Toggle ==========
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // ========== Password Strength Checker ==========
        function checkPasswordStrength(password) {
            let strength = 0;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordStrengthText');

            if (!password || password.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.className = 'strength-bar h-full bg-gray-300 rounded-full';
                strengthText.textContent = 'Not entered';
                strengthText.className = 'text-xs font-medium text-gray-500';
                return;
            }

            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 10;

            strengthBar.style.width = strength + '%';

            if (strength < 30) {
                strengthBar.className = 'strength-bar h-full bg-red-500 rounded-full';
                strengthText.textContent = 'Weak';
                strengthText.className = 'text-xs font-medium text-red-600';
            } else if (strength < 60) {
                strengthBar.className = 'strength-bar h-full bg-yellow-500 rounded-full';
                strengthText.textContent = 'Fair';
                strengthText.className = 'text-xs font-medium text-yellow-600';
            } else if (strength < 80) {
                strengthBar.className = 'strength-bar h-full bg-blue-500 rounded-full';
                strengthText.textContent = 'Good';
                strengthText.className = 'text-xs font-medium text-blue-600';
            } else {
                strengthBar.className = 'strength-bar h-full bg-green-500 rounded-full';
                strengthText.textContent = 'Strong';
                strengthText.className = 'text-xs font-medium text-green-600';
            }
        }

        $('#newPassword').on('input', function() {
            checkPasswordStrength($(this).val());
        });

        // ========== Alerts ==========
        function showAlert(message, type = 'success') {
            const container = document.getElementById('alertContainer');
            const id = 'alert-' + Date.now();
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            container.insertAdjacentHTML('beforeend', `
                <div id="${id}" class="${bgColor} text-white px-5 py-3.5 rounded-xl shadow-lg animate-alert flex items-center gap-3">
                    <i class="fas ${icon} flex-shrink-0"></i>
                    <span class="text-sm font-medium flex-1">${message}</span>
                    <button onclick="removeAlert('${id}')" class="text-white/80 hover:text-white flex-shrink-0">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            `);

            setTimeout(() => removeAlert(id), 5000);
        }

        function removeAlert(id) {
            const el = document.getElementById(id);
            if (el) { el.style.opacity = '0'; setTimeout(() => el.remove(), 200); }
        }

        // ========== Clear Errors ==========
        function clearErrors() {
            document.querySelectorAll('[id$="Error"]').forEach(el => {
                el.classList.add('hidden');
                el.textContent = '';
            });
            document.querySelectorAll('input').forEach(input => {
                input.classList.remove('border-red-500');
            });
        }

        // ========== Form Submission ==========
        $('#resetPasswordForm').on('submit', function(e) {
            e.preventDefault();

            const password = $('#newPassword').val();
            const confirmPassword = $('#confirmPassword').val();

            if (password !== confirmPassword) {
                showAlert('Passwords do not match!', 'error');
                return;
            }

            if (password.length < 8) {
                showAlert('Password must be at least 8 characters long!', 'error');
                return;
            }

            clearErrors();
            const button = $('#resetButton');
            const buttonText = $('#resetButtonText');

            button.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
            buttonText.html('<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mx-auto"></div>');

            $.ajax({
                url: '{{ route("auth.reset.password.submit") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => {
                            window.location.href = '{{ route("auth.show") }}';
                        }, 2000);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert(response?.message || 'Failed to reset password', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                    buttonText.html('<i class="fas fa-check-circle text-sm"></i> Reset Password');
                }
            });
        });

        // ========== Flash Messages ==========
        @if(session('error'))
            showAlert('{{ session("error") }}', 'error');
        @endif
        @if(session('success'))
            showAlert('{{ session("success") }}', 'success');
        @endif
    </script>
</body>
</html>
