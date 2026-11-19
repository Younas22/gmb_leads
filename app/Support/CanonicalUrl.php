<?php

namespace App\Support;

use Illuminate\Routing\UrlGenerator;

/**
 * Builds a URL on the public address in APP_URL (host AND scheme) instead of whichever
 * host/scheme the current request came in on (the same app can be reached on more than
 * one hostname, and behind a proxy the request may look like plain http).
 *
 * Used for URLs that leave the app and must keep working later: links inside emails
 * (unsubscribe, tracked links) and the Resend webhook endpoint. Signed URLs stay valid
 * because they are signed for exactly the URL they point at. OAuth callbacks are NOT
 * routed through here (they must match what is registered with Google).
 */
final class CanonicalUrl
{
    public static function make(callable $build): string
    {
        $root = rtrim((string) config('app.url'), '/');
        if (!preg_match('#^(https?)://[^/\s]+#i', $root, $m)) {
            return $build();
        }

        /** @var UrlGenerator $url */
        $url = app('url');
        // Remember anything forced elsewhere so it is restored exactly
        [$previousRoot, $previousScheme] = (fn () => [$this->forcedRoot, $this->forceScheme])->call($url);

        $url->forceRootUrl($root);
        $url->forceScheme(strtolower($m[1]));
        try {
            return $build();
        } finally {
            $url->forceRootUrl($previousRoot);
            $url->forceScheme($previousScheme ? rtrim($previousScheme, ':/') : null);
        }
    }
}
