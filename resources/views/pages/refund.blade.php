<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Policy - {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}</title>

    <meta name="description" content="Read the refund policy for {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}. Learn about our refund process, eligibility, and how to request a refund for your subscription." />
    <meta name="keywords" content="refund policy, CustomerNearme refund, subscription refund, money back policy, cancellation policy" />
    <meta name="robots" content="index, follow" />
    <meta name="author" content="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}" />
    <link rel="canonical" href="{{ url('/refund-policy') }}" />

    <!-- Open Graph -->
    <meta property="og:title" content="Refund Policy - {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}" />
    <meta property="og:description" content="Read the refund policy for {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}. Learn about our refund process, eligibility, and conditions." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url('/refund-policy') }}" />
    <meta property="og:site_name" content="{{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="Refund Policy - {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}" />
    <meta name="twitter:description" content="Read the refund policy for {{ \App\Models\Setting::get('site_name', 'CustomerNearme') }}. Learn about our refund process and eligibility." />

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
            background: linear-gradient(135deg, #f97316, #1d4ed8);
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
            background: linear-gradient(135deg, #f97316, #1d4ed8);
        }
    </style>
</head>

<body class="min-h-screen bg-white font-inter overflow-x-hidden">

    <!-- Decorative Background -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute inset-0" style="background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(249,115,22,0.06) 0%, transparent 60%), radial-gradient(ellipse 60% 50% at 20% 80%, rgba(29,78,216,0.05) 0%, transparent 60%);"></div>
        <div class="bg-blob-1 absolute -top-32 -right-32 w-[500px] h-[500px] rounded-full" style="background: radial-gradient(circle, rgba(249,115,22,0.07) 0%, transparent 70%);"></div>
        <div class="bg-blob-2 absolute -bottom-40 -left-40 w-[600px] h-[600px] rounded-full" style="background: radial-gradient(circle, rgba(29,78,216,0.06) 0%, transparent 70%);"></div>
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

        <main class="flex-1 px-4 sm:px-8 py-10 sm:py-16">
    <div class="max-w-4xl mx-auto card-animate">

        <!-- Page Header -->
        <div class="text-center mb-10 sm:mb-14">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl shadow-xl mb-5" style="background: linear-gradient(135deg, #f97316, #1d4ed8); box-shadow: 0 10px 25px -5px rgba(249,115,22,0.3);">
                <i class="fas fa-undo-alt text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight mb-3">Refund Policy</h1>
            <p class="text-gray-500 text-base">Effective Date: February 9, 2026</p>
            <div class="flex items-center justify-center gap-3 mt-4">
                <a href="{{ route('terms') }}" class="text-xs font-medium text-primary-orange hover:text-dark-orange transition-colors">Terms of Service</a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('privacy.policy') }}" class="text-xs font-medium text-primary-orange hover:text-dark-orange transition-colors">Privacy Policy</a>
            </div>
        </div>

        <!-- Content Card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-200/50 overflow-hidden">
            <div class="h-1.5 w-full" style="background: linear-gradient(90deg, #f97316, #1d4ed8, #f97316);"></div>

            <div class="p-6 sm:p-10 lg:p-12 policy-content">

                <p>CustomerNearme offers digital SaaS services. Please read our refund policy carefully before making a purchase.</p>

                <h3><span class="num">1</span> Free Trial</h3>
                <p>We offer a free trial on selected plans so users can evaluate the service before purchasing.</p>

                <h3><span class="num">2</span> Monthly Subscription Plans</h3>
                <p>All subscription payments are eligible for a <b>full refund within 14 days of purchase</b>, no questions asked. After 14 days, all sales are final.</p>

                <h3><span class="num">3</span> Payment Processing</h3>
                <p>All payments and refunds are handled by Paddle, our Merchant of Record. Approved refunds will be processed back to the original payment method.</p>

                <h3><span class="num">4</span> Contact</h3>
                <p>For refund-related questions, contact us at:</p>
                <div class="flex items-center gap-3 p-4 rounded-xl" style="background: linear-gradient(135deg, rgba(249,115,22,0.08), rgba(29,78,216,0.08)); border: 1px solid rgba(249,115,22,0.15);">
                    <i class="fas fa-envelope text-primary-orange"></i>
                    <a href="mailto:info@customernearme.com" class="text-sm font-semibold text-primary-orange hover:text-dark-orange transition-colors">info@customernearme.com</a>
                </div>

                <!-- Important Notice -->
                <div class="mt-8 p-5 bg-amber-50 border border-amber-100 rounded-xl">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-lg bg-amber-500 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-amber-900 mb-1">Important Notice</h4>
                            <p class="text-sm text-amber-800 mb-0">We strongly recommend using the free trial to evaluate the service before purchasing a plan. Refunds are available <b>unconditionally within 14 days</b> of purchase. After this period, payments are final.</p>
                        </div>
                    </div>
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
</body>
</html>
