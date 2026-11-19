<?php

namespace App\Console\Commands;

use Anthropic\Client;
use App\Services\Ai\Contracts\AiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Shows the AI configuration (never the key) and the AI queue backlog.
 * --check-model asks the provider's Models API whether the configured model id
 * exists for this key. That is a free metadata call, not a generation, and it
 * only runs when the flag is given.
 */
class AiStatus extends Command
{
    protected $signature = 'ai:status {--check-model : Verify the configured model id with the provider (network call, no token cost)}';

    protected $description = 'Show AI provider configuration and queue backlog';

    public function handle(AiClient $client): int
    {
        // Effective settings of the ACTIVE provider (Admin → AI Setup over .env)
        $config = array_merge(['max_retries' => 0, 'timeout' => 75], (fn ($st) => $st->providerConfig($st->activeProvider()))(app(\App\Services\Ai\AiSettings::class)));

        $this->table(['Setting', 'Value'], [
            ['AI enabled', config('ai.enabled') ? 'yes' : 'no'],
            ['Provider', $client->name()],
            ['Model', (string) $client->model()],
            ['API key configured', filled($config['api_key'] ?? null) ? 'yes' : 'no'],
            ['Ready', $client->isConfigured() && config('ai.enabled') ? 'yes' : 'no'],
            ['Default queue connection', (string) config('queue.default')],
            ['AI queue connection', (string) config('ai.analysis.connection') . ' (' . (string) \App\Services\Ai\Outreach\AnalysisLauncher::queueDriver() . ')'],
            ['AI queue', (string) config('ai.analysis.queue')],
            ['AI job timeout / retry_after (s)', config('ai.analysis.job_timeout') . ' / ' . (string) config('queue.connections.' . config('ai.analysis.connection') . '.retry_after', 'n/a')],
            ['AI call timeout (s) / SDK retries', $config['timeout'] . ' / ' . $config['max_retries']],
            ['Jobs waiting on AI queue', $this->backlog()],
            ['pcntl (enforces --timeout)', extension_loaded('pcntl') ? 'yes' : 'no'],
        ]);

        // Timing safety: a job must finish before the queue hands it to another worker.
        $calls = 1 + (int) config('ai.analysis.invalid_output_retries', 1);
        $worstCase = $calls * (1 + (int) $config['max_retries']) * (float) $config['timeout'];
        $jobTimeout = (int) config('ai.analysis.job_timeout');
        $retryAfter = (int) config('queue.connections.' . config('ai.analysis.connection') . '.retry_after', 0);
        if ($worstCase >= $jobTimeout || ($retryAfter > 0 && $jobTimeout >= $retryAfter)) {
            $this->warn(sprintf(
                'UNSAFE timing: worst case per AI job is %ds (%d calls x %d tries x %ss) vs job timeout %ds / retry_after %ds. '
                . 'Set AI_MAX_RETRIES=0 and keep AI_TIMEOUT x %d below AI_JOB_TIMEOUT, and AI_JOB_TIMEOUT below AI_QUEUE_RETRY_AFTER.',
                $worstCase, $calls, 1 + (int) $config['max_retries'], $config['timeout'], $jobTimeout, $retryAfter, $calls
            ));
        } else {
            $this->info("Timing OK: worst case per AI job {$worstCase}s < job timeout {$jobTimeout}s < retry_after {$retryAfter}s.");
        }

        if (!$this->option('check-model')) {
            return self::SUCCESS;
        }

        if (!filled($config['api_key'] ?? null)) {
            $this->error('No ANTHROPIC_API_KEY configured.');
            return self::FAILURE;
        }

        try {
            $sdk = new Client(apiKey: $config['api_key'], requestOptions: ['maxRetries' => 1], openTelemetry: false);
            $info = $sdk->models->retrieve($config['model']);
            $this->info('Model is available: ' . $info->id . (isset($info->displayName) ? ' (' . $info->displayName . ')' : ''));
        } catch (Throwable $e) {
            $this->error('Model check failed: ' . class_basename($e) . '. Check the model id and the API key permissions.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function backlog(): string
    {
        $connection = config('ai.analysis.connection', 'ai_database');
        if (config("queue.connections.{$connection}.driver") !== 'database') {
            return 'n/a';
        }

        try {
            return (string) DB::table(config("queue.connections.{$connection}.table", 'jobs'))
                ->where('queue', config('ai.analysis.queue'))->count();
        } catch (Throwable) {
            return 'unknown';
        }
    }
}
