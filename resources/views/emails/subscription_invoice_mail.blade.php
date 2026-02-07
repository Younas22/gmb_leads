@extends('emails.layout')

@section('content')
    <h2>Payment Receipt 🧾</h2>

    <p>Hi {{ $user->name ?? 'User' }},</p>

    <p>Thank you for your payment! This email confirms that we have received your subscription payment.</p>

    <div class="info-box">
        <p><strong>Invoice Details</strong></p>
        <p style="margin-top: 10px;">
            <strong>Invoice #:</strong> {{ $invoice_number ?? 'INV-' . date('Ymd') . '-' . rand(1000, 9999) }}<br>
            <strong>Date:</strong> {{ $payment_date ?? date('F d, Y') }}<br>
            <strong>Amount:</strong> ${{ number_format($amount ?? 0, 2) }}<br>
            <strong>Payment Method:</strong> {{ $payment_method ?? 'Credit Card' }}<br>
            <strong>Status:</strong> <span style="color: #22c55e;">Paid</span>
        </p>
    </div>

    <p><strong>Subscription Details:</strong></p>
    <ul>
        <li><strong>Plan:</strong> {{ $plan_name ?? 'Premium Plan' }}</li>
        <li><strong>Billing Period:</strong> {{ $billing_period ?? 'Monthly' }}</li>
        <li><strong>Next Billing Date:</strong> {{ $next_billing_date ?? date('F d, Y', strtotime('+1 month')) }}</li>
    </ul>

    <p style="text-align: center;">
        <a href="{{ url('/user/subscription') }}" class="button">View Subscription</a>
    </p>

    <div class="divider"></div>

    <p style="font-size: 14px; color: #666;">
        If you have any questions about this invoice, please contact our billing support team.
    </p>

    <p>Best regards,<br>
    The {{ config('app.name') }} Team</p>
@endsection
