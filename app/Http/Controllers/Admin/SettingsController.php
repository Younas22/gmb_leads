<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Resend;

class SettingsController extends Controller
{
    /**
     * Display settings page
     */
    public function index()
    {
        $settings = Setting::where('group', 'email')->get()->pluck('value', 'key');

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update general settings
     */
    public function updateGeneralSettings(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string',
            'contact_email' => 'required|email',
            'contact_phone' => 'nullable|string|max:50',
            'support_email' => 'nullable|email',
            'site_logo' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            'site_favicon' => 'nullable|mimes:ico,png|max:512',
            'default_country' => 'nullable|string|max:2',
            'default_currency' => 'nullable|string|max:3',
            'currency_position' => 'nullable|in:before,after',
            'timezone' => 'nullable|string',
            'date_format' => 'nullable|string',
            'time_format' => 'nullable|in:12,24',
            'max_search_results' => 'nullable|integer|min:10|max:200',
            'default_search_radius' => 'nullable|integer|min:1|max:100',
            'max_saved_leads' => 'nullable|integer|min:10|max:10000',
        ]);

        // Handle file uploads
        if ($request->hasFile('site_logo')) {
            $logo = $request->file('site_logo');
            $logoName = 'logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('images/logo'), $logoName);
            Setting::set('site_logo', 'images/logo/' . $logoName, 'text', 'general', 'Site Logo Path');
        }

        if ($request->hasFile('site_favicon')) {
            $favicon = $request->file('site_favicon');
            $faviconName = 'favicon_' . time() . '.' . $favicon->getClientOriginalExtension();
            $favicon->move(public_path('images/logo'), $faviconName);
            Setting::set('site_favicon', 'images/logo/' . $faviconName, 'text', 'general', 'Favicon Path');
        }

        // Application Settings
        Setting::set('site_name', $request->site_name, 'text', 'general', 'Site Name');
        Setting::set('site_description', $request->site_description, 'text', 'general', 'Site Description');
        Setting::set('contact_email', $request->contact_email, 'email', 'general', 'Contact Email');
        Setting::set('contact_phone', $request->contact_phone, 'text', 'general', 'Contact Phone');
        Setting::set('support_email', $request->support_email, 'email', 'general', 'Support Email');

        // Business Settings
        Setting::set('default_country', $request->default_country, 'text', 'general', 'Default Country');
        Setting::set('default_currency', $request->default_currency, 'text', 'general', 'Default Currency');
        Setting::set('currency_position', $request->currency_position, 'text', 'general', 'Currency Symbol Position');
        Setting::set('timezone', $request->timezone, 'text', 'general', 'Timezone');
        Setting::set('date_format', $request->date_format, 'text', 'general', 'Date Format');
        Setting::set('time_format', $request->time_format, 'text', 'general', 'Time Format');

        // Search Settings
        Setting::set('max_search_results', $request->max_search_results ?? 50, 'integer', 'general', 'Max Search Results');
        Setting::set('default_search_radius', $request->default_search_radius ?? 10, 'integer', 'general', 'Default Search Radius');
        Setting::set('max_saved_leads', $request->max_saved_leads ?? 1000, 'integer', 'general', 'Max Saved Leads');
        Setting::set('enable_search_history', $request->has('enable_search_history') ? 1 : 0, 'boolean', 'general', 'Enable Search History');
        Setting::set('autosave_results', $request->has('autosave_results') ? 1 : 0, 'boolean', 'general', 'Auto-save Results');

        // Notification Settings
        Setting::set('enable_email_notifications', $request->has('enable_email_notifications') ? 1 : 0, 'boolean', 'general', 'Enable Email Notifications');
        Setting::set('enable_sms_notifications', $request->has('enable_sms_notifications') ? 1 : 0, 'boolean', 'general', 'Enable SMS Notifications');
        Setting::set('notify_new_registration', $request->has('notify_new_registration') ? 1 : 0, 'boolean', 'general', 'Notify New Registration');
        Setting::set('notify_new_subscription', $request->has('notify_new_subscription') ? 1 : 0, 'boolean', 'general', 'Notify New Subscription');
        Setting::set('notify_payment_received', $request->has('notify_payment_received') ? 1 : 0, 'boolean', 'general', 'Notify Payment Received');

