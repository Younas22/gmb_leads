@extends('emails.layout')

@section('content')
    <h2>Test Email - Email System Working! ✅</h2>

    <p>Hi there,</p>

    <p>This is a test email from {{ config('app.name') }}. If you're reading this, it means your email configuration is working correctly!</p>

    <div class="info-box">
        <p><strong>Test Details</strong></p>
        <p style="margin-top: 10px;">
            <strong>Sent At:</strong> {{ date('F d, Y H:i:s') }}<br>
            <strong>Email Service:</strong> Resend<br>
            <strong>Status:</strong> <span style="color: #22c55e;">Delivered Successfully</span>
        </p>
    </div>

    <p><strong>What This Confirms:</strong></p>
    <ul>
        <li>✅ Resend API key is valid and working</li>
        <li>✅ Email templates are rendering correctly</li>
        <li>✅ SMTP configuration is properly set up</li>
        <li>✅ Email delivery system is operational</li>
    </ul>

    <p style="text-align: center;">
        <a href="{{ url('/admin/settings') }}" class="button">Back to Settings</a>
    </p>

    <div class="divider"></div>

    <p style="font-size: 14px; color: #666;">
        This is an automated test email. You can safely ignore or delete this message.
    </p>

    <p>Best regards,<br>
    The {{ config('app.name') }} Team</p>
@endsection
