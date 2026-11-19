<?php

namespace App\Services\EmailTracking;

use App\Models\EmailTrackingLink;
use App\Models\EmailTrackingSetting;
use App\Models\OutboundEmail;
use App\Models\User;
use App\Services\PlanFeatures;

/**
 * Replaces eligible links in an outgoing email with opaque tracking redirects.
 *
 *  - runs when the email is queued, BEFORE any Gmail call, inside the same DB
 *    transaction that creates the email: if it fails nothing is queued or sent;
 *  - tokens are 192-bit random values; only their SHA-256 hash is stored and the
 *    token carries no lead, user or Gmail account information;
 *  - unsubscribe links and unsafe or non-http(s) URLs are never changed.
 */
class LinkTracker
{
    /** Tracking applies only when the plan includes it AND the sender opted in. */
    public function enabledFor(User $user): bool
    {
        return PlanFeatures::enabled($user, 'email_tracking_enabled')
            && EmailTrackingSetting::for($user)->link_tracking_enabled;
    }

    /**
     * Links that would be tracked in this body (for the send review screen).
     *
     * @return list<string>
     */
    public function eligibleLinks(string $body, array $exclude = []): array
    {
        $links = [];
        foreach (LinkRewriter::findInText($body) as $url) {
            if (count($links) >= (int) config('email_tracking.max_links_per_email', 20)) {
                break;
            }
            if (!LinkRewriter::isExcluded($url, $exclude) && SafeUrl::check($url) !== null) {
                $links[] = $url;
            }
        }

        return $links;
    }

    /**
     * Create link records for $email and return the body with tracked links.
     *
     * @return array{body: string, count: int}
     */
    public function prepare(OutboundEmail $email, string $body, array $exclude = []): array
    {
        $position = 0;
        $max = (int) config('email_tracking.max_links_per_email', 20);

        $tracked = LinkRewriter::rewriteText($body, function (string $url) use ($email, $exclude, &$position, $max) {
            if ($position >= $max || LinkRewriter::isExcluded($url, $exclude) || SafeUrl::check($url) === null) {
                return null;
            }

            return $this->createLink($email, $url, ++$position);
        });

        return ['body' => $tracked, 'count' => $position];
    }

    /** Same as prepare() for an HTML body (href attributes only). */
    public function prepareHtml(OutboundEmail $email, string $html, array $exclude = []): array
    {
        $position = 0;
        $max = (int) config('email_tracking.max_links_per_email', 20);

        $tracked = LinkRewriter::rewriteHtml($html, function (string $url) use ($email, $exclude, &$position, $max) {
            if ($position >= $max || LinkRewriter::isExcluded($url, $exclude) || SafeUrl::check($url) === null) {
                return null;
            }

            return $this->createLink($email, $url, ++$position);
        });

        return ['body' => $tracked, 'count' => $position];
    }

    private function createLink(OutboundEmail $email, string $url, int $position): string
    {
        $token = self::newToken();

        EmailTrackingLink::create([
            'owner_user_id' => $email->owner_user_id,
            'outbound_email_id' => $email->id,
            'token_hash' => EmailTrackingLink::hashToken($token),
            'original_url' => $url,
            'link_position' => $position,
            'is_active' => true,
        ]);

        // Always on the public APP_URL host (the link outlives this request)
        return \App\Support\CanonicalUrl::make(fn () => route('email.link', ['token' => $token]));
    }

    /** 32 URL-safe characters (24 random bytes). */
    public static function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }
}
