<?php

namespace Tests\Feature\Resend;

use App\Models\MarketingContact;
use App\Models\ResendDomain;
use App\Models\ResendIntegration;
use App\Models\ResendSender;
use App\Models\ResendWebhookConfig;
use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Str;
use Tests\Feature\EmailTracking\TrackingTestCase;

/**
 * Phase 5 helpers. Every Resend endpoint is faked through GmailTestCase's HTTP fake:
 * an unexpected URL fails the test, so no real API call or email can happen.
 */
abstract class ResendTestCase extends TrackingTestCase
{
    public const API = 'https://api.resend.com';
    public const KEY = 're_customerKey_ABCD1234wxyz';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'resend_user.requests_per_second' => 0,
            'services.resend.key' => 're_PLATFORM_secret_never_used',
        ]);
    }

    protected function resendUser(array $features = [], array $userAttrs = []): User
    {
        return $this->trackingSender(array_merge([
            'resend_connection_enabled' => '1',
            'resend_single_sending_enabled' => '1',
            'resend_campaigns_enabled' => '1',
            'resend_emails_daily' => '10',
            'resend_emails_monthly' => '100',
            'resend_contacts_limit' => '100',
            'resend_campaigns_monthly' => '3',
        ], $features), $userAttrs);
    }

    /** @return array{0: ResendIntegration, 1: ResendDomain, 2: ResendSender} */
    protected function connectResend(User $owner, string $status = 'verified'): array
    {
        $integration = new ResendIntegration();
        $integration->forceFill([
            'owner_user_id' => $owner->id, 'api_key' => self::KEY, 'key_last4' => substr(self::KEY, -4),
            'key_permission' => 'full_access', 'status' => 'active', 'connected_at' => now(), 'last_verified_at' => now(),
        ])->save();
        $domain = ResendDomain::create([
            'integration_id' => $integration->id, 'owner_user_id' => $owner->id, 'provider_domain_id' => 'dom_1',
            'name' => 'mail.pixelstudio.example', 'status' => $status, 'region' => 'eu-west-1', 'records' => [], 'present' => true,
        ]);
        $sender = ResendSender::create([
            'owner_user_id' => $owner->id, 'domain_id' => $domain->id, 'from_name' => 'Ayesha',
            'from_email' => 'hello@mail.pixelstudio.example', 'is_default' => true,
        ]);

        return [$integration, $domain, $sender];
    }

    protected function contact(User $owner, string $email = 'info@smiledental.example', array $attrs = []): MarketingContact
    {
        return MarketingContact::create(array_merge([
            'owner_user_id' => $owner->id, 'email' => $email, 'email_hash' => MarketingContact::hashFor($email),
            'first_name' => 'Sara', 'source' => 'manual', 'consent_status' => 'opted_in', 'consent_source' => 'signup_form',
            'consent_at' => now()->subWeek(), 'consent_evidence' => 'Newsletter form on pixelstudio.example on 1 Nov',
        ], $attrs));
    }

    protected function webhookFor(ResendIntegration $integration, ?string $secret = null): array
    {
        $secret ??= 'whsec_' . base64_encode(random_bytes(32));
        $config = ResendWebhookConfig::create([
            'integration_id' => $integration->id, 'owner_user_id' => $integration->owner_user_id,
            'endpoint_token' => Str::random(48), 'signing_secret' => $secret, 'status' => 'active',
        ]);

        return [$config, $secret];
    }

    protected function postWebhook(ResendWebhookConfig $config, string $secret, array $payload, ?string $id = null, ?int $timestamp = null, ?string $signature = null)
    {
        $id ??= 'msg_' . Str::random(20);
        $timestamp ??= time();
        $body = json_encode($payload);
        $key = base64_decode(substr($secret, 6));
        $signature ??= 'v1,' . base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", $key, true));

        return $this->call('POST', '/webhooks/resend/' . $config->endpoint_token, [], [], [], [
            'HTTP_SVIX_ID' => $id, 'HTTP_SVIX_TIMESTAMP' => (string) $timestamp, 'HTTP_SVIX_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    protected function event(string $type, array $data, ?string $createdAt = null): array
    {
        return ['type' => $type, 'created_at' => $createdAt ?? now()->toIso8601String(), 'data' => $data];
    }

    /** All faked requests to api.resend.com. @return list<HttpRequest> */
    protected function resendCalls(string $method = null, string $path = null): array
    {
        return array_values(array_filter($this->google, fn (HttpRequest $r) => str_starts_with((string) $r->url(), self::API)
            && ($method === null || strtoupper($r->method()) === strtoupper($method))
            && ($path === null || strtok((string) $r->url(), '?') === self::API . $path)));
    }

    protected function domainPayload(string $id = 'dom_1', string $name = 'mail.pixelstudio.example', string $status = 'verified'): array
    {
        return [
            'object' => 'domain', 'id' => $id, 'name' => $name, 'status' => $status, 'region' => 'eu-west-1',
            'open_tracking' => false, 'click_tracking' => false,
            'records' => [
                ['record' => 'SPF', 'name' => 'send.mail', 'type' => 'MX', 'ttl' => 'Auto', 'status' => $status === 'verified' ? 'verified' : 'not_started', 'value' => 'feedback-smtp.eu-west-1.amazonses.com', 'priority' => 10],
                ['record' => 'DKIM', 'name' => 'resend._domainkey.mail', 'type' => 'TXT', 'ttl' => 'Auto', 'status' => $status === 'verified' ? 'verified' : 'not_started', 'value' => 'p=MIGfMA0GCSqDKIMtestvalue'],
            ],
        ];
    }
}
