<?php

namespace App\Services\EmailTracking;

/**
 * Decides whether a URL may be used as a tracked-link destination.
 * Only plain http(s) URLs to public hostnames qualify; everything else is
 * left untouched in the email (and is never accepted by the redirect).
 */
class SafeUrl
{
    public const MAX_LENGTH = 2048;

    /** @return string|null the URL when it is a safe destination, null otherwise */
    public static function check(string $url): ?string
    {
        if ($url === '' || strlen($url) > self::MAX_LENGTH) {
            return null;
        }
        // No whitespace, control characters, quotes, angle brackets or backslashes
        if (preg_match('/[\x00-\x20\x7F"<>\\\\`]/', $url)) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }
        // user:password@host tricks ("https://trusted.com@evil.example")
        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        if (!str_starts_with(strtolower($url), strtolower($parts['scheme']) . '://')) {
            return null;
        }

        $host = strtolower(trim($parts['host'], '[]'));

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // IP literals: public addresses only
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ? $url : null;
        }

        // ASCII hostnames only (internationalised names must be in punycode), with a dot
        if (!preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9-]{2,63}$/', $host)) {
            return null;
        }
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return null;
        }
        if (isset($parts['port']) && ($parts['port'] < 1 || $parts['port'] > 65535)) {
            return null;
        }

        return $url;
    }
}
