<?php

namespace App\Jobs;

use App\Models\ResendWebhookReceipt;
use App\Services\Resend\ResendEventProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Applies one verified Resend webhook. Database work only; no outbound calls. */
class ProcessResendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public readonly int $receiptId)
    {
    }

    public function handle(ResendEventProcessor $processor): void
    {
        $receipt = ResendWebhookReceipt::find($this->receiptId);
        if (!$receipt || in_array($receipt->status, ['processed', 'ignored'], true)) {
            return;
        }

        $processor->process($receipt);
    }
}
