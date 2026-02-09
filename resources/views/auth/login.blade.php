<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CustomerNearme - Login & Signup</title>
    @php
        $siteFavicon = \App\Models\Setting::get('site_favicon');
        $allowCompanyRegistration = \App\Models\Setting::get('allow_company_registration', true);
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
        .card-animate-delay {
            animation: card-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.15s forwards;
            opacity: 0;
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

        /* Slide transition for mobile tab panels */
        .panel-slide-enter {
            animation: panel-in 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes panel-in {
            from { opacity: 0; transform: translateX(12px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Tab indicator */
        .tab-indicator {
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Alert slide-in */
        @keyframes alert-slide {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .animate-alert {
            animation: alert-slide 0.3s ease-out;
        }

        /* Google button hover */
        .google-btn {
            transition: all 0.25s ease;
        }
        .google-btn:hover {
            border-color: #d1d5db;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transform: translateY(-1px);
        }

        /* Divider gradient */
        .divider-gradient {
            background: linear-gradient(90deg, transparent, #e5e7eb 20%, #e5e7eb 80%, transparent);
            height: 1px;
        }

        /* Account type selection */
        .account-type-option {
            transition: all 0.2s ease;
        }
        .account-type-option:has(input:checked) {
            border-color: #f97316;
            background-color: #fff7ed;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
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

    <!-- ===== Loading Overlay ===== -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[60] hidden">
        <div class="bg-white rounded-2xl p-6 flex items-center space-x-4 shadow-2xl">
            <div class="w-6 h-6 border-2 border-primary-orange border-t-transparent rounded-full animate-spin"></div>
            <span class="text-gray-700 font-medium">Please wait...</span>
        </div>
    </div>

    <!-- ===== Alert Messages ===== -->
    <div id="alertContainer" class="fixed top-4 right-4 z-[50] space-y-3 max-w-sm w-full"></div>

    <!-- ===== Forgot Password Modal ===== -->
    <div id="forgotPasswordModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[55] hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Reset Password</h3>
                    <button onclick="closeForgotPasswordModal()" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <form id="forgotPasswordForm" class="p-6">
                <div class="mb-6 text-center">
                    <div class="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-key text-primary-orange text-2xl"></i>
                    </div>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Enter your email address and we'll send you a link to reset your password.
                    </p>
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400 text-sm"></i>
                        </div>
                        <input type="email" name="email" id="forgotEmail" required
                               class="input-glow block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none transition-all"
                               placeholder="you@example.com">
                    </div>
                    <span class="text-red-500 text-xs mt-1 hidden" id="forgotEmailError"></span>
                </div>
                <button type="submit" id="forgotPasswordBtn" class="btn-primary w-full text-white font-semibold py-3 px-4 rounded-xl">
                    <span id="forgotPasswordBtnText">Send Reset Link</span>
                </button>
                <div class="mt-4 text-center">
                    <button type="button" onclick="closeForgotPasswordModal()" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                        Back to Login
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== Main Content ===== -->
    <div class="relative z-10 min-h-screen flex flex-col">

        <!-- ===== Header / Logo ===== -->
        <header class="py-6 px-4 sm:px-8">
            <div class="max-w-6xl mx-auto flex items-center justify-between">
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
                <a href="{{ url('/') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Home
                </a>
            </div>
        </header>

        <!-- ===== Auth Cards Section ===== -->
        <main class="flex-1 flex items-center justify-center px-4 sm:px-8 py-6 sm:py-10">
            <div class="w-full max-w-5xl">

                <!-- Heading -->
                <div class="text-center mb-8 sm:mb-10">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        Welcome to Customer<span class="text-primary-orange">Nearme</span>
                    </h1>
                    <p class="mt-2 text-gray-500 text-sm sm:text-base max-w-md mx-auto">
                        Access millions of verified business leads and grow your network.
                    </p>
                </div>

                <!-- ===== Mobile Tab Toggle (visible < lg) ===== -->
                <div class="lg:hidden mb-6">
                    <div class="relative bg-gray-100 rounded-xl p-1 flex">
                        <!-- Sliding indicator -->
                        <div id="tabIndicator" class="tab-indicator absolute top-1 bottom-1 left-1 w-[calc(50%-4px)] bg-white rounded-lg shadow-sm"></div>
                        <button onclick="switchTab('login')" id="tabLogin"
                                class="relative z-10 flex-1 py-2.5 text-sm font-semibold text-gray-900 rounded-lg transition-colors text-center">
                            Sign In
                        </button>
                        <button onclick="switchTab('signup')" id="tabSignup"
                                class="relative z-10 flex-1 py-2.5 text-sm font-semibold text-gray-500 rounded-lg transition-colors text-center">
                            Sign Up
                        </button>
                    </div>
                </div>

                <!-- ===== Cards Grid ===== -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 items-start">

                    <!-- ========== LOGIN CARD ========== -->
                    <div id="loginCard" class="card-animate">
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-200/50 overflow-hidden">
                            <!-- Card header accent -->
                            <div class="h-1.5 w-full bg-gradient-to-r from-primary-orange via-orange-400 to-primary-orange"></div>

                            <div class="p-6 sm:p-8">
                                <!-- Card title -->
                                <div class="mb-6">
                                    <div class="flex items-center gap-3 mb-1">
                                        <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center">
                                            <i class="fas fa-sign-in-alt text-primary-orange"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-bold text-gray-900">Sign In</h2>
                                            <p class="text-sm text-gray-500">Welcome back to your account</p>
                                        </div>
                                    </div>
                                </div>

                                <form id="loginFormElement" class="space-y-4">
                                    @csrf
                                    <!-- Email -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <i class="fas fa-envelope text-gray-400 text-sm"></i>
                                            </div>
                                            <input type="email" name="email" id="loginEmail" required
                                                   class="input-glow block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-gray-50/50 hover:bg-white"
                                                   placeholder="you@example.com">
                                        </div>
                                        <span class="text-red-500 text-xs mt-1 hidden" id="loginEmailError"></span>
                                    </div>

                                    <!-- Password -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <i class="fas fa-lock text-gray-400 text-sm"></i>
                                            </div>
                                            <input type="password" name="password" id="loginPassword" required
                                                   class="input-glow block w-full pl-10 pr-11 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-gray-50/50 hover:bg-white"
                                                   placeholder="Enter your password">
                                            <button type="button" onclick="togglePassword('loginPassword', 'loginToggleIcon')"
                                                    class="pwd-toggle absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400">
                                                <i class="fas fa-eye text-sm" id="loginToggleIcon"></i>
                                            </button>
                                        </div>
                                        <span class="text-red-500 text-xs mt-1 hidden" id="loginPasswordError"></span>
                                    </div>

                                    <!-- Remember + Forgot -->
                                    <div class="flex items-center justify-between pt-0.5">
                                        <label class="flex items-center gap-2 cursor-pointer group">
                                            <input type="checkbox" name="remember"
                                                   class="w-4 h-4 rounded border-gray-300 text-primary-orange focus:ring-primary-orange/30 transition-colors">
                                            <span class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors">Remember me</span>
                                        </label>
                                        <button type="button" onclick="openForgotPasswordModal()"
                                                class="text-sm font-medium text-primary-orange hover:text-dark-orange transition-colors">
                                            Forgot password?
                                        </button>
                                    </div>

                                    <!-- Login Button -->
                                    <button type="submit" id="loginButton" class="btn-primary w-full text-white font-semibold py-3.5 px-4 rounded-xl mt-2">
                                        <span id="loginButtonText" class="flex items-center justify-center gap-2">
                                            <i class="fas fa-arrow-right text-sm"></i>
                                            Sign In
                                        </span>
                                    </button>

                                    <!-- Divider -->
                                    <div class="relative my-5">
                                        <div class="divider-gradient"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="px-3 bg-white text-xs font-medium text-gray-400 uppercase tracking-wider">or</span>
                                        </div>
                                    </div>

                                    <!-- Google Sign In -->
                                    <a href="{{ route('auth.google') }}"
                                       class="google-btn w-full flex items-center justify-center gap-3 py-3 px-4 border-2 border-gray-100 rounded-xl bg-white text-sm font-medium text-gray-700 hover:text-gray-900">
                                        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
                                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                        </svg>
                                        Continue with Google
                                    </a>

                                    <!-- Toggle to signup (mobile) -->
                                    <div class="text-center pt-2 lg:hidden">
                                        <span class="text-sm text-gray-500">Don't have an account?</span>
                                        <button type="button" onclick="switchTab('signup')"
                                                class="text-sm font-semibold text-primary-orange hover:text-dark-orange ml-1 transition-colors">
                                            Sign up
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Social proof under login -->
                        <div class="mt-4 flex items-center justify-center gap-5 text-xs text-gray-400">
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-shield-alt text-green-500"></i>
                                256-bit SSL
                            </span>
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-lock text-primary-blue"></i>
                                Secure Login
                            </span>
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-check-circle text-primary-orange"></i>
                                GDPR Ready
                            </span>
                        </div>
                    </div>

                    <!-- ========== SIGNUP CARD ========== -->
                    <div id="signupCard" class="card-animate-delay hidden lg:block">
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-200/50 overflow-hidden">
                            <!-- Card header accent -->
                            <div class="h-1.5 w-full bg-gradient-to-r from-dark-blue via-primary-blue to-dark-blue"></div>

                            <div class="p-6 sm:p-8">
                                <!-- Card title -->
                                <div class="mb-6">
                                    <div class="flex items-center gap-3 mb-1">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                                            <i class="fas fa-user-plus text-dark-blue"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-bold text-gray-900">Create Account</h2>
                                            <p class="text-sm text-gray-500">Join thousands of businesses</p>
                                        </div>
                                    </div>
                                </div>

                                <form id="signupFormElement" class="space-y-4">
                                    @csrf
                                    <!-- Name fields -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">First Name</label>
                                            <input type="text" name="first_name" id="firstName" required
                                                   class="input-glow block w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-gray-50/50 hover:bg-white"
                                                   placeholder="John">
                                            <span class="text-red-500 text-xs mt-1 hidden" id="firstNameError"></span>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Last Name</label>
                                            <input type="text" name="last_name" id="lastName" required
                                                   class="input-glow block w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-gray-50/50 hover:bg-white"
                                                   placeholder="Doe">
                                            <span class="text-red-500 text-xs mt-1 hidden" id="lastNameError"></span>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <i class="fas fa-envelope text-gray-400 text-sm"></i>
                                            </div>
                                            <input type="email" name="email" id="signupEmail" required
                                                   class="input-glow block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-gray-50/50 hover:bg-white"
                                                   placeholder="you@example.com">
                                        </div>
                                        <span class="text-red-500 text-xs mt-1 hidden" id="signupEmailError"></span>
                                    </div>

                                    <!-- Account Type -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label class="account-type-option relative flex items-center gap-3 p-3 border-2 border-gray-100 rounded-xl cursor-pointer hover:border-orange-200">
                                                <input type="radio" name="user_type" value="user" checked class="w-4 h-4 text-primary-orange focus:ring-primary-orange/30 border-gray-300">
                                                <div>
                                                    <span class="block text-sm font-semibold text-gray-900">Individual</span>
                                                    <span class="block text-xs text-gray-400">Personal use</span>
                                                </div>
                                            </label>
                                            <label class="account-type-option relative flex items-center gap-3 p-3 border-2 border-gray-100 rounded-xl {{ $allowCompanyRegistration ? 'cursor-pointer hover:border-blue-200' : 'cursor-not-allowed opacity-50' }}">
                                                <input type="radio" name="user_type" value="company" class="w-4 h-4 text-primary-orange focus:ring-primary-orange/30 border-gray-300" {{ $allowCompanyRegistration ? '' : 'disabled' }}>
                                                <div>
                                                    <span class="block text-sm font-semibold text-gray-900">Company</span>
                                                    <span class="block text-xs {{ $allowCompanyRegistration ? 'text-gray-400' : 'text-red-400' }}">{{ $allowCompanyRegistration ? 'Team access' : 'Currently disabled' }}</span>
                                                </div>
                                            </label>
                                        </div>
                                        <span class="text-red-500 text-xs mt-1 hidden" id="userTypeError"></span>
                                    </div>

                                    <!-- Password -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <i class="fas fa-lock text-gray-400 text-sm"></i>
                                            </div>
                                            <input type="password" name="password" id="signupPassword" required
                                                   class="input-glow block w-full pl-10 pr-11 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-gray-50/50 hover:bg-white"
                                                   placeholder="Min 8 characters">
                                            <button type="button" onclick="togglePassword('signupPassword', 'signupToggleIcon')"
                                                    class="pwd-toggle absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400">
                                                <i class="fas fa-eye text-sm" id="signupToggleIcon"></i>
                                            </button>
                                        </div>
                                        <span class="text-red-500 text-xs mt-1 hidden" id="signupPasswordError"></span>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                <i class="fas fa-lock text-gray-400 text-sm"></i>
                                            </div>
                                            <input type="password" name="password_confirmation" id="confirmPassword" required
                                                   class="input-glow block w-full pl-10 pr-11 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none transition-all bg-gray-50/50 hover:bg-white"
                                                   placeholder="Re-enter password">
                                            <button type="button" onclick="togglePassword('confirmPassword', 'confirmToggleIcon')"
                                                    class="pwd-toggle absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400">
                                                <i class="fas fa-eye text-sm" id="confirmToggleIcon"></i>
                                            </button>
                                        </div>
                                        <span class="text-red-500 text-xs mt-1 hidden" id="confirmPasswordError"></span>
                                    </div>

                                    <!-- Terms -->
                                    <div class="flex items-start gap-2.5 pt-0.5">
                                        <input type="checkbox" id="agreeTerms" required
                                               class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary-orange focus:ring-primary-orange/30 transition-colors">
                                        <label for="agreeTerms" class="text-sm text-gray-500 leading-snug">
                                            I agree to the
                                            <a href="{{ route('terms') }}" target="_blank" class="font-medium text-primary-orange hover:text-dark-orange transition-colors">Terms of Service</a>
                                            and
                                            <a href="{{ route('privacy.policy') }}" target="_blank" class="font-medium text-primary-orange hover:text-dark-orange transition-colors">Privacy Policy</a>
                                        </label>
                                    </div>

                                    <!-- Signup Button -->
                                    <button type="submit" id="signupButton" class="btn-primary w-full text-white font-semibold py-3.5 px-4 rounded-xl mt-1">
                                        <span id="signupButtonText" class="flex items-center justify-center gap-2">
                                            <i class="fas fa-rocket text-sm"></i>
                                            Create Account
                                        </span>
                                    </button>

                                    <!-- Divider -->
                                    <div class="relative my-5">
                                        <div class="divider-gradient"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="px-3 bg-white text-xs font-medium text-gray-400 uppercase tracking-wider">or</span>
                                        </div>
                                    </div>

                                    <!-- Google Sign Up -->
                                    <a href="{{ route('auth.google') }}"
                                       class="google-btn w-full flex items-center justify-center gap-3 py-3 px-4 border-2 border-gray-100 rounded-xl bg-white text-sm font-medium text-gray-700 hover:text-gray-900">
                                        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
                                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                        </svg>
                                        Continue with Google
                                    </a>

                                    <!-- Toggle to login (mobile) -->
                                    <div class="text-center pt-2 lg:hidden">
                                        <span class="text-sm text-gray-500">Already have an account?</span>
                                        <button type="button" onclick="switchTab('login')"
                                                class="text-sm font-semibold text-primary-orange hover:text-dark-orange ml-1 transition-colors">
                                            Sign in
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Free trial badge under signup -->
                        <div class="mt-4 flex items-center justify-center">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 border border-green-100 rounded-full">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-xs font-medium text-green-700">Free trial available — No credit card required</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>

        <!-- ===== Footer ===== -->
        <footer class="py-6 px-4 sm:px-8">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-gray-400">
                    &copy; {{ date('Y') }} CustomerNearme. All rights reserved.
                </p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('terms') }}" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Terms of Service</a>
                    <span class="text-gray-200">|</span>
                    <a href="{{ route('privacy.policy') }}" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Privacy Policy</a>
                    <span class="text-gray-200">|</span>
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

        // ========== Mobile Tab Switching ==========
        let activeTab = 'login';

        function switchTab(tab) {
            const loginCard = document.getElementById('loginCard');
            const signupCard = document.getElementById('signupCard');
            const tabLogin = document.getElementById('tabLogin');
            const tabSignup = document.getElementById('tabSignup');
            const indicator = document.getElementById('tabIndicator');

            activeTab = tab;
            clearErrors();

            if (tab === 'login') {
                loginCard.classList.remove('hidden');
                signupCard.classList.add('hidden');
                loginCard.classList.add('panel-slide-enter');
                tabLogin.classList.replace('text-gray-500', 'text-gray-900');
                tabSignup.classList.replace('text-gray-900', 'text-gray-500');
                if (indicator) indicator.style.transform = 'translateX(0)';
            } else {
                signupCard.classList.remove('hidden');
                loginCard.classList.add('hidden');
                signupCard.classList.add('panel-slide-enter');
                tabSignup.classList.replace('text-gray-500', 'text-gray-900');
                tabLogin.classList.replace('text-gray-900', 'text-gray-500');
                if (indicator) indicator.style.transform = 'translateX(100%)';
            }

            // Remove animation class after it finishes
            setTimeout(() => {
                loginCard.classList.remove('panel-slide-enter');
                signupCard.classList.remove('panel-slide-enter');
            }, 350);
        }

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

        // ========== Forgot Password Modal ==========
        function openForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.remove('hidden');
            document.getElementById('forgotEmail').value = '';
            clearErrors();
        }
        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.add('hidden');
            document.getElementById('forgotPasswordForm').reset();
            clearErrors();
        }

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

        // ========== Error Handling ==========
        function clearErrors() {
            document.querySelectorAll('[id$="Error"]').forEach(el => {
                el.classList.add('hidden');
                el.textContent = '';
            });
            document.querySelectorAll('input').forEach(input => {
                input.classList.remove('border-red-500');
            });
        }

        function showErrors(errors) {
            Object.keys(errors).forEach(field => {
                const errEl = document.getElementById(field + 'Error');
                const inputEl = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
                if (errEl) {
                    errEl.textContent = errors[field][0];
                    errEl.classList.remove('hidden');
                }
                if (inputEl) {
                    inputEl.classList.add('border-red-500');
                }
            });
        }

        // ========== Button Loading State ==========
        function setButtonLoading(button, text, loading) {
            if (loading) {
                button.disabled = true;
                button.classList.add('opacity-75', 'cursor-not-allowed');
                text.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mx-auto"></div>';
            } else {
                button.disabled = false;
                button.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }

        // ========== Login Submission ==========
        $('#loginFormElement').on('submit', function(e) {
            e.preventDefault();
            clearErrors();
            const button = document.getElementById('loginButton');
            const text = document.getElementById('loginButtonText');
            setButtonLoading(button, text, true);

            $.ajax({
                url: '{{ route("auth.login") }}',
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => { window.location.href = response.redirect; }, 1000);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    if (response && response.errors) showErrors(response.errors);
                    showAlert(response?.message || 'Login failed', 'error');
                },
                complete: function() {
                    text.innerHTML = '<i class="fas fa-arrow-right text-sm"></i> Sign In';
                    setButtonLoading(button, text, false);
                }
            });
        });

        // ========== Signup Submission ==========
        $('#signupFormElement').on('submit', function(e) {
            e.preventDefault();

            const password = document.getElementById('signupPassword').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (password !== confirm) {
                showAlert('Passwords do not match!', 'error');
                return;
            }
            if (password.length < 8) {
                showAlert('Password must be at least 8 characters!', 'error');
                return;
            }

            clearErrors();
            const button = document.getElementById('signupButton');
            const text = document.getElementById('signupButtonText');
            setButtonLoading(button, text, true);

            $.ajax({
                url: '{{ route("auth.signup") }}',
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => { window.location.href = response.redirect; }, 1500);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    if (response && response.errors) showErrors(response.errors);
                    showAlert(response?.message || 'Signup failed', 'error');
                },
                complete: function() {
                    text.innerHTML = '<i class="fas fa-rocket text-sm"></i> Create Account';
                    setButtonLoading(button, text, false);
                }
            });
        });

        // ========== Forgot Password Submission ==========
        $('#forgotPasswordForm').on('submit', function(e) {
            e.preventDefault();
            clearErrors();
            const button = document.getElementById('forgotPasswordBtn');
            const text = document.getElementById('forgotPasswordBtnText');
            setButtonLoading(button, text, true);

            $.ajax({
                url: '{{ route("auth.send.reset.link") }}',
                method: 'POST',
                data: { email: document.getElementById('forgotEmail').value },
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        closeForgotPasswordModal();
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert(response?.message || 'Failed to send reset link', 'error');
                },
                complete: function() {
                    text.textContent = 'Send Reset Link';
                    setButtonLoading(button, text, false);
                }
            });
        });

        // ========== Clear Field Errors on Input ==========
        $('input').on('input', function() {
            const name = $(this).attr('name') || $(this).attr('id');
            const errEl = document.getElementById(name + 'Error');
            if (errEl && !errEl.classList.contains('hidden')) {
                errEl.classList.add('hidden');
                $(this).removeClass('border-red-500');
            }
        });

        // ========== Flash Messages ==========
        @if(session('success'))
            showAlert('{{ session("success") }}', 'success');
        @endif
        @if(session('error'))
            showAlert('{{ session("error") }}', 'error');
        @endif

        // ========== ESC to close modal ==========
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeForgotPasswordModal();
        });
    </script>
</body>
</html>
