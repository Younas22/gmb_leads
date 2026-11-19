<?php

namespace Tests\Unit\Ai;

use Anthropic\Client;
use App\Services\Ai\AiRequest;
use App\Services\Ai\Exceptions\AiNotConfiguredException;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Providers\AnthropicAiClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

/**
 * Exercises the real Anthropic SDK request/response path against a fake
 * PSR-18 transport. No network calls, no API cost.
 */
class AnthropicAiClientTest extends TestCase
{
    private array $sent = [];

    private function config(array $overrides = []): array
    {
        return array_merge([
            'api_key' => 'test-key-not-real',
            'model' => 'claude-opus-5-5',
            'max_tokens' => 1024,
            'effort' => 'medium',
            'fallbacks' => true,
            'timeout' => 5,
            'connect_timeout' => 2,
            'max_retries' => 0,
        ], $overrides);
    }

    /** @param list<ResponseInterface> $responses */
    private function clientReturning(array $responses, array $config = []): AnthropicAiClient
    {
        $transport = new class($responses, $this->sent) implements ClientInterface {
            public function __construct(private array $responses, private array &$sent) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->sent[] = $request;
                return array_shift($this->responses) ?? new Response(500);
            }
        };

        $sdk = new Client(apiKey: 'test-key-not-real', requestOptions: [
            'transporter' => $transport,
            'maxRetries' => 0,
        ], openTelemetry: false);

