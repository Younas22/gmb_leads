<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'welcome',
                'name' => 'Welcome Email',
                'subject' => 'Welcome to {app_name}!',
                'available_variables' => ['user_name', 'app_name', 'dashboard_url'],
                'body' => '<h2>Welcome to {app_name}!</h2>

<p>Hi {user_name},</p>

<p>We\'re thrilled to have you on board! Thank you for joining {app_name}, your trusted platform for generating high-quality Google My Business leads.</p>

<div class="info-box">
    <p><strong>Your account is now active and ready to use!</strong></p>
</div>

<p>Here\'s what you can do next:</p>

<ul>
    <li><strong>Start Searching:</strong> Find targeted GMB leads in your area</li>
    <li><strong>Save Leads:</strong> Build your prospect database</li>
    <li><strong>Export Data:</strong> Download leads in various formats</li>
    <li><strong>Advanced Filters:</strong> Refine your search criteria</li>
</ul>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="button">Go to Dashboard</a>
</p>

<div class="divider"></div>

<p><strong>Need help getting started?</strong></p>
<p>Check out our quick start guide or contact our support team. We\'re here to help you succeed!</p>

<p>Best regards,<br>
The {app_name} Team</p>',
            ],
            [
                'slug' => 'new_feature',
                'name' => 'New Feature Email',
                'subject' => 'New Feature: {feature_title}',
                'available_variables' => ['user_name', 'app_name', 'feature_title', 'feature_description', 'dashboard_url'],
                'body' => '<h2>Exciting New Feature Released!</h2>

<p>Hi {user_name},</p>

<p>We\'re excited to announce a new feature that will help you generate leads even more effectively!</p>

<div class="info-box">
    <p><strong>{feature_title}</strong></p>
    <p style="margin-top: 10px;">{feature_description}</p>
</div>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="button">Try It Now</a>
</p>

<div class="divider"></div>

<p>We\'re constantly working to improve your experience. Stay tuned for more updates!</p>

<p>Best regards,<br>
The {app_name} Team</p>',
            ],
            [
                'slug' => 'subscription_invoice',
                'name' => 'Subscription Invoice Email',
                'subject' => 'Payment Receipt - Invoice #{invoice_number}',
                'available_variables' => ['user_name', 'app_name', 'invoice_number', 'payment_date', 'amount', 'payment_method', 'plan_name', 'billing_period', 'next_billing_date', 'subscription_url'],
                'body' => '<h2>Payment Receipt</h2>

<p>Hi {user_name},</p>

<p>Thank you for your payment! This email confirms that we have received your subscription payment.</p>

<div class="info-box">
    <p><strong>Invoice Details</strong></p>
    <p style="margin-top: 10px;">
        <strong>Invoice #:</strong> {invoice_number}<br>
        <strong>Date:</strong> {payment_date}<br>
        <strong>Amount:</strong> {amount}<br>
        <strong>Payment Method:</strong> {payment_method}<br>
        <strong>Status:</strong> <span style="color: #22c55e;">Paid</span>
    </p>
</div>

<p><strong>Subscription Details:</strong></p>
<ul>
    <li><strong>Plan:</strong> {plan_name}</li>
    <li><strong>Billing Period:</strong> {billing_period}</li>
    <li><strong>Next Billing Date:</strong> {next_billing_date}</li>
</ul>

<p style="text-align: center;">
    <a href="{subscription_url}" class="button">View Subscription</a>
</p>

<div class="divider"></div>

<p style="font-size: 14px; color: #666;">
    If you have any questions about this invoice, please contact our billing support team.
</p>

<p>Best regards,<br>
The {app_name} Team</p>',
            ],
            [
                'slug' => 'subscription_start',
                'name' => 'Subscription Start Email',
                'subject' => 'Your Subscription is Now Active!',
                'available_variables' => ['user_name', 'app_name', 'plan_name', 'start_date', 'renewal_date', 'searches_limit', 'exports_limit', 'dashboard_url'],
                'body' => '<h2>Your Subscription is Now Active!</h2>

<p>Hi {user_name},</p>

<p>Great news! Your subscription to {app_name} has been successfully activated.</p>

<div class="info-box">
    <p><strong>{plan_name}</strong></p>
    <p style="margin-top: 10px;">
        <strong>Start Date:</strong> {start_date}<br>
        <strong>Renewal Date:</strong> {renewal_date}<br>
        <strong>Status:</strong> <span style="color: #22c55e;">Active</span>
    </p>
</div>

<p><strong>Your Plan Includes:</strong></p>
<ul>
    <li>{searches_limit} searches per month</li>
    <li>{exports_limit} exports per month</li>
    <li>Advanced filtering options</li>
    <li>Priority customer support</li>
    <li>Regular feature updates</li>
</ul>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="button">Start Using Your Plan</a>
</p>

<div class="divider"></div>

<p><strong>What\'s Next?</strong></p>
<p>You now have full access to all premium features. Start generating high-quality leads and grow your business!</p>

<p>Best regards,<br>
The {app_name} Team</p>',
            ],
            [
                'slug' => 'subscription_end',
                'name' => 'Subscription End Email',
                'subject' => 'Your Subscription Has Ended',
                'available_variables' => ['user_name', 'app_name', 'plan_name', 'end_date', 'subscription_url'],
                'body' => '<h2>Your Subscription Has Ended</h2>

<p>Hi {user_name},</p>

<p>We wanted to let you know that your {plan_name} subscription has expired.</p>

<div class="info-box">
    <p><strong>Subscription Details</strong></p>
    <p style="margin-top: 10px;">
        <strong>Plan:</strong> {plan_name}<br>
        <strong>End Date:</strong> {end_date}<br>
        <strong>Status:</strong> <span style="color: #ef4444;">Expired</span>
    </p>
</div>

<p><strong>What This Means:</strong></p>
<ul>
    <li>Access to premium features has been restricted</li>
    <li>Your saved leads are still safe and accessible</li>
    <li>Search and export limits have been applied</li>
    <li>You can renew anytime to restore full access</li>
</ul>

<p>We\'d love to have you back! Renew your subscription now to continue enjoying all premium features.</p>

<p style="text-align: center;">
    <a href="{subscription_url}" class="button">Renew Subscription</a>
</p>

<div class="divider"></div>

<p><strong>Need help?</strong></p>
<p>If you have any questions about renewing your subscription or need assistance, our support team is here to help.</p>

<p>We hope to see you back soon!<br>
The {app_name} Team</p>',
            ],
            [
                'slug' => 'system_maintenance',
                'name' => 'System Maintenance Email',
                'subject' => 'Scheduled System Maintenance - {app_name}',
                'available_variables' => ['user_name', 'app_name', 'start_time', 'end_time', 'duration', 'status', 'maintenance_reason'],
                'body' => '<h2>Scheduled System Maintenance</h2>

<p>Hi {user_name},</p>

<p>This is to inform you that we have scheduled maintenance for {app_name} to improve our services and performance.</p>

<div class="info-box" style="border-left-color: #f59e0b; background-color: #fffbeb;">
    <p><strong>Maintenance Schedule</strong></p>
    <p style="margin-top: 10px;">
        <strong>Start Time:</strong> {start_time}<br>
        <strong>End Time:</strong> {end_time}<br>
        <strong>Expected Duration:</strong> {duration}<br>
        <strong>Status:</strong> {status}
    </p>
</div>

<p><strong>What to Expect:</strong></p>
<ul>
    <li>The platform will be temporarily unavailable during maintenance</li>
    <li>All your data will remain safe and secure</li>
    <li>Improved performance and new features after completion</li>
    <li>You\'ll receive a notification when maintenance is complete</li>
</ul>

<div class="divider"></div>

<p><strong>Need immediate assistance?</strong></p>
<p>If you have any urgent concerns, please contact our support team before the maintenance window begins.</p>

<p>Thank you for your patience and understanding.<br>
The {app_name} Team</p>',
            ],
            [
                'slug' => 'verify_email',
                'name' => 'Email Verification',
                'subject' => 'Verify Your Email Address - {app_name}',
                'available_variables' => ['user_name', 'app_name', 'verification_url', 'dashboard_url'],
                'body' => '<h2>Verify Your Email Address</h2>

<p>Hi {user_name},</p>

<p>Thank you for signing up! We\'re excited to have you on board. To get started and access all features, please verify your email address.</p>

<div class="info-box">
    <p><strong>Why verify your email?</strong></p>
    <p style="margin-top: 10px;">Email verification helps us ensure your account security and enables important notifications about your searches and subscriptions.</p>
</div>

<p style="text-align: center;">
    <a href="{verification_url}" class="button">Verify Email Address</a>
</p>

<p style="font-size: 14px; color: #666; margin-top: 20px;">
    If the button doesn\'t work, copy and paste this link into your browser:<br>
    <span style="word-break: break-all; color: #667eea;">{verification_url}</span>
</p>

<div class="divider"></div>

<p><strong>What\'s next?</strong></p>
<p>Once verified, you\'ll be able to:</p>
<ul>
    <li>Search for business leads worldwide</li>
    <li>Save and export lead data</li>
    <li>Access API for integrations</li>
    <li>Track your search history</li>
</ul>

<p style="font-size: 14px; color: #999;">
    This verification link will expire in 24 hours. If you didn\'t create an account, please ignore this email.
</p>

<p>Best regards,<br>
The {app_name} Team</p>',
            ],
            [
                'slug' => 'reset_password',
                'name' => 'Password Reset',
                'subject' => 'Reset Your Password - {app_name}',
                'available_variables' => ['user_name', 'app_name', 'reset_url', 'dashboard_url'],
                'body' => '<h2>Password Reset Request</h2>

<p>Hi {user_name},</p>

<p>We received a request to reset your password for your {app_name} account. If you made this request, click the button below to set a new password.</p>

<p style="text-align: center;">
    <a href="{reset_url}" class="button">Reset Password</a>
</p>

<div class="info-box" style="border-left-color: #f59e0b; background-color: #fffbeb;">
    <p><strong>⚠️ Important Security Notice:</strong></p>
    <p style="margin-top: 10px;">This password reset link will expire in 24 hours. If you didn\'t request this reset, please ignore this email and your password will remain unchanged.</p>
</div>

<p style="font-size: 14px; color: #666; margin-top: 20px;">
    If the button doesn\'t work, copy and paste this link into your browser:<br>
    <span style="word-break: break-all; color: #667eea;">{reset_url}</span>
</p>

<div class="divider"></div>

<p><strong>Tips for a strong password:</strong></p>
<ul>
    <li>Use at least 8 characters</li>
    <li>Include uppercase and lowercase letters</li>
    <li>Add numbers and special characters</li>
    <li>Avoid common words or personal information</li>
</ul>

<p style="font-size: 14px; color: #999;">
    For security reasons, this link will expire in 24 hours.
</p>

<p>Best regards,<br>
The {app_name} Team</p>',
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'default_subject' => $template['subject'],
                    'default_body' => $template['body'],
                    'available_variables' => $template['available_variables'],
                ]
            );
        }
    }
}
