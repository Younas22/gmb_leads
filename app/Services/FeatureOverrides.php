<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\UserFeatureOverride;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves the feature row that applies to a subscription: the subscription owner's
 * override (admin "Custom features") first, else the package's own row.
 * Works before the migration has run (no locks, no overrides yet).
 * Registered as a scoped singleton, so overrides are read once per request.
 */
class FeatureOverrides
{
    public const DEFAULT_LOCK_MESSAGE = 'This feature is locked on your plan.';

    private static bool $installed = false;

    private static int $notInstalledCheckedAt = 0;

    /** @var array<int, Collection> user id => overrides keyed by feature_key */
    private array $cache = [];

    /**
     * Whether the migration has run. "Yes" is remembered for the process; "no" only for a
     * minute, so long-running queue workers notice the migration without a restart.
     */
    public static function installed(): bool
    {
        if (self::$installed) {
            return true;
        }
        if (self::$notInstalledCheckedAt && time() - self::$notInstalledCheckedAt < 60) {
            return false;
        }

        self::$installed = Schema::hasTable('user_feature_overrides') && Schema::hasColumn('package_features', 'is_locked');
        self::$notInstalledCheckedAt = self::$installed ? 0 : time();

        return self::$installed;
    }

    /** The overrides of one user, keyed by feature_key. */
    public function forUser(int $userId): Collection
    {
        if (!self::installed()) {
            return collect();
        }

        return $this->cache[$userId] ??= UserFeatureOverride::where('user_id', $userId)->get()->keyBy('feature_key');
    }

    public function forget(int $userId): void
    {
        unset($this->cache[$userId]);
    }

    /**
     * The row that applies (an override or a PackageFeature), or null when neither defines the key.
     * Both expose feature_value, is_unlimited, is_locked and lock_message.
     */
    public function row(Subscription $subscription, string $key): ?object
    {
        $override = $this->forUser((int) $subscription->user_id)->get($key);
        if ($override) {
            return $override;
        }

        return $subscription->package?->features->firstWhere('feature_key', $key);
    }

    /** The message to show for a locked row, or null when it is not locked. */
    public static function lockMessageOf(?object $row): ?string
    {
        if (!$row || empty($row->is_locked)) {
            return null;
        }

        $message = trim((string) ($row->lock_message ?? ''));

        return $message !== '' ? $message : self::DEFAULT_LOCK_MESSAGE;
    }
}
