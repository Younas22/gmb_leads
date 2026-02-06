<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Services\EmailService;
use Carbon\Carbon;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';
    protected $description = 'Check for expired subscriptions and send notification emails';

    public function handle()
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', Carbon::today())
            ->with(['user', 'package'])
            ->get();

        $count = 0;
        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);

            try {
                $subscriptionData = [
                    'plan_name' => $subscription->package->name,
                    'end_date' => $subscription->end_date->format('F d, Y'),
                ];

                EmailService::sendSubscriptionEnd($subscription->user, $subscriptionData);
                $count++;

                $this->info("Sent expiry email to {$subscription->user->email}");
            } catch (\Exception $e) {
                $this->error("Failed: " . $e->getMessage());
            }
        }

        $this->info("Processed {$count} expired subscriptions.");
        return 0;
    }
}
