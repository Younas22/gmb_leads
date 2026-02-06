@extends('emails.layout')

@section('content')
    <h2>Exciting New Feature Released! 🚀</h2>

    <p>Hi {{ $user->name ?? 'User' }},</p>

    <p>We're excited to announce a new feature that will help you generate leads even more effectively!</p>

    <div class="info-box">
        <p><strong>{{ $feature_title ?? 'New Feature Available' }}</strong></p>
        <p style="margin-top: 10px;">{{ $feature_description ?? 'Check out what\'s new in your dashboard.' }}</p>
    </div>

    @if(isset($feature_benefits) && is_array($feature_benefits))
    <p><strong>What's New:</strong></p>
    <ul>
        @foreach($feature_benefits as $benefit)
        <li>{{ $benefit }}</li>
        @endforeach
    </ul>
    @endif

    <p style="text-align: center;">
        <a href="{{ url('/dashboard') }}" class="button">Try It Now</a>
    </p>

    <div class="divider"></div>

    <p>We're constantly working to improve your experience. Stay tuned for more updates!</p>

    <p>Best regards,<br>
    The {{ config('app.name') }} Team</p>
@endsection
