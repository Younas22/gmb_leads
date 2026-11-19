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

/**
 * Google Gemini via the Gemini API generateContent endpoint
 * (POST {base_url}/models/{model}:generateContent), using Laravel's HTTP client.
 * Same contract as the Claude / ChatGPT clients:
 *  - system prompt → systemInstruction, messages → contents (assistant → "model");
 *  - structured output → responseMimeType application/json + responseJsonSchema
 *    (callers validate the JSON either way);
 *  - effort → thinkingConfig.thinkingLevel;
 *  - generateContent is stateless (nothing is stored server-side); the key goes in the
 *    x-goog-api-key header and is never logged.
 */
class GeminiAiClient implements AiClient
{
    /** Thinking levels the Gemini API accepts. Other app-wide effort names are mapped. */
    private const LEVELS = ['minimal', 'low', 'medium', 'high', 'xhigh'];

    /** @param array{api_key?: ?string, model?: ?string, max_tokens?: int, max_tokens_cap?: int, effort?: ?string, base_url?: string, timeout?: float, connect_timeout?: float} $config */
    public function __construct(private readonly array $config)
    {
    }

    public function name(): string
    {
        return 'gemini';
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

        $generation = ['maxOutputTokens' => $maxTokens];
        // Admin-chosen level wins over a feature's generic hint (the admin knows the model's levels)
        $level = filled($this->config['effort'] ?? null) ? (string) $this->config['effort'] : self::level($request->effort);
        if ($level !== null) {
            $generation['thinkingConfig'] = ['thinkingLevel' => $level];
        }
        if ($request->jsonSchema !== null) {
            $generation['responseMimeType'] = 'application/json';
            $generation['responseJsonSchema'] = $request->jsonSchema;
        }

        $body = [
            'contents' => array_map(fn (array $m) => [
                'role' => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) $m['content']]],
            ], $request->messages),
            'generationConfig' => $generation,
        ];
        if (trim($request->system) !== '') {
            $body['systemInstruction'] = ['parts' => [['text' => $request->system]]];
        }

        try {
            $response = Http::baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/'))
                ->withHeaders(['x-goog-api-key' => (string) $this->config['api_key']])
                ->acceptJson()
                ->timeout((int) ceil((float) ($this->config['timeout'] ?? 75)))
                ->connectTimeout((int) ceil((float) ($this->config['connect_timeout'] ?? 10)))
                ->post('/models/' . rawurlencode((string) $this->model()) . ':generateContent', $body);
        } catch (ConnectionException $e) {
            throw new AiProviderException(str_contains(strtolower($e->getMessage()), 'timed out') ? AiProviderException::TIMEOUT : AiProviderException::CONNECTION, $e);
        }

        if (!$response->successful()) {
            throw new AiProviderException(self::errorType($response));
        }

        $data = (array) $response->json();
        if (filled($data['promptFeedback']['blockReason'] ?? null)) {
            throw new AiProviderException(AiProviderException::REFUSAL);
        }

        $candidate = (array) ($data['candidates'][0] ?? []);
        $finish = (string) ($candidate['finishReason'] ?? '');
        $text = '';
        foreach ((array) ($candidate['content']['parts'] ?? []) as $part) {
            if (!empty($part['thought'])) {
                continue; // thought summaries are not part of the answer
            }
            $text .= (string) ($part['text'] ?? '');
        }

        if (trim($text) === '' && in_array($finish, ['SAFETY', 'RECITATION', 'BLOCKLIST', 'PROHIBITED_CONTENT', 'SPII'], true)) {
            throw new AiProviderException(AiProviderException::REFUSAL);
        }
        if (trim($text) === '') {
            throw new AiProviderException(AiProviderException::EMPTY_RESPONSE);
        }

        $usage = (array) ($data['usageMetadata'] ?? []);

        return new AiResponse(
            text: $text,
            model: $data['modelVersion'] ?? $this->model(),
            // Same meaning as Claude's stop reasons, so callers can detect truncation
            stopReason: match ($finish) {
                'MAX_TOKENS' => 'max_tokens',
                'STOP', '' => 'end_turn',
                default => strtolower($finish),
            },
            inputTokens: (int) ($usage['promptTokenCount'] ?? 0),
            // Thinking tokens are billed as output, like the other providers report them
            outputTokens: (int) ($usage['candidatesTokenCount'] ?? 0) + (int) ($usage['thoughtsTokenCount'] ?? 0),
            cacheReadTokens: (int) ($usage['cachedContentTokenCount'] ?? 0),
        );
    }

    /** App-wide effort names (Claude/OpenAI style) → Gemini thinking level; null = model default. */
    public static function level(?string $effort): ?string
    {
        return match ($effort) {
            null, '' => null,
            'none' => 'minimal',
            'xhigh', 'max' => 'high', // xhigh only exists on a few Gemini models
            default => in_array($effort, self::LEVELS, true) ? $effort : null,
        };
    }

    private static function errorType(Response $response): string
    {
        $status = (string) ($response->json('error.status') ?? '');
        $message = strtolower((string) ($response->json('error.message') ?? ''));

        return match (true) {
            // Prepaid credits used up / billing problem: retrying will not help
            $response->status() === 402 || str_contains($message, 'billing') || str_contains($message, 'prepayment') => AiProviderException::BILLING,
            in_array($response->status(), [401, 403], true) => AiProviderException::AUTHENTICATION,
            $response->status() === 400 && ($status === 'PERMISSION_DENIED' || str_contains($message, 'api key')) => AiProviderException::AUTHENTICATION,
            // Free-tier daily/minute quota or paid rate limit
            $response->status() === 429 => AiProviderException::RATE_LIMITED,
            $response->status() === 503 => AiProviderException::OVERLOADED,
            $response->status() >= 500 => AiProviderException::SERVER_ERROR,
            $response->status() === 408 => AiProviderException::TIMEOUT,
            in_array($response->status(), [400, 404, 409, 422], true) => AiProviderException::BAD_REQUEST,
            default => AiProviderException::UNKNOWN,
        };
    }
}
