<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Services\Resend\CampaignService;
use App\Services\Resend\ScheduledCampaignGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Cancels a scheduled broadcast when a recipient was suppressed after approval
 * (see ScheduledCampaignGuard). Runs on the same queue as campaign preparation.
 */
class GuardScheduledCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;
    public int $timeout = 60;

    public function __construct(public readonly int $campaignId)
    {
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function handle(ScheduledCampaignGuard $guard, CampaignService $campaigns): void
    {
        $lock = Cache::lock('campaign:' . $this->campaignId, 60);
        if (!$lock->get()) {
            $this->release(10); // the campaign is being prepared or cancelled right now
            return;
        }

        try {
            $campaign = EmailCampaign::with('integration')->find($this->campaignId);
            if ($campaign) {
                $guard->protect($campaign, $campaigns);
            }
        } finally {
            $lock->release();
        }
    }

    /** Resend kept failing: make the risk visible instead of silently leaving it scheduled. */
    public function failed(?Throwable $e): void
    {
        $campaign = EmailCampaign::find($this->campaignId);
        if ($campaign && $campaign->status === EmailCampaign::SCHEDULED) {
            app(ScheduledCampaignGuard::class)->markUnconfirmed($campaign, 'Resend unreachable');
        }
    }
}
