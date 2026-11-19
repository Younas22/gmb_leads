<?php

namespace App\Console\Commands;

use App\Services\Outreach\FolderSendService;
use Illuminate\Console\Command;

/** Every minute: hand the next due email of each running folder send to the normal send pipeline. */
class ProcessFolderSends extends Command
{
    protected $signature = 'outreach:folder-sends';

    protected $description = 'Send the next due AI email of each running lead-folder send (one per run, so emails stay spaced)';

    public function handle(FolderSendService $service): int
    {
        $n = $service->processDue();
        $this->info("Queued {$n} email(s).");

        return self::SUCCESS;
    }
}
