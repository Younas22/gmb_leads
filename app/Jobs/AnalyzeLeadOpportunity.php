<?php

namespace App\Jobs;

use App\Jobs\Concerns\RunsLeadAnalysisStage;
use App\Models\AiLeadAnalysis;
use App\Services\Ai\Outreach\LeadOpportunityAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/** Stage 2: AI opportunity analysis and offer matching (ai_audits_monthly). */
class AnalyzeLeadOpportunity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, RunsLeadAnalysisStage;

    public function handle(LeadOpportunityAnalyzer $analyzer): void
    {
        $analysis = $this->loadOpen(AiLeadAnalysis::STAGE_ANALYSIS);
        if (!$analysis) {
            return;
        }

        try {
            $analyzer->analyze($this->facts($analysis), $this->actor($analysis));
        } catch (Throwable $e) {
            $this->handleAiError($analysis, $e);
            return;
        }

        $analysis->advanceTo(AiLeadAnalysis::STAGE_DRAFT);
        \App\Services\Ai\Outreach\AnalysisLauncher::queue(new GeneratePersonalizedEmailDraft($analysis->id));
    }
}
