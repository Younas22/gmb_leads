<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customer Nearme - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
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
</head>
<body class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-orange-50">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
            <i class="fas fa-spinner fa-spin text-primary-600 text-xl"></i>
            <span class="text-gray-700">Please wait...</span>
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alertContainer" class="fixed top-4 right-4 z-40"></div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Reset Password</h3>
                    <button onclick="closeForgotPasswordModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form id="forgotPasswordForm" class="p-6">
                <div class="mb-6">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-key text-primary-600 text-2xl"></i>
                    </div>
                    <p class="text-center text-gray-600 mb-4">
                        Enter your email address and we'll send you a link to reset your password.
                    </p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="email" id="forgotEmail" required 
                               class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                               placeholder="Enter your email">
                    </div>
                    <span class="text-red-500 text-sm hidden" id="forgotEmailError"></span>
                </div>

                <button type="submit" id="forgotPasswordBtn"
                        class="w-full bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02]">
                    <span id="forgotPasswordBtnText">Send Reset Link</span>
                </button>

                <div class="mt-4 text-center">
                    <button type="button" onclick="closeForgotPasswordModal()" class="text-sm text-gray-600 hover:text-gray-900">
                        Back to Login
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="min-h-screen flex">
        <!-- Left Side - Hero Section -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 to-primary-800 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 bg-gradient-to-br from-primary-600/90 to-primary-800/90"></div>
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            
            <div class="relative z-10 flex flex-col justify-center px-12 py-12 text-white">
                <div class="max-w-md">
                    <div class="flex items-center space-x-3 mb-8">
                        <img src="{{ asset('public/assets/images/white-logo.svg') }}" 
                            alt="BusinessFinder Logo" 
                            class="h-20 w-auto">
                    </div>
                    
                    <h2 class="text-3xl font-bold mb-6">Discover Business Opportunities Worldwide</h2>
                    <p class="text-primary-100 text-lg mb-8 leading-relaxed">
                        Access millions of verified business contacts, generate high-quality leads, 
                        and grow your network with our advanced search tools.
                    </p>
                    
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4">
                            <div class="bg-white/20 rounded-full p-2">
                                <i class="fas fa-globe text-orange-300"></i>
                            </div>
                            <span class="text-primary-100">Global business database</span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="bg-white/20 rounded-full p-2">
                                <i class="fas fa-download text-orange-300"></i>
                            </div>
                            <span class="text-primary-100">Export to CSV & Excel</span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="bg-white/20 rounded-full p-2">
                                <i class="fas fa-rocket text-orange-300"></i>
                            </div>
                            <span class="text-primary-100">Advanced filtering & search</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Form Section -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden flex items-center justify-center space-x-3 mb-8">
                    <div class="bg-primary-600 rounded-lg p-3">
                        <i class="fas fa-search text-white text-xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">BusinessFinder</h1>
                </div>

                <!-- Form Container -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
                    <!-- Form Header -->
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900" id="formTitle">Welcome Back</h2>
                        <p class="text-gray-600 mt-2" id="formSubtitle">Sign in to access your account</p>
                    </div>

                    <!-- Login Form -->
                    <div id="loginForm">
                        <form id="loginFormElement" class="space-y-6">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input type="email" name="email" id="loginEmail" required 
                                           class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="Enter your email">
                                </div>
                                <span class="text-red-500 text-sm hidden" id="loginEmailError"></span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" name="password" id="loginPassword" required 
                                           class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="Enter your password">
                                    <button type="button" onclick="togglePassword('loginPassword', 'loginToggleIcon')" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="loginToggleIcon"></i>
                                    </button>
                                </div>
                                <span class="text-red-500 text-sm hidden" id="loginPasswordError"></span>
                            </div>

                            <div class="flex items-center justify-between">
                                <label class="flex items-center">
                                    <input type="checkbox" name="remember" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Remember me</span>
                                </label>
                                <button type="button" onclick="openForgotPasswordModal()" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                                    Forgot password?
                                </button>
                            </div>

                            <button type="submit" id="loginButton"
                                    class="w-full bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <span id="loginButtonText"><i class="fas fa-sign-in-alt mr-2"></i>Sign In</span>
                            </button>

                            <!-- Divider -->
                            <div class="relative my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-4 bg-white text-gray-500">or continue with</span>
                                </div>
                            </div>

                            <!-- Google Sign In -->
                            <a href="{{ route('auth.google') }}" 
                               class="w-full flex items-center justify-center py-3 px-4 border-2 border-gray-200 rounded-xl bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 group">
                                <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span class="text-gray-700 font-medium group-hover:text-gray-900">Continue with Google</span>
                            </a>

                            <div class="text-center mt-6">
                                <span class="text-gray-600">Don't have an account? </span>
                                <button type="button" onclick="toggleForm()" 
                                        class="font-medium text-primary-600 hover:text-primary-500 focus:outline-none">
                                    Sign up
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Signup Form -->
                    <div id="signupForm" class="hidden">
                        <form id="signupFormElement" class="space-y-6">
                            @csrf
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input type="text" name="first_name" id="firstName" required 
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="First name">
                                    <span class="text-red-500 text-sm hidden" id="firstNameError"></span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input type="text" name="last_name" id="lastName" required 
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="Last name">
                                    <span class="text-red-500 text-sm hidden" id="lastNameError"></span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input type="email" name="email" id="signupEmail" required
                                           class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="Enter your email">
                                </div>
                                <span class="text-red-500 text-sm hidden" id="signupEmailError"></span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Account Type</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="relative flex items-center p-4 border-2 border-gray-300 rounded-xl cursor-pointer hover:border-primary-500 transition-colors account-type-option">
                                        <input type="radio" name="user_type" value="user" checked class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300">
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-gray-900">
                                                <i class="fas fa-user mr-2 text-primary-600"></i>Individual
                                            </span>
                                            <span class="block text-xs text-gray-500 mt-1">For personal use</span>
                                        </div>
                                    </label>
                                    <label class="relative flex items-center p-4 border-2 border-gray-300 rounded-xl cursor-pointer hover:border-primary-500 transition-colors account-type-option">
                                        <input type="radio" name="user_type" value="company" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300">
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-gray-900">
                                                <i class="fas fa-building mr-2 text-orange-600"></i>Company
                                            </span>
                                            <span class="block text-xs text-gray-500 mt-1">For team collaboration</span>
                                        </div>
                                    </label>
                                </div>
                                <span class="text-red-500 text-sm hidden" id="userTypeError"></span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" name="password" id="signupPassword" required 
                                           class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="Create password">
                                    <button type="button" onclick="togglePassword('signupPassword', 'signupToggleIcon')" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="signupToggleIcon"></i>
                                    </button>
                                </div>
                                <span class="text-red-500 text-sm hidden" id="signupPasswordError"></span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" name="password_confirmation" id="confirmPassword" required 
                                           class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="Confirm password">
                                    <button type="button" onclick="togglePassword('confirmPassword', 'confirmToggleIcon')" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="confirmToggleIcon"></i>
                                    </button>
                                </div>
                                <span class="text-red-500 text-sm hidden" id="confirmPasswordError"></span>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" id="agreeTerms" required
                                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <span class="text-gray-600">I agree to the </span>
                                    <a href="#" class="font-medium text-primary-600 hover:text-primary-500">Terms of Service</a>
                                    <span class="text-gray-600"> and </span>
                                    <a href="#" class="font-medium text-primary-600 hover:text-primary-500">Privacy Policy</a>
                                </div>
                            </div>

                            <button type="submit" id="signupButton"
                                    class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-medium py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                                <span id="signupButtonText"><i class="fas fa-user-plus mr-2"></i>Create Account</span>
                            </button>

                            <!-- Divider -->
                            <div class="relative my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-4 bg-white text-gray-500">or sign up with</span>
                                </div>
                            </div>

                            <!-- Google Sign Up -->
                            <a href="{{ route('auth.google') }}" 
                               class="w-full flex items-center justify-center py-3 px-4 border-2 border-gray-200 rounded-xl bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 group">
                                <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span class="text-gray-700 font-medium group-hover:text-gray-900">Continue with Google</span>
                            </a>

                            <div class="text-center mt-6">
                                <span class="text-gray-600">Already have an account? </span>
                                <button type="button" onclick="toggleForm()" 
                                        class="font-medium text-primary-600 hover:text-primary-500 focus:outline-none">
                                    Sign in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Features for Mobile -->
                <div class="lg:hidden mt-8 bg-white/80 backdrop-blur-sm rounded-xl p-6 border border-gray-200">
                    <h3 class="font-semibold text-gray-900 mb-4 text-center">Why Choose BusinessFinder?</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="flex items-center space-x-2">
                            <div class="bg-primary-100 rounded-lg p-2">
                                <i class="fas fa-search text-primary-600"></i>
                            </div>
                            <span class="text-gray-700">Smart Search</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="bg-orange-100 rounded-lg p-2">
                                <i class="fas fa-download text-orange-600"></i>
                            </div>
                            <span class="text-gray-700">Export Data</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="bg-green-100 rounded-lg p-2">
                                <i class="fas fa-globe text-green-600"></i>
                            </div>
                            <span class="text-gray-700">Global Access</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="bg-purple-100 rounded-lg p-2">
                                <i class="fas fa-chart-line text-purple-600"></i>
                            </div>
                            <span class="text-gray-700">Analytics</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Setup CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function toggleForm() {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const formTitle = document.getElementById('formTitle');
            const formSubtitle = document.getElementById('formSubtitle');

            clearErrors();
            clearForms();

            if (loginForm.classList.contains('hidden')) {
                loginForm.classList.remove('hidden');
                signupForm.classList.add('hidden');
                formTitle.textContent = 'Welcome Back';
                formSubtitle.textContent = 'Sign in to access your account';
            } else {
                loginForm.classList.add('hidden');
                signupForm.classList.remove('hidden');
                formTitle.textContent = 'Create Account';
                formSubtitle.textContent = 'Join thousands of businesses growing with us';
            }
        }

        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

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

        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alertId = 'alert-' + Date.now();
            
            const alertClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            const alertHtml = `
                <div id="${alertId}" class="${alertClass} text-white px-6 py-4 rounded-lg shadow-lg mb-4 animate-slide-in">
                    <div class="flex items-center">
                        <i class="fas ${iconClass} mr-3"></i>
                        <span>${message}</span>
                        <button onclick="removeAlert('${alertId}')" class="ml-4 text-white hover:text-gray-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            alertContainer.insertAdjacentHTML('beforeend', alertHtml);
            
            setTimeout(() => {
                removeAlert(alertId);
            }, 5000);
        }

        function removeAlert(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.remove();
            }
        }

        function clearErrors() {
            document.querySelectorAll('[id$="Error"]').forEach(errorElement => {
                errorElement.classList.add('hidden');
                errorElement.textContent = '';
            });

            document.querySelectorAll('input').forEach(input => {
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
            });
        }

        function showErrors(errors) {
            Object.keys(errors).forEach(field => {
                const errorElement = document.getElementById(field + 'Error');
                const inputElement = document.getElementById(field) || 
                                   document.querySelector(`[name="${field}"]`);
                
                if (errorElement) {
                    errorElement.textContent = errors[field][0];
                    errorElement.classList.remove('hidden');
                }
                
                if (inputElement) {
                    inputElement.classList.add('border-red-500');
                    inputElement.classList.remove('border-gray-300');
                }
            });
        }

        function clearForms() {
            document.getElementById('loginFormElement').reset();
            document.getElementById('signupFormElement').reset();
        }

        function setButtonLoading(button, text, loading = true) {
            if (loading) {
                button.disabled = true;
                button.classList.add('opacity-75', 'cursor-not-allowed');
                text.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            } else {
                button.disabled = false;
                button.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }

        // Login form submission
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
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    if (response.errors) {
                        showErrors(response.errors);
                    }
                    showAlert(response.message || 'Login failed', 'error');
                },
                complete: function() {
                    text.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Sign In';
                    setButtonLoading(button, text, false);
                }
            });
        });

        // Signup form submission
        $('#signupFormElement').on('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                showAlert('Passwords do not match!', 'error');
                return;
            }
            
            if (password.length < 8) {
                showAlert('Password must be at least 8 characters long!', 'error');
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
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1500);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    if (response.errors) {
                        showErrors(response.errors);
                    }
                    showAlert(response.message || 'Signup failed', 'error');
                },
                complete: function() {
                    text.innerHTML = '<i class="fas fa-user-plus mr-2"></i>Create Account';
                    setButtonLoading(button, text, false);
                }
            });
        });

        // Forgot password form submission
        $('#forgotPasswordForm').on('submit', function(e) {
            e.preventDefault();
            
            clearErrors();
            const button = document.getElementById('forgotPasswordBtn');
            const text = document.getElementById('forgotPasswordBtnText');
            setButtonLoading(button, text, true);
            
            $.ajax({
                url: '{{ route("auth.send.reset.link") }}',
                method: 'POST',
                data: {
                    email: document.getElementById('forgotEmail').value
                },
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        closeForgotPasswordModal();
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert(response.message || 'Failed to send reset link', 'error');
                },
                complete: function() {
                    text.textContent = 'Send Reset Link';
                    setButtonLoading(button, text, false);
                }
            });
        });

        // Clear errors when typing
        $('input').on('input', function() {
            const fieldName = $(this).attr('name') || $(this).attr('id');
            const errorElement = document.getElementById(fieldName + 'Error');
            
            if (errorElement && !errorElement.classList.contains('hidden')) {
                errorElement.classList.add('hidden');
                $(this).removeClass('border-red-500').addClass('border-gray-300');
            }
        });

        // Show Laravel flash messages
        @if(session('success'))
            showAlert('{{ session("success") }}', 'success');
        @endif

        @if(session('error'))
            showAlert('{{ session("error") }}', 'error');
        @endif

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeForgotPasswordModal();
            }
        });
    </script>

    <style>
        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }

        /* Account type selection styling */
        .account-type-option:has(input:checked) {
            border-color: #2563eb;
            background-color: #eff6ff;
        }
    </style>
</body>
</html>