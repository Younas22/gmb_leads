<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;
use Resend;

class EmailService
{
    /**
     * Send email using Resend with system settings
     */
    public static function send($to, $subject, $view, $data = [])
    {
        try {
            // Get email settings from database
            $apiKey = Setting::get('resend_api_key');
            $fromEmail = Setting::get('from_email', config('mail.from.address'));
            $fromName = Setting::get('from_name', config('mail.from.name'));

            if (!$apiKey) {
                throw new \Exception('Resend API key not configured. Please configure it in Settings.');
            }

            $resend = Resend::client($apiKey);

            // Render the email view
            $html = view($view, $data)->render();

            // Send email
            $response = $resend->emails->send([
                'from' => $fromName . ' <' . $fromEmail . '>',
                'to' => is_array($to) ? $to : [$to],
                'subject' => $subject,
                'html' => $html,
            ]);

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'response' => $response
            ];
        } catch (\Exception $e) {
            \Log::error('Email sending failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send email using DB template with placeholders
     */
    public static function sendWithTemplate($to, $templateSlug, $placeholders = [])
    {
        try {
            $template = EmailTemplate::getBySlug($templateSlug);

            if (!$template) {
                throw new \Exception("Email template '{$templateSlug}' not found.");
            }

            // Always include app_name
            $placeholders['app_name'] = $placeholders['app_name'] ?? config('app.name');

            // Render subject and body with placeholders
            $subject = $template->renderSubject($placeholders);
            $content = $template->renderBody($placeholders);

            // Use dynamic view that extends layout
            return self::send($to, $subject, 'emails.dynamic', ['content' => $content]);
        } catch (\Exception $e) {
            \Log::error('Template email sending failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send verification email to new user
     */
    public static function sendVerificationEmail($user, $verificationUrl)
    {
        // Check if verify email is enabled
        if (!Setting::get('enable_verify_email', true)) {
            Log::info('Verify email is disabled. Skipping verification email for: ' . $user->email);
            return [
                'success' => false,
                'message' => 'Verification email is disabled in settings'
            ];
        }

        // Check if dynamic emails are enabled
        $useDynamicEmails = Setting::get('use_dynamic_emails', false);

        if ($useDynamicEmails) {
            // Use dynamic email template from database
            return self::sendWithTemplate(
                $user->email,
                'verify_email',
                [
                    'user_name' => $user->first_name ?? $user->name ?? 'User',
                    'verification_url' => $verificationUrl,
                ]
            );
        } else {
            // Use static blade template
            return self::send(
                $user->email,
                'Verify Your Email - ' . config('app.name'),
                'emails.verify-email',
                [
                    'user' => $user,
                    'verificationUrl' => $verificationUrl,
                ]
            );
        }
    }

    /**
     * Send welcome email to new user
     */
    public static function sendWelcomeEmail($user)
    {
        // Check if welcome email is enabled
        if (!Setting::get('enable_welcome_email', true)) {
            Log::info('Welcome email is disabled. Skipping welcome email for: ' . $user->email);
            return [
                'success' => false,
                'message' => 'Welcome email is disabled in settings'
            ];
        }

        // Check if dynamic emails are enabled
        $useDynamicEmails = Setting::get('use_dynamic_emails', false);

        if ($useDynamicEmails) {
            // Use dynamic email template from database
            return self::sendWithTemplate(
                $user->email,
                'welcome',
                [
                    'user_name' => $user->name ?? 'User',
                    'dashboard_url' => url('/dashboard'),
                ]
            );
        } else {
            // Use static blade template
            return self::send(
                $user->email,
                'Welcome to ' . config('app.name'),
                'emails.welcome_mail',
                [
                    'user' => $user,
                ]
            );
        }
    }

    /**
     * Send password reset email
     */
    public static function sendPasswordResetEmail($user, $resetUrl)
    {
        // Check if password reset email is enabled
        if (!Setting::get('enable_password_reset_email', true)) {
            Log::info('Password reset email is disabled. Skipping reset email for: ' . $user->email);
            return [
                'success' => false,
                'message' => 'Password reset email is disabled in settings'
            ];
        }

        // Check if dynamic emails are enabled
        $useDynamicEmails = Setting::get('use_dynamic_emails', false);

        if ($useDynamicEmails) {
            // Use dynamic email template from database
            return self::sendWithTemplate(
                $user->email,
                'password_reset',
                [
                    'user_name' => $user->first_name ?? $user->name ?? 'User',
                    'reset_url' => $resetUrl,
                ]
            );
        } else {
            // Use static blade template
            return self::send(
                $user->email,
                'Reset Your Password - ' . config('app.name'),
                'emails.reset-password',
                [
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                ]
            );
        }
    }

    /**
     * Send new feature announcement email
     */
    public static function sendNewFeatureEmail($user, $featureData)
    {
        // Check if new feature email is enabled
        if (!Setting::get('enable_new_feature_email', true)) {
            return [
                'success' => false,
                'message' => 'New feature email is disabled in settings'
            ];
        }

        return self::sendWithTemplate(
            $user->email,
            'new_feature',
            [
                'user_name' => $user->name ?? 'User',
                'feature_title' => $featureData['feature_title'] ?? 'Update Available',
                'feature_description' => $featureData['feature_description'] ?? 'Check out what\'s new in your dashboard.',
                'dashboard_url' => url('/dashboard'),
            ]
        );
    }

    /**
     * Send subscription invoice email
     */
    public static function sendSubscriptionInvoice($user, $invoiceData)
    {
        // Check if subscription invoice email is enabled
        if (!Setting::get('enable_subscription_invoice_email', true)) {
            return [
                'success' => false,
                'message' => 'Subscription invoice email is disabled in settings'
            ];
        }

        return self::sendWithTemplate(
            $user->email,
            'subscription_invoice',
            [
                'user_name' => $user->name ?? 'User',
                'invoice_number' => $invoiceData['invoice_number'] ?? 'INV-' . date('Ymd') . '-' . rand(1000, 9999),
                'payment_date' => $invoiceData['payment_date'] ?? date('F d, Y'),
                'amount' => isset($invoiceData['amount']) ? '$' . number_format($invoiceData['amount'], 2) : '$0.00',
                'payment_method' => $invoiceData['payment_method'] ?? 'Credit Card',
                'plan_name' => $invoiceData['plan_name'] ?? 'Premium Plan',
                'billing_period' => $invoiceData['billing_period'] ?? 'Monthly',
                'next_billing_date' => $invoiceData['next_billing_date'] ?? date('F d, Y', strtotime('+1 month')),
                'subscription_url' => url('/subscription'),
            ]
        );
    }

    /**
     * Send subscription start email
     */
    public static function sendSubscriptionStart($user, $subscriptionData)
    {
        // Check if subscription start email is enabled
        if (!Setting::get('enable_subscription_start_email', true)) {
            return [
                'success' => false,
                'message' => 'Subscription start email is disabled in settings'
            ];
        }

        return self::sendWithTemplate(
            $user->email,
            'subscription_start',
            [
                'user_name' => $user->name ?? 'User',
                'plan_name' => $subscriptionData['plan_name'] ?? 'Premium Plan',
                'start_date' => $subscriptionData['start_date'] ?? date('F d, Y'),
                'renewal_date' => $subscriptionData['renewal_date'] ?? date('F d, Y', strtotime('+1 month')),
                'searches_limit' => $subscriptionData['searches_limit'] ?? 'Unlimited',
                'exports_limit' => $subscriptionData['exports_limit'] ?? 'Unlimited',
                'dashboard_url' => url('/dashboard'),
            ]
        );
    }

    /**
     * Send subscription end email
     */
    public static function sendSubscriptionEnd($user, $subscriptionData)
    {
        // Check if subscription end email is enabled
        if (!Setting::get('enable_subscription_end_email', true)) {
            return [
                'success' => false,
                'message' => 'Subscription end email is disabled in settings'
            ];
        }

        return self::sendWithTemplate(
            $user->email,
            'subscription_end',
            [
                'user_name' => $user->name ?? 'User',
                'plan_name' => $subscriptionData['plan_name'] ?? 'Premium Plan',
                'end_date' => $subscriptionData['end_date'] ?? date('F d, Y'),
                'subscription_url' => url('/subscription'),
            ]
        );
    }

    /**
     * Send system maintenance notification
     */
    public static function sendMaintenanceNotification($user, $maintenanceData)
    {
        // Check if system maintenance email is enabled
        if (!Setting::get('enable_system_maintenance_email', true)) {
            return [
                'success' => false,
                'message' => 'System maintenance email is disabled in settings'
            ];
        }

        return self::sendWithTemplate(
            $user->email,
            'system_maintenance',
            [
                'user_name' => $user->name ?? 'User',
                'start_time' => $maintenanceData['start_time'] ?? 'TBD',
                'end_time' => $maintenanceData['end_time'] ?? 'TBD',
                'duration' => $maintenanceData['duration'] ?? '2-3 hours',
                'status' => $maintenanceData['status'] ?? 'Scheduled',
                'maintenance_reason' => $maintenanceData['maintenance_reason'] ?? '',
            ]
        );
    }

    /**
     * Send bulk emails to multiple users
     */
    public static function sendBulk($users, $subject, $view, $data = [])
    {
        $results = [];

        foreach ($users as $user) {
            $results[] = self::send(
                $user->email,
                $subject,
                $view,
                array_merge(['user' => $user], $data)
            );
        }

        return $results;
    }
}
