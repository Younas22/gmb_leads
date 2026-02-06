@extends('emails.layout')

@section('content')
    <h2>Your Subscription is Now Active! 🎊</h2>

    <p>Hi {{ $user->name ?? 'User' }},</p>

    <p>Great news! Your subscription to {{ config('app.name') }} has been successfully activated.</p>

    <div class="info-box">
        <p><strong>{{ $plan_name ?? 'Premium Plan' }}</strong></p>
        <p style="margin-top: 10px;">
            <strong>Start Date:</strong> {{ $start_date ?? date('F d, Y') }}<br>
            <strong>Renewal Date:</strong> {{ $renewal_date ?? date('F d, Y', strtotime('+1 month')) }}<br>
            <strong>Status:</strong> <span style="color: #22c55e;">Active</span>
        </p>
    </div>

    <p><strong>Your Plan Includes:</strong></p>
    <ul>
        <li>✅ {{ $searches_limit ?? 'Unlimited' }} searches per month</li>
        <li>✅ {{ $exports_limit ?? 'Unlimited' }} exports per month</li>
        <li>✅ Advanced filtering options</li>
        <li>✅ Priority customer support</li>
        <li>✅ Regular feature updates</li>
    </ul>

    <p style="text-align: center;">
        <a href="{{ url('/dashboard') }}" class="button">Start Using Your Plan</a>
    </p>

    <div class="divider"></div>

    <p><strong>What's Next?</strong></p>
    <p>You now have full access to all premium features. Start generating high-quality leads and grow your business!</p>

    <p>Best regards,<br>
    The {{ config('app.name') }} Team</p>
@endsection
