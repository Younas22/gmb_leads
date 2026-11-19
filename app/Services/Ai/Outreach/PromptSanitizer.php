<?php

namespace App\Services\Ai\Outreach;

/**
 * Cleans untrusted text (website content, scraped lead data) before it is put
 * into a prompt: removes control characters, collapses whitespace, neutralises
 * angle brackets and backticks so the text cannot close our delimiter tags or
 * fake markup, and truncates to a budget.
 */
class PromptSanitizer
{
    public static function text(?string $value, int $maxChars): string
    {
        if ($value === null) {
            return '';
        }

        $value = mb_scrub($value, 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $value) ?? '';
        $value = str_replace(['<', '>', '`'], ['‹', '›', "'"], $value);
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return mb_substr($value, 0, $maxChars);
    }

    /** @param list<string> $items */
    public static function list(array $items, int $maxItems, int $maxChars): string
    {
        $clean = array_values(array_filter(array_map(fn ($v) => self::text(is_string($v) ? $v : '', $maxChars), array_slice($items, 0, $maxItems))));

        return $clean === [] ? '(none)' : implode(' | ', $clean);
    }
}
