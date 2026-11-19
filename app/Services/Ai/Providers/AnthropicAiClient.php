<?php

namespace App\Services\Ai\Providers;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIException;
use Anthropic\Core\Exceptions\APITimeoutException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\InternalServerException;
use Anthropic\Core\Exceptions\NotFoundException;
use Anthropic\Core\Exceptions\OverloadedException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Exceptions\AiNotConfiguredException;
use App\Services\Ai\Exceptions\AiProviderException;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Claude via the official Anthropic PHP SDK (anthropic-ai/sdk).
 *
 * - API key only from config (ANTHROPIC_API_KEY); never hardcoded or logged.
 * - Timeouts are enforced by the Guzzle transport handed to the SDK.
 * - The SDK retries 408/409/429/5xx and connection errors (max_retries).
 * - Server-side refusal fallback ("default") is on unless disabled in config.
 */
class AnthropicAiClient implements AiClient
{
    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    private ?Client $client = null;

    /**
     * @param array{api_key?: ?string, model?: string, max_tokens?: int, effort?: ?string, fallbacks?: bool, timeout?: float, connect_timeout?: float, max_retries?: int} $config
     */
    public function __construct(private readonly array $config, ?Client $client = null)
    {
        $this->client = $client;
    }

    public function name(): string
    {
        return 'anthropic';
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

        $params = [
            'model' => $this->model(),
            'maxTokens' => self::cap($request->maxTokens ?? (int) ($this->config['max_tokens'] ?? 4096), $this->config['max_tokens_cap'] ?? null),
            'messages' => $request->messages,
            'system' => $request->system,
        ];

        $outputConfig = [];
        $effort = $request->effort ?? ($this->config['effort'] ?? null);
        if ($effort) {
            $outputConfig['effort'] = $effort;
        }
        if ($request->jsonSchema !== null) {
            // Structured outputs: the response text is JSON matching this schema
            $outputConfig['format'] = ['type' => 'json_schema', 'schema' => $request->jsonSchema];
        }
        if ($outputConfig !== []) {
            $params['outputConfig'] = $outputConfig;
        }

        if ($this->config['fallbacks'] ?? true) {
            $params['fallbacks'] = 'default';
            $params['betas'] = [self::FALLBACK_BETA];
        }

        try {
            $message = $this->client()->beta->messages->create(...$params);
        } catch (AuthenticationException|PermissionDeniedException $e) {
            throw new AiProviderException(AiProviderException::AUTHENTICATION, $e);
        } catch (NotFoundException|BadRequestException $e) {
            throw new AiProviderException(AiProviderException::BAD_REQUEST, $e);
        } catch (RateLimitException $e) {
            throw new AiProviderException(AiProviderException::RATE_LIMITED, $e);
        } catch (OverloadedException $e) {
            throw new AiProviderException(AiProviderException::OVERLOADED, $e);
        } catch (InternalServerException $e) {
            throw new AiProviderException(AiProviderException::SERVER_ERROR, $e);
        } catch (APITimeoutException $e) {
            throw new AiProviderException(AiProviderException::TIMEOUT, $e);
        } catch (APIConnectionException $e) {
            throw new AiProviderException(AiProviderException::CONNECTION, $e);
        } catch (APIException $e) {
            throw new AiProviderException(AiProviderException::UNKNOWN, $e);
        }

        if ($message->stopReason === 'refusal') {
            throw new AiProviderException(AiProviderException::REFUSAL);
        }

        $text = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $text .= $block->text;
            }
        }

        if (trim($text) === '') {
            throw new AiProviderException(AiProviderException::EMPTY_RESPONSE);
        }

        $usage = $message->usage;

        return new AiResponse(
            text: $text,
            model: $message->model,
            stopReason: $message->stopReason,
            inputTokens: (int) $usage->inputTokens,
            outputTokens: (int) $usage->outputTokens,
            cacheReadTokens: (int) ($usage->cacheReadInputTokens ?? 0),
            cacheWriteTokens: (int) ($usage->cacheCreationInputTokens ?? 0),
        );
    }

    /** Admin cap (Admin → AI Setup) lowers any per-feature limit. */
    private static function cap(int $tokens, ?int $cap): int
    {
        return $cap ? min($tokens, $cap) : $tokens;
    }

    private function client(): Client
    {
        return $this->client ??= new Client(
            apiKey: $this->config['api_key'],
            requestOptions: [
                'maxRetries' => (int) ($this->config['max_retries'] ?? 2),
                'timeout' => (float) ($this->config['timeout'] ?? 60),
                // The SDK does not enforce timeouts itself; the transport does.
                'transporter' => new GuzzleClient([
                    'timeout' => (float) ($this->config['timeout'] ?? 60),
                    'connect_timeout' => (float) ($this->config['connect_timeout'] ?? 10),
                    'http_errors' => false,
                ]),
            ],
            openTelemetry: false,
        );
    }
}
