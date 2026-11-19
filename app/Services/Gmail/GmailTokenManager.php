<?php

namespace App\Services\Gmail;

use App\Models\ConnectedEmailAccount;

/** Returns a valid access token, refreshing it when needed. */
class GmailTokenManager
{
    public function __construct(private readonly GmailOAuth $oauth)
    {
    }

    /** @throws GmailException */
    public function accessToken(ConnectedEmailAccount $account, bool $forceRefresh = false): string
    {
        if (!$account->isUsable()) {
            throw new GmailException(GmailException::AUTH_REVOKED);
        }
        if (!$account->canSend()) {
            throw new GmailException(GmailException::MISSING_SCOPE);
        }

        $fresh = !$forceRefresh && filled($account->access_token)
            && $account->token_expires_at && $account->token_expires_at->isAfter(now()->addSeconds(60));

        if ($fresh) {
            return $account->access_token;
        }

        try {
            $tokens = $this->oauth->refresh($account->refresh_token);
        } catch (GmailException $e) {
            if ($e->needsReconnect()) {
                $this->markNeedsReconnect($account, $e->getMessage());
            }
            throw $e;
        }

        $account->forceFill([
            'access_token' => $tokens['access_token'],
            'token_expires_at' => now()->addSeconds(max(60, $tokens['expires_in'])),
            'last_error' => null,
        ])->save();

        return $tokens['access_token'];
    }

    public function markNeedsReconnect(ConnectedEmailAccount $account, string $reason): void
    {
        $account->forceFill([
            'status' => ConnectedEmailAccount::STATUS_NEEDS_RECONNECT,
            'access_token' => null,
            'last_error' => mb_substr($reason, 0, 255),
        ])->save();
    }
}
