<?php

namespace App\Jobs;

use App\Jobs\Concerns\RunsLeadAnalysisStage;
use App\Models\AiLeadAnalysis;
use App\Services\Ai\Outreach\EmailDraftWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/** Stage 3: AI email draft (ai_email_drafts_monthly). Saves a draft only; never sends. */
class GeneratePersonalizedEmailDraft implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, RunsLeadAnalysisStage;

    public function handle(EmailDraftWriter $writer): void
    {
        $analysis = $this->loadOpen(AiLeadAnalysis::STAGE_DRAFT);
        if (!$analysis) {
            return;
        }

        try {
            $writer->write($this->facts($analysis), $this->actor($analysis));
        } catch (Throwable $e) {
            $this->handleAiError($analysis, $e);
            return;
        }

        $analysis->markCompleted();
    }
}
