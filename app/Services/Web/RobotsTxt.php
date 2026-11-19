<?php

namespace App\Services\Web;

use Illuminate\Support\Facades\Cache;

/**
 * Minimal robots.txt support (RFC 9309): user-agent groups, Allow/Disallow
 * with * and $ wildcards, longest match wins. Our token is "CustomerNearMeBot";
 * otherwise the "*" group applies.
 *
 * Missing robots.txt (4xx) = everything allowed. Server errors (5xx) =
 * nothing allowed for now (polite default). Network failure = allowed here;
 * the page fetch itself will then fail and be reported.
 */
class RobotsTxt
{
    public const AGENT_TOKEN = 'customernearmebot';

    public function __construct(private readonly SafeHttpFetcher $fetcher)
    {
    }

    public function isAllowed(string $url, ?float $deadline = null): bool
    {
        $parts = parse_url($url);
        if (empty($parts['host'])) {
            return false;
        }

        $origin = strtolower($parts['scheme'] ?? 'https') . '://' . strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

        $rules = Cache::remember('robots:' . sha1($origin), (int) config('ai.fetcher.robots_cache_seconds', 3600), function () use ($origin, $deadline) {
            return $this->loadRules($origin, $deadline);
        });

        return self::matches($rules, $path);
    }

    /** @return array{mode: string, rules: list<array{0: string, 1: string}>} */
    private function loadRules(string $origin, ?float $deadline): array
    {
        try {
            $result = $this->fetcher->get($origin . '/robots.txt', ['text/plain', 'text/'], $deadline, 200_000);
        } catch (FetchException $e) {
            if ($e->reason === FetchException::RATE_LIMITED) {
                throw $e;
            }
            return ['mode' => 'allow_all', 'rules' => []];
        }

        if ($result->status >= 500) {
            return ['mode' => 'disallow_all', 'rules' => []];
        }
        if ($result->status >= 400 || !$result->isSuccessful()) {
            return ['mode' => 'allow_all', 'rules' => []];
        }

        return ['mode' => 'rules', 'rules' => self::parse($result->body)];
    }

    /** @return list<array{0: string, 1: string}> [directive, pattern] for our group */
    public static function parse(string $content): array
    {
        $groups = [];
        $currentAgents = [];
        $inRules = false;

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            $line = trim(preg_replace('/#.*/', '', $line));
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                if ($inRules) {
                    $currentAgents = [];
                    $inRules = false;
                }
                $currentAgents[] = strtolower($value);
                foreach ($currentAgents as $agent) {
                    $groups[$agent] ??= [];
                }
            } elseif (in_array($field, ['allow', 'disallow'], true)) {
                $inRules = true;
                foreach ($currentAgents as $agent) {
                    $groups[$agent][] = [$field, $value];
                }
            }
        }

        foreach ($groups as $agent => $rules) {
            if ($agent !== '*' && str_contains(self::AGENT_TOKEN, $agent)) {
                return $rules;
            }
        }

        return $groups['*'] ?? [];
    }

    public static function matches(array $rules, string $path): bool
    {
        if (($rules['mode'] ?? 'rules') === 'allow_all') {
            return true;
        }
        if (($rules['mode'] ?? 'rules') === 'disallow_all') {
            return false;
        }

        $best = null;
        $bestLength = -1;

        foreach ($rules['rules'] ?? [] as [$directive, $pattern]) {
            if ($pattern === '') {
                continue; // "Disallow:" with no value allows everything
            }

            $regex = '#^' . str_replace(['\*', '\$'], ['.*', '$'], preg_quote($pattern, '#')) . '#';
            if (preg_match($regex, $path)) {
                $length = strlen($pattern);
                if ($length > $bestLength || ($length === $bestLength && $directive === 'allow')) {
                    $best = $directive;
                    $bestLength = $length;
                }
            }
        }

        return $best !== 'disallow';
    }
}
