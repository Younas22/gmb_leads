@extends('emails.layout')

@section('content')
    <h2>Scheduled System Maintenance 🔧</h2>

    <p>Hi {{ $user->name ?? 'User' }},</p>

    <p>This is to inform you that we have scheduled maintenance for {{ config('app.name') }} to improve our services and performance.</p>

    <div class="info-box" style="border-left-color: #f59e0b; background-color: #fffbeb;">
        <p><strong>⚠️ Maintenance Schedule</strong></p>
        <p style="margin-top: 10px;">
            <strong>Start Time:</strong> {{ $start_time ?? 'TBD' }}<br>
            <strong>End Time:</strong> {{ $end_time ?? 'TBD' }}<br>
            <strong>Expected Duration:</strong> {{ $duration ?? '2-3 hours' }}<br>
            <strong>Status:</strong> {{ $status ?? 'Scheduled' }}
        </p>
    </div>

    <p><strong>What to Expect:</strong></p>
    <ul>
        <li>🚫 The platform will be temporarily unavailable during maintenance</li>
        <li>💾 All your data will remain safe and secure</li>
        <li>✨ Improved performance and new features after completion</li>
        <li>📧 You'll receive a notification when maintenance is complete</li>
    </ul>

    @if(isset($maintenance_reason))
    <p><strong>Reason for Maintenance:</strong></p>
    <p>{{ $maintenance_reason }}</p>
    @endif

    <div class="divider"></div>

    <p><strong>Need immediate assistance?</strong></p>
    <p>If you have any urgent concerns, please contact our support team before the maintenance window begins.</p>

    <p>Thank you for your patience and understanding.<br>
    The {{ config('app.name') }} Team</p>
@endsection
