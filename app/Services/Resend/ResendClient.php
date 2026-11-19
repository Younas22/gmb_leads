<?php

namespace App\Services\Resend;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Minimal client for ONE customer's Resend account, using only documented
 * endpoints (https://resend.com/docs/api-reference). Every call carries the
 * customer's own key; the platform key (services.resend.key) is never used here.
 */
class ResendClient
{
    public function __construct(private readonly string $apiKey)
    {
    }

    // ── Domains ─────────────────────────────────────────────────────────
    public function listDomains(): array { return $this->call('get', '/domains')['data'] ?? []; }
    public function getDomain(string $id): array { return $this->call('get', '/domains/' . rawurlencode($id)); }
    public function createDomain(string $name, string $region): array { return $this->call('post', '/domains', ['name' => $name, 'region' => $region]); }
    public function verifyDomain(string $id): array { return $this->call('post', '/domains/' . rawurlencode($id) . '/verify'); }
    public function updateDomain(string $id, array $fields): array { return $this->call('patch', '/domains/' . rawurlencode($id), $fields); }

    // ── Emails ──────────────────────────────────────────────────────────
    /** @return array{id: string} */
    public function sendEmail(array $payload, string $idempotencyKey): array
    {
        return $this->call('post', '/emails', $payload, ['Idempotency-Key' => $idempotencyKey]);
    }

    // ── Webhooks ────────────────────────────────────────────────────────
    /** @return array{id: string, signing_secret: string} */
    public function createWebhook(string $endpoint, array $events): array { return $this->call('post', '/webhooks', ['endpoint' => $endpoint, 'events' => array_values($events)]); }
    public function deleteWebhook(string $id): array { return $this->call('delete', '/webhooks/' . rawurlencode($id)); }

    // ── Contacts, segments, broadcasts ─────────────────────────────────
    public function createSegment(string $name): array { return $this->call('post', '/segments', ['name' => $name]); }
    public function createContact(array $payload): array { return $this->call('post', '/contacts', $payload); }
    public function addContactToSegment(string $contactIdOrEmail, string $segmentId): array
    {
        return $this->call('post', '/contacts/' . rawurlencode($contactIdOrEmail) . '/segments/' . rawurlencode($segmentId));
    }
    public function removeContactFromSegment(string $contactIdOrEmail, string $segmentId): array
    {
        return $this->call('delete', '/contacts/' . rawurlencode($contactIdOrEmail) . '/segments/' . rawurlencode($segmentId));
    }
    public function createBroadcast(array $payload): array { return $this->call('post', '/broadcasts', $payload); }
    public function deleteBroadcast(string $id): array { return $this->call('delete', '/broadcasts/' . rawurlencode($id)); }

    private function http(array $headers = []): PendingRequest
    {
        return Http::baseUrl((string) config('resend_user.base_url'))
            ->withToken($this->apiKey)
            ->withHeaders(array_merge(['User-Agent' => (string) config('resend_user.user_agent')], $headers))
            ->acceptJson()
            ->timeout((int) config('resend_user.timeout', 15))
            ->connectTimeout((int) config('resend_user.connect_timeout', 5));
    }

    private function call(string $method, string $path, array $body = [], array $headers = []): array
    {
        try {
            $request = $this->http($headers);
            $response = $method === 'get' ? $request->get($path) : $request->{$method}($path, $body);
        } catch (ConnectionException) {
            throw new ResendException(ResendException::NETWORK);
        }

        if ($response->successful()) {
            return (array) $response->json();
        }

        throw self::toException($response);
    }

    public static function toException(Response $response): ResendException
    {
        $status = $response->status();
        $name = (string) ($response->json('name') ?? '');
        // Provider messages can describe the problem (e.g. unverified domain); keep them short
        // and never include anything we sent.
        $message = mb_substr(strip_tags((string) ($response->json('message') ?? '')), 0, 200);

        $reason = match (true) {
            $name === 'restricted_api_key' && $status === 401 => ResendException::RESTRICTED,
            in_array($name, ['missing_api_key', 'suspended_api_key'], true), $name === 'restricted_api_key',
            $status === 401, $status === 403 && $name !== 'validation_error' && $name !== 'email_above_quota' && $name !== 'invalid_permission' => ResendException::INVALID_KEY,
            in_array($name, ['daily_quota_exceeded', 'monthly_quota_exceeded', 'email_above_quota'], true) => ResendException::QUOTA,
            $status === 429 => ResendException::RATE_LIMIT,
            $status === 404 => ResendException::NOT_FOUND,
            $status === 409 => ResendException::CONFLICT,
            $status >= 500 => ResendException::SERVER,
            default => ResendException::VALIDATION,
        };

        $safe = in_array($reason, [ResendException::VALIDATION, ResendException::QUOTA], true) && $message !== ''
            ? 'Resend: ' . $message
            : '';

        return new ResendException($reason, $safe, $status, $name ?: null);
    }
}
