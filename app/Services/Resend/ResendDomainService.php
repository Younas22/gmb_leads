<?php

namespace App\Services\Resend;

use App\Models\EmailAuditLog;
use App\Models\ResendDomain;
use App\Models\ResendIntegration;
use App\Models\ResendSender;
use App\Models\User;
use App\Services\PlanFeatures;

/**
 * Sending domains. Status and DNS records are always copied from Resend; nothing
 * is invented and no DNS is changed automatically (the customer adds records at
 * their DNS host, e.g. Hostinger).
 */
class ResendDomainService
{
    public const REGIONS = ['us-east-1', 'eu-west-1', 'sa-east-1', 'ap-northeast-1'];

    /** Store the GET /domains list (no DNS records in it) and fetch details for each. */
    public function applyList(ResendIntegration $integration, array $remote): void
    {
        $seen = [];
        foreach (array_slice($remote, 0, 50) as $item) {
            if (empty($item['id']) || empty($item['name'])) {
                continue;
            }
            $seen[] = (string) $item['id'];
            $domain = $this->upsert($integration, $item);

            // DNS records are only in the single-domain response
            if ($domain->records === null || !$domain->canSend()) {
                try {
                    $this->upsert($integration, ResendConnectionService::client($integration)->getDomain($domain->provider_domain_id));
                } catch (ResendException) {
                    // keep what we have; shown as "refresh failed" on the next manual refresh
                }
            }
        }

        // Domains removed in Resend can no longer be used to send
        $integration->domains()->whereNotIn('provider_domain_id', $seen ?: ['-'])->update(['present' => false, 'updated_at' => now()]);
    }

    public function upsert(ResendIntegration $integration, array $item): ResendDomain
    {
        $domain = ResendDomain::firstOrNew(['integration_id' => $integration->id, 'provider_domain_id' => (string) $item['id']]);
        $domain->fill([
            'owner_user_id' => $integration->owner_user_id,
            'name' => mb_strtolower((string) ($item['name'] ?? $domain->name)),
            'status' => (string) ($item['status'] ?? $domain->status ?? 'not_started'),
            'region' => $item['region'] ?? $domain->region,
            'open_tracking' => (bool) ($item['open_tracking'] ?? $domain->open_tracking),
            'click_tracking' => (bool) ($item['click_tracking'] ?? $domain->click_tracking),
            'present' => true,
            'last_synced_at' => now(),
        ]);
        if (array_key_exists('records', $item) && is_array($item['records'])) {
            $domain->records = array_values(array_map(fn ($r) => array_intersect_key((array) $r, array_flip(['record', 'name', 'type', 'ttl', 'status', 'value', 'priority'])), $item['records']));
        }
        $domain->save();

        return $domain;
    }

    public static function validDomainName(string $name): bool
    {
        return (bool) preg_match('/^(?=.{4,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $name);
    }

    /** @return string|null problem */
    public function add(User $user, ResendIntegration $integration, string $name, string $region): ?string
    {
        $name = mb_strtolower(trim($name));
        if (!self::validDomainName($name)) {
            return 'Enter a domain such as mail.yourcompany.com.';
        }
        if (!in_array($region, self::REGIONS, true)) {
            return 'Choose a valid region.';
        }

        try {
            $created = ResendConnectionService::client($integration)->createDomain($name, $region);
        } catch (ResendException $e) {
            return $e->getMessage();
        }
        $this->upsert($integration, $created);
        EmailAuditLog::record($integration->owner_user_id, $user->id, 'resend_domain_added', null, ['domain' => $name]);

        return null;
    }

    /** Ask Resend to check the DNS records again, then read the result. */
    public function verify(ResendDomain $domain): ?string
    {
        try {
            $client = ResendConnectionService::client($domain->integration);
            $client->verifyDomain($domain->provider_domain_id);
            $this->upsert($domain->integration, $client->getDomain($domain->provider_domain_id));
        } catch (ResendException $e) {
            return $e->getMessage();
        }

        return null;
    }

    public function refresh(ResendDomain $domain): ?string
    {
        try {
            $this->upsert($domain->integration, ResendConnectionService::client($domain->integration)->getDomain($domain->provider_domain_id));
        } catch (ResendException $e) {
            if ($e->reason === ResendException::NOT_FOUND) {
                $domain->forceFill(['present' => false])->save();
            }

            return $e->getMessage();
        }

        return null;
    }

    /**
     * Resend open/click tracking is a per-domain setting. It is only changed when the
     * customer explicitly asks; turning it ON also needs the email_tracking_enabled plan feature.
     */
    public function setTracking(User $user, ResendDomain $domain, bool $open, bool $click): ?string
    {
        if (($open || $click) && !PlanFeatures::enabled($user, 'email_tracking_enabled')) {
            return 'Your plan does not include email tracking.';
        }

        try {
            ResendConnectionService::client($domain->integration)->updateDomain($domain->provider_domain_id, ['open_tracking' => $open, 'click_tracking' => $click]);
        } catch (ResendException $e) {
            return $e->getMessage();
        }
        $domain->forceFill(['open_tracking' => $open, 'click_tracking' => $click])->save();
        EmailAuditLog::record($domain->owner_user_id, $user->id, 'resend_domain_tracking', null, ['domain' => $domain->name, 'open' => $open, 'click' => $click]);

        return null;
    }

    /** @return array{sender: ?ResendSender, problem: ?string} */
    public function addSender(ResendDomain $domain, string $name, string $localPart, ?string $replyTo): array
    {
        if (!$domain->canSend()) {
            return ['sender' => null, 'problem' => 'Verify the domain in Resend before adding a sender on it.'];
        }
        $localPart = mb_strtolower(trim($localPart));
        if (!preg_match('/^[a-z0-9](?:[a-z0-9._+-]{0,62}[a-z0-9])?$/', $localPart)) {
            return ['sender' => null, 'problem' => 'Use a simple address such as "hello" or "sales.team".'];
        }
        $email = $localPart . '@' . $domain->name;
        if ($replyTo !== null && $replyTo !== '' && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            return ['sender' => null, 'problem' => 'The reply-to address is not valid.'];
        }
        if (ResendSender::where('owner_user_id', $domain->owner_user_id)->where('from_email', $email)->exists()) {
            return ['sender' => null, 'problem' => 'That sender already exists.'];
        }

        $sender = ResendSender::create([
            'owner_user_id' => $domain->owner_user_id,
            'domain_id' => $domain->id,
            'from_name' => mb_substr(trim(str_replace(['"', '<', '>', "\r", "\n"], '', $name)), 0, 100),
            'from_email' => $email,
            'reply_to' => $replyTo ?: null,
            'is_default' => !ResendSender::where('owner_user_id', $domain->owner_user_id)->exists(),
        ]);

        return ['sender' => $sender, 'problem' => null];
    }

    /** Senders this account may use right now (verified, still present domains only). */
    public static function usableSenders(User $owner)
    {
        return ResendSender::where('owner_user_id', $owner->id)->with('domain')->get()->filter(fn (ResendSender $s) => $s->canSend())->values();
    }
}