        return new AnthropicAiClient($this->config($config), $sdk);
    }

    private function messageResponse(array $overrides = []): Response
    {
        return new Response(200, ['content-type' => 'application/json'], json_encode(array_merge([
            'id' => 'msg_test',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-opus-5-5',
            'content' => [['type' => 'text', 'text' => 'Hello from Claude']],
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 120, 'output_tokens' => 45, 'cache_read_input_tokens' => 10, 'cache_creation_input_tokens' => 5],
        ], $overrides)));
    }

    private function errorResponse(int $status, string $type): Response
    {
        return new Response($status, ['content-type' => 'application/json'], json_encode([
            'type' => 'error',
            'error' => ['type' => $type, 'message' => 'details that must not reach users'],
        ]));
    }

    public function test_successful_completion_is_parsed_with_usage(): void
    {
        $client = $this->clientReturning([$this->messageResponse()]);

        $response = $client->complete(AiRequest::prompt('You are helpful.', 'Say hello'));

        $this->assertSame('Hello from Claude', $response->text);
        $this->assertSame('claude-opus-5-5', $response->model);
        $this->assertSame('end_turn', $response->stopReason);
        $this->assertSame(120, $response->inputTokens);
        $this->assertSame(45, $response->outputTokens);
        $this->assertSame(10, $response->cacheReadTokens);
        $this->assertSame(5, $response->cacheWriteTokens);
        $this->assertFalse($response->wasTruncated());
    }

    public function test_request_uses_configured_model_effort_and_refusal_fallback(): void
    {
        $client = $this->clientReturning([$this->messageResponse()]);
        $client->complete(new AiRequest('System text', [['role' => 'user', 'content' => 'Hi']], maxTokens: 300));

        $this->assertCount(1, $this->sent);
        $request = $this->sent[0];
        $body = json_decode((string) $request->getBody(), true);

        $this->assertStringEndsWith('/v1/messages', $request->getUri()->getPath());
        $this->assertSame('claude-opus-5-5', $body['model']);
        $this->assertSame(300, $body['max_tokens']);
        $this->assertSame('System text', $body['system']);
        $this->assertSame([['role' => 'user', 'content' => 'Hi']], $body['messages']);
        $this->assertSame(['effort' => 'medium'], $body['output_config']);
        $this->assertSame('default', $body['fallbacks']);
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $request->getHeaderLine('anthropic-beta'));
        // The configured key is sent (this SDK version uses the Authorization header)
        $this->assertTrue(
            $request->getHeaderLine('x-api-key') === 'test-key-not-real'
            || $request->getHeaderLine('Authorization') === 'Bearer test-key-not-real'
        );
    }

    public function test_json_schema_is_sent_as_structured_output_format(): void
    {
        $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['a'], 'properties' => ['a' => ['type' => 'string']]];
        $client = $this->clientReturning([$this->messageResponse(['content' => [['type' => 'text', 'text' => '{"a":"x"}']]])]);

        $response = $client->complete(AiRequest::prompt('s', 'u', 500, null, $schema));

        $body = json_decode((string) $this->sent[0]->getBody(), true);
        $this->assertSame(['type' => 'json_schema', 'schema' => $schema], $body['output_config']['format']);
        $this->assertSame('medium', $body['output_config']['effort']);
        $this->assertSame('{"a":"x"}', $response->text);
    }

    public function test_fallbacks_can_be_disabled(): void
    {
        $client = $this->clientReturning([$this->messageResponse()], ['fallbacks' => false]);
        $client->complete(AiRequest::prompt('s', 'u'));

        $body = json_decode((string) $this->sent[0]->getBody(), true);
        $this->assertArrayNotHasKey('fallbacks', $body);
        $this->assertStringNotContainsString('server-side-fallback', $this->sent[0]->getHeaderLine('anthropic-beta'));
    }

    public function test_max_tokens_stop_is_reported_as_truncated(): void
    {
        $client = $this->clientReturning([$this->messageResponse(['stop_reason' => 'max_tokens'])]);

        $this->assertTrue($client->complete(AiRequest::prompt('s', 'u'))->wasTruncated());
    }

    public function test_refusal_is_an_error(): void
    {
        $client = $this->clientReturning([$this->messageResponse(['stop_reason' => 'refusal', 'content' => []])]);

        try {
            $client->complete(AiRequest::prompt('s', 'u'));
            $this->fail('Expected refusal exception');
        } catch (AiProviderException $e) {
            $this->assertSame(AiProviderException::REFUSAL, $e->errorType());
            $this->assertFalse($e->isRetryable());
        }
    }

    public function test_empty_text_is_an_error(): void
    {
        $client = $this->clientReturning([$this->messageResponse(['content' => [['type' => 'text', 'text' => '   ']]])]);

        $this->expectExceptionObject(new AiProviderException(AiProviderException::EMPTY_RESPONSE));
        $client->complete(AiRequest::prompt('s', 'u'));
    }

    public static function httpErrors(): array
    {
        return [
            'rate limited'  => [429, 'rate_limit_error', AiProviderException::RATE_LIMITED, true],
            'overloaded'    => [529, 'overloaded_error', AiProviderException::OVERLOADED, true],
            'server error'  => [500, 'api_error', AiProviderException::SERVER_ERROR, true],
            'bad request'   => [400, 'invalid_request_error', AiProviderException::BAD_REQUEST, false],
            'unknown model' => [404, 'not_found_error', AiProviderException::BAD_REQUEST, false],
            'bad key'       => [401, 'authentication_error', AiProviderException::AUTHENTICATION, false],
            'forbidden'     => [403, 'permission_error', AiProviderException::AUTHENTICATION, false],
        ];
    }

    #[DataProvider('httpErrors')]
    public function test_http_errors_map_to_safe_provider_exceptions(int $status, string $apiType, string $expected, bool $retryable): void
    {
        $client = $this->clientReturning([$this->errorResponse($status, $apiType)]);

        try {
            $client->complete(AiRequest::prompt('s', 'u'));
            $this->fail('Expected provider exception');
        } catch (AiProviderException $e) {
            $this->assertSame($expected, $e->errorType());
            $this->assertSame($retryable, $e->isRetryable());
            // The user-facing message never leaks provider details
            $this->assertStringNotContainsString('details that must not reach users', $e->getMessage());
        }
    }

    public function test_without_api_key_it_is_not_configured_and_never_calls_out(): void
    {
        $client = new AnthropicAiClient($this->config(['api_key' => null]));

        $this->assertFalse($client->isConfigured());
        $this->expectException(AiNotConfiguredException::class);
        $client->complete(AiRequest::prompt('s', 'u'));
    }

    public function test_request_requires_a_message(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AiRequest('system', []);
    }
}
