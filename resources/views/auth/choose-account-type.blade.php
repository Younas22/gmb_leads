<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Choose Account Type - CustomerNearme</title>
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

        /* Account type card selection */
        .account-type-card {
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .account-type-card:has(input:checked) {
            border-color: #f97316;
            background-color: #fff7ed;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1), 0 8px 24px -4px rgba(249, 115, 22, 0.15);
            transform: translateY(-4px);
        }
        .account-type-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.1);
        }
        .account-type-card:has(input:checked):hover {
            transform: translateY(-6px);
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

        /* Alert slide-in */
        @keyframes alert-slide {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .animate-alert {
            animation: alert-slide 0.3s ease-out;
        }

        /* Check mark animation */
        @keyframes check-pop {
            0% { transform: scale(0) rotate(-45deg); }
            50% { transform: scale(1.2) rotate(-45deg); }
            100% { transform: scale(1) rotate(0deg); }
        }
        .check-mark-animate {
            animation: check-pop 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Feature list icon animation */
        .feature-icon {
            transition: transform 0.2s ease;
        }
        .account-type-card:hover .feature-icon {
            transform: scale(1.1);
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
            <span class="text-gray-700 font-medium">Setting up your account...</span>
        </div>
    </div>

    <!-- ===== Alert Messages ===== -->
    <div id="alertContainer" class="fixed top-4 right-4 z-[50] space-y-3 max-w-sm w-full"></div>

    <!-- ===== Main Content ===== -->
    <div class="relative z-10 min-h-screen flex flex-col">

        <!-- ===== Header / Logo ===== -->
        <header class="py-6 px-4 sm:px-8">
            <div class="max-w-4xl mx-auto flex items-center justify-between">
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

        <!-- ===== Main Form Section ===== -->
        <main class="flex-1 flex items-center justify-center px-4 sm:px-8 py-6 sm:py-10">
            <div class="w-full max-w-4xl">

                <!-- ===== Welcome Card ===== -->
                <div class="text-center mb-8 sm:mb-10 card-animate">
                    <!-- Welcome Icon -->
                    <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-primary-orange to-orange-600 rounded-2xl shadow-xl shadow-orange-200/50 mb-5">
                        <i class="fas fa-user-cog text-white text-2xl sm:text-3xl"></i>
                    </div>

                    <!-- Welcome Text -->
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight mb-3">
                        Welcome, <span class="text-primary-orange">{{ $user->first_name ?? $user->name }}</span>!
                    </h1>
                    <p class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto">
                        Choose your account type to unlock the full power of CustomerNearme
                    </p>

                    <!-- Progress indicator -->
                    <div class="flex items-center justify-center gap-2 mt-6">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700 hidden sm:inline">Account Created</span>
                        </div>
                        <div class="w-8 sm:w-16 h-0.5 bg-primary-orange"></div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-primary-orange flex items-center justify-center animate-pulse">
                                <i class="fas fa-arrow-right text-white text-xs"></i>
                            </div>
                            <span class="text-sm font-medium text-primary-orange hidden sm:inline">Choose Type</span>
                        </div>
                        <div class="w-8 sm:w-16 h-0.5 bg-gray-200"></div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-rocket text-gray-400 text-xs"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-400 hidden sm:inline">Dashboard</span>
                        </div>
                    </div>
                </div>

                <!-- ===== Account Type Form ===== -->
                <form id="accountTypeForm" class="card-animate">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">

                    <!-- Account Type Cards Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 mb-8">

                        <!-- ========== INDIVIDUAL CARD ========== -->
                        <label class="account-type-card relative bg-white rounded-2xl border-2 border-gray-200 p-6 sm:p-8 cursor-pointer">
                            <input type="radio" name="user_type" value="user" checked class="peer sr-only">

                            <!-- Selection Badge -->
                            <div class="absolute top-4 right-4 sm:top-6 sm:right-6">
                                <div class="w-7 h-7 rounded-full border-2 border-gray-300 bg-white flex items-center justify-center peer-checked:bg-primary-orange peer-checked:border-primary-orange transition-all duration-300">
                                    <i class="fas fa-check text-white text-xs opacity-0 peer-checked:opacity-100 check-mark-animate"></i>
                                </div>
                            </div>

                            <!-- Card Content -->
                            <div class="flex flex-col items-center text-center space-y-5">
                                <!-- Icon -->
                                <div class="w-20 h-20 sm:w-24 sm:h-24 bg-orange-50 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110">
                                    <i class="fas fa-user text-primary-orange text-3xl sm:text-4xl"></i>
                                </div>

                                <!-- Title & Description -->
                                <div>
                                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Individual</h3>
                                    <p class="text-sm sm:text-base text-gray-500 leading-relaxed">
                                        Perfect for freelancers, entrepreneurs, and solo professionals
                                    </p>
                                </div>

                                <!-- Features -->
                                <div class="pt-5 border-t border-gray-100 w-full">
                                    <ul class="space-y-3 text-left">
                                        <li class="flex items-center gap-3">
                                            <div class="feature-icon w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-check text-green-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm sm:text-base text-gray-700">Personal dashboard</span>
                                        </li>
                                        <li class="flex items-center gap-3">
                                            <div class="feature-icon w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-check text-green-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm sm:text-base text-gray-700">Basic lead exports</span>
                                        </li>
                                        <li class="flex items-center gap-3">
                                            <div class="feature-icon w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-check text-green-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm sm:text-base text-gray-700">Standard support</span>
                                        </li>
                                        <li class="flex items-center gap-3">
                                            <div class="feature-icon w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-check text-green-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm sm:text-base text-gray-700">Flexible pricing</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Badge -->
                                <div class="pt-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 border border-orange-100 rounded-full text-xs font-medium text-primary-orange">
                                        <i class="fas fa-star"></i>
                                        Most Popular
                                    </span>
                                </div>
                            </div>
                        </label>

                        <!-- ========== COMPANY CARD ========== -->
                        <label class="account-type-card relative bg-white rounded-2xl border-2 border-gray-200 p-6 sm:p-8 {{ $allowCompanyRegistration ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' }}">
                            <input type="radio" name="user_type" value="company" class="peer sr-only" {{ $allowCompanyRegistration ? '' : 'disabled' }}>

                            <!-- Selection Badge -->
                            <div class="absolute top-4 right-4 sm:top-6 sm:right-6">
                                <div class="w-7 h-7 rounded-full border-2 border-gray-300 bg-white flex items-center justify-center peer-checked:bg-primary-orange peer-checked:border-primary-orange transition-all duration-300">
                                    <i class="fas fa-check text-white text-xs opacity-0 peer-checked:opacity-100 check-mark-animate"></i>
                                </div>
                            </div>

                            <!-- Card Content -->
                            <div class="flex flex-col items-center text-center space-y-5">
                                <!-- Icon -->
                                <div class="w-20 h-20 sm:w-24 sm:h-24 bg-blue-50 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110">
                                    <i class="fas fa-building text-dark-blue text-3xl sm:text-4xl"></i>
                                </div>

                                <!-- Title & Description -->
                                <div>
                                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Company</h3>
                                    <p class="text-sm sm:text-base {{ $allowCompanyRegistration ? 'text-gray-500' : 'text-red-500' }} leading-relaxed">
                                        {{ $allowCompanyRegistration ? 'Ideal for teams, agencies, and organizations' : 'Currently disabled - Contact support for access' }}
                                    </p>
                                </div>

                                <!-- Features -->
                                <div class="pt-5 border-t border-gray-100 w-full">
                                    <ul class="space-y-3 text-left">
                                        <li class="flex items-center gap-3">
                                            <div class="feature-icon w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-check text-green-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm sm:text-base text-gray-700">Team collaboration</span>
                                        </li>
                                        <li class="flex items-center gap-3">
                                            <div class="feature-icon w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-check text-green-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm sm:text-base text-gray-700">Bulk lead exports</span>
                                        </li>
                                        <li class="flex items-center gap-3">
                                            <div class="feature-icon w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-check text-green-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm sm:text-base text-gray-700">Priority support</span>
                                        </li>
                                        <li class="flex items-center gap-3">
                                            <div class="feature-icon w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                <i class="fas fa-check text-green-600 text-xs"></i>
                                            </div>
                                            <span class="text-sm sm:text-base text-gray-700">Advanced analytics</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Badge -->
                                <div class="pt-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-100 rounded-full text-xs font-medium text-dark-blue">
                                        <i class="fas fa-bolt"></i>
                                        For Teams
                                    </span>
                                </div>
                            </div>
                        </label>

                    </div>

                    <!-- Submit Button -->
                    <div class="max-w-xl mx-auto space-y-5">
                        <button type="submit" id="submitBtn" class="btn-primary w-full text-white font-semibold py-4 px-6 rounded-xl shadow-lg">
                            <span id="submitBtnText" class="flex items-center justify-center gap-2 text-base sm:text-lg">
                                <i class="fas fa-arrow-right"></i>
                                Continue to Dashboard
                            </span>
                        </button>

                        <!-- Info Note -->
                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i class="fas fa-info text-white text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-blue-900">
                                        <strong class="font-semibold">Good to know:</strong> You can change your account type anytime from your profile settings.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </main>

        <!-- ===== Footer ===== -->
        <footer class="py-6 px-4 sm:px-8">
            <div class="max-w-4xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-gray-400">
                    &copy; {{ date('Y') }} CustomerNearme. All rights reserved.
                </p>
                <div class="flex items-center gap-4">
                    <a href="https://wa.me/923460820722" target="_blank" rel="noopener noreferrer" class="text-xs text-green-600 hover:text-green-700 transition-colors flex items-center gap-1.5">
                        <i class="fab fa-whatsapp"></i>
                        Need Help?
                    </a>
                    <span class="text-gray-200">|</span>
                    <a href="mailto:info@customernearme.com" class="text-xs text-primary-orange hover:text-dark-orange transition-colors font-medium">Contact Support</a>
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

        // ========== Loading States ==========
        function showLoading(show = true) {
            document.getElementById('loadingOverlay').classList.toggle('hidden', !show);
        }

        function setButtonLoading(loading = true) {
            const button = document.getElementById('submitBtn');
            const buttonText = document.getElementById('submitBtnText');

            if (loading) {
                button.disabled = true;
                button.classList.add('opacity-75', 'cursor-not-allowed');
                buttonText.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div> Setting up...';
            } else {
                button.disabled = false;
                button.classList.remove('opacity-75', 'cursor-not-allowed');
                buttonText.innerHTML = '<i class="fas fa-arrow-right"></i> Continue to Dashboard';
            }
        }

        // ========== Form Submission ==========
        $('#accountTypeForm').on('submit', function(e) {
            e.preventDefault();

            setButtonLoading(true);

            $.ajax({
                url: '{{ route("auth.save.account.type") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        showLoading(true);

                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    } else {
                        showAlert(response.message || 'Something went wrong', 'error');
                        setButtonLoading(false);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert(response?.message || 'Failed to save account type. Please try again.', 'error');
                    setButtonLoading(false);
                }
            });
        });

        // ========== Flash Messages ==========
        @if(session('success'))
            showAlert('{{ session("success") }}', 'success');
        @endif
        @if(session('error'))
            showAlert('{{ session("error") }}', 'error');
        @endif
    </script>
</body>
</html>
