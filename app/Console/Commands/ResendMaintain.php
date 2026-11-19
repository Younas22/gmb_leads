<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Models\OutboundEmail;
use App\Models\ResendWebhookReceipt;
use App\Services\Resend\ResendEventProcessor;
use App\Services\Resend\ScheduledCampaignGuard;
use Illuminate\Console\Command;

/**
 * Light periodic housekeeping for customer Resend accounts (no API calls):
 *  - re-applies webhook events that arrived before their email row was ready;
 *  - marks scheduled broadcasts whose time has passed as handed to Resend;
 *  - deletes old webhook receipts (minimal metadata only).
 */
class ResendMaintain extends Command
{
    protected $signature = 'resend:maintain';

    protected $description = 'Retry unmatched Resend webhook events, update scheduled campaigns, prune receipts';

    public function handle(ResendEventProcessor $processor): int
    {
        $retried = 0;
        ResendWebhookReceipt::where('status', 'unmatched')->orderBy('id')->limit(500)->get()
            ->each(function (ResendWebhookReceipt $receipt) use ($processor, &$retried) {
                $processor->process($receipt);
                $retried++;
            });

        // Safety net for the immediate check in EmailSuppression::suppress(): any scheduled
        // broadcast that still includes a suppressed / opted-out recipient is cancelled.
        $guarded = 0;
        $guard = app(ScheduledCampaignGuard::class);
        EmailCampaign::where('status', EmailCampaign::SCHEDULED)->whereNotNull('provider_broadcast_id')
            ->where('scheduled_at', '>', now())->get()
            ->each(function (EmailCampaign $campaign) use ($guard, &$guarded) {
                if ($guard->blockedRecipients($campaign)->isNotEmpty()) {
                    ScheduledCampaignGuard::dispatch($campaign->id);
                    $guarded++;
                }
            });

        $due = 0;
        EmailCampaign::where('status', EmailCampaign::SCHEDULED)->where('scheduled_at', '<', now()->subMinutes(15))->get()
            ->each(function (EmailCampaign $campaign) use (&$due) {
                $campaign->forceFill(['status' => EmailCampaign::SUBMITTED])->save();
                OutboundEmail::where('campaign_id', $campaign->id)->where('status', OutboundEmail::STATUS_PENDING)
                    ->update(['status' => OutboundEmail::STATUS_SENT, 'sent_at' => $campaign->scheduled_at, 'updated_at' => now()]);
                $due++;
            });

        $pruned = ResendWebhookReceipt::where('created_at', '<', now()->subDays((int) config('resend_user.receipt_retention_days', 30)))
            ->limit(5000)->delete();

        $this->info("Re-processed {$retried} unmatched event(s), {$guarded} scheduled campaign(s) sent for suppression check, {$due} scheduled campaign(s) handed to Resend, pruned {$pruned} receipt(s).");

        return self::SUCCESS;
    }
}
