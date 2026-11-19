<?php

namespace App\Services\Ai\Exceptions;

use Throwable;

/**
 * The provider call failed. The message is generic and user-safe; the
 * original exception (which may contain request details) is kept as previous.
 */
class AiProviderException extends AiException
{
    public const RATE_LIMITED = 'rate_limited';
    public const OVERLOADED = 'overloaded';
    public const SERVER_ERROR = 'server_error';
    public const TIMEOUT = 'timeout';
    public const CONNECTION = 'connection';
    public const AUTHENTICATION = 'authentication';
    public const BAD_REQUEST = 'bad_request';
    public const BILLING = 'billing';
    public const REFUSAL = 'refusal';
    public const EMPTY_RESPONSE = 'empty_response';
    public const INVALID_OUTPUT = 'invalid_output';
    public const UNKNOWN = 'unknown';

    private const RETRYABLE = [self::RATE_LIMITED, self::OVERLOADED, self::SERVER_ERROR, self::TIMEOUT, self::CONNECTION];

    private const MESSAGES = [
        self::RATE_LIMITED => 'The AI service is busy right now. Please try again in a minute.',
        self::OVERLOADED => 'The AI service is busy right now. Please try again in a minute.',
        self::SERVER_ERROR => 'The AI service had a temporary problem. Please try again shortly.',
        self::TIMEOUT => 'The AI service took too long to respond. Please try again.',
        self::CONNECTION => 'Could not reach the AI service. Please try again shortly.',
        self::AUTHENTICATION => 'AI is not configured correctly on this server. Please contact support.',
        self::BAD_REQUEST => 'The AI request could not be processed.',
        self::BILLING => 'The AI provider account has no credit left (billing). Please contact support.',
        self::REFUSAL => 'The AI declined to answer this request.',
        self::EMPTY_RESPONSE => 'The AI returned an empty answer. Please try again.',
        self::INVALID_OUTPUT => 'The AI answer did not pass our quality checks. Please try again.',
        self::UNKNOWN => 'The AI request failed. Please try again later.',
    ];

    public function __construct(private readonly string $type, ?Throwable $previous = null)
    {
        parent::__construct(self::MESSAGES[$type] ?? self::MESSAGES[self::UNKNOWN], 0, $previous);
    }

    public function errorType(): string
    {
        return $this->type;
    }

    public function isRetryable(): bool
    {
        return in_array($this->type, self::RETRYABLE, true);
    }
}
