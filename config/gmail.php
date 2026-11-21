<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gmail sending (Phase 3)
    |--------------------------------------------------------------------------
    |
    | A SEPARATE OAuth flow from "Sign in with Google": its own redirect URI and
    | scopes, and its OWN OAuth client (GMAIL_CLIENT_ID/SECRET). It never falls
    | back to the Google Login client, so login keeps working independently.
    |
    */

    'enabled' => (bool) env('GMAIL_SENDING_ENABLED', true),

    'client_id' => env('GMAIL_CLIENT_ID'),
    'client_secret' => env('GMAIL_CLIENT_SECRET'),

    // Must be registered in Google Cloud Console exactly. Default: {APP_URL}/user/email-sending/google/callback
    'redirect_uri' => env('GMAIL_REDIRECT_URI'),

    // Minimum scopes: send only. "openid email" (non-sensitive) give the address
    // of the connected account; no read/modify scopes are requested.
    'scopes' => array_values(array_filter([
        'openid',
        'email',
        'https://www.googleapis.com/auth/gmail.send',
        // Reply detection (opt-in): read-only access so replies to sent emails are found
        // automatically. Add this scope to the OAuth consent screen in Google Cloud first.
        env('GMAIL_REPLY_DETECTION', false) ? 'https://www.googleapis.com/auth/gmail.readonly' : null,
    ])),

    'reply_detection' => (bool) env('GMAIL_REPLY_DETECTION', false),
    'readonly_scope' => 'https://www.googleapis.com/auth/gmail.readonly',
    // How far back sent emails are checked for replies (days)
    'reply_lookback_days' => 30,
    // Emails checked per run (each is one Gmail API call)
    'reply_check_batch' => 100,

    'endpoints' => [
        'authorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token' => 'https://oauth2.googleapis.com/token',
        'revoke' => 'https://oauth2.googleapis.com/revoke',
        'send' => 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send',
        'thread' => 'https://gmail.googleapis.com/gmail/v1/users/me/threads/',
        'messages' => 'https://gmail.googleapis.com/gmail/v1/users/me/messages',
    ],

    // OAuth state lifetime (seconds)
    'state_ttl' => 600,

    // HTTP timeouts for Google calls (seconds). Worst case per job: refresh + send +
    // (401) refresh + send = 4 x timeout = 60s, inside the job timeout (75s), which
    // stays below the mail_database retry_after (120s).
    'timeout' => 15,
    'connect_timeout' => 5,

    // Queue for sending jobs (kept separate from AI jobs)
    'queue' => [
        'connection' => env('MAIL_SEND_QUEUE_CONNECTION', 'mail_database'),
        'name' => env('MAIL_SEND_QUEUE', 'mail'),
        'tries' => 3,
        'timeout' => 75,
    ],

    // Per sender: Gmail allows far more, but cold outreach must stay small and personal.
    'per_minute_per_account' => (int) env('GMAIL_PER_MINUTE', 5),

    // Body footer: sender identification + opt-out (required by CAN-SPAM and similar laws)
    'footer_template' => "--\n:sender\n:address\nIf you would rather not receive emails from me, reply \"unsubscribe\" or use this link: :url",
];
