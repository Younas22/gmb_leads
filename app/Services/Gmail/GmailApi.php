<?php

namespace App\Services\Gmail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Gmail API users.messages.send with conservative error classification. */
class GmailApi
{
    /**
     * @return array{id: string, threadId: ?string}
     * @throws GmailException
     */
    public function send(string $accessToken, string $raw): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout((int) config('gmail.timeout'))
                ->connectTimeout((int) config('gmail.connect_timeout'))
                ->post(config('gmail.endpoints.send'), ['raw' => $raw]);
        } catch (ConnectionException $e) {
            throw new GmailException(self::connectFailureIsSafe($e) ? GmailException::CONNECT_FAILED : GmailException::AMBIGUOUS, null, $e);
        }

        $status = $response->status();

        if ($response->successful()) {
            $id = (string) $response->json('id');
            if ($id === '') {
                throw new GmailException(GmailException::AMBIGUOUS, $status);
            }

            return ['id' => $id, 'threadId' => $response->json('threadId')];
        }

        $reason = (string) ($response->json('error.errors.0.reason') ?? $response->json('error.status') ?? '');

        throw new GmailException(match (true) {
            $status === 401 => GmailException::AUTH,
            $status === 429,
            $status === 403 && in_array($reason, ['rateLimitExceeded', 'userRateLimitExceeded', 'dailyLimitExceeded', 'RESOURCE_EXHAUSTED'], true) => GmailException::RATE_LIMITED,
            $status === 403 && in_array($reason, ['insufficientPermissions', 'ACCESS_TOKEN_SCOPE_INSUFFICIENT'], true) => GmailException::MISSING_SCOPE,
            $status === 403 => GmailException::PERMISSION,
            $status >= 400 && $status < 500 => GmailException::INVALID_REQUEST,
            // 5xx: Google may or may not have sent it — never retry automatically
            default => GmailException::AMBIGUOUS,
        }, $status);
    }

    /**
     * Only failures that happen before any data reaches Google are safe to retry:
     * DNS (cURL 6), connection refused (7), connect timeout, TLS handshake (35).
     */
    private static function connectFailureIsSafe(ConnectionException $e): bool
    {
        $m = strtolower($e->getMessage());

        return str_contains($m, 'curl error 6:') || str_contains($m, 'curl error 7:') || str_contains($m, 'curl error 35:')
            || str_contains($m, 'connection timed out') || str_contains($m, 'could not resolve host') || str_contains($m, 'failed to connect');
    }
}
