<?php

namespace App\Services;

use App\Models\Setting;
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
     * Send welcome email to new user
     */
    public static function sendWelcomeEmail($user)
    {
        return self::send(
            $user->email,
            'Welcome to ' . config('app.name') . '!',
            'emails.welcome_mail',
            ['user' => $user]
        );
    }

    /**
     * Send new feature announcement email
     */
    public static function sendNewFeatureEmail($user, $featureData)
    {
        return self::send(
            $user->email,
            'New Feature: ' . ($featureData['feature_title'] ?? 'Update Available'),
            'emails.newfeature_mail',
            array_merge(['user' => $user], $featureData)
        );
    }

    /**
     * Send subscription invoice email
     */
    public static function sendSubscriptionInvoice($user, $invoiceData)
    {
        return self::send(
            $user->email,
            'Payment Receipt - Invoice #' . ($invoiceData['invoice_number'] ?? 'N/A'),
            'emails.subscription_invoice_mail',
            array_merge(['user' => $user], $invoiceData)
        );
    }

    /**
     * Send subscription start email
     */
    public static function sendSubscriptionStart($user, $subscriptionData)
    {
        return self::send(
            $user->email,
            'Your Subscription is Now Active!',
            'emails.subscription_start_mail',
            array_merge(['user' => $user], $subscriptionData)
        );
    }

    /**
     * Send subscription end email
     */
    public static function sendSubscriptionEnd($user, $subscriptionData)
    {
        return self::send(
            $user->email,
            'Your Subscription Has Ended',
            'emails.subscription_end_mail',
            array_merge(['user' => $user], $subscriptionData)
        );
    }

    /**
     * Send system maintenance notification
     */
    public static function sendMaintenanceNotification($user, $maintenanceData)
    {
        return self::send(
            $user->email,
            'Scheduled System Maintenance - ' . config('app.name'),
            'emails.system_maintenance_mail',
            array_merge(['user' => $user], $maintenanceData)
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
