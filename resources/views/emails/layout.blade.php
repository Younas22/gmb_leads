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
            background-color: #f4f4f4;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }

        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .email-header p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .email-body {
            padding: 40px 30px;
        }

        .email-body h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .email-body p {
            margin-bottom: 15px;
            color: #555;
            font-size: 16px;
        }

        .email-body ul {
            margin-bottom: 20px;
            padding-left: 20px;
        }

        .email-body li {
            margin-bottom: 10px;
            color: #555;
        }

        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
            font-size: 16px;
        }

        .button:hover {
            opacity: 0.9;
        }

        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .info-box p {
            margin: 0;
            color: #555;
        }

        .email-footer {
            background-color: #2d3748;
            padding: 30px 20px;
            text-align: center;
            color: #a0aec0;
        }

        .email-footer p {
            margin: 5px 0;
            font-size: 14px;
        }

        .email-footer a {
            color: #667eea;
            text-decoration: none;
        }

        .email-footer a:hover {
            text-decoration: underline;
        }

        .social-links {
            margin: 15px 0;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #a0aec0;
            text-decoration: none;
        }

        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 30px 0;
        }

        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 20px;
            }

            .email-header h1 {
                font-size: 24px;
            }

            .email-body h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            <h1>{{ config('app.name') }}</h1>
            <p>Google My Business Lead Generation</p>
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
                <a href="#">Facebook</a> |
                <a href="#">Twitter</a> |
                <a href="#">LinkedIn</a>
            </div>

            <div class="divider" style="background-color: #4a5568; margin: 20px auto; width: 80%;"></div>

            <p style="font-size: 12px;">
                This is an automated email from {{ config('app.name') }}.<br>
                Please do not reply to this email.
            </p>

            <p style="font-size: 12px; margin-top: 10px;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>

            <p style="font-size: 12px; margin-top: 10px;">
                <a href="{{ url('/') }}">Visit our website</a> |
                <a href="#">Privacy Policy</a> |
                <a href="#">Terms of Service</a>
            </p>
        </div>
    </div>
</body>
</html>
