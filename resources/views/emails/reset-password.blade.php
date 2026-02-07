<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 40px 30px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                            @php
                                use App\Models\Setting;
                                $siteLogo = Setting::get('site_logo');
                            @endphp
                            @if($siteLogo)
                                <img src="{{ asset('public/' . $siteLogo) }}"
                                     alt="{{ config('app.name') }}"
                                     style="width: 280px; height: auto;">
                            @else
                                <h1 style="margin: 0; color: #111827; font-size: 28px; font-weight: bold;">
                                    {{ config('app.name') }}
                                </h1>
                            @endif
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #1f2937; font-size: 24px;">
                                Hello, {{ $user->first_name }}!
                            </h2>
                            
                            <p style="margin: 0 0 20px 0; color: #4b5563; font-size: 16px; line-height: 1.6;">
                                We received a request to reset your password for your Customer NearMe account. If you made this request, click the button below to set a new password.
                            </p>
                            
                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{{ $resetUrl }}" 
                                           style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 6px rgba(220, 38, 38, 0.3);">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 30px 0; border-radius: 4px;">
                                <p style="margin: 0; color: #92400e; font-size: 14px; line-height: 1.6;">
                                    <strong>⚠️ Important Security Notice:</strong><br>
                                    This password reset link will expire in 24 hours. If you didn't request this reset, please ignore this email and your password will remain unchanged.
                                </p>
                            </div>
                            
                            <p style="margin: 30px 0 20px 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
                                If the button doesn't work, copy and paste this link into your browser:
                            </p>
                            
                            <p style="margin: 0 0 30px 0; padding: 15px; background-color: #f3f4f6; border-radius: 6px; word-break: break-all;">
                                <a href="{{ $resetUrl }}" style="color: #dc2626; text-decoration: none; font-size: 13px;">
                                    {{ $resetUrl }}
                                </a>
                            </p>
                            
                            <div style="border-top: 2px solid #e5e7eb; padding-top: 20px; margin-top: 30px;">
                                <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
                                    <strong>Tips for a strong password:</strong>
                                </p>
                                <ul style="color: #6b7280; font-size: 14px; line-height: 1.8; padding-left: 20px;">
                                    <li>Use at least 8 characters</li>
                                    <li>Include uppercase and lowercase letters</li>
                                    <li>Add numbers and special characters</li>
                                    <li>Avoid common words or personal information</li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 13px;">
                                Need help? Contact our support team at info@customernearme.com
                            </p>
                            <p style="margin: 0 0 15px 0; color: #9ca3af; font-size: 12px;">
                                For security reasons, this link will expire in 24 hours.
                            </p>
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                                © {{ date('Y') }} Customer NearMe. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>