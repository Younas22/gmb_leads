<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Setting;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Exceptions\AiException;
use App\Services\Ai\Providers\AnthropicAiClient;
use App\Services\Ai\Providers\GeminiAiClient;
use App\Services\Ai\Providers\OpenAiAiClient;
use App\Services\Ai\Providers\OpenRouterAiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Admin → AI Setup: choose the ONE active AI provider (Claude, ChatGPT, Gemini, OpenRouter) and manage each
 * provider's API key, model, effort and output limit. Keys are stored encrypted and are
 * never sent back to the browser (only the last 4 characters are shown).
 */
class AiSetupController extends Controller
{
    public function __construct(private readonly AiSettings $settings)
    {
    }

    public function index()
    {
        $today = now()->startOfDay();
        $month = now()->subDays(30);

        $providers = [];
        foreach (AiSettings::PROVIDERS as $key => $label) {
            $config = $this->settings->providerConfig($key);
            $providers[$key] = [
                'label' => $label,
                'key_source' => $this->settings->keySource($key),
                'key_last4' => $this->settings->keyLast4($key),
                'model' => $config['model'] ?? null,
                'effort' => $config['effort'] ?? null,
                'max_tokens' => $config['max_tokens'] ?? null,
                'admin' => [
                    'model' => Setting::get($this->settings->key($key, 'model')),
                    'effort' => Setting::get($this->settings->key($key, 'effort')),
                    'max_tokens' => Setting::get($this->settings->key($key, 'max_tokens')),
                ],
                'today' => $this->stats(AiUsageLog::where('provider', $key)->where('created_at', '>=', $today)),
                'usage' => $this->stats(AiUsageLog::where('provider', $key)->where('created_at', '>=', $month)),
                'last_call' => AiUsageLog::where('provider', $key)->latest('id')->first(['status', 'error_type', 'model', 'created_at']),
            ];
        }

        // Usage tab: everything the app sent to any AI provider
        $recent = AiUsageLog::with(['user:id,name,email'])->latest('id')->limit(50)
            ->get(['id', 'user_id', 'feature', 'provider', 'model', 'status', 'error_type', 'input_tokens', 'output_tokens', 'duration_ms', 'created_at']);
        $byFeature = AiUsageLog::where('created_at', '>=', $month)
            ->selectRaw('provider, feature, COUNT(*) as calls, SUM(status = ?) as ok, COALESCE(SUM(input_tokens),0) as tin, COALESCE(SUM(output_tokens),0) as tout, ROUND(AVG(duration_ms)) as avg_ms', [AiUsageLog::STATUS_SUCCESS])
            ->groupBy('provider', 'feature')->orderByDesc('calls')->get();
        $dailyRows = AiUsageLog::where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as calls, SUM(status = ?) as ok', [AiUsageLog::STATUS_SUCCESS])
            ->groupBy('d')->get()->keyBy('d');
        $daily = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $daily[$d] = ['calls' => (int) ($dailyRows[$d]->calls ?? 0), 'ok' => (int) ($dailyRows[$d]->ok ?? 0)];
        }

