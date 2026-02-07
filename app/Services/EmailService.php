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
     * Send welcome email to new user
     */
    public static function sendWelcomeEmail($user)
    {
        // Check if welcome email is enabled
        if (!Setting::get('enable_welcome_email', true)) {
            return [
                'success' => false,
                'message' => 'Welcome email is disabled in settings'
            ];
        }

        return self::sendWithTemplate(
            $user->email,
            'welcome',
            [
                'user_name' => $user->name ?? 'User',
                'dashboard_url' => url('/dashboard'),
            ]
        );
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
