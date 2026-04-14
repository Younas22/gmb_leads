<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}</title>

    <meta name="description" content="Privacy Policy for {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}. Learn how we collect, use, and protect your personal data on our Google Maps lead generation platform." />
    <meta name="keywords" content="privacy policy, CustomerNearme privacy, data protection, personal data, GDPR, user privacy" />
    <meta name="robots" content="index, follow" />
    <meta name="author" content="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}" />
    <link rel="canonical" href="https://www.customernearme.com/privacy-policy" />

    <!-- Open Graph -->
    <meta property="og:title" content="Privacy Policy - {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}" />
    <meta property="og:description" content="Privacy Policy for {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}. Learn how we collect, use, and protect your personal data." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url('/privacy-policy') }}" />
    <meta property="og:site_name" content="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="Privacy Policy - {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}" />
    <meta name="twitter:description" content="Privacy Policy for {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}. Learn how we handle your data." />

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
                    }
                }
            }
        }
    </script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }

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
        .bg-blob-1 { animation: blob-drift 18s ease-in-out infinite; }
        .bg-blob-2 { animation: blob-drift-reverse 22s ease-in-out infinite; }

        @keyframes card-enter {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card-animate {
            animation: card-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .policy-content h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .policy-content h3 .num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .policy-content p {
            color: #4b5563;
            font-size: 0.9375rem;
            line-height: 1.75;
            margin-bottom: 1rem;
        }
        .policy-content ul {
            list-style: none;
            padding: 0;
            margin-bottom: 1rem;
        }
        .policy-content ul li {
            position: relative;
            padding-left: 1.75rem;
            color: #4b5563;
            font-size: 0.9375rem;
            line-height: 1.75;
            margin-bottom: 0.5rem;
        }
        .policy-content ul li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.625rem;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #1d4ed8;
        }
    </style>
</head>

<body class="min-h-screen bg-white font-inter overflow-x-hidden">

    <!-- Decorative Background -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute inset-0" style="background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(29,78,216,0.06) 0%, transparent 60%), radial-gradient(ellipse 60% 50% at 80% 80%, rgba(249,115,22,0.05) 0%, transparent 60%);"></div>
        <div class="bg-blob-1 absolute -top-32 -right-32 w-[500px] h-[500px] rounded-full" style="background: radial-gradient(circle, rgba(29,78,216,0.07) 0%, transparent 70%);"></div>
        <div class="bg-blob-2 absolute -bottom-40 -left-40 w-[600px] h-[600px] rounded-full" style="background: radial-gradient(circle, rgba(249,115,22,0.06) 0%, transparent 70%);"></div>
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h40v40H0z' fill='none' stroke='%23000' stroke-width='0.5'/%3E%3C/svg%3E&quot;);"></div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col">

        <!-- Header -->
        <header class="py-6 px-4 sm:px-8 border-b border-gray-100">
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
                <a href="{{ url('/') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Home
                </a>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 px-4 sm:px-8 py-10 sm:py-16">
            <div class="max-w-4xl mx-auto card-animate">

                <!-- Page Header -->
                <div class="text-center mb-10 sm:mb-14">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-dark-blue to-primary-blue rounded-2xl shadow-xl shadow-blue-200/50 mb-5">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight mb-3">Privacy Policy</h1>
                    <p class="text-gray-500 text-base">Effective Date: February 9, 2026</p>
                    <div class="flex items-center justify-center gap-3 mt-4">
                        <a href="{{ route('terms') }}" class="text-xs font-medium text-primary-orange hover:text-dark-orange transition-colors">Terms of Service</a>
                        <span class="text-gray-300">|</span>
                        <a href="{{ route('refund.policy') }}" class="text-xs font-medium text-primary-orange hover:text-dark-orange transition-colors">Refund Policy</a>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-200/50 overflow-hidden">
                    <div class="h-1.5 w-full bg-gradient-to-r from-dark-blue via-primary-blue to-dark-blue"></div>

                    <div class="p-6 sm:p-10 lg:p-12 policy-content">

                        <p>CustomerNearme values your privacy. This policy explains how we collect, use, and protect your information.</p>

                        <h3><span class="num">1</span> Information We Collect</h3>
                        <p>We may collect:</p>
                        <ul>
                            <li>Name, email address, and account details</li>
                            <li>Payment status (payments are handled by Paddle; we do not store card details)</li>
                            <li>Usage data for analytics and service improvement</li>
                        </ul>

                        <h3><span class="num">2</span> How We Use Your Information</h3>
                        <p>Your information is used to:</p>
                        <ul>
                            <li>Provide and improve our services</li>
                            <li>Manage accounts and subscriptions</li>
                            <li>Communicate service updates or support messages</li>
                        </ul>

                        <h3><span class="num">3</span> Payments</h3>
                        <p>All payments are securely processed by Paddle. CustomerNearme does not store or process credit/debit card information.</p>

                        <h3><span class="num">4</span> Data Security</h3>
                        <p>We implement reasonable technical and organizational measures to protect your data from unauthorized access.</p>

                        <h3><span class="num">5</span> Third-Party Services</h3>
                        <p>We may use trusted third-party services for analytics, infrastructure, and payments. These providers only access data necessary to perform their services.</p>

                        <h3><span class="num">6</span> Cookies</h3>
                        <p>We may use cookies to improve user experience and analyze traffic.</p>

                        <h3><span class="num">7</span> Your Rights</h3>
                        <p>You may request access, correction, or deletion of your personal data by contacting us.</p>

                        <h3><span class="num">8</span> Updates</h3>
                        <p>This Privacy Policy may be updated periodically. Continued use of the service means you accept the changes.</p>

                        <h3><span class="num">9</span> Contact</h3>
                        <p>For questions regarding this Privacy Policy, contact us at:</p>
                        <div class="flex items-center gap-3 p-4 bg-blue-50 border border-blue-100 rounded-xl">
                            <i class="fas fa-envelope text-dark-blue"></i>
                            <a href="mailto:info@customernearme.com" class="text-sm font-semibold text-dark-blue hover:text-primary-blue transition-colors">info@customernearme.com</a>
                        </div>

                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-6 px-4 sm:px-8 border-t border-gray-100">
            <div class="max-w-4xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-gray-400">&copy; {{ date('Y') }} CustomerNearme. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('terms') }}" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Terms of Service</a>
                    <span class="text-gray-200">|</span>
                    <a href="{{ route('refund.policy') }}" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Refund Policy</a>
                    <span class="text-gray-200">|</span>
                    <a href="https://wa.me/923460820722" target="_blank" rel="noopener noreferrer" class="text-xs text-green-600 hover:text-green-700 transition-colors flex items-center gap-1">
                        <i class="fab fa-whatsapp"></i>
                        Support
                    </a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
