<?php

namespace App\Jobs;

use App\Jobs\Concerns\RunsLeadAnalysisStage;
use App\Models\AiLeadAnalysis;
use App\Services\Web\FetchException;
use App\Services\Web\WebsiteAuditor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Stage 1: safe website audit (no AI). Leads without a usable website are
 * marked accordingly and still continue to the AI analysis.
 */
class AuditLeadWebsite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, RunsLeadAnalysisStage;

    public function handle(WebsiteAuditor $auditor): void
    {
        $analysis = $this->loadOpen(AiLeadAnalysis::STAGE_AUDIT);
        if (!$analysis) {
            return;
        }

        try {
            $result = $auditor->audit($analysis->lead, $analysis);
        } catch (FetchException $e) {
            // Per-host rate limit: try again later instead of hammering the site
            if ($this->attempts() < $this->tries) {
                $this->release(60);
                return;
            }
            $result = ['website_status' => AiLeadAnalysis::WEBSITE_UNREACHABLE, 'audit' => null];
        }

        $analysis->forceFill([
            'website_status' => $result['website_status'],
            'website_audit_id' => $result['audit']?->id,
            'stage' => AiLeadAnalysis::STAGE_ANALYSIS,
        ])->save();

        // Website problems never block the rest: the analysis uses lead data.
        \App\Services\Ai\Outreach\AnalysisLauncher::queue(new AnalyzeLeadOpportunity($analysis->id));
    }
}
