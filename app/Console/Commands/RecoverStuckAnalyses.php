<?php

namespace App\Console\Commands;

use App\Models\AiLeadAnalysis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Safety net for shared hosting: if the host kills a worker mid-job and the
 * queued job is lost, an item could stay "pending/analyzing" forever and block
 * re-analysis of that lead (its active_key stays set).
 *
 * Items that have not changed for ai.analysis.stuck_after_minutes AND have no
 * job left in the AI queue are marked failed (retryable from the UI).
 */
class RecoverStuckAnalyses extends Command
{
    protected $signature = 'ai:recover-stuck {--minutes= : Override the stuck threshold}';

    protected $description = 'Close AI analysis items whose background job was lost';

    public function handle(): int
    {
        $minutes = (int) ($this->option('minutes') ?? config('ai.analysis.stuck_after_minutes', 30));
        $cutoff = now()->subMinutes(max(1, $minutes));

        $queuedIds = $this->queuedAnalysisIds();
        $closed = 0;

        AiLeadAnalysis::whereIn('status', AiLeadAnalysis::OPEN_STATUSES)
            ->where('updated_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($items) use ($queuedIds, &$closed) {
                foreach ($items as $item) {
                    if (in_array($item->id, $queuedIds, true)) {
                        continue; // still waiting in the queue: not stuck
                    }
                    $item->markFailed('stuck', 'Background processing was interrupted. Please retry.');
                    $closed++;
                }
            });

        $this->info("Closed {$closed} stuck analysis item(s).");

        return self::SUCCESS;
    }

    /** @return list<int> analysis ids that still have a job in the AI queue */
    private function queuedAnalysisIds(): array
    {
        $connection = config('ai.analysis.connection', 'ai_database');
        if (config("queue.connections.{$connection}.driver") !== 'database') {
            return [];
        }

        $ids = [];
        DB::table(config("queue.connections.{$connection}.table", 'jobs'))
            ->where('queue', config('ai.analysis.queue', 'ai'))
            ->orderBy('id')
            ->chunk(500, function ($jobs) use (&$ids) {
                foreach ($jobs as $job) {
                    $payload = json_decode($job->payload, true);
                    $command = $payload['data']['command'] ?? '';
                    // Serialized job object: analysisId is a public int property
                    if (preg_match('/analysisId";i:(\d+);/', $command, $m)) {
                        $ids[] = (int) $m[1];
                    }
                }
            });

        return $ids;
    }
}
