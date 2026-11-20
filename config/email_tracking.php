<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email tracking, replies and follow-ups (Phase 4)
    |--------------------------------------------------------------------------
    |
    | Link tracking is opt-in per sender and also needs the plan feature
    | "email_tracking_enabled". No open-tracking pixels are used.
    |
    */

    // How long click events are kept (sender can pick 30/90/180 days)
    'default_retention_days' => 90,
    'max_retention_days' => 180,

    // After this many days a tracked link stops working (its row is deleted)
    'link_lifetime_days' => (int) env('EMAIL_TRACKING_LINK_DAYS', 365),

    // Most links tracked in one email (the rest are sent unchanged)
    'max_links_per_email' => 20,

    // Requests this soon after Gmail accepted the email are almost always
    // automated security scanners, not people.
    'scanner_window_seconds' => 60,

    // Recorded events per link per hour (the redirect keeps working past this)
    'max_events_per_link_per_hour' => 30,

    // Lower-case substrings of user agents that belong to link scanners, previewers and bots
    'scanner_user_agents' => [
        'bot', 'crawl', 'spider', 'slurp', 'scan', 'preview', 'proofpoint', 'mimecast',
        'barracuda', 'safelinks', 'urldefense', 'fireeye', 'trendmicro', 'symantec',
        'forcepoint', 'sophos', 'cisco', 'ironport', 'headless', 'phantomjs', 'python',
        'curl', 'wget', 'httpclient', 'go-http', 'java/', 'okhttp', 'libwww', 'axios',
        'node-fetch', 'facebookexternalhit', 'slack', 'whatsapp', 'telegram', 'discord',
        'skype', 'linkedin', 'google-safety', 'googleimageproxy', 'microsoft office',
    ],

    // Automatic follow-up reminder this many days after an email is sent to a lead
    // (single Gmail / Resend emails, not campaigns). 0 = off. Skipped when the lead
    // already has an open reminder, unsubscribed, or replied "not interested".
    'auto_follow_up_days' => (int) env('EMAIL_AUTO_FOLLOW_UP_DAYS', 3),

    // Retention cleanup batch size (keeps each DELETE small on shared hosting)
    'cleanup_batch' => 1000,
];
