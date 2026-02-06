@extends('emails.layout')

@section('content')
    <h2>Welcome to {{ config('app.name') }}! 🎉</h2>

    <p>Hi {{ $user->name ?? 'User' }},</p>

    <p>We're thrilled to have you on board! Thank you for joining {{ config('app.name') }}, your trusted platform for generating high-quality Google My Business leads.</p>

    <div class="info-box">
        <p><strong>Your account is now active and ready to use!</strong></p>
    </div>

    <p>Here's what you can do next:</p>

    <ul>
        <li>🔍 <strong>Start Searching:</strong> Find targeted GMB leads in your area</li>
        <li>💾 <strong>Save Leads:</strong> Build your prospect database</li>
        <li>📊 <strong>Export Data:</strong> Download leads in various formats</li>
        <li>🎯 <strong>Advanced Filters:</strong> Refine your search criteria</li>
    </ul>

    <p style="text-align: center;">
        <a href="{{ url('/dashboard') }}" class="button">Go to Dashboard</a>
    </p>

    <div class="divider"></div>

    <p><strong>Need help getting started?</strong></p>
    <p>Check out our quick start guide or contact our support team. We're here to help you succeed!</p>

    <p>Best regards,<br>
    The {{ config('app.name') }} Team</p>
@endsection
