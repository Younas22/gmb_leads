<?php

namespace App\Services\Resend;

use App\Models\EmailAuditLog;
use App\Models\ResendIntegration;
use App\Models\ResendWebhookConfig;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Per-customer webhook endpoints and Svix signature checks.
 *
 * Resend signs webhooks with Svix. Signed content is "{svix-id}.{svix-timestamp}.{raw body}",
 * HMAC-SHA256 with the base64-decoded part of the "whsec_..." secret; the svix-signature
 * header holds one or more space-separated "v1,<base64 signature>" values.
 */
class ResendWebhookService
{
    public function config(ResendIntegration $integration): ResendWebhookConfig
    {
        return ResendWebhookConfig::firstOrCreate(
            ['integration_id' => $integration->id],
            ['owner_user_id' => $integration->owner_user_id, 'endpoint_token' => Str::random(48), 'status' => ResendWebhookConfig::NEEDS_SETUP]
        );
    }

    /** Create the webhook in the customer's Resend account (after explicit confirmation). */
    public function createRemote(User $user, ResendIntegration $integration): ?string
    {
        $config = $this->config($integration);
        $url = $config->endpointUrl();
        if (!str_starts_with($url, 'https://') && app()->environment('production')) {
            return 'The webhook address must use https. Check APP_URL.';
        }

        try {
            $client = ResendConnectionService::client($integration);
            if ($config->provider_webhook_id) {
                try {
                    $client->deleteWebhook($config->provider_webhook_id);
                } catch (ResendException $e) {
                    if ($e->reason !== ResendException::NOT_FOUND) {
                        throw $e;
                    }
                }
            }
            $created = $client->createWebhook($url, (array) config('resend_user.webhook_events'));
        } catch (ResendException $e) {
            return $e->getMessage();
        }

        if (empty($created['id']) || !self::validSecret((string) ($created['signing_secret'] ?? ''))) {
            return 'Resend created the webhook but did not return a signing secret. Copy it from the Resend dashboard and paste it below.';
        }

        $config->forceFill([
            'provider_webhook_id' => (string) $created['id'],
            'signing_secret' => (string) $created['signing_secret'],
            'status' => ResendWebhookConfig::ACTIVE,
            'last_failure_at' => null,
            'last_failure_reason' => null,
        ])->save();
        EmailAuditLog::record($integration->owner_user_id, $user->id, 'resend_webhook_created');

        return null;
    }

    /** Manual setup: the customer created the webhook in Resend and pastes its secret. */
    public function setSecret(User $user, ResendIntegration $integration, string $secret): ?string
    {
        $secret = trim($secret);
        if (!self::validSecret($secret)) {
            return 'Paste the signing secret exactly as shown in Resend (it starts with "whsec_").';
        }
        $this->config($integration)->forceFill(['signing_secret' => $secret, 'status' => ResendWebhookConfig::ACTIVE])->save();
        EmailAuditLog::record($integration->owner_user_id, $user->id, 'resend_webhook_secret_set');

        return null;
    }

    public static function validSecret(string $secret): bool
    {
        if (!str_starts_with($secret, 'whsec_')) {
            return false;
        }
        $raw = base64_decode(substr($secret, 6), true);

        return $raw !== false && strlen($raw) >= 16;
    }

    /** @return string|null failure reason, null when the signature is valid */
    public static function verify(string $secret, ?string $id, ?string $timestamp, ?string $signatureHeader, string $rawBody): ?string
    {
        if (!$id || !$timestamp || !$signatureHeader) {
            return 'missing_headers';
        }
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > (int) config('resend_user.webhook_tolerance_seconds', 300)) {
            return 'timestamp_out_of_range';
        }
        if (!self::validSecret($secret)) {
            return 'no_secret';
        }

        $key = base64_decode(substr($secret, 6), true);
        $expected = base64_encode(hash_hmac('sha256', $id . '.' . $timestamp . '.' . $rawBody, $key, true));

        foreach (preg_split('/\s+/', trim($signatureHeader)) as $part) {
            [$version, $signature] = array_pad(explode(',', $part, 2), 2, '');
            if ($version === 'v1' && hash_equals($expected, $signature)) {
                return null;
            }
        }

        return 'bad_signature';
    }

    /** Only what processing needs. No IP addresses, user agents, bodies or headers. */
    /** "Sara Khan <Sara@Clinic.com>" -> "sara@clinic.com" (null when not an address). */
    private static function address(string $value): ?string
    {
        if (preg_match('/<([^>]+)>/', $value, $m)) {
            $value = $m[1];
        }
        $value = mb_strtolower(trim(mb_substr($value, 0, 254)));

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    public static function minimal(array $payload): array
    {
        $data = (array) ($payload['data'] ?? []);
        $to = $data['to'] ?? null;
        $to = is_array($to) ? ($to[0] ?? null) : $to;

        return array_filter([
            'type' => (string) ($payload['type'] ?? ''),
            'created_at' => $payload['created_at'] ?? null,
            'email_id' => isset($data['email_id']) ? mb_substr((string) $data['email_id'], 0, 64) : null,
            'broadcast_id' => isset($data['broadcast_id']) ? mb_substr((string) $data['broadcast_id'], 0, 64) : null,
            'to' => is_string($to) ? mb_strtolower(trim(mb_substr($to, 0, 254))) : null,
            'bounce_type' => isset($data['bounce']['type']) ? mb_substr((string) $data['bounce']['type'], 0, 40) : null,
            'bounce_sub_type' => isset($data['bounce']['subType']) ? mb_substr((string) $data['bounce']['subType'], 0, 40) : null,
            'click_link' => isset($data['click']['link']) ? mb_substr((string) $data['click']['link'], 0, 500) : null,
            // email.received (a reply to a Resend receiving address): who sent it
            'from' => isset($data['from']) && is_string($data['from']) ? self::address($data['from']) : null,
            // contact.* and domain.* events
            'email' => isset($data['email']) && is_string($data['email']) ? mb_strtolower(mb_substr($data['email'], 0, 254)) : null,
            'unsubscribed' => array_key_exists('unsubscribed', $data) ? (bool) $data['unsubscribed'] : null,
            'id' => isset($data['id']) ? mb_substr((string) $data['id'], 0, 64) : null,
            'status' => isset($data['status']) && is_string($data['status']) ? mb_substr($data['status'], 0, 30) : null,
        ], fn ($v) => $v !== null);
    }
}
