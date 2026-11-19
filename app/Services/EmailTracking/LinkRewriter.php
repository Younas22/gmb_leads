<?php

namespace App\Services\EmailTracking;

/**
 * Finds links in plain-text or HTML bodies and lets a callback replace them.
 * Only the URL itself is changed; every other character stays exactly as written.
 */
class LinkRewriter
{
    private const TEXT_PATTERN = '~https?://[^\s<>"\'`\x00-\x1F\x7F]+~i';

    /**
     * Links that must never be tracked: unsubscribe/opt-out links and anything
     * already pointing at our tracking endpoint.
     */
    public static function isExcluded(string $url, array $exclude = []): bool
    {
        if (in_array($url, $exclude, true)) {
            return true;
        }
        $lower = strtolower($url);

        return str_contains($lower, 'unsubscribe')
            || str_contains($lower, 'opt-out')
            || str_contains($lower, 'optout')
            || preg_match('~/r/[A-Za-z0-9_-]{32}(?:[/?#]|$)~', $url) === 1;
    }

    /** @return list<string> URLs in a plain-text body, in order (trailing punctuation removed) */
    public static function findInText(string $text): array
    {
        preg_match_all(self::TEXT_PATTERN, $text, $m);

        return array_values(array_map(fn ($u) => self::trimTrailing($u)[0], $m[0]));
    }

    /**
     * @param callable(string $url): ?string $replace returns the new URL, or null to keep it
     */
    public static function rewriteText(string $text, callable $replace): string
    {
        return preg_replace_callback(self::TEXT_PATTERN, function ($m) use ($replace) {
            [$url, $trailing] = self::trimTrailing($m[0]);
            $new = $replace($url);

            return ($new ?? $url) . $trailing;
        }, $text);
    }

    /**
     * Rewrites href attributes of <a> tags in HTML. Link text is not changed.
     *
     * @param callable(string $url): ?string $replace
     */
    public static function rewriteHtml(string $html, callable $replace): string
    {
        return preg_replace_callback('~(<a\b[^>]*?\bhref\s*=\s*)(["\'])(.*?)\2~is', function ($m) use ($replace) {
            $url = html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!preg_match('~^https?://~i', $url)) {
                return $m[0];
            }
            $new = $replace($url);

            return $new === null ? $m[0] : $m[1] . $m[2] . htmlspecialchars($new, ENT_QUOTES | ENT_HTML5, 'UTF-8') . $m[2];
        }, $html);
    }

    /** Split "https://x.com/a)." into ["https://x.com/a", ")."] respecting balanced parentheses. */
    private static function trimTrailing(string $url): array
    {
        $trailing = '';
        while ($url !== '') {
            $last = substr($url, -1);
            if (str_contains('.,;:!?\'"]}*', $last)) {
                $trailing = $last . $trailing;
                $url = substr($url, 0, -1);
                continue;
            }
            if ($last === ')' && substr_count($url, '(') < substr_count($url, ')')) {
                $trailing = $last . $trailing;
                $url = substr($url, 0, -1);
                continue;
            }
            break;
        }

        return [$url, $trailing];
    }
}
