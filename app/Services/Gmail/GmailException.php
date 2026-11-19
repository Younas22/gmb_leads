<?php

namespace App\Services\Gmail;

use RuntimeException;
use Throwable;

/**
 * A Gmail / Google OAuth failure, classified for safe handling:
 *  - retryable: Google rejected the request (rate limit, connection never made) —
 *    the message was NOT sent, retrying is safe;
 *  - ambiguous: the request may have reached Google (timeout after sending,
 *    5xx) — the message MIGHT have been sent, so it is never retried automatically.
 * Messages are safe to show; provider details stay in $previous.
 */
class GmailException extends RuntimeException
{
    public const NOT_CONFIGURED = 'not_configured';
    public const AUTH_REVOKED = 'auth_revoked';
    public const AUTH = 'auth_error';
    public const MISSING_SCOPE = 'missing_scope';
    public const RATE_LIMITED = 'rate_limited';
    public const INVALID_REQUEST = 'invalid_request';
    public const PERMISSION = 'permission_denied';
    public const CONNECT_FAILED = 'connect_failed';
    public const AMBIGUOUS = 'outcome_unknown';
    public const INVALID_GRANT_RESPONSE = 'invalid_oauth_response';

    private const MESSAGES = [
        self::NOT_CONFIGURED => 'Gmail sending is not configured on this server.',
        self::AUTH_REVOKED => 'Gmail access was revoked or expired. Please reconnect your Gmail account.',
        self::AUTH => 'Gmail could not authorize this request. Please reconnect your Gmail account.',
        self::MISSING_SCOPE => 'Permission to send email was not granted. Please reconnect and allow sending.',
        self::RATE_LIMITED => 'Gmail is limiting sends right now. It will be retried shortly.',
        self::INVALID_REQUEST => 'Gmail rejected this message (for example an invalid recipient).',
        self::PERMISSION => 'Gmail refused to send from this account.',
        self::CONNECT_FAILED => 'Could not reach Gmail. It will be retried shortly.',
        self::AMBIGUOUS => 'We could not confirm whether Gmail sent this message. Check your Gmail "Sent" folder before sending again.',
        self::INVALID_GRANT_RESPONSE => 'Google returned an unexpected response. Please try connecting again.',
    ];

    public function __construct(public readonly string $reason, public readonly ?int $httpStatus = null, ?Throwable $previous = null)
    {
        parent::__construct(self::MESSAGES[$reason] ?? 'Gmail request failed.', 0, $previous);
    }

    public function isRetryable(): bool
    {
        return in_array($this->reason, [self::RATE_LIMITED, self::CONNECT_FAILED], true);
    }

    public function isAmbiguous(): bool
    {
        return $this->reason === self::AMBIGUOUS;
    }

    public function needsReconnect(): bool
    {
        return in_array($this->reason, [self::AUTH_REVOKED, self::AUTH, self::MISSING_SCOPE], true);
    }
}
