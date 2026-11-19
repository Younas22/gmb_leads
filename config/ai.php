<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI integration master switch
    |--------------------------------------------------------------------------
    |
    | AI features run only when this is true AND the selected provider has
    | credentials. With no API key the app keeps working; AI actions simply
    | report "AI is not configured".
    |
    */

    'enabled' => (bool) env('AI_ENABLED', true),

    // Which provider implementation to use: "anthropic" or "null" (always disabled).
    'provider' => env('AI_PROVIDER', 'anthropic'),

    'providers' => [
        'anthropic' => [
            // Never hardcode keys. Set ANTHROPIC_API_KEY in the server .env only.
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('AI_ANTHROPIC_MODEL', 'claude-opus-5-5'),
            'max_tokens' => (int) env('AI_MAX_TOKENS', 4096),
            // low | medium | high | xhigh | max  (claude-opus-5-5 defaults to medium)
            'effort' => env('AI_EFFORT', 'medium'),
            // Server-side refusal fallback ("default" routing). Set false to disable.
            'fallbacks' => (bool) env('AI_REFUSAL_FALLBACKS', true),
            // Seconds per model call. Enforced by the HTTP transport passed to the SDK.
            'timeout' => (float) env('AI_TIMEOUT', 75),
            'connect_timeout' => (float) env('AI_CONNECT_TIMEOUT', 10),
            // SDK-level retries. Kept at 0 for queued work: retries happen at job level
            // (with backoff), so one job can never exceed its timeout because of hidden retries.
            'max_retries' => (int) env('AI_MAX_RETRIES', 0),
        ],

        // ChatGPT via the OpenAI Responses API (POST /v1/responses). Usually configured in
        // Admin → AI Setup; these .env values are only the fallback.
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('AI_OPENAI_MODEL', 'gpt-6.1-sol'),
            'max_tokens' => (int) env('AI_OPENAI_MAX_TOKENS', 16000),
            // none | low | medium | high | xhigh | max (empty = model default)
            'effort' => env('AI_OPENAI_EFFORT', 'medium'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => (float) env('AI_TIMEOUT', 75),
            'connect_timeout' => (float) env('AI_CONNECT_TIMEOUT', 10),
        ],

        // Google Gemini via generateContent (POST /v1beta/models/{model}:generateContent).
        // Usually configured in Admin → AI Setup; these .env values are only the fallback.
        // Free tier = a Google AI Studio project WITHOUT a linked billing account.
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('AI_GEMINI_MODEL', 'gemini-3.5-flash-lite'),
            'max_tokens' => (int) env('AI_GEMINI_MAX_TOKENS', 16000),
            // minimal | low | medium | high | xhigh (empty = model default)
            'effort' => env('AI_GEMINI_EFFORT'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'timeout' => (float) env('AI_TIMEOUT', 75),
            'connect_timeout' => (float) env('AI_CONNECT_TIMEOUT', 10),
        ],

        // OpenRouter (OpenAI-compatible /chat/completions). "openrouter/free" routes each request
        // to an available free model ($0). Free models: 20 requests/min and 50/day, or 1000/day
        // once $10 of credit has ever been bought. Usually configured in Admin → AI Setup.
        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'model' => env('AI_OPENROUTER_MODEL', 'openrouter/free'),
            'max_tokens' => (int) env('AI_OPENROUTER_MAX_TOKENS', 16000),
            // none | minimal | low | medium | high | xhigh (empty = model default)
            'effort' => env('AI_OPENROUTER_EFFORT'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'timeout' => (float) env('AI_TIMEOUT', 75),
            'connect_timeout' => (float) env('AI_CONNECT_TIMEOUT', 10),
        ],
    ],

    /*
    | Gemini free-tier limits shown in Admin → AI Setup (requests per minute / per day).
    | Google no longer publishes these numbers; each project's exact limits are shown at
    | aistudio.google.com/rate-limit. These are approximate values observed on free
    | projects in Oct–Nov 2026 (0 = the model has no free tier). The admin can override
    | them per installation. Daily quotas reset at midnight Pacific time.
    */
    'gemini_free_tier' => [
        'gemini-3.5-flash-lite' => ['rpm' => 15, 'rpd' => 500],
        'gemini-3.1-flash-lite' => ['rpm' => 15, 'rpd' => 500],
        'gemini-2.5-flash-lite' => ['rpm' => 15, 'rpd' => 500],
        'gemini-3.8-flash' => ['rpm' => 5, 'rpd' => 20],
        'gemini-3.6-flash' => ['rpm' => 5, 'rpd' => 20],
        'gemini-3-flash-preview' => ['rpm' => 5, 'rpd' => 20],
        'gemini-4-carbon' => ['rpm' => 0, 'rpd' => 0],
        'gemini-4-argon' => ['rpm' => 0, 'rpd' => 0],
        'gemini-4-neon' => ['rpm' => 0, 'rpd' => 0],
        'gemini-3.1-pro-preview' => ['rpm' => 0, 'rpd' => 0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan feature defaults
    |--------------------------------------------------------------------------
    |
    | Used when a package does not define the feature key in package_features.
    | Admins override per package by adding the key in Admin → Packages.
    | Numeric limits: -1 = unlimited, 0 = not included, N = per calendar month.
    |
    */

    'feature_defaults' => [
        'ai_sales_profile_enabled' => true,
        'ai_audits_monthly' => 0,
        'ai_email_drafts_monthly' => 0,
        // AI Offer Builder generations per month (browsing and drafts are free)
        'ai_offers_monthly' => 0,
        // Phase 3: individual Gmail sending
        // OFF unless a package explicitly sets gmail_sending_enabled = 1/true (Phase 3.1)
        'gmail_sending_enabled' => false,
        'email_sends_daily' => 20,
        // Phase 4: OFF unless a package explicitly enables them (no silent upgrades)
        'email_tracking_enabled' => false,
        'email_analytics_enabled' => false,
        'email_followups_enabled' => false,
        // Phase 5: customer Resend accounts. All OFF until an admin enables them per package.
        'resend_connection_enabled' => false,
        'resend_single_sending_enabled' => false,
        'resend_campaigns_enabled' => false,
        'resend_emails_daily' => 0,
        'resend_emails_monthly' => 0,
        'resend_contacts_limit' => 0,
        'resend_campaigns_monthly' => 0,
    ],

    // AI feature name (as logged) => package feature key holding its monthly quota.
    'quota_features' => [
        'website_audit' => 'ai_audits_monthly',
        'email_draft' => 'ai_email_drafts_monthly',
        'offer_builder' => 'ai_offers_monthly',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Sales Profile options
    |--------------------------------------------------------------------------
    */

    'languages' => [
        'en' => 'English',
        'ur' => 'Urdu',
        'ar' => 'Arabic',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'pt' => 'Portuguese',
        'it' => 'Italian',
        'nl' => 'Dutch',
        'tr' => 'Turkish',
        'hi' => 'Hindi',
    ],

    'tones' => [
        'professional' => 'Professional',
        'friendly' => 'Friendly',
        'consultative' => 'Consultative',
        'direct' => 'Short & direct',
        'formal' => 'Formal',
        'casual' => 'Casual',
    ],

    // Per-account limits for the offers library (skills + services + offers).
    'max_offers_per_type' => 50,
    // Services: an account must keep at least `min` and may add up to `max` (skills: config/skills.php).
    'services' => ['min' => 1, 'max' => 5],

    /*
    |--------------------------------------------------------------------------
    | Phase 2: lead analysis + email drafts
    |--------------------------------------------------------------------------
    */

    'analysis' => [
        // Queue used by the AI jobs (processed by the scheduler, see routes/console.php).
        'queue' => env('AI_QUEUE', 'ai'),
        // Queue connection for AI jobs (config/queue.php -> ai_database, retry_after 210s).
        'connection' => env('AI_QUEUE_CONNECTION', 'ai_database'),
        // Max leads per "Analyze with AI" request and max unfinished items per account.
        'max_batch_size' => (int) env('AI_MAX_BATCH_SIZE', 25),
        'max_open_items' => (int) env('AI_MAX_OPEN_ITEMS', 100),
        // Attempts for retryable failures (rate limits, timeouts) per job.
        'job_tries' => 3,
        // Seconds. Worst case per AI job: 2 model calls x AI_TIMEOUT (75s) + overhead.
        // Must stay below the ai_database connection retry_after (210s).
        'job_timeout' => (int) env('AI_JOB_TIMEOUT', 170),
        // Open items untouched this long are treated as stuck (worker killed) and closed.
        'stuck_after_minutes' => (int) env('AI_STUCK_AFTER_MINUTES', 30),
        // Malformed AI output is re-requested this many extra times (same quota unit).
        'invalid_output_retries' => (int) env('AI_INVALID_OUTPUT_RETRIES', 1),
        // Prompt budget: characters of website text sent to the model.
        'max_website_chars' => 6000,
        'max_prompt_chars' => 24000,
        // Delete website audits older than this (drafts keep their own evidence snapshot).
        'audit_retention_days' => (int) env('AI_AUDIT_RETENTION_DAYS', 180),
        'prompt_versions' => [
            'analysis' => 'analysis-v1',
            'draft' => 'draft-v1',
        ],
        // Max output tokens per call.
        // Thinking is always on for claude-opus-5-5 and counts toward max_tokens, so leave
        // generous room (only tokens actually generated are billed).
        'max_tokens' => [
            'analysis' => 16000,
            'draft' => 16000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Website fetching (SafeHttpFetcher)
    |--------------------------------------------------------------------------
    */

    'fetcher' => [
        'user_agent' => env('AI_FETCHER_USER_AGENT', 'CustomerNearMeBot/1.0 (+website audit for a business lead; respects robots.txt)'),
        'connect_timeout' => 5,
        'timeout' => 10,
        // Total time budget for all requests of one audit (seconds).
        'audit_time_budget' => 30,
        'max_bytes' => 1_000_000,
        'max_redirects' => 3,
        // Homepage + up to N-1 internal pages (contact / about / services).
        'max_pages' => 3,
        'allowed_ports' => [80, 443],
        // Requests per host per minute across the whole app.
        'per_host_per_minute' => 12,
        'robots_cache_seconds' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost estimation (USD per million tokens). Used only for the usage page.
    | Check current prices before relying on these numbers.
    |--------------------------------------------------------------------------
    */

    'pricing' => [
        // Verified against platform.claude.com/docs/en/about-claude/pricing on 2026-11-13.
        'claude-opus-5-5' => ['input' => 4.00, 'output' => 20.00, 'cache_read' => 0.20, 'cache_write' => 5.00],
        'claude-sonnet-5-5' => ['input' => 2.00, 'output' => 10.00, 'cache_read' => 0.10, 'cache_write' => 2.50],
        'claude-haiku-5-5' => ['input' => 0.10, 'output' => 0.50, 'cache_read' => 0.01, 'cache_write' => 0.125],
    ],
];
