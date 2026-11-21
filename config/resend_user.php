<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer-owned Resend accounts (Phase 5, "bring your own API key")
    |--------------------------------------------------------------------------
    |
    | Completely separate from the PLATFORM Resend account in
    | config/services.php (services.resend.key), which sends CustomerNearMe's own
    | verification / password / billing emails. Nothing here ever reads or uses
    | that key, and customer keys are never used for platform mail.
    |
    | Resend may only be used for recipients who explicitly opted in
    | (Resend Acceptable Use Policy): never for scraped Google Maps leads.
    |
    */

    'base_url' => 'https://api.resend.com',

    // Resend rejects requests without a User-Agent (403, code 1010)
    'user_agent' => 'CustomerNearMe/1.0 (+customer-integration)',

    'timeout' => 15,
    'connect_timeout' => 5,

    // Resend default: 10 requests/second per team. Stay well below it.
    'requests_per_second' => 4,

    // Same database queue connection as Gmail sending; separate queue names
    'queue' => [
        'connection' => env('MAIL_SEND_QUEUE_CONNECTION', 'mail_database'),
        'send' => 'resend',
        'webhook' => 'webhook',
        'tries' => 3,
        'timeout' => 75,
    ],

    // Webhook signature (Svix scheme): reject timestamps further than this from now
    'webhook_tolerance_seconds' => 300,

    // Events requested when the webhook is created through the API
    'webhook_events' => [
        'email.sent', 'email.delivered', 'email.delivery_delayed', 'email.bounced',
        'email.complained', 'email.failed', 'email.suppressed', 'email.opened',
        'email.clicked', 'contact.updated', 'domain.updated',
        // Replies sent to a Resend receiving address (see ResendEventProcessor::emailReceived)
        'email.received',
    ],

    // Subdomain Resend uses for open/click tracking ("links" => links.<your domain>)
    'tracking_subdomain' => 'links',

    // Unmatched webhook events (arrived before our row was finalised) are retried for this long
    'unmatched_retry_minutes' => 60,

    // Webhook receipts (minimal metadata only) are deleted after this many days
    'receipt_retention_days' => 30,

    // Campaign audience sync: contacts per job run (each is one API call)
    'sync_batch' => 40,

    // Scheduling window for campaigns
    'schedule_min_minutes' => 10,
    'schedule_max_days' => 30,

    // CSV import bounds
    'import_max_rows' => 5000,
    'import_max_kb' => 2048,

    // Accepted consent sources (documented evidence is always required as well)
    'consent_sources' => [
        'signup_form' => 'Website sign-up form',
        'existing_customer' => 'Existing customer (asked to receive emails)',
        'event_signup' => 'Event or webinar registration with opt-in',
        'written_request' => 'Written request / email asking to subscribe',
        'double_opt_in' => 'Confirmed double opt-in',
        'other' => 'Other documented opt-in',
    ],

    // Resend domain status => [label, can send]
    'domain_statuses' => [
        'not_started' => ['Pending', false],
        'pending' => ['Verifying', false],
        'verified' => ['Verified', true],
        'partially_verified' => ['Needs attention', false],
        'partially_failed' => ['Needs attention', false],
        'temporary_failure' => ['Needs attention', false],
        'failed' => ['Failed', false],
    ],
];
