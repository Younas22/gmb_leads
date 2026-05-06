<?php

namespace App\Jobs;

use App\Models\AffiliateConversion;
use App\Services\AffiliateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAffiliateCommission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public AffiliateConversion $conversion) {}

    public function handle(): void
    {
        // Re-fetch to get latest status (may have been manually approved/rejected)
        $conversion = AffiliateConversion::find($this->conversion->id);

        if ($conversion && $conversion->isPending()) {
            AffiliateService::approveConversion($conversion);
        }
    }
}
