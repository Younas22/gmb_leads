<?php

namespace App\Console\Commands;

use App\Models\EmailTrackingEvent;
use App\Models\EmailTrackingLink;
use App\Models\EmailTrackingSetting;
use App\Models\OutboundEmail;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Retention for link-tracking data:
 *  - click events older than the sender's retention period (30/90/180 days) are deleted;
 *  - tracked links older than link_lifetime_days are deleted (the link then shows
 *    a neutral "no longer available" page).
 * Works in small batches so it stays light on shared hosting.
 */
class PruneEmailTracking extends Command
{
    protected $signature = 'email:prune-tracking';

    protected $description = 'Delete link-tracking data past its retention period';

    public function handle(): int
    {
        $batch = max(100, (int) config('email_tracking.cleanup_batch', 1000));
        $default = (int) config('email_tracking.default_retention_days', 90);
        $deletedEvents = 0;

        // Senders with their own retention setting
        $groups = EmailTrackingSetting::query()->get(['user_id', 'retention_days'])
            ->groupBy(fn (EmailTrackingSetting $s) => $s->retentionDays());

        foreach ($groups as $days => $settings) {
            $userIds = $settings->pluck('user_id')->all();
            $deletedEvents += $this->deleteClicks(fn (Builder $q) => $q->whereIn('user_id', $userIds), (int) $days, $batch);
        }

        // Everyone else (and emails whose sender was deleted): the default period
        $withSetting = EmailTrackingSetting::query()->pluck('user_id')->all();
        $deletedEvents += $this->deleteClicks(fn (Builder $q) => $q->where(fn ($w) => $w->whereNull('user_id')->orWhereNotIn('user_id', $withSetting ?: [0])), $default, $batch);

        // Links past their lifetime
        $cutoff = now()->subDays((int) config('email_tracking.link_lifetime_days', 365));
        $deletedLinks = 0;
        do {
            $ids = EmailTrackingLink::where('created_at', '<', $cutoff)->limit($batch)->pluck('id');
            $deletedLinks += $ids->isEmpty() ? 0 : EmailTrackingLink::whereIn('id', $ids)->delete();
        } while ($ids->count() === $batch);

        $this->info("Deleted {$deletedEvents} click event(s) and {$deletedLinks} expired link(s).");

        return self::SUCCESS;
    }

    private function deleteClicks(callable $emailScope, int $days, int $batch): int
    {
        $cutoff = now()->subDays($days);
        $deleted = 0;

        do {
            $ids = EmailTrackingEvent::where('event_type', EmailTrackingEvent::LINK_CLICK)
                ->where('occurred_at', '<', $cutoff)
                ->whereIn('outbound_email_id', $emailScope(OutboundEmail::query())->select('id'))
                ->limit($batch)->pluck('id');
            $deleted += $ids->isEmpty() ? 0 : EmailTrackingEvent::whereIn('id', $ids)->delete();
        } while ($ids->count() === $batch);

        return $deleted;
    }
}
