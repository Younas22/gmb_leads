<?php

namespace App\Services\Resend;

use App\Models\User;
use App\Services\PlanFeatures;
use Illuminate\Support\Facades\DB;

/**
 * Atomic usage reservations for Resend, shared by a company and its team members.
 * A row lock on the counter makes concurrent requests queue up, so two requests can
 * never both take the last unit. Reservations are released when nothing was sent.
 *
 *   daily   d:YYYY-MM-DD  resend_emails_daily     (single sends + test sends)
 *   monthly m:YYYY-MM     resend_emails_monthly   (every Resend email incl. campaign recipients)
 *   campaigns c:YYYY-MM   resend_campaigns_monthly
 */
class ResendQuota
{
    public const DAILY = 'resend_emails_daily';
    public const MONTHLY = 'resend_emails_monthly';
    public const CAMPAIGNS = 'resend_campaigns_monthly';

    public static function period(string $feature): string
    {
        return match ($feature) {
            self::DAILY => 'd:' . now()->toDateString(),
            self::MONTHLY => 'm:' . now()->format('Y-m'),
            self::CAMPAIGNS => 'c:' . now()->format('Y-m'),
        };
    }

    public function used(User $owner, string $feature): int
    {
        return (int) DB::table('resend_usage_counters')->where('owner_user_id', $owner->id)->where('period', self::period($feature))->value('used');
    }

    /** -1 unlimited, 0 not included. */
    public function limit(User $user, string $feature): int
    {
        return PlanFeatures::limit($user, $feature);
    }

    /**
     * Reserve $n units of every feature, all or nothing.
     *
     * @param array<string, int> $wanted feature => units
     * @return string|null problem message, or null when reserved
     */
    public function reserve(User $user, array $wanted): ?string
    {
        $owner = $user->getAccountOwner();

        return DB::transaction(function () use ($user, $owner, $wanted) {
            $rows = [];
            foreach ($wanted as $feature => $n) {
                $period = self::period($feature);
                DB::table('resend_usage_counters')->insertOrIgnore([
                    'owner_user_id' => $owner->id, 'period' => $period, 'used' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $row = DB::table('resend_usage_counters')->where('owner_user_id', $owner->id)->where('period', $period)->lockForUpdate()->first();
                $limit = $this->limit($user, $feature);

                if ($limit === 0) {
                    return 'Your plan does not include this Resend usage (' . self::name($feature) . ').';
                }
                if ($limit !== -1 && $row->used + $n > $limit) {
                    return 'This would exceed your plan limit for ' . self::name($feature) . " ({$row->used} of {$limit} used).";
                }
                $rows[$period] = $n;
            }
            foreach ($rows as $period => $n) {
                DB::table('resend_usage_counters')->where('owner_user_id', $owner->id)->where('period', $period)
                    ->update(['used' => DB::raw('used + ' . (int) $n), 'updated_at' => now()]);
            }

            return null;
        });
    }

    /** Give back units that were reserved but not used. Never goes below zero. */
    public function release(int $ownerId, string $feature, int $n, ?string $period = null): void
    {
        if ($n <= 0) {
            return;
        }
        DB::table('resend_usage_counters')->where('owner_user_id', $ownerId)->where('period', $period ?? self::period($feature))
            ->update(['used' => DB::raw('CASE WHEN used >= ' . (int) $n . ' THEN used - ' . (int) $n . ' ELSE 0 END'), 'updated_at' => now()]);
    }

    private static function name(string $feature): string
    {
        return match ($feature) {
            self::DAILY => 'Resend emails per day',
            self::MONTHLY => 'Resend emails per month',
            self::CAMPAIGNS => 'campaigns per month',
            default => $feature,
        };
    }
}
