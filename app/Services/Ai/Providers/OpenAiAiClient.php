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
 * ChatGPT via the OpenAI Responses API (POST {base_url}/responses), using Laravel's HTTP
 * client (no extra SDK). Same contract as the Claude client:
 *  - system prompt → "instructions", messages → "input";
 *  - structured output → text.format json_schema ("strict" only when the schema allows it;
 *    callers validate the JSON either way);
 *  - nothing is stored at OpenAI ("store": false); the key is never logged.
 */
class OpenAiAiClient implements AiClient
{
    /** @param array{api_key?: ?string, model?: ?string, max_tokens?: int, effort?: ?string, base_url?: string, timeout?: float, connect_timeout?: float} $config */
    public function __construct(private readonly array $config)
    {
    }

    public function name(): string
    {
        return 'openai';
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

        $body = [
            'model' => $this->model(),
            'instructions' => $request->system,
            'input' => array_map(fn (array $m) => ['role' => $m['role'], 'content' => $m['content']], $request->messages),
            'max_output_tokens' => (fn (int $t, ?int $cap) => $cap ? min($t, $cap) : $t)($request->maxTokens ?? (int) ($this->config['max_tokens'] ?? 4096), $this->config['max_tokens_cap'] ?? null),
            'store' => false,
        ];
        $effort = $request->effort ?? ($this->config['effort'] ?? null);
        if (filled($effort)) {
            $body['reasoning'] = ['effort' => $effort];
        }
        if ($request->jsonSchema !== null) {
            $body['text'] = ['format' => [
                'type' => 'json_schema',
                'name' => 'result',
                'schema' => $request->jsonSchema,
                'strict' => self::strictCompatible($request->jsonSchema),
            ]];
        }

        try {
            $response = Http::baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://api.openai.com/v1'), '/'))
                ->withToken((string) $this->config['api_key'])
                ->acceptJson()
                ->timeout((int) ceil((float) ($this->config['timeout'] ?? 75)))
                ->connectTimeout((int) ceil((float) ($this->config['connect_timeout'] ?? 10)))
                ->post('/responses', $body);
        } catch (ConnectionException $e) {
            throw new AiProviderException(str_contains(strtolower($e->getMessage()), 'timed out') ? AiProviderException::TIMEOUT : AiProviderException::CONNECTION, $e);
        }

        if (!$response->successful()) {
            throw new AiProviderException(self::errorType($response));
        }

        $data = (array) $response->json();
        $text = '';
        $refused = false;
        foreach ((array) ($data['output'] ?? []) as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue; // reasoning items etc.
            }
            foreach ((array) ($item['content'] ?? []) as $part) {
                if (($part['type'] ?? null) === 'output_text') {
                    $text .= (string) ($part['text'] ?? '');
                } elseif (($part['type'] ?? null) === 'refusal') {
                    $refused = true;
                }
            }
        }

        $incomplete = (string) ($data['incomplete_details']['reason'] ?? '');
        if (($refused || $incomplete === 'content_filter') && trim($text) === '') {
            throw new AiProviderException(AiProviderException::REFUSAL);
        }
        if (trim($text) === '') {
            throw new AiProviderException(AiProviderException::EMPTY_RESPONSE);
        }

        $usage = (array) ($data['usage'] ?? []);

        return new AiResponse(
            text: $text,
            model: $data['model'] ?? $this->model(),
            // Same meaning as Claude's stop reasons, so callers can detect truncation
            stopReason: $incomplete === 'max_output_tokens' ? 'max_tokens' : ((($data['status'] ?? '') === 'completed') ? 'end_turn' : ($incomplete ?: null)),
            inputTokens: (int) ($usage['input_tokens'] ?? 0),
            outputTokens: (int) ($usage['output_tokens'] ?? 0),
            cacheReadTokens: (int) ($usage['input_tokens_details']['cached_tokens'] ?? 0),
        );
    }

    private static function errorType(Response $response): string
    {
        $code = (string) ($response->json('error.code') ?? '');

        return match (true) {
            in_array($response->status(), [401, 403], true) => AiProviderException::AUTHENTICATION,
            // No credit left on the OpenAI account: retrying will not help
            $response->status() === 429 && $code === 'insufficient_quota' => AiProviderException::BAD_REQUEST,
            $response->status() === 429 => AiProviderException::RATE_LIMITED,
            $response->status() === 503 => AiProviderException::OVERLOADED,
            $response->status() >= 500 => AiProviderException::SERVER_ERROR,
            $response->status() === 408 => AiProviderException::TIMEOUT,
            in_array($response->status(), [400, 404, 409, 422], true) => AiProviderException::BAD_REQUEST,
            default => AiProviderException::UNKNOWN,
        };
    }

    /**
     * OpenAI strict mode needs every object to list all its properties as required and to
     * forbid extra ones. Our schemas follow that for most features; when one does not,
     * the request is sent non-strict and the caller's validation still applies.
     */
    public static function strictCompatible(array $schema): bool
    {
        $type = $schema['type'] ?? null;
        if ($type === 'object' || (is_array($type) && in_array('object', $type, true))) {
            $props = (array) ($schema['properties'] ?? []);
            if (($schema['additionalProperties'] ?? null) !== false) {
                return false;
            }
            $required = (array) ($schema['required'] ?? []);
            sort($required);
            $keys = array_keys($props);
            sort($keys);
            if ($required !== $keys) {
                return false;
            }
            foreach ($props as $p) {
                if (is_array($p) && !self::strictCompatible($p)) {
                    return false;
                }
            }
        }
        if (isset($schema['items']) && is_array($schema['items']) && !self::strictCompatible($schema['items'])) {
            return false;
        }
        foreach (['anyOf', 'oneOf', 'allOf'] as $k) {
            foreach ((array) ($schema[$k] ?? []) as $sub) {
                if (is_array($sub) && !self::strictCompatible($sub)) {
                    return false;
                }
            }
        }

        return true;
    }
}
