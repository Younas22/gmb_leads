<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            padding: 32px 16px;
            -webkit-font-smoothing: antialiased;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        /* ── Header ── */
        .email-header {
            padding: 32px 40px;
            text-align: center;
            border-bottom: 1px solid #f3f4f6;
        }

        .brand-text h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.3px;
        }

        .brand-text h1 span {
            color: #ea580c;
        }

        .tagline {
            margin: 4px 0 0;
            font-size: 12px;
            color: #9ca3af;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* ── Body ── */
        .email-body {
            padding: 32px 40px;
        }

        .email-body h2 {
            color: #111827;
            margin-bottom: 12px;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: -0.2px;
        }

        .email-body p {
            margin-bottom: 14px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.7;
        }

        .email-body ul {
            margin-bottom: 14px;
            padding-left: 20px;
        }

        .email-body li {
            margin-bottom: 8px;
            color: #4b5563;
            line-height: 1.6;
            font-size: 14px;
        }

        .button {
            display: inline-block;
            padding: 12px 32px;
            background-color: #ea580c;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            margin: 8px 0;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.2px;
            transition: background-color 0.2s ease;
        }

        .button:hover {
            background-color: #c2410c;
        }

        .info-box {
            background-color: #fff7ed;
            border-left: 3px solid #ea580c;
            padding: 14px 16px;
            margin: 16px 0;
            border-radius: 0 8px 8px 0;
        }

        .info-box p {
            margin: 0;
            color: #4b5563;
            font-weight: 500;
            font-size: 13px;
        }

        /* ── Footer ── */
        .email-footer {
            background-color: #f9fafb;
            padding: 28px 40px;
            text-align: center;
            border-top: 1px solid #f3f4f6;
        }

        .footer-brand {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .footer-tagline {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 20px;
        }

        .social-links {
            margin-bottom: 20px;
        }

        .social-links a {
            display: inline-block;
            margin: 0 6px;
            color: #6b7280;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .social-links a:hover {
            color: #ea580c;
        }

        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 0 auto 16px;
            width: 60px;
        }

        .footer-note {
            font-size: 11px;
            color: #9ca3af;
            line-height: 1.6;
        }

        .footer-note a {
            color: #ea580c;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 16px 8px;
            }

            .email-wrapper {
                border-radius: 8px;
            }

            .email-header {
                padding: 24px 20px;
            }

            .email-body {
                padding: 24px 20px;
            }

            .email-footer {
                padding: 24px 20px;
            }

            .brand-text h1 {
                font-size: 20px;
            }

            .button {
                padding: 10px 24px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            @php
                use App\Models\Setting;
                $siteLogo = Setting::get('site_logo');
            @endphp
            @if($siteLogo)
                <img src="{{ asset('public/' . $siteLogo) }}" alt="{{ config('app.name') }}" />
            @else
                <h1>{{ config('app.name') }}</h1>
            @endif
            <!-- <div class="brand-text">
                <h1><span>Customer</span>NearMe</h1>
                <p class="tagline">Verified GMB Leads Instantly</p>
            </div> -->
        </div>

        <!-- Body Content -->
        <div class="email-body">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p class="footer-brand">{{ config('app.name') }}</p>
            <p class="footer-tagline">Professional GMB Lead Generation</p>

            <div class="social-links">
                <a href="https://www.facebook.com/CustomerNearme">Facebook</a>
                <a href="https://x.com/CustomerNearme">Twitter</a>
                <a href="https://www.instagram.com/customernearme">Instagram</a>
                <a href="https://www.linkedin.com/company/customernearme">LinkedIn</a>
                <a href="https://www.youtube.com/@CustomerNearme">YouTube</a>
                <a href="https://chat.whatsapp.com/JoXwhqeKW5sCRGuovBNQm8">WhatsApp</a>
            </div>

            <div class="divider"></div>

            <p class="footer-note">
                This is an automated message from {{ config('app.name') }}.<br>
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                <a href="{{ url('/') }}">Visit our website</a>
            </p>
        </div>
    </div>
</body>
</html>