        // Update APP_NAME in .env file
        $this->updateEnvFile([
            'APP_NAME' => $request->site_name,
        ]);

        return back()->with('success', 'General settings updated successfully!');
    }

    /**
     * Update API settings
     */
    public function updateApiSettings(Request $request)
    {
        $request->validate([
            'google_maps_api_key' => 'nullable|string',
            'google_places_api_key' => 'nullable|string',
            'api_rate_limit' => 'nullable|integer|min:1|max:1000',
        ]);

        Setting::set('google_maps_api_key', $request->google_maps_api_key, 'password', 'general', 'Google Maps API Key');
        Setting::set('google_places_api_key', $request->google_places_api_key, 'password', 'general', 'Google Places API Key');
        Setting::set('api_rate_limit', $request->api_rate_limit ?? 60, 'integer', 'general', 'API Rate Limit');
        Setting::set('enable_api_logging', $request->has('enable_api_logging') ? 1 : 0, 'boolean', 'general', 'Enable API Logging');

        return back()->with('success', 'API settings updated successfully!');
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(Request $request)
    {
        $request->validate([
            'session_timeout' => 'nullable|integer|min:5|max:1440',
            'cache_duration' => 'nullable|integer|min:1|max:1440',
        ]);

        Setting::set('maintenance_mode', $request->has('maintenance_mode') ? 1 : 0, 'boolean', 'general', 'Maintenance Mode');
        Setting::set('allow_registration', $request->has('allow_registration') ? 1 : 0, 'boolean', 'general', 'Allow Registration');
        Setting::set('email_verification', $request->has('email_verification') ? 1 : 0, 'boolean', 'general', 'Email Verification Required');
        Setting::set('session_timeout', $request->session_timeout ?? 120, 'integer', 'general', 'Session Timeout');
        Setting::set('cache_duration', $request->cache_duration ?? 60, 'integer', 'general', 'Cache Duration');

        return back()->with('success', 'System settings updated successfully!');
    }

    /**
     * Clear application cache
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize database tables
     */
    public function optimizeDatabase()
    {
        try {
            Artisan::call('optimize');

            // Run database optimization
            DB::statement('OPTIMIZE TABLE users, subscriptions, packages, saved_leads, settings');

            return response()->json([
                'success' => true,
                'message' => 'Database optimized successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize database: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update email settings
     */
    public function updateEmailSettings(Request $request)
    {
        $request->validate([
            'resend_api_key' => 'required|string',
            'from_email' => 'required|email',
            'from_name' => 'required|string',
        ]);

        // Save settings
        Setting::set('resend_api_key', $request->resend_api_key, 'password', 'email', 'Resend API Key');
        Setting::set('from_email', $request->from_email, 'email', 'email', 'Default From Email');
        Setting::set('from_name', $request->from_name, 'text', 'email', 'Default From Name');

        // Save email template toggles
        Setting::set('enable_welcome_email', $request->has('enable_welcome_email') ? 1 : 0, 'boolean', 'email', 'Enable Welcome Email');
        Setting::set('enable_new_feature_email', $request->has('enable_new_feature_email') ? 1 : 0, 'boolean', 'email', 'Enable New Feature Email');
        Setting::set('enable_subscription_invoice_email', $request->has('enable_subscription_invoice_email') ? 1 : 0, 'boolean', 'email', 'Enable Subscription Invoice Email');
        Setting::set('enable_subscription_start_email', $request->has('enable_subscription_start_email') ? 1 : 0, 'boolean', 'email', 'Enable Subscription Start Email');
        Setting::set('enable_subscription_end_email', $request->has('enable_subscription_end_email') ? 1 : 0, 'boolean', 'email', 'Enable Subscription End Email');
        Setting::set('enable_system_maintenance_email', $request->has('enable_system_maintenance_email') ? 1 : 0, 'boolean', 'email', 'Enable System Maintenance Email');

        // Update .env file dynamically (optional - for Laravel mail config)
        $this->updateEnvFile([
            'RESEND_API_KEY' => $request->resend_api_key,
            'MAIL_FROM_ADDRESS' => $request->from_email,
            'MAIL_FROM_NAME' => $request->from_name,
        ]);

        return back()->with('success', 'Email settings updated successfully!');
    }

    /**
     * Verify Resend API Key
     */
    public function verifyResendKey(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
        ]);

        try {
            $resend = Resend::client($request->api_key);

            // Try to get API key info (this will fail if key is invalid)
            $response = $resend->apiKeys->list();

            return response()->json([
                'success' => true,
                'message' => 'API Key is valid!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API Key: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Send test email
     */
    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            $resend = Resend::client(Setting::get('resend_api_key'));

            $resend->emails->send([
                'from' => Setting::get('from_email'),
                'to' => [$request->test_email],
                'subject' => 'Test Email from ' . config('app.name'),
                'html' => view('emails.test_mail')->render(),
            ]);

            return back()->with('success', 'Test email sent successfully to ' . $request->test_email);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    /**
     * Toggle email template status via AJAX
     */
    public function toggleEmailTemplate(Request $request)
    {
        $request->validate([
            'template_key' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        try {
            // Validate template key
            $validKeys = [
                'enable_welcome_email',
                'enable_new_feature_email',
                'enable_subscription_invoice_email',
                'enable_subscription_start_email',
                'enable_subscription_end_email',
                'enable_system_maintenance_email',
            ];

            if (!in_array($request->template_key, $validKeys)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid template key'
                ], 400);
            }

            // Update the setting
            Setting::set($request->template_key, $request->enabled ? 1 : 0, 'boolean', 'email');

            return response()->json([
                'success' => true,
                'message' => 'Email template ' . ($request->enabled ? 'enabled' : 'disabled') . ' successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show email templates list
     */
    public function emailTemplates()
    {
        $templates = EmailTemplate::all();
        return view('admin.settings.email-templates.index', compact('templates'));
    }

    /**
     * Edit email template
     */
    public function editEmailTemplate($id)
    {
        $template = EmailTemplate::findOrFail($id);
        return view('admin.settings.email-templates.edit', compact('template'));
    }

    /**
     * Update email template
     */
    public function updateEmailTemplate(Request $request, $id)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $template = EmailTemplate::findOrFail($id);
        $template->update([
            'subject' => $request->subject,
            'body' => $request->body,
        ]);

        return back()->with('success', 'Email template updated successfully!');
    }

    /**
     * Preview email template
     */
    public function previewEmailTemplate($id)
    {
        $template = EmailTemplate::findOrFail($id);

        // Build sample placeholders for preview
        $sampleData = [];
        if ($template->available_variables) {
            foreach ($template->available_variables as $var) {
                $sampleData[$var] = match ($var) {
                    'user_name' => 'John Doe',
                    'app_name' => config('app.name'),
                    'dashboard_url' => url('/dashboard'),
                    'subscription_url' => url('/subscription'),
                    'feature_title' => 'Amazing New Feature',
                    'feature_description' => 'This is a sample feature description for preview purposes.',
                    'invoice_number' => 'INV-20260207-1234',
                    'payment_date' => date('F d, Y'),
                    'amount' => '$29.99',
                    'payment_method' => 'Credit Card',
                    'plan_name' => 'Premium Plan',
                    'billing_period' => 'Monthly',
                    'next_billing_date' => date('F d, Y', strtotime('+1 month')),
                    'start_date' => date('F d, Y'),
                    'renewal_date' => date('F d, Y', strtotime('+1 month')),
                    'end_date' => date('F d, Y'),
                    'searches_limit' => '500',
                    'exports_limit' => '100',
                    'start_time' => date('F d, Y h:i A'),
                    'end_time' => date('F d, Y h:i A', strtotime('+3 hours')),
                    'duration' => '2-3 hours',
                    'status' => 'Scheduled',
                    'maintenance_reason' => 'System upgrade and performance improvements.',
                    default => '{' . $var . '}',
                };
            }
        }

        $content = $template->renderBody($sampleData);

        return view('emails.dynamic', ['content' => $content]);
    }

    /**
     * Reset email template to default
     */
    public function resetEmailTemplate($id)
    {
        $template = EmailTemplate::findOrFail($id);
        $template->resetToDefault();

        return back()->with('success', 'Email template reset to default successfully!');
    }

    /**
     * Update .env file
     */
    protected function updateEnvFile($data)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            // Escape special characters in value
            $value = '"' . str_replace('"', '\"', $value) . '"';

            // Check if key exists
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                // Update existing key
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                // Add new key
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envFile, $envContent);

        // Clear config cache
        Artisan::call('config:clear');
    }
}
