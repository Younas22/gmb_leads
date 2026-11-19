{{-- Provider tabs on the Email Sending module. Expects $active = 'gmail' | 'resend'. --}}
@include('user._ui-kit')
@php
    $gmailOn = \App\Services\PlanFeatures::enabled(auth()->user(), 'gmail_sending_enabled');
    $resendOn = \App\Services\PlanFeatures::enabled(auth()->user(), 'resend_connection_enabled');
@endphp
@if($gmailOn && $resendOn)
    <nav class="ui-tabs cnm-ui" aria-label="Email provider">
        <a href="{{ route('user.email-sending.index') }}" class="ui-tab {{ $active === 'gmail' ? 'is-on' : '' }}" @if($active === 'gmail') aria-current="page" @endif><i class="fab fa-google"></i> Gmail</a>
        <a href="{{ route('user.email-sending.resend.show') }}" class="ui-tab {{ $active === 'resend' ? 'is-on' : '' }}" @if($active === 'resend') aria-current="page" @endif><i class="fas fa-envelope-circle-check"></i> Resend</a>
    </nav>
@endif
