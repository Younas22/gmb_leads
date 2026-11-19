<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Exceptions\AiNotConfiguredException;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * OpenRouter (one key, many models; ":free" models and the "openrouter/free" router cost $0)
 * via its OpenAI-compatible Chat Completions API (POST {base_url}/chat/completions).
 * Same contract as the other clients:
 *  - system prompt → first "system" message;
 *  - structured output → response_format json_schema, and provider.require_parameters so
 *    the request is only routed to models that support it (callers validate the JSON anyway);
 *  - effort → reasoning.effort; the key is never logged.
 */
class OpenRouterAiClient implements AiClient
{
    private const EFFORTS = ['none', 'minimal', 'low', 'medium', 'high', 'xhigh'];

    /** @param array{api_key?: ?string, model?: ?string, max_tokens?: int, max_tokens_cap?: int, effort?: ?string, base_url?: string, timeout?: float, connect_timeout?: float} $config */
    public function __construct(private readonly array $config)
    {
    }

    public function name(): string
    {
        return 'openrouter';
    }

    public function model(): ?string
    {
        return $this->config['model'] ?? null;
    }

    public function isConfigured(): bool
    {
        return filled($this->config['api_key'] ?? null) && filled($this->model());
    }

    public function complete(AiRequest $request): AiResponse
    {
        if (!$this->isConfigured()) {
            throw new AiNotConfiguredException();
        }

        $maxTokens = $request->maxTokens ?? (int) ($this->config['max_tokens'] ?? 8192);
        if (!empty($this->config['max_tokens_cap'])) {
            $maxTokens = min($maxTokens, (int) $this->config['max_tokens_cap']);
        }

        $messages = trim($request->system) !== '' ? [['role' => 'system', 'content' => $request->system]] : [];
        foreach ($request->messages as $m) {
            $messages[] = ['role' => $m['role'] === 'assistant' ? 'assistant' : 'user', 'content' => (string) $m['content']];
        }

        $body = ['model' => $this->model(), 'messages' => $messages, 'max_tokens' => $maxTokens];
        // Admin-chosen effort wins over a feature's generic hint
        $effort = filled($this->config['effort'] ?? null) ? (string) $this->config['effort'] : self::effort($request->effort);
        if ($effort !== null) {
            $body['reasoning'] = ['effort' => $effort, 'exclude' => true];
        }
        if ($request->jsonSchema !== null) {
            $body['response_format'] = ['type' => 'json_schema', 'json_schema' => [
                'name' => 'result',
                'strict' => OpenAiAiClient::strictCompatible($request->jsonSchema),
                'schema' => $request->jsonSchema,
            ]];
            $body['provider'] = ['require_parameters' => true];
        }

        try {
            $response = $this->http()->post('/chat/completions', $body);
        } catch (ConnectionException $e) {
            throw new AiProviderException(str_contains(strtolower($e->getMessage()), 'timed out') ? AiProviderException::TIMEOUT : AiProviderException::CONNECTION, $e);
        }

        if (!$response->successful()) {
            throw new AiProviderException(self::errorType($response->status(), $response));
        }

        $data = (array) $response->json();
        // OpenRouter can return an error object with HTTP 200 (e.g. the upstream model failed mid-way)
        if (isset($data['error'])) {
            throw new AiProviderException(self::errorType((int) ($data['error']['code'] ?? 500), null));
        }

        $choice = (array) ($data['choices'][0] ?? []);
        $finish = (string) ($choice['finish_reason'] ?? '');
        $text = (string) ($choice['message']['content'] ?? '');

        if (trim($text) === '' && (filled($choice['message']['refusal'] ?? null) || $finish === 'content_filter')) {
            throw new AiProviderException(AiProviderException::REFUSAL);
        }
        if (trim($text) === '') {
            throw new AiProviderException(AiProviderException::EMPTY_RESPONSE);
        }

        $usage = (array) ($data['usage'] ?? []);

        return new AiResponse(
            text: $text,
            // The model that actually answered (the free router picks one per request)
            model: $data['model'] ?? $this->model(),
            stopReason: match ($finish) {
                'length' => 'max_tokens',
                'stop', '' => 'end_turn',
                default => $finish,
            },
            inputTokens: (int) ($usage['prompt_tokens'] ?? 0),
            outputTokens: (int) ($usage['completion_tokens'] ?? 0),
            cacheReadTokens: (int) ($usage['prompt_tokens_details']['cached_tokens'] ?? 0),
        );
    }

    /**
     * Live key status from GET /key: credit used/limit and the free-model daily allowance
     * (50/day, or 1000/day once $10 of credit has been bought; 20/min either way).
     * Returns null when the key is missing or OpenRouter cannot be reached.
     */
    public function keyInfo(): ?array
    {
        if (blank($this->config['api_key'] ?? null)) {
            return null;
        }
        try {
            $response = $this->http(10)->get('/key');
        } catch (Throwable) {
            return null;
        }
        if (!$response->successful()) {
            return ['error' => self::errorType($response->status(), $response)];
        }
        $d = (array) $response->json('data', []);

        return [
            'is_free_tier' => (bool) ($d['is_free_tier'] ?? true),
            'usage' => (float) ($d['usage'] ?? 0),
            'usage_daily' => (float) ($d['usage_daily'] ?? 0),
            'usage_monthly' => (float) ($d['usage_monthly'] ?? 0),
            'limit' => isset($d['limit']) ? (float) $d['limit'] : null,
            'limit_remaining' => isset($d['limit_remaining']) ? (float) $d['limit_remaining'] : null,
            'free_used' => isset($d['free_model_daily_requests']['used']) ? (int) $d['free_model_daily_requests']['used'] : null,
            'free_limit' => isset($d['free_model_daily_requests']['limit']) ? (int) $d['free_model_daily_requests']['limit'] : null,
            'free_remaining' => isset($d['free_model_daily_requests']['remaining']) ? (int) $d['free_model_daily_requests']['remaining'] : null,
            'expires_at' => $d['expires_at'] ?? null,
        ];
    }

    /** App-wide effort names → OpenRouter reasoning.effort; null = model default. */
    public static function effort(?string $effort): ?string
    {
        return match ($effort) {
            null, '' => null,
            'max' => 'xhigh',
            default => in_array($effort, self::EFFORTS, true) ? $effort : null,
        };
    }

    private function http(?int $timeout = null)
    {
        return Http::baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://openrouter.ai/api/v1'), '/'))
            ->withToken((string) $this->config['api_key'])
            // Optional attribution headers (app name shown in the OpenRouter dashboard)
            ->withHeaders(['X-Title' => (string) config('app.name'), 'HTTP-Referer' => (string) config('app.url')])
            ->acceptJson()
            ->timeout($timeout ?? (int) ceil((float) ($this->config['timeout'] ?? 75)))
            ->connectTimeout((int) ceil((float) ($this->config['connect_timeout'] ?? 10)));
    }

    private static function errorType(int $status, ?Response $response): string
    {
        return match (true) {
            $status === 402 => AiProviderException::BILLING,
            in_array($status, [401, 403], true) => AiProviderException::AUTHENTICATION,
            // Free-model daily/minute cap, or the upstream provider is rate limited
            $status === 429 => AiProviderException::RATE_LIMITED,
            in_array($status, [502, 503], true) => AiProviderException::OVERLOADED,
            $status >= 500 => AiProviderException::SERVER_ERROR,
            $status === 408 => AiProviderException::TIMEOUT,
            in_array($status, [400, 404, 409, 422], true) => AiProviderException::BAD_REQUEST,
            default => AiProviderException::UNKNOWN,
        };
    }
}
