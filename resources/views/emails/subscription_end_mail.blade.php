@extends('emails.layout')

@section('content')
    <h2>Your Subscription Has Ended</h2>

    <p>Hi {{ $user->name ?? 'User' }},</p>

    <p>We wanted to let you know that your {{ $plan_name ?? 'Premium' }} subscription has expired.</p>

    <div class="info-box">
        <p><strong>Subscription Details</strong></p>
        <p style="margin-top: 10px;">
            <strong>Plan:</strong> {{ $plan_name ?? 'Premium Plan' }}<br>
            <strong>End Date:</strong> {{ $end_date ?? date('F d, Y') }}<br>
            <strong>Status:</strong> <span style="color: #ef4444;">Expired</span>
        </p>
    </div>

    <p><strong>What This Means:</strong></p>
    <ul>
        <li>⚠️ Access to premium features has been restricted</li>
        <li>⚠️ Your saved leads are still safe and accessible</li>
        <li>⚠️ Search and export limits have been applied</li>
        <li>✅ You can renew anytime to restore full access</li>
    </ul>

    <p>We'd love to have you back! Renew your subscription now to continue enjoying all premium features.</p>

    <p style="text-align: center;">
        <a href="{{ url('/subscription') }}" class="button">Renew Subscription</a>
    </p>

    <div class="divider"></div>

    <p><strong>Need help?</strong></p>
    <p>If you have any questions about renewing your subscription or need assistance, our support team is here to help.</p>

    <p>We hope to see you back soon!<br>
    The {{ config('app.name') }} Team</p>
@endsection
