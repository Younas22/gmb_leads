<?php

namespace App\Services\Resend;

use RuntimeException;

/**
 * A failed call to a customer's Resend account. Messages are safe to show:
 * they never contain the API key or provider response bodies.
 */
class ResendException extends RuntimeException
{
    public const INVALID_KEY = 'invalid_key';           // missing / invalid / revoked / suspended key
    public const RESTRICTED = 'restricted_key';         // sending_access key used for management calls
    public const VALIDATION = 'validation';             // 400/422, e.g. unverified from-domain
    public const NOT_FOUND = 'not_found';
    public const CONFLICT = 'conflict';                 // 409 (idempotency / locked resource)
    public const QUOTA = 'quota';                       // Resend daily / monthly quota
    public const RATE_LIMIT = 'rate_limit';
    public const SERVER = 'server_error';               // 5xx: request may or may not have been applied
    public const NETWORK = 'network';                   // timeout / connection lost: outcome unknown

    public function __construct(public readonly string $reason, string $message = '', public readonly ?int $httpStatus = null, public readonly ?string $providerName = null)
    {
        parent::__construct($message !== '' ? $message : self::defaultMessage($reason));
    }

    public static function defaultMessage(string $reason): string
    {
        return match ($reason) {
            self::INVALID_KEY => 'Resend rejected the API key. It may have been deleted or revoked. Connect a new key.',
            self::RESTRICTED => 'This API key only has "Sending access". Create a key with "Full access" in Resend so domains, webhooks and campaigns can be managed.',
            self::VALIDATION => 'Resend did not accept the request.',
            self::NOT_FOUND => 'Resend could not find that item.',
            self::CONFLICT => 'Resend is already processing the same request.',
            self::QUOTA => 'Your Resend account has reached its sending quota.',
            self::RATE_LIMIT => 'Resend rate limit reached. Please try again shortly.',
            self::SERVER => 'Resend had a temporary problem.',
            default => 'Resend could not be reached.',
        };
    }

    /** The request may have been applied (timeout or 5xx): never retry a send automatically. */
    public function isAmbiguous(): bool
    {
        return in_array($this->reason, [self::SERVER, self::NETWORK], true);
    }

    /** Clearly not applied and worth retrying later. */
    public function isRetryable(): bool
    {
        return $this->reason === self::RATE_LIMIT;
    }
}
