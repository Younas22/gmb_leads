<?php

namespace App\Support;

/**
 * Normalizes externally supplied URLs (scraped websites, social links, Google
 * profile links, user input) to plain http(s) URLs.
 *
 * Anything else — javascript:, data:, vbscript:, file:, malformed values — is
 * rejected, so it can never end up in an href or be fetched.
 */
class SafeUrl
{
    /**
     * Return a normalized http(s) URL, or null when the value is not one.
     * Bare domains ("example.com") are treated as https.
     */
    public static function http(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $url = trim($value);
        if ($url === '' || preg_match('/[\x00-\x1F\x7F\s]/', $url)) {
            return null;
        }

        if (!preg_match('/^[a-z][a-z0-9+.\-]*:/i', $url)) {
            $url = str_starts_with($url, '//') ? 'https:' . $url : 'https://' . $url;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }

    /**
     * Same as http(), but returns "#" instead of null — convenient for href attributes.
     */
    public static function href(?string $value): string
    {
        return self::http($value) ?? '#';
    }
}
