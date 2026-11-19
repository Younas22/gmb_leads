<?php

namespace App\Support;

/**
 * Main skill → sub skills catalog (config/skills.php) for the AI Sales Profile.
 * The sub skill is what gets saved as a skill's title.
 */
class SkillCatalog
{
    /** @return array<string, list<string>> */
    public static function all(): array
    {
        return config('skills.catalog', []);
    }

    public static function min(): int
    {
        return (int) config('skills.min', 3);
    }

    public static function max(): int
    {
        return (int) config('skills.max', 10);
    }

    public static function isValid(string $main, string $sub): bool
    {
        return in_array($sub, self::all()[$main] ?? [], true);
    }

    /** Main skill of a saved sub skill (case-insensitive), or null for a legacy free-text skill. */
    public static function mainOf(?string $sub): ?string
    {
        $needle = mb_strtolower(trim((string) $sub));
        foreach (self::all() as $main => $subs) {
            foreach ($subs as $s) {
                if (mb_strtolower($s) === $needle) {
                    return $main;
                }
            }
        }

        return null;
    }
}
