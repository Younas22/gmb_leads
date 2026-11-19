<?php

namespace App\Services\Resend;

use App\Models\EmailAuditLog;
use App\Models\EmailCampaign;
use App\Models\OutboundEmail;
use App\Models\ResendIntegration;
use App\Models\ResendWebhookConfig;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Connect / check / rotate / disconnect a customer's own Resend API key.
 * The key is validated with GET /domains (documented, read-only). A key with
 * "Sending access" only cannot manage domains, webhooks or campaigns, so it is refused.
 */
class ResendConnectionService
{
    public function __construct(private readonly ResendDomainService $domains)
    {
    }

    public static function client(ResendIntegration $integration): ResendClient
    {
        return new ResendClient((string) $integration->api_key);
    }

    public static function validKeyFormat(string $key): bool
    {
        return (bool) preg_match('/^re_[A-Za-z0-9_\-]{8,200}$/', $key);
    }

    /** @return array{integration: ?ResendIntegration, problem: ?string, account_changed: bool} */
    public function connect(User $user, string $key): array
    {
        $key = trim($key);
        if (!self::validKeyFormat($key)) {
            return ['integration' => null, 'problem' => 'That does not look like a Resend API key (it should start with "re_").', 'account_changed' => false];
        }

        try {
            $remote = (new ResendClient($key))->listDomains();
        } catch (ResendException $e) {
            return ['integration' => null, 'problem' => $e->getMessage(), 'account_changed' => false];
        }

        $owner = $user->getAccountOwner();
        $integration = ResendIntegration::forOwner($owner);

        // Rotation to a key from a DIFFERENT Resend account: domains, senders and the
        // webhook belong to the old account and must be set up again.
        $knownIds = $integration ? $integration->domains()->pluck('provider_domain_id')->all() : [];
        $newIds = array_column($remote, 'id');
        $accountChanged = $integration && $knownIds !== [] && array_intersect($knownIds, $newIds) === [];

        $integration = DB::transaction(function () use ($owner, $user, $integration, $key, $accountChanged) {
            $integration ??= new ResendIntegration(['owner_user_id' => $owner->id]);
            if ($accountChanged) {
                $integration->domains()->delete(); // senders cascade
                $integration->webhook?->forceFill(['provider_webhook_id' => null, 'signing_secret' => null, 'status' => ResendWebhookConfig::NEEDS_SETUP])->save();
            }
            $integration->forceFill([
                'api_key' => $key,
                'key_last4' => substr($key, -4),
                'key_permission' => 'full_access',
                'status' => ResendIntegration::ACTIVE,
                'last_error' => null,
                'last_checked_at' => now(),
                'last_verified_at' => now(),
                'connected_at' => $integration->connected_at ?? now(),
                'connected_by_user_id' => $user->id,
            ])->save();

            return $integration;
        });

        $this->domains->applyList($integration, $remote);
        EmailAuditLog::record($owner->id, $user->id, $integration->wasRecentlyCreated ? 'resend_connected' : 'resend_key_replaced', null, ['account_changed' => $accountChanged]);

        return ['integration' => $integration, 'problem' => null, 'account_changed' => $accountChanged];
    }

    /** Health check: is the stored key still accepted? */
    public function check(ResendIntegration $integration): ?string
    {
        if (!filled($integration->api_key)) {
            return 'Resend is not connected.';
        }

        try {
            $remote = self::client($integration)->listDomains();
        } catch (ResendException $e) {
            $this->markProblem($integration, $e);

            return $e->getMessage();
        }

        $integration->forceFill(['status' => ResendIntegration::ACTIVE, 'last_error' => null, 'last_checked_at' => now(), 'last_verified_at' => now()])->save();
        $this->domains->applyList($integration, $remote);

        return null;
    }

    public function markProblem(ResendIntegration $integration, ResendException $e): void
    {
        $status = match ($e->reason) {
            ResendException::INVALID_KEY => ResendIntegration::INVALID,
            ResendException::RESTRICTED => ResendIntegration::RESTRICTED,
            default => $integration->status,
        };
        $integration->forceFill(['status' => $status, 'last_error' => mb_substr($e->getMessage(), 0, 255), 'last_checked_at' => now()])->save();
    }

    /** @return string|null problem */
    public function disconnect(User $user, ResendIntegration $integration): ?string
    {
        $busy = EmailCampaign::where('resend_integration_id', $integration->id)
            ->whereIn('status', [EmailCampaign::PREPARING, EmailCampaign::SCHEDULED])->exists();
        if ($busy) {
            return 'Cancel your scheduled or preparing campaigns before disconnecting Resend.';
        }

        // Best effort: remove the webhook we created in the customer's Resend account
        $webhook = $integration->webhook;
        if ($webhook?->provider_webhook_id && $integration->isUsable()) {
            try {
                self::client($integration)->deleteWebhook($webhook->provider_webhook_id);
            } catch (ResendException) {
                // The customer can delete it in the Resend dashboard
            }
        }

        DB::transaction(function () use ($integration, $webhook) {
            OutboundEmail::where('resend_integration_id', $integration->id)->where('status', OutboundEmail::STATUS_PENDING)->whereNull('campaign_id')
                ->update(['status' => OutboundEmail::STATUS_CANCELLED, 'cancelled_at' => now(), 'draft_lock' => null, 'updated_at' => now()]);
            $webhook?->forceFill(['provider_webhook_id' => null, 'signing_secret' => null, 'status' => ResendWebhookConfig::NEEDS_SETUP])->save();
            $integration->forceFill(['api_key' => null, 'status' => ResendIntegration::DISCONNECTED, 'last_error' => null])->save();
        });

        EmailAuditLog::record($integration->owner_user_id, $user->id, 'resend_disconnected');

        return null;
    }
}