        return view('admin.ai-setup.index', [
            'providers' => $providers,
            'geminiFree' => $this->geminiFreeUsage(),
            'openRouterKey' => $this->openRouterKeyInfo(),
            'active' => $this->settings->activeProvider(),
            'masterSwitch' => (bool) config('ai.enabled'),
            'totals' => [
                'today' => $this->stats(AiUsageLog::where('created_at', '>=', $today)),
                'month' => $this->stats(AiUsageLog::where('created_at', '>=', $month)),
            ],
            'recent' => $recent,
            'byFeature' => $byFeature,
            'daily' => $daily,
        ]);
    }

    /** calls / ok / failed / tokens for a usage-log query. */
    private function stats($query): array
    {
        $row = $query->selectRaw(
            'COUNT(*) as calls, SUM(status = ?) as ok, SUM(status IN (?, ?)) as failed, COALESCE(SUM(input_tokens),0) as tin, COALESCE(SUM(output_tokens),0) as tout',
            [AiUsageLog::STATUS_SUCCESS, AiUsageLog::STATUS_FAILED, AiUsageLog::STATUS_REFUSED]
        )->first();

        return [
            'calls' => (int) ($row->calls ?? 0),
            'ok' => (int) ($row->ok ?? 0),
            'failed' => (int) ($row->failed ?? 0),
            'tin' => (int) ($row->tin ?? 0),
            'tout' => (int) ($row->tout ?? 0),
        ];
    }

    /** Live OpenRouter key status (free requests left today, credit), cached for a minute. */
    private function openRouterKeyInfo(): ?array
    {
        $config = $this->settings->providerConfig('openrouter');
        if (blank($config['api_key'] ?? null)) {
            return null;
        }

        return Cache::remember('ai-openrouter-key-info:' . substr(hash('sha256', (string) $config['api_key']), 0, 16), 60,
            fn () => (new OpenRouterAiClient($config))->keyInfo());
    }

    public function update(Request $request)
    {
        $rules = ['active_provider' => ['required', Rule::in(array_merge(array_keys(AiSettings::PROVIDERS), [AiSettings::OFF]))]];
        foreach (array_keys(AiSettings::PROVIDERS) as $p) {
            $rules["{$p}.api_key"] = ['nullable', 'string', 'max:400', 'regex:/^\S+$/'];
            $rules["{$p}.remove_key"] = ['nullable', 'boolean'];
            $rules["{$p}.model"] = ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:\-\/]+$/'];
            $rules["{$p}.effort"] = ['nullable', Rule::in(AiSettings::EFFORTS[$p])];
            $rules["{$p}.max_tokens"] = ['nullable', 'integer', 'min:256', 'max:128000'];
        }
        // "Save & make active" button inside a provider tab
        $rules['activate'] = ['nullable', Rule::in(array_keys(AiSettings::PROVIDERS))];
        $rules['tab'] = ['nullable', 'string', 'max:20'];
        $rules['gemini.free_rpm'] = ['nullable', 'integer', 'min:0', 'max:100000'];
        $rules['gemini.free_rpd'] = ['nullable', 'integer', 'min:0', 'max:10000000'];
        $data = $request->validate($rules, ['*.api_key.regex' => 'The API key must not contain spaces.']);

        foreach (array_keys(AiSettings::PROVIDERS) as $p) {
            $in = (array) ($data[$p] ?? []);
            $label = AiSettings::PROVIDERS[$p];
            if (!empty($in['remove_key'])) {
                Setting::set($this->settings->key($p, 'api_key'), '', Setting::TYPE_SECRET, 'ai', "{$label} API key");
            } elseif (filled($in['api_key'] ?? null)) {
                Setting::set($this->settings->key($p, 'api_key'), trim($in['api_key']), Setting::TYPE_SECRET, 'ai', "{$label} API key");
            }
            // Empty = use the .env default again
            Setting::set($this->settings->key($p, 'model'), (string) ($in['model'] ?? ''), 'text', 'ai', "{$label} model");
            Setting::set($this->settings->key($p, 'effort'), (string) ($in['effort'] ?? ''), 'text', 'ai', "{$label} effort");
            Setting::set($this->settings->key($p, 'max_tokens'), (string) ($in['max_tokens'] ?? ''), 'text', 'ai', "{$label} max output tokens");
        }
        // Gemini free-tier limits as shown in AI Studio (empty = approximate defaults)
        Setting::set('ai_gemini_free_rpm', (string) ($data['gemini']['free_rpm'] ?? ''), 'text', 'ai', 'Gemini free tier: requests per minute');
        Setting::set('ai_gemini_free_rpd', (string) ($data['gemini']['free_rpd'] ?? ''), 'text', 'ai', 'Gemini free tier: requests per day');

        // Only a provider that can actually be called may be switched on
        $active = filled($data['activate'] ?? null) ? $data['activate'] : $data['active_provider'];
        if ($active !== AiSettings::OFF) {
            $config = $this->settings->providerConfig($active);
            if (blank($config['api_key'] ?? null) || blank($config['model'] ?? null)) {
                return back()->withErrors(['active_provider' => AiSettings::PROVIDERS[$active] . ' needs an API key and a model before it can be activated.'])->withInput();
            }
        }
        Setting::set('ai_active_provider', $active, 'text', 'ai', 'Active AI provider (only one at a time)');

        Log::info('AI settings updated', ['admin_id' => $request->user()->id, 'active_provider' => $active]);

        $tab = preg_match('/^[a-z]+$/', (string) ($data['tab'] ?? '')) ? '#' . $data['tab'] : '';

        return redirect()->to(route('admin.ai-setup.index') . $tab)->with('success', $active === AiSettings::OFF
            ? 'AI settings saved. AI features are switched off.'
            : 'AI settings saved. Active provider: ' . AiSettings::PROVIDERS[$active] . ' (' . $this->settings->providerConfig($active)['model'] . ').');
    }

    /**
     * Gemini requests counted against Google's free-tier windows: today since midnight
     * Pacific (when Google resets daily quotas) and the last 60 seconds. Rows "rejected"
     * by our own plan quota never reached Google and are not counted.
     */
    private function geminiFreeUsage(): array
    {
        $tz = config('app.timezone');
        $dayStart = now('America/Los_Angeles')->startOfDay()->setTimezone($tz);
        $sent = fn () => AiUsageLog::where('provider', 'gemini')->where('status', '!=', AiUsageLog::STATUS_REJECTED);

        $today = $sent()->where('created_at', '>=', $dayStart)
            ->selectRaw('COUNT(*) as calls, SUM(status = ?) as ok, COALESCE(SUM(input_tokens),0) as tin, COALESCE(SUM(output_tokens),0) as tout', [AiUsageLog::STATUS_SUCCESS])
            ->first();
        $lastError = $sent()->where('created_at', '>=', $dayStart)->where('status', AiUsageLog::STATUS_FAILED)
            ->latest('created_at')->value('error_type');

        return $this->settings->geminiFreeTier() + [
            'today' => (int) ($today->calls ?? 0),
            'today_ok' => (int) ($today->ok ?? 0),
            'today_tokens_in' => (int) ($today->tin ?? 0),
            'today_tokens_out' => (int) ($today->tout ?? 0),
            'minute' => $sent()->where('created_at', '>=', now()->subMinute())->count(),
            'last_error' => $lastError,
            'resets_at' => now('America/Los_Angeles')->addDay()->startOfDay()->setTimezone($tz),
            'model' => $this->settings->providerConfig('gemini')['model'] ?? null,
            'admin_rpm' => Setting::get('ai_gemini_free_rpm'),
            'admin_rpd' => Setting::get('ai_gemini_free_rpd'),
        ];
    }

    /** What the admin should do about a failed connection test (user-facing messages say "contact support"). */
    private function adminHint(string $provider, ?string $type): ?string
    {
        return match ([$provider, $type]) {
            ['gemini', 'billing'] => 'Google: "prepayment credits depleted" (402). This key\'s Google project is linked to a paid billing account with $0 balance, so the free tier does not apply. Fix: add credit in AI Studio → Billing, OR unlink billing from the project, OR create a key in a new project without billing (aistudio.google.com/apikey → Create key in new project).',
            ['openrouter', 'billing'] => 'OpenRouter: not enough credit for this model (402). Use a free model (id ending in :free, or openrouter/free) or add credit.',
            ['openai', 'billing'], ['anthropic', 'billing'] => 'The provider account has no credit left (402). Add credit in the provider console.',
            [$provider, 'authentication'] => 'The key was rejected (invalid, revoked or disabled). Paste a new key and save.',
            [$provider, 'rate_limited'] => 'Rate or daily limit reached (429). Free tiers reset daily; try again later or use another model.',
            [$provider, 'bad_request'] => 'The request was rejected, most often because the model id does not exist for this key. Pick one of the suggested models.',
            default => null,
        };
    }

    /** One small paid request with the SAVED settings of one provider (active or not). */
    public function test(string $provider)
    {
        abort_unless(isset(AiSettings::PROVIDERS[$provider]), 404);
        $config = $this->settings->providerConfig($provider);
        $client = match ($provider) {
            'openai' => new OpenAiAiClient($config),
            'gemini' => new GeminiAiClient($config),
            'openrouter' => new OpenRouterAiClient($config),
            default => new AnthropicAiClient($config),
        };
        if (!$client->isConfigured()) {
            return response()->json(['ok' => false, 'message' => 'Save an API key and a model first.']);
        }

        $started = microtime(true);
        try {
            $response = $client->complete(AiRequest::prompt('You are a connection test. Reply with exactly: OK', 'Connection test', 1024, 'low'));
        } catch (AiException $e) {
            $type = method_exists($e, 'errorType') ? $e->errorType() : null;

            return response()->json(['ok' => false, 'message' => $this->adminHint($provider, $type) ?? $e->getMessage(), 'error' => $type]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Connected. Model ' . ($response->model ?? $client->model()) . ' replied in ' . (int) round((microtime(true) - $started) * 1000) . ' ms.',
        ]);
    }
}
