<?php

namespace App\Services;

use App\Models\User;

/**
 * Reads plan features from the existing package_features system, falling back
 * to config('ai.feature_defaults') when a package does not define the key.
 * (Team members inherit their company's plan through User::getFeatureValue.)
 */
class PlanFeatures
{
    public static function enabled(User $user, string $key): bool
    {
        $value = $user->getFeatureValue($key, null);

        if ($value === null) {
            return (bool) (config("ai.feature_defaults.{$key}") ?? false);
        }

        return $value === -1 || in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
    }

    /**
     * The message for a feature the admin locked (in the package or for this user), or null.
     * A locked feature is never enabled; the sidebar shows it with a lock and this message.
     */
    public static function lockMessage(User $user, string $key): ?string
    {
        $subscription = $user->activeSubscription();
        if (!$subscription || !$subscription->package) {
            return null;
        }

        return FeatureOverrides::lockMessageOf(app(FeatureOverrides::class)->row($subscription, $key));
    }

    /** -1 = unlimited, 0 = not included, N = limit. */
    public static function limit(User $user, string $key): int
    {
        $value = $user->getFeatureValue($key, null);

        if ($value === null) {
            return (int) (config("ai.feature_defaults.{$key}") ?? 0);
        }

        return $value === -1 ? -1 : max(0, (int) $value);
    }
}
