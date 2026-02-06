# Quick Start Guide - Email System

## 🚀 Step-by-Step Setup (5 minutes)

### Step 1: Access Settings
1. Login as Admin
2. Go to: `http://localhost/gmb_leads/admin/settings`
3. You'll see the Settings page with Email Settings tab

### Step 2: Get Resend API Key
1. Visit: https://resend.com/api-keys
2. Create a new API key
3. Copy the key (starts with `re_`)

### Step 3: Configure Email Settings
1. Paste API Key in the "Resend API Key" field
2. Click **Verify** button to test the key
3. Add your **From Email** (e.g., noreply@yourdomain.com)
   - ⚠️ Email must be verified in Resend dashboard
4. Add your **From Name** (e.g., "GMB Leads")
5. Click **Save Email Settings**

### Step 4: Test Email
1. Scroll to "Send Test Email" section
2. Enter any email address
3. Click **Send Test**
4. Check inbox for test email

---

## 💡 How to Send Emails in Your Code

### Example 1: Send Welcome Email
```php
use App\Services\EmailService;

// In your AuthController after registration
public function signup(Request $request)
{
    // ... your registration logic ...

    // Send welcome email
    EmailService::sendWelcomeEmail($user);

    return redirect()->route('user.dashboard');
}
```

### Example 2: Send Subscription Invoice
```php
use App\Services\EmailService;

// In your payment controller
public function submitPayment(Request $request)
{
    // ... payment processing ...

    EmailService::sendSubscriptionInvoice($user, [
        'invoice_number' => 'INV-2024-001',
        'payment_date' => now()->format('F d, Y'),
        'amount' => 29.99,
        'payment_method' => 'Credit Card',
        'plan_name' => 'Premium Plan',
        'billing_period' => 'Monthly',
        'next_billing_date' => now()->addMonth()->format('F d, Y')
    ]);

    return back()->with('success', 'Payment successful!');
}
```

### Example 3: Send Subscription Start Email
```php
EmailService::sendSubscriptionStart($user, [
    'plan_name' => 'Premium Plan',
    'start_date' => now()->format('F d, Y'),
    'renewal_date' => now()->addMonth()->format('F d, Y'),
    'searches_limit' => 'Unlimited',
    'exports_limit' => '500'
]);
```

### Example 4: Send Subscription End Email
```php
EmailService::sendSubscriptionEnd($user, [
    'plan_name' => 'Premium Plan',
    'end_date' => now()->format('F d, Y')
]);
```

### Example 5: Send New Feature Announcement
```php
EmailService::sendNewFeatureEmail($user, [
    'feature_title' => 'Advanced Lead Filters',
    'feature_description' => 'Now you can filter leads by multiple criteria simultaneously',
    'feature_benefits' => [
        'Filter by average rating',
        'Filter by number of reviews',
        'Filter by business status',
        'Save filter presets'
    ]
]);
```

### Example 6: Send Maintenance Notification
```php
EmailService::sendMaintenanceNotification($user, [
    'start_time' => '2024-02-10 02:00 AM UTC',
    'end_time' => '2024-02-10 04:00 AM UTC',
    'duration' => '2 hours',
    'status' => 'Scheduled',
    'maintenance_reason' => 'Database optimization and security updates'
]);
```

### Example 7: Send Custom Email
```php
EmailService::send(
    'customer@example.com',
    'Custom Subject Line',
    'emails.your_custom_template',
    [
        'custom_data' => 'value',
        'user' => $user
    ]
);
```

### Example 8: Send Bulk Emails
```php
// Get all active users
$users = User::where('is_active', true)->get();

// Send to all
EmailService::sendBulk(
    $users,
    'Monthly Newsletter',
    'emails.newsletter',
    ['month' => 'February']
);
```

---

## 📧 Available Email Templates

| Template | Purpose | Variables |
|----------|---------|-----------|
| `welcome_mail` | New user registration | `$user` |
| `newfeature_mail` | Feature announcements | `$user`, `$feature_title`, `$feature_description`, `$feature_benefits[]` |
| `subscription_invoice_mail` | Payment receipts | `$user`, `$invoice_number`, `$amount`, `$payment_date`, `$payment_method`, `$plan_name`, `$billing_period`, `$next_billing_date` |
| `subscription_start_mail` | Subscription activation | `$user`, `$plan_name`, `$start_date`, `$renewal_date`, `$searches_limit`, `$exports_limit` |
| `subscription_end_mail` | Subscription expiry | `$user`, `$plan_name`, `$end_date` |
| `system_maintenance_mail` | Maintenance alerts | `$user`, `$start_time`, `$end_time`, `$duration`, `$status`, `$maintenance_reason` |

---

## 🎨 Customizing Email Templates

### Change Header/Footer
Edit: `resources/views/emails/layout.blade.php`

```blade
<!-- Change logo -->
<div class="email-header">
    <h1>{{ config('app.name') }}</h1>
    <p>Your Custom Tagline</p>
</div>

<!-- Change footer links -->
<div class="social-links">
    <a href="https://facebook.com/yourpage">Facebook</a> |
    <a href="https://twitter.com/yourhandle">Twitter</a>
</div>
```

### Modify Email Content
Edit individual template files: `resources/views/emails/*.blade.php`

```blade
@extends('emails.layout')

@section('content')
    <h2>Your Custom Title</h2>
    <p>{{ $user->name }}, your custom message here</p>
@endsection
```

---

## ⚙️ Advanced: Direct Settings Access

```php
use App\Models\Setting;

// Get a setting
$apiKey = Setting::get('resend_api_key');
$fromEmail = Setting::get('from_email');

// Set a setting
Setting::set('custom_key', 'value', 'text', 'general', 'Description');

// Get all settings in a group
$emailSettings = Setting::getByGroup('email');
```

---

## 🔧 Troubleshooting

### Email Not Sending?
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log
```

**Common Issues:**
- ❌ API key not verified in Resend
- ❌ From email not verified in Resend dashboard
- ❌ Invalid API key format
- ❌ Network/firewall blocking API calls

### Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Test API Key Manually
```bash
curl https://api.resend.com/emails \
  -H "Authorization: Bearer re_your_key" \
  -H "Content-Type: application/json" \
  -d '{
    "from": "noreply@yourdomain.com",
    "to": "test@example.com",
    "subject": "Test",
    "html": "<p>Test email</p>"
  }'
```

---

## 📝 Important Notes

✅ **DO:**
- Verify your domain in Resend before sending
- Use verified sender emails
- Test emails before going live
- Keep API keys secure

❌ **DON'T:**
- Share API keys publicly
- Use unverified email addresses
- Send spam or unsolicited emails
- Exceed Resend rate limits

---

## 🔗 Useful Links

- Resend Dashboard: https://resend.com/dashboard
- Resend API Docs: https://resend.com/docs
- Email Templates: `resources/views/emails/`
- Settings Controller: `app/Http/Controllers/Admin/SettingsController.php`
- Email Service: `app/Services/EmailService.php`

---

**Setup Complete! 🎉**

Your email system is ready to use. Start sending professional transactional emails!
