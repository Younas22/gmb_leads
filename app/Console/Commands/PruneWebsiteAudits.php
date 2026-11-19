<?php

namespace App\Console\Commands;

use App\Models\LeadWebsiteAudit;
use Illuminate\Console\Command;

/**
 * Retention: deletes website audits older than ai.analysis.audit_retention_days.
 * Drafts keep their own evidence snapshot, so draft history stays readable.
 */
class PruneWebsiteAudits extends Command
{
    protected $signature = 'ai:prune-audits {--days= : Override the retention period in days}';

    protected $description = 'Delete website audits older than the configured retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('ai.analysis.audit_retention_days', 180));
        if ($days < 1) {
            $this->error('Retention must be at least 1 day.');
            return self::FAILURE;
        }

        $deleted = LeadWebsiteAudit::where('created_at', '<', now()->subDays($days))->delete();
        $this->info("Deleted {$deleted} website audit(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
