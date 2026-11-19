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
        $siteLogo = \App\Models\Setting::get('site_logo');
        $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        $googleLoginOn = filled(config('services.google.client_id'));
        $authIllustration = file_exists(public_path('assets/images/auth-illustration.png'));
    @endphp
    @if($siteFavicon)
        <link rel="icon" type="image/png" href="{{ asset('public/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('public/assets/images/favicon.png') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'poppins': ['Poppins', 'system-ui', '-apple-system', 'sans-serif'] },
                    colors: { 'primary-orange': '#f97316', 'dark-orange': '#ea580c' }
                }
            }
        }
    </script>

    <script>
        const BASE_URL = '{{ url("/") }}';
    </script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Poppins', system-ui, -apple-system, sans-serif; color: #111827; }

        /* Fields */
        .au-label { display: block; margin-bottom: 6px; font-size: 13.5px; font-weight: 600; color: #111827; }
        .au-field-wrap { position: relative; }
        .au-field-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #64748B; font-size: 14px; pointer-events: none; width: 18px; text-align: center; }
        .au-field { display: block; width: 100%; height: 46px; padding: 0 14px 0 42px; border: 1px solid #CBD5E1; border-radius: 10px; background: #fff; color: #111827; font-size: 14.5px; transition: border-color .15s, box-shadow .15s; }
        .au-field::placeholder { color: #94A3B8; }
        .au-field:hover { border-color: #94A3B8; }
        .au-field:focus { outline: none; border-color: #F97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, .15); }
        .au-field.has-toggle { padding-right: 46px; }
        .au-field.no-icon { padding-left: 14px; }
        .au-field.border-red-500 { border-color: #DC2626; }
        .au-eye { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); width: 34px; height: 34px; border-radius: 8px; color: #64748B; display: flex; align-items: center; justify-content: center; transition: color .15s, background-color .15s; }
        .au-eye:hover { color: #F97316; background: #FFF7F2; }
        .au-err { display: block; margin-top: 4px; font-size: 12px; color: #DC2626; }

        /* Buttons */
        .au-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 48px; border-radius: 10px; background: #F97316; color: #fff; font-size: 15.5px; font-weight: 600; transition: background-color .15s; }
        .au-btn:hover { background: #EA580C; }
        .au-btn:disabled { opacity: .75; cursor: not-allowed; }
        .au-google { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; height: 46px; border-radius: 10px; border: 1px solid #D1D5DB; background: #fff; color: #111827; font-size: 14.5px; font-weight: 600; transition: border-color .15s, background-color .15s; }
        .au-google:hover { border-color: #9CA3AF; background: #FAFAFA; }
        .au-check { width: 17px; height: 17px; border-radius: 4px; accent-color: #F97316; cursor: pointer; flex-shrink: 0; }
        :is(.au-btn, .au-google, .au-eye, .au-link):focus-visible { outline: 2px solid #F97316; outline-offset: 3px; }

        /* Alerts */
        @keyframes alert-slide { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .animate-alert { animation: alert-slide .3s ease-out; }
        @media (prefers-reduced-motion: reduce) { .animate-alert { animation: none; } * { transition: none !important; } }
    </style>
</head>

<body class="min-h-screen bg-white font-poppins overflow-x-hidden">

    <!-- ===== Loading Overlay ===== -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] hidden">
        <div class="bg-white rounded-2xl p-6 flex items-center space-x-4 shadow-2xl">
            <div class="w-6 h-6 border-2 border-primary-orange border-t-transparent rounded-full animate-spin"></div>
            <span class="text-gray-700 font-medium">Please wait...</span>
        </div>
    </div>

    <!-- ===== Alert Messages ===== -->
    <div id="alertContainer" class="fixed top-4 right-4 z-[50] space-y-3 max-w-sm w-[calc(100%-2rem)]" aria-live="polite"></div>

    <!-- ===== Forgot Password Modal ===== -->
    <div id="forgotPasswordModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[55] hidden" role="dialog" aria-modal="true" aria-labelledby="forgotTitle">
        <div class="bg-white rounded-[22px] shadow-2xl max-w-md w-full mx-4">
            <div class="flex items-center justify-between px-7 pt-6">
                <h3 id="forgotTitle" class="text-[22px] font-bold text-gray-900">Reset Password</h3>
                <button type="button" onclick="closeForgotPasswordModal()" class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="forgotPasswordForm" class="px-7 pb-7 pt-3">
                <p class="text-[14.5px] text-[#475569] leading-relaxed mb-5">Enter your email address and we'll send you a link to reset your password.</p>
                <div class="mb-5">
                    <label for="forgotEmail" class="au-label">Email Address</label>
                    <div class="au-field-wrap">
                        <i class="far fa-envelope au-field-icon" aria-hidden="true"></i>
                        <input type="email" name="email" id="forgotEmail" required autocomplete="email" class="au-field" placeholder="you@example.com">
                    </div>
                    <span class="au-err hidden" id="forgotEmailError"></span>
                </div>
                <button type="submit" id="forgotPasswordBtn" class="au-btn !h-[54px] !text-[16px]">
                    <span id="forgotPasswordBtnText">Send Reset Link</span>
                </button>
                <div class="mt-4 text-center">
                    <button type="button" onclick="closeForgotPasswordModal()" class="text-sm text-gray-500 hover:text-gray-700">Back to Login</button>
                </div>
            </form>
        </div>
    </div>

    <div class="min-h-screen lg:h-screen grid grid-cols-1 lg:grid-cols-2">

        <!-- ===== Left: brand + illustration (desktop) ===== -->
        <aside class="hidden lg:flex flex-col bg-[#FEF4EE] px-10 xl:px-16 pt-8 pb-6 overflow-hidden" aria-label="About {{ $siteName }}">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 self-start" aria-label="{{ $siteName }} home">
                @if($siteLogo)
                    <img src="{{ asset('public/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain">
                @else
                    <span class="w-10 h-10 rounded-full bg-[#F97316] flex items-center justify-center"><i class="fas fa-location-dot text-white" aria-hidden="true"></i></span>
                    <span class="leading-tight">
                        <span class="block text-[21px] font-bold tracking-tight"><span class="text-[#F97316]">Customer</span>NearMe</span>
                        <span class="block text-[12px] text-[#475569]">Verified GMB Leads Instantly</span>
                    </span>
                @endif
            </a>

            <h1 class="mt-8 xl:mt-10 text-[32px] xl:text-[40px] font-extrabold leading-[1.1] tracking-[-0.02em] text-[#111827]">
                Find Verified<br>Business Leads<br>with <span class="text-[#F97316]">CustomerNearMe</span>
            </h1>
            <p class="mt-4 text-[15px] xl:text-[16px] leading-relaxed text-[#475569] max-w-[500px]">
                Discover high-quality Google Maps business leads and reach them faster with AI-powered outreach.
            </p>

            <ul class="mt-5 flex flex-wrap items-center gap-y-3">
                @foreach([['fa-check', 'Verified', 'GMB Leads'], ['fa-bolt', 'AI-Powered', 'Outreach'], ['fa-chart-simple', 'Grow', 'Your Business']] as $i => [$bIcon, $bTop, $bBottom])
                    <li class="flex items-center gap-2.5 {{ $i > 0 ? 'pl-4 xl:pl-5 ml-4 xl:ml-5 border-l border-[#E5D5CB]' : '' }}">
                        <span class="w-9 h-9 rounded-lg bg-white text-[#F97316] flex items-center justify-center shadow-sm flex-shrink-0"><i class="fas {{ $bIcon }} text-[14px]" aria-hidden="true"></i></span>
                        <span class="text-[13px] font-semibold leading-tight text-[#111827]">{{ $bTop }}<br>{{ $bBottom }}@if($i === 1 && \App\Support\PlanFeatureCatalog::aiComingSoon())<span class="ml-1.5 align-middle px-1.5 py-0.5 rounded bg-[#111827] text-white text-[9.5px] font-semibold uppercase tracking-wide">Soon</span>@endif</span>
                    </li>
                @endforeach
            </ul>

            @if($authIllustration)
                <div class="flex-1 min-h-0 flex items-end pt-5">
                    <img src="{{ asset('public/assets/images/auth-illustration.png') }}" width="650" height="425" loading="lazy"
                         alt="Illustration of Google Maps business leads with an AI Outreach label"
                         class="w-auto max-w-full max-h-full object-contain object-left-bottom" style="mix-blend-mode: multiply;">
                </div>
            @endif
        </aside>

        <!-- ===== Right: login / signup ===== -->
        <main class="flex flex-col bg-[#FAFAFB] px-4 sm:px-8 xl:px-12 pt-5 pb-5 lg:overflow-y-auto">
            <div class="flex items-center justify-between lg:justify-end">
                <a href="{{ url('/') }}" class="lg:hidden inline-flex items-center gap-2" aria-label="{{ $siteName }} home">
                    @if($siteLogo)
                        <img src="{{ asset('public/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-9 w-auto object-contain">
                    @else
                        <span class="w-9 h-9 rounded-full bg-[#F97316] flex items-center justify-center"><i class="fas fa-location-dot text-white" aria-hidden="true"></i></span>
                        <span class="text-[18px] font-bold tracking-tight"><span class="text-[#F97316]">Customer</span>NearMe</span>
                    @endif
                </a>
                <a href="{{ url('/') }}" class="au-link inline-flex items-center gap-2 text-[14px] font-medium text-[#334155] hover:text-[#111827] rounded-md">
                    <i class="fas fa-arrow-left text-[14px]" aria-hidden="true"></i> Back to Home
                </a>
            </div>

            <div class="flex-1 flex flex-col items-center justify-center py-4">
                <div class="w-full max-w-[460px] bg-white border border-[#EEF0F3] rounded-[20px] shadow-[0_10px_40px_-18px_rgba(17,24,39,.18)] px-6 py-6 sm:px-8 sm:py-7">

                    <!-- ========== LOGIN ========== -->
                    <section id="loginCard" aria-labelledby="loginTitle">
                        <h2 id="loginTitle" class="text-[26px] sm:text-[30px] font-extrabold leading-tight tracking-[-0.02em] text-[#111827]">Welcome Back</h2>
                        <span class="block w-[60px] h-[4px] rounded-full bg-[#F97316] mt-2" aria-hidden="true"></span>
                        <p class="mt-3 text-[14px] leading-relaxed text-[#475569]">Sign in to your {{ $siteName }} account and continue growing your network.</p>

                        <form id="loginFormElement" class="mt-5 space-y-3.5">
                            @csrf
                            <div>
                                <label for="loginEmail" class="au-label">Email Address</label>
                                <div class="au-field-wrap">
                                    <i class="far fa-envelope au-field-icon" aria-hidden="true"></i>
                                    <input type="email" name="email" id="loginEmail" required autocomplete="email" aria-describedby="loginEmailError"
                                           class="au-field" placeholder="you@example.com">
                                </div>
                                <span class="au-err hidden" id="loginEmailError"></span>
                            </div>

                            <div>
                                <label for="loginPassword" class="au-label">Password</label>
                                <div class="au-field-wrap">
                                    <i class="fas fa-lock au-field-icon" aria-hidden="true"></i>
                                    <input type="password" name="password" id="loginPassword" required autocomplete="current-password" aria-describedby="loginPasswordError"
                                           class="au-field has-toggle" placeholder="Enter your password">
                                    <button type="button" onclick="togglePassword('loginPassword', 'loginToggleIcon', this)" class="au-eye" aria-label="Show password" aria-pressed="false">
                                        <i class="far fa-eye" id="loginToggleIcon" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <span class="au-err hidden" id="loginPasswordError"></span>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label class="inline-flex items-center gap-2.5 cursor-pointer min-h-[28px]">
                                    <input type="checkbox" name="remember" class="au-check">
                                    <span class="text-[13.5px] text-[#334155]">Remember me</span>
                                </label>
                                <button type="button" onclick="openForgotPasswordModal()" class="au-link text-[13.5px] font-medium text-[#F97316] hover:text-[#EA580C] rounded-md">
                                    Forgot password?
                                </button>
                            </div>

                            <button type="submit" id="loginButton" class="au-btn">
                                <span id="loginButtonText" class="flex items-center justify-center gap-2.5"><i class="fas fa-arrow-right" aria-hidden="true"></i> Sign In</span>
                            </button>
                        </form>

                        @if($googleLoginOn)
                            <div class="flex items-center gap-3 my-4" aria-hidden="true">
                                <span class="flex-1 h-px bg-[#E5E7EB]"></span>
                                <span class="text-[12.5px] font-medium text-[#64748B]">OR</span>
                                <span class="flex-1 h-px bg-[#E5E7EB]"></span>
                            </div>
                            <a href="{{ route('auth.google') }}" class="au-google">
                                @include('auth._google-icon')
                                Continue with Google
                            </a>
                        @endif

                        <div class="mt-4 rounded-lg bg-[#F3F4F6] px-4 py-2.5 text-center text-[14px] text-[#111827]">
                            Don't have an account?
                            <a href="#signup" onclick="switchTab('signup'); return false;" class="au-link font-semibold text-[#F97316] underline underline-offset-4 hover:text-[#EA580C] ml-1 rounded">Sign up</a>
                        </div>
                    </section>

                    <!-- ========== SIGNUP ========== -->
                    <section id="signupCard" class="hidden" aria-labelledby="signupTitle">
                        <h2 id="signupTitle" class="text-[26px] sm:text-[30px] font-extrabold leading-tight tracking-[-0.02em] text-[#111827]">Create Account</h2>
                        <span class="block w-[60px] h-[4px] rounded-full bg-[#F97316] mt-2" aria-hidden="true"></span>
                        <p class="mt-3 text-[14px] leading-relaxed text-[#475569]">Start your 3-day free trial. No credit card required.</p>

                        <form id="signupFormElement" class="mt-5 space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="firstName" class="au-label">First Name</label>
                                    <input type="text" name="first_name" id="firstName" required autocomplete="given-name" class="au-field no-icon" placeholder="John">
                                    <span class="au-err hidden" id="firstNameError"></span>
                                </div>
                                <div>
                                    <label for="lastName" class="au-label">Last Name</label>
                                    <input type="text" name="last_name" id="lastName" required autocomplete="family-name" class="au-field no-icon" placeholder="Doe">
                                    <span class="au-err hidden" id="lastNameError"></span>
                                </div>
                            </div>

                            <div>
                                <label for="signupEmail" class="au-label">Email Address</label>
                                <div class="au-field-wrap">
                                    <i class="far fa-envelope au-field-icon" aria-hidden="true"></i>
                                    <input type="email" name="email" id="signupEmail" required autocomplete="email" class="au-field" placeholder="you@example.com">
                                </div>
                                <span class="au-err hidden" id="signupEmailError"></span>
                            </div>

                            <input type="hidden" name="user_type" value="user">
                            <input type="hidden" name="ref_code" id="signupRefCode" value="">

                            <div>
                                <label for="signupPassword" class="au-label">Password</label>
                                <div class="au-field-wrap">
                                    <i class="fas fa-lock au-field-icon" aria-hidden="true"></i>
                                    <input type="password" name="password" id="signupPassword" required autocomplete="new-password" class="au-field has-toggle" placeholder="Min 8 characters">
                                    <button type="button" onclick="togglePassword('signupPassword', 'signupToggleIcon', this)" class="au-eye" aria-label="Show password" aria-pressed="false">
                                        <i class="far fa-eye" id="signupToggleIcon" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <span class="au-err hidden" id="signupPasswordError"></span>
                            </div>

                            <div>
                                <label for="confirmPassword" class="au-label">Confirm Password</label>
                                <div class="au-field-wrap">
                                    <i class="fas fa-lock au-field-icon" aria-hidden="true"></i>
                                    <input type="password" name="password_confirmation" id="confirmPassword" required autocomplete="new-password" class="au-field has-toggle" placeholder="Re-enter password">
                                    <button type="button" onclick="togglePassword('confirmPassword', 'confirmToggleIcon', this)" class="au-eye" aria-label="Show password" aria-pressed="false">
                                        <i class="far fa-eye" id="confirmToggleIcon" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <span class="au-err hidden" id="confirmPasswordError"></span>
                            </div>

                            <label for="agreeTerms" class="flex items-start gap-2.5 pt-0.5 cursor-pointer">
                                <input type="checkbox" id="agreeTerms" required class="au-check mt-0.5">
                                <span class="text-[13px] text-[#475569] leading-snug">
                                    I agree to the
                                    <a href="{{ route('terms') }}" target="_blank" class="font-medium text-[#F97316] hover:text-[#EA580C]">Terms of Service</a>
                                    and
                                    <a href="{{ route('privacy.policy') }}" target="_blank" class="font-medium text-[#F97316] hover:text-[#EA580C]">Privacy Policy</a>
                                </span>
                            </label>

                            <button type="submit" id="signupButton" class="au-btn">
                                <span id="signupButtonText" class="flex items-center justify-center gap-2.5"><i class="fas fa-rocket" aria-hidden="true"></i> Create Account</span>
                            </button>
                        </form>

                        @if($googleLoginOn)
                            <div class="flex items-center gap-3 my-4" aria-hidden="true">
                                <span class="flex-1 h-px bg-[#E5E7EB]"></span>
                                <span class="text-[12.5px] font-medium text-[#64748B]">OR</span>
                                <span class="flex-1 h-px bg-[#E5E7EB]"></span>
                            </div>
                            <a href="{{ route('auth.google') }}" class="au-google">
                                @include('auth._google-icon')
                                Continue with Google
                            </a>
                        @endif

                        <div class="mt-4 rounded-lg bg-[#F3F4F6] px-4 py-2.5 text-center text-[14px] text-[#111827]">
                            Already have an account?
                            <a href="#login" onclick="switchTab('login'); return false;" class="au-link font-semibold text-[#F97316] underline underline-offset-4 hover:text-[#EA580C] ml-1 rounded">Sign in</a>
                        </div>
                    </section>
                </div>

                <!-- Trust indicators (both are true for this app: HTTPS login, trial without payment) -->
                <ul class="mt-4 flex flex-wrap items-center justify-center gap-y-2 text-[13px] text-[#475569]">
                    <li class="inline-flex items-center gap-2 pr-5"><i class="fas fa-shield-halved text-[#16A34A] text-[15px]" aria-hidden="true"></i>Secure Login</li>
                    <li class="inline-flex items-center gap-2 pl-5 border-l border-[#D1D5DB]"><i class="far fa-credit-card text-[#16A34A] text-[15px]" aria-hidden="true"></i>No Credit Card Required</li>
                </ul>
            </div>
        </main>
    </div>

    <!-- ===== JavaScript ===== -->
    <script>
        // CSRF setup for AJAX
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // ========== Login / Signup switch (one card; #signup opens the signup form) ==========
        let activeTab = 'login';

        function switchTab(tab) {
            const loginCard = document.getElementById('loginCard');
            const signupCard = document.getElementById('signupCard');
            activeTab = tab;
            clearErrors();
            loginCard.classList.toggle('hidden', tab !== 'login');
            signupCard.classList.toggle('hidden', tab !== 'signup');
            history.replaceState(null, '', tab === 'signup' ? '#signup' : window.location.pathname + window.location.search);
            const first = (tab === 'signup' ? signupCard : loginCard).querySelector('h2');
            if (first) { first.setAttribute('tabindex', '-1'); first.focus({ preventScroll: true }); }
        }

        // ========== Password Toggle ==========
        function togglePassword(inputId, iconId, btn) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
            if (btn) {
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            }
        }

        // ========== Forgot Password Modal ==========
        function openForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.remove('hidden');
            document.getElementById('forgotEmail').value = document.getElementById('loginEmail').value || '';
            clearErrors();
            document.getElementById('forgotEmail').focus();
        }
        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.add('hidden');
            document.getElementById('forgotPasswordForm').reset();
            clearErrors();
        }

        // ========== Alerts (text only, never HTML) ==========
        function showAlert(message, type = 'success') {
            const container = document.getElementById('alertContainer');
            const id = 'alert-' + Date.now() + Math.random().toString(16).slice(2);
            const box = document.createElement('div');
            box.id = id;
            box.setAttribute('role', type === 'success' ? 'status' : 'alert');
            box.className = (type === 'success' ? 'bg-green-600' : 'bg-red-600') + ' text-white px-5 py-3.5 rounded-xl shadow-lg animate-alert flex items-center gap-3';
            const icon = document.createElement('i');
            icon.className = 'fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + ' flex-shrink-0';
            const text = document.createElement('span');
            text.className = 'text-sm font-medium flex-1';
            text.textContent = message;
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'text-white/80 hover:text-white flex-shrink-0';
            close.setAttribute('aria-label', 'Dismiss');
            close.innerHTML = '<i class="fas fa-times text-sm"></i>';
            close.onclick = () => removeAlert(id);
            box.append(icon, text, close);
            container.append(box);
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
                input.removeAttribute('aria-invalid');
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
                    inputEl.setAttribute('aria-invalid', 'true');
                }
            });
        }

        // ========== Button Loading State ==========
        function setButtonLoading(button, text, loading) {
            if (loading) {
                button.disabled = true;
                text.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mx-auto"></div>';
            } else {
                button.disabled = false;
            }
        }

        // ========== CSRF Token Refresh Helper ==========
        function refreshCsrfToken(callback) {
            $.get('{{ route("csrf.token") }}', function(data) {
                const token = data.token;
                $('meta[name="csrf-token"]').attr('content', token);
                $('input[name="_token"]').val(token);
                $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });
                if (callback) callback(token);
            });
        }

        // ========== Login Submission ==========
        let loginRetrying = false;
        $('#loginFormElement').on('submit', function(e) {
            e.preventDefault();
            const button = document.getElementById('loginButton');
            if (button.disabled) return; // no double submit
            clearErrors();
            const text = document.getElementById('loginButtonText');
            setButtonLoading(button, text, true);
            const form = this;

            $.ajax({
                url: '{{ route("auth.login") }}',
                method: 'POST',
                data: new FormData(form),
                processData: false,
                contentType: false,
                success: function(response) {
                    loginRetrying = false;
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => { window.location.href = response.redirect; }, 1000);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 419 && !loginRetrying) {
                        loginRetrying = true;
                        refreshCsrfToken(function() {
                            setButtonLoading(button, text, false);
                            $(form).submit();
                        });
                        return;
                    }
                    loginRetrying = false;
                    const response = xhr.responseJSON;
                    if (response && response.errors) showErrors(response.errors);
                    showAlert(response?.message || 'Login failed', 'error');
                },
                complete: function(xhr) {
                    if (xhr.status !== 419 || loginRetrying === false) {
                        text.innerHTML = '<i class="fas fa-arrow-right" aria-hidden="true"></i> Sign In';
                        setButtonLoading(button, text, false);
                    }
                }
            });
        });

        // ========== Signup Submission ==========
        let signupRetrying = false;
        $('#signupFormElement').on('submit', function(e) {
            e.preventDefault();
            const button = document.getElementById('signupButton');
            if (button.disabled) return;

            const password = document.getElementById('signupPassword').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (!document.getElementById('agreeTerms').checked) {
                showAlert('Please agree to the Terms of Service and Privacy Policy.', 'error');
                return;
            }
            if (password !== confirm) {
                showAlert('Passwords do not match!', 'error');
                return;
            }
            if (password.length < 8) {
                showAlert('Password must be at least 8 characters!', 'error');
                return;
            }

            clearErrors();
            const text = document.getElementById('signupButtonText');
            setButtonLoading(button, text, true);
            const form = this;

            $.ajax({
                url: '{{ route("auth.signup") }}',
                method: 'POST',
                data: new FormData(form),
                processData: false,
                contentType: false,
                success: function(response) {
                    signupRetrying = false;
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => { window.location.href = response.redirect; }, 1500);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 419 && !signupRetrying) {
                        signupRetrying = true;
                        refreshCsrfToken(function() {
                            setButtonLoading(button, text, false);
                            $(form).submit();
                        });
                        return;
                    }
                    signupRetrying = false;
                    const response = xhr.responseJSON;
                    if (response && response.errors) showErrors(response.errors);
                    showAlert(response?.message || 'Signup failed', 'error');
                },
                complete: function(xhr) {
                    if (xhr.status !== 419 || signupRetrying === false) {
                        text.innerHTML = '<i class="fas fa-rocket" aria-hidden="true"></i> Create Account';
                        setButtonLoading(button, text, false);
                    }
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
            const id = $(this).attr('id');
            const errEl = id ? document.getElementById(id + 'Error') : null;
            if (errEl && !errEl.classList.contains('hidden')) {
                errEl.classList.add('hidden');
                $(this).removeClass('border-red-500').removeAttr('aria-invalid');
            }
        });

        // ========== Flash Messages ==========
        @if(session('success'))
            showAlert(@json(session('success')), 'success');
        @endif
        @if(session('error'))
            showAlert(@json(session('error')), 'error');
        @endif

        // ========== ESC to close modal ==========
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeForgotPasswordModal();
        });

        // ========== Open the signup form from links like /auth#signup ==========
        if (window.location.hash === '#signup' || new URLSearchParams(window.location.search).has('signup')) {
            switchTab('signup');
        }

        // ========== Referral Code Tracking ==========
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            const refFromUrl = urlParams.get('ref');

            // URL mein ?ref= hai toh localStorage mein save karo (30 din ke liye)
            if (refFromUrl) {
                localStorage.setItem('ref_code', refFromUrl);
                localStorage.setItem('ref_code_ts', Date.now().toString());
            }

            // localStorage se ref code pado (30 din tak valid)
            const storedCode = localStorage.getItem('ref_code');
            const storedTs   = parseInt(localStorage.getItem('ref_code_ts') || '0');
            const thirtyDays = 30 * 24 * 60 * 60 * 1000;

            if (storedCode && (Date.now() - storedTs) < thirtyDays) {
                const field = document.getElementById('signupRefCode');
                if (field) field.value = storedCode;
            }
        })();
    </script>
</body>
</html>
