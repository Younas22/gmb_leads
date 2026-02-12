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
            color: #333;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 20px 0;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: #ffffff;
            padding: 50px 30px;
            text-align: center;
            position: relative;
            border-bottom: 3px solid rgb(234, 88, 12);
        }

        .logo-container {
            display: inline-block;
            padding: 10px;
        }

        .email-header img {
            max-width: 280px;
            height: auto;
            margin: 0 auto;
            display: block;
        }

        .email-header h1 {
            margin: 15px 0 0 0;
            font-size: 28px;
            font-weight: 700;
            color: rgb(234, 88, 12);
        }

        .email-header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #4a5568;
        }

        .email-body {
            padding: 45px 35px;
            background: linear-gradient(135deg, #fff5f1 0%, #ffedd5 50%, #fff5f1 100%);
            position: relative;
        }

        .email-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><circle cx="30" cy="30" r="1.5" fill="rgba(234, 88, 12, 0.05)"/></svg>');
            opacity: 0.6;
            pointer-events: none;
        }

        .email-body h2 {
            color: rgb(234, 88, 12);
            margin-bottom: 20px;
            font-size: 26px;
            font-weight: 700;
            border-bottom: 3px solid rgb(234, 88, 12);
            padding-bottom: 10px;
            display: inline-block;
            position: relative;
            z-index: 1;
        }

        .email-body p {
            margin-bottom: 15px;
            color: #4a5568;
            font-size: 16px;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .email-body ul {
            margin-bottom: 20px;
            padding-left: 20px;
            position: relative;
            z-index: 1;
        }

        .email-body li {
            margin-bottom: 12px;
            color: #4a5568;
            line-height: 1.7;
        }

        .button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, rgb(234, 88, 12) 0%, #c2410c 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 10px;
            margin: 25px 0;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 6px 20px rgba(234, 88, 12, 0.4);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(234, 88, 12, 0.5);
        }

        .info-box {
            background: #ffffff;
            border-left: 5px solid rgb(234, 88, 12);
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(234, 88, 12, 0.15);
            position: relative;
            z-index: 1;
        }

        .info-box p {
            margin: 0;
            color: #4a5568;
            font-weight: 500;
        }

        .email-footer {
            background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
            padding: 35px 25px;
            text-align: center;
            color: #cbd5e1;
        }

        .email-footer p {
            margin: 8px 0;
            font-size: 14px;
        }

        .email-footer strong {
            color: #ffffff;
            font-size: 18px;
            letter-spacing: 0.5px;
        }

        .email-footer a {
            color: rgb(234, 88, 12);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .email-footer a:hover {
            color: #fb923c;
            text-decoration: underline;
        }

        .social-links {
            margin: 20px 0;
            padding: 15px 0;
        }

        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: rgb(234, 88, 12);
        }

        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #64748b 50%, transparent 100%);
            margin: 25px auto;
            width: 60%;
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 10px 0;
            }

            .email-wrapper {
                border-radius: 8px;
            }

            .email-body {
                padding: 30px 20px;
            }

            .email-header {
                padding: 35px 20px;
            }

            .logo-container {
                padding: 15px 20px;
            }

            .email-header h1 {
                font-size: 24px;
            }

            .email-body h2 {
                font-size: 22px;
            }

            .button {
                padding: 14px 30px;
                font-size: 15px;
            }

            .social-links a {
                margin: 0 5px;
                font-size: 12px;
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
                <div class="logo-container">
                    <img src="{{ asset('public/' . $siteLogo) }}" alt="{{ config('app.name') }}" />
                </div>
            @else
                <h1>{{ config('app.name') }}</h1>
            @endif
        </div>

        <!-- Body Content -->
        <div class="email-body">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Professional GMB Lead Generation Platform</p>

            <div class="social-links">
                <a href="https://www.facebook.com/CustomerNearme">Facebook</a> |
                <a href="https://x.com/CustomerNearme">Twitter</a> |
                <a href="https://www.instagram.com/customernearme">Instagram</a>
                <a href="https://www.linkedin.com/company/customernearme">LinkedIn</a>
                <a href="https://www.youtube.com/@CustomerNearme">YouTube</a>
                <a href="https://chat.whatsapp.com/JoXwhqeKW5sCRGuovBNQm8">WhatsApp Group</a>
            </div>

            <div class="divider"></div>

            <p style="font-size: 12px;">
                This is an automated email from {{ config('app.name') }}.<br>
                Please do not reply to this email.
            </p>

            <p style="font-size: 12px; margin-top: 10px;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>

            <p style="font-size: 12px; margin-top: 10px;">
                <a href="{{ url('/') }}">Visit our website</a>
                <!-- <a href="#">Privacy Policy</a> |
                <a href="#">Terms of Service</a> -->
            </p>
        </div>
    </div>
</body>
</html>
