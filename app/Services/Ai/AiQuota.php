<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\PlanFeatures;
use InvalidArgumentException;

/**
 * Monthly AI quotas from the plan (package_features), counted per account
 * owner so a company and its team members share one allowance.
 */
class AiQuota
{
    public const PENDING = 'pending';

    /** Package feature key that holds the quota for an AI feature. */
    public function featureKey(string $feature): string
    {
        $key = config("ai.quota_features.{$feature}");
        if (!$key) {
            // Every AI feature must be metered — refuse unknown features instead of running unlimited.
            throw new InvalidArgumentException("Unknown AI feature [{$feature}]. Add it to config('ai.quota_features').");
        }

        return $key;
    }

    /** -1 = unlimited, 0 = not in plan, N = calls per calendar month. */
    public function limit(User $actor, string $feature): int
    {
        return PlanFeatures::limit($actor, $this->featureKey($feature));
    }

    /** Calls counted against this month's quota (successful + in-flight). */
    public function used(User $owner, string $feature): int
    {
        return AiUsageLog::where('owner_user_id', $owner->id)
            ->where('feature', $feature)
            ->whereIn('status', [AiUsageLog::STATUS_SUCCESS, self::PENDING])
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    /** null = unlimited. */
    public function remaining(User $actor, string $feature): ?int
    {
        $limit = $this->limit($actor, $feature);
        if ($limit === -1) {
            return null;
        }

        return max(0, $limit - $this->used($actor->getAccountOwner(), $feature));
    }
}
