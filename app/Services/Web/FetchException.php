<?php

namespace App\Services\Web;

use RuntimeException;

/** A website fetch was refused or failed. The code is safe to store and show. */
class FetchException extends RuntimeException
{
    public const INVALID_URL = 'invalid_url';
    public const BLOCKED_HOST = 'blocked_host';
    public const PRIVATE_ADDRESS = 'private_address';
    public const DNS_FAILED = 'dns_failed';
    public const BAD_PORT = 'bad_port';
    public const TOO_MANY_REDIRECTS = 'too_many_redirects';
    public const TIMEOUT = 'timeout';
    public const CONNECTION_FAILED = 'connection_failed';
    public const UNSUPPORTED_CONTENT = 'unsupported_content';
    public const RATE_LIMITED = 'rate_limited';
    public const BUDGET_EXCEEDED = 'budget_exceeded';
    public const ADDRESS_MISMATCH = 'address_mismatch';

    private const MESSAGES = [
        self::INVALID_URL => 'The website address is not a valid public http(s) URL.',
        self::BLOCKED_HOST => 'This website address cannot be checked.',
        self::PRIVATE_ADDRESS => 'The website address points to a private or reserved network address.',
        self::DNS_FAILED => 'The website domain could not be found.',
        self::BAD_PORT => 'Only standard web ports (80/443) can be checked.',
        self::TOO_MANY_REDIRECTS => 'The website redirected too many times.',
        self::TIMEOUT => 'The website took too long to respond.',
        self::CONNECTION_FAILED => 'The website could not be reached.',
        self::UNSUPPORTED_CONTENT => 'The website did not return an HTML page.',
        self::RATE_LIMITED => 'This website was checked too often recently; it will be retried shortly.',
        self::BUDGET_EXCEEDED => 'The website check ran out of time.',
        self::ADDRESS_MISMATCH => 'The website connection did not match the verified address.',
    ];

    public function __construct(public readonly string $reason, ?\Throwable $previous = null)
    {
        parent::__construct(self::MESSAGES[$reason] ?? 'The website could not be checked.', 0, $previous);
    }
}
