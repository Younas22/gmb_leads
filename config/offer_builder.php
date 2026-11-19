<?php

/*
|--------------------------------------------------------------------------
| AI Offer Builder
|--------------------------------------------------------------------------
| Generation runs on the existing AI queue (config/ai.php "analysis"), counted
| against the "offer_builder" AI feature (package key ai_offers_monthly).
*/

return [
    // AI feature name used for quota + usage logs (see config/ai.php quota_features)
    'ai_feature' => 'offer_builder',

    // Model call: output budget and effort (null = provider default). "low" keeps a
    // full offer well inside the provider timeout.
    'max_tokens' => (int) env('OFFER_BUILDER_MAX_TOKENS', 8000),
    'effort' => env('OFFER_BUILDER_EFFORT', 'low'),

    // A queued/running generation older than this is treated as lost (worker down).
    'stale_after_minutes' => 15,

    // At most this many generations in progress per user at once.
    'max_open_generations_per_user' => 3,

    // Saved offers per user (drafts + generated + archived).
    'max_offers_per_user' => 300,

    // Input limits (characters)
    'limits' => [
        'title' => 150,
        'field' => 1000,
        'long_field' => 2000,
        'additional_instructions' => 2000,
        'custom_prompt' => 20000,
    ],
];
