# Email System Setup Guide

## Overview
Complete email management system with Resend integration for sending transactional emails.

## Features
- ✅ Resend API integration
- ✅ Database-driven email settings (no manual .env editing)
- ✅ API key verification
- ✅ Test email functionality
- ✅ Pre-built email templates with static header/footer
- ✅ Easy-to-use EmailService helper

## Setup Instructions

### 1. Configure Email Settings

1. Login as admin
2. Navigate to: `http://localhost/gmb_leads/admin/dashboard`
3. Click **Settings** in the sidebar
4. Go to **Email Settings** tab

### 2. Add Resend API Key

1. Get your API key from [Resend Dashboard](https://resend.com/api-keys)
2. Enter the API key in the settings form
3. Click **Verify** to validate the key
4. Add your **From Email** (must be verified in Resend)
5. Add your **From Name** (e.g., "GMB Leads")
6. Click **Save Email Settings**

### 3. Test Email Configuration

After saving settings:
1. Scroll to **Send Test Email** section
2. Enter a test email address
3. Click **Send Test**
4. Check your inbox for the test email

## Email Templates

All templates are located in: `resources/views/emails/`

### Available Templates:

1. **welcome_mail.blade.php**
   - Sent when user registers/logs in
   - Variables: `$user`

2. **newfeature_mail.blade.php**
   - Sent for new feature announcements
   - Variables: `$user`, `$feature_title`, `$feature_description`, `$feature_benefits[]`

3. **subscription_invoice_mail.blade.php**
   - Sent after payment
   - Variables: `$user`, `$invoice_number`, `$payment_date`, `$amount`, `$payment_method`, `$plan_name`, `$billing_period`, `$next_billing_date`

4. **subscription_start_mail.blade.php**
   - Sent when subscription starts
   - Variables: `$user`, `$plan_name`, `$start_date`, `$renewal_date`, `$searches_limit`, `$exports_limit`

5. **subscription_end_mail.blade.php**
   - Sent when subscription expires
   - Variables: `$user`, `$plan_name`, `$end_date`

6. **system_maintenance_mail.blade.php**
   - Sent for maintenance notifications
   - Variables: `$user`, `$start_time`, `$end_time`, `$duration`, `$status`, `$maintenance_reason`

7. **test_mail.blade.php**
   - Used for testing email configuration

## Using EmailService

### Quick Examples:

```php
use App\Services\EmailService;

// Send welcome email
EmailService::sendWelcomeEmail($user);

// Send new feature email
EmailService::sendNewFeatureEmail($user, [
    'feature_title' => 'Advanced Filters',
    'feature_description' => 'Filter leads by multiple criteria',
    'feature_benefits' => [
        'Filter by rating',
        'Filter by reviews count',
        'Filter by open status'
    ]
]);

// Send subscription invoice
EmailService::sendSubscriptionInvoice($user, [
    'invoice_number' => 'INV-2024-001',
    'payment_date' => now()->format('F d, Y'),
    'amount' => 29.99,
    'payment_method' => 'Credit Card',
    'plan_name' => 'Premium Plan',
    'billing_period' => 'Monthly',
    'next_billing_date' => now()->addMonth()->format('F d, Y')
]);

// Send subscription start email
EmailService::sendSubscriptionStart($user, [
    'plan_name' => 'Premium Plan',
    'start_date' => now()->format('F d, Y'),
    'renewal_date' => now()->addMonth()->format('F d, Y'),
    'searches_limit' => 'Unlimited',
    'exports_limit' => '500'
]);

// Send subscription end email
EmailService::sendSubscriptionEnd($user, [
    'plan_name' => 'Premium Plan',
    'end_date' => now()->format('F d, Y')
]);

// Send maintenance notification
EmailService::sendMaintenanceNotification($user, [
    'start_time' => '2024-02-10 02:00 AM UTC',
    'end_time' => '2024-02-10 04:00 AM UTC',
    'duration' => '2 hours',
    'status' => 'Scheduled',
    'maintenance_reason' => 'Database optimization and security updates'
]);

// Send custom email
EmailService::send(
    $user->email,
    'Custom Subject',
    'emails.your_template',
    ['custom_data' => 'value']
);

// Send bulk emails
$users = User::where('is_active', true)->get();
EmailService::sendBulk($users, 'Subject', 'emails.template', ['data' => 'value']);
```

## Template Customization

### Header & Footer
The header and footer are in: `resources/views/emails/layout.blade.php`

To customize:
1. Edit the logo/branding in the header
2. Update social links in the footer
3. Modify colors using inline CSS

### Email Content
Each template extends the layout:

```blade
@extends('emails.layout')

@section('content')
    <h2>Your Title</h2>
    <p>Your content here...</p>
@endsection
```

## Database Settings Model

Access settings programmatically:

```php
use App\Models\Setting;

// Get a setting
$apiKey = Setting::get('resend_api_key');

// Set a setting
Setting::set('resend_api_key', 're_xxxxx', 'password', 'email', 'Resend API Key');

// Get all email settings
$emailSettings = Setting::getByGroup('email');
```

## Troubleshooting

### Email Not Sending?
1. Check API key is valid (use Verify button)
2. Ensure "From Email" is verified in Resend dashboard
3. Check Laravel logs: `storage/logs/laravel.log`

### API Key Verification Failing?
1. Make sure API key starts with `re_`
2. Verify key hasn't been revoked in Resend dashboard
3. Check internet connection

### Template Not Found?
1. Clear view cache: `php artisan view:clear`
2. Verify template exists in `resources/views/emails/`

## Integration Examples

### Send Welcome Email on Registration

```php
// In AuthController after user registration
use App\Services\EmailService;

public function signup(Request $request)
{
    // ... registration logic ...

    // Send welcome email
    EmailService::sendWelcomeEmail($user);

    return redirect()->route('user.dashboard');
}
```

### Send Invoice After Payment

```php
// In SubscriptionController after payment
use App\Services\EmailService;

public function submitPayment(Request $request)
{
    // ... payment logic ...

    // Send invoice email
    EmailService::sendSubscriptionInvoice($user, [
        'invoice_number' => $payment->invoice_number,
        'payment_date' => $payment->created_at->format('F d, Y'),
        'amount' => $payment->amount,
        'payment_method' => 'Credit Card',
        'plan_name' => $subscription->package->name,
        'billing_period' => 'Monthly',
        'next_billing_date' => $subscription->ends_at->format('F d, Y')
    ]);

    return back()->with('success', 'Payment successful!');
}
```

## Security Notes

- API keys are stored encrypted in database
- .env file is automatically updated for Laravel mail config
- Never expose API keys in client-side code
- Use password input type for API key fields

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Verify Resend API status: https://resend.com/status
- Review Resend documentation: https://resend.com/docs

---

**Created:** 2024-02-06
**Version:** 1.0
**Framework:** Laravel 12
**Email Provider:** Resend
