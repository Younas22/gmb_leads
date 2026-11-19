<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Throwable;

/**
 * Admin-managed AI configuration (Admin → AI Setup), stored in the `settings` table
 * (API keys encrypted with APP_KEY). Anything not set by the admin falls back to the
 * .env based config/ai.php, so existing installations keep working unchanged.
 *
 * Exactly one provider is active at a time: `ai_active_provider` holds a single value.
 */
class AiSettings
{
    public const OFF = 'off';

    /** provider key => [label, settings prefix] */
    public const PROVIDERS = [
        'anthropic' => 'Claude (Anthropic)',
        'openai' => 'ChatGPT (OpenAI)',
        'gemini' => 'Gemini (Google)',
        'openrouter' => 'OpenRouter',
    ];

    /** Model suggestions shown in the admin form (any model id can be typed). */
    public const MODEL_SUGGESTIONS = [
        'anthropic' => ['claude-opus-5-5', 'claude-sonnet-5-5', 'claude-haiku-5-5', 'claude-fable-5-2'],
        'openai' => ['gpt-6-astra', 'gpt-6.1-sol', 'gpt-6.1-terra', 'gpt-6-luna'],
        'gemini' => ['gemini-3.5-flash-lite', 'gemini-4-hydrogen', 'gemini-3.8-flash', 'gemini-3.1-flash-lite', 'gemini-4-argon', 'gemini-4-carbon'],
        // ':free' models cost $0 (50 requests/day, 1000/day after buying $10 credit); openrouter/free picks one automatically
        'openrouter' => ['openrouter/free', 'nvidia/nemotron-3-ultra-550b-a55b:free', 'google/gemma-4-31b-it:free', 'nvidia/nemotron-3.5-super-vl:free', 'inclusionai/ling-3.2-flash:free', 'baidu/ernie-5.5-flash-preview:free', 'particle-ai/particle-1:free'],
    ];

    /** Reasoning / effort levels the admin can choose ('' = provider default). */
    public const EFFORTS = [
        'anthropic' => ['low', 'medium', 'high', 'xhigh', 'max'],
        'openai' => ['none', 'low', 'medium', 'high', 'xhigh', 'max'],
        'gemini' => ['minimal', 'low', 'medium', 'high', 'xhigh'],
        'openrouter' => ['none', 'minimal', 'low', 'medium', 'high', 'xhigh'],
    ];

    public const FIELDS = ['api_key', 'model', 'effort', 'max_tokens'];

    /** "anthropic" | "openai" | "off" (also "fixture"/"null" from .env for local and tests). */
    public function activeProvider(): string
    {
        if (!config('ai.enabled')) {
            return self::OFF; // .env master switch (AI_ENABLED=false) always wins
        }
        $chosen = $this->setting('ai_active_provider');

        return filled($chosen) ? (string) $chosen : (string) config('ai.provider');
    }

    /** Effective config for one provider: admin values over config/ai.php (.env). */
    public function providerConfig(string $provider): array
    {
        $config = (array) config("ai.providers.{$provider}", []);
        foreach (self::FIELDS as $field) {
            $value = $this->setting($this->key($provider, $field));
            if ($value !== null && $value !== '') {
                $config[$field] = $field === 'max_tokens' ? (int) $value : $value;
                if ($field === 'max_tokens') {
                    // Admin value is also a hard cap: features (analysis, drafts) ask for their own
                    // limit, which is lowered to this. Not set = no extra cap (as before).
                    $config['max_tokens_cap'] = (int) $value;
                }
            }
        }

        return $config;
    }

    /** Where the API key comes from: "admin" (saved in settings), "env", or null (missing). */
    public function keySource(string $provider): ?string
    {
        if (filled($this->setting($this->key($provider, 'api_key')))) {
            return 'admin';
        }

        return filled(config("ai.providers.{$provider}.api_key")) ? 'env' : null;
    }

    public function keyLast4(string $provider): ?string
    {
        $key = (string) ($this->providerConfig($provider)['api_key'] ?? '');

        return $key === '' ? null : substr($key, -4);
    }

    /**
     * Gemini free-tier limits for the model in use: admin values (copied from AI Studio →
     * Rate limits) over the approximate defaults in config/ai.php. null = unknown.
     *
     * @return array{rpm: ?int, rpd: ?int, source: string}
     */
    public function geminiFreeTier(): array
    {
        $model = (string) ($this->providerConfig('gemini')['model'] ?? '');
        // Not config("...{$model}"): model ids contain dots (gemini-3.8-flash)
        $defaults = (array) (((array) config('ai.gemini_free_tier', []))[$model] ?? []);
        $rpm = $this->setting('ai_gemini_free_rpm');
        $rpd = $this->setting('ai_gemini_free_rpd');
        $admin = filled($rpm) || filled($rpd);

        return [
            'rpm' => filled($rpm) ? (int) $rpm : (isset($defaults['rpm']) ? (int) $defaults['rpm'] : null),
            'rpd' => filled($rpd) ? (int) $rpd : (isset($defaults['rpd']) ? (int) $defaults['rpd'] : null),
            'source' => $admin ? 'admin' : ($defaults ? 'default' : 'unknown'),
        ];
    }

    public function key(string $provider, string $field): string
    {
        return "ai_{$provider}_{$field}";
    }

    private function setting(string $key): mixed
    {
        try {
            return Setting::get($key);
        } catch (Throwable) {
            return null; // settings table not migrated yet (fresh install, early boot)
        }
    }
}
