<?php

namespace App\Jobs\Concerns;

use App\Models\AiLeadAnalysis;
use App\Models\LeadWebsiteAudit;
use App\Models\SavedLead;
use App\Models\User;
use App\Services\Ai\Exceptions\AiNotConfiguredException;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Exceptions\AiQuotaExceededException;
use App\Services\Ai\Outreach\LeadFacts;
use App\Services\Ai\Outreach\ProfileIncompleteException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shared plumbing for the three analysis jobs: idempotent loading, ownership
 * re-check, safe status transitions and consistent error mapping.
 * Job payloads contain only the analysis id.
 */
trait RunsLeadAnalysisStage
{
    public int $tries = 3;
    public int $timeout = 80;
    public bool $failOnTimeout = true;

    public function __construct(public readonly int $analysisId)
    {
        $this->tries = (int) config('ai.analysis.job_tries', 3);
        $this->timeout = (int) config('ai.analysis.job_timeout', 80);
    }

    /** @return array<int> seconds */
    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Load the item if it is still open and at the expected stage; null = nothing to do.
     */
    protected function loadOpen(string $stage): ?AiLeadAnalysis
    {
        $analysis = AiLeadAnalysis::find($this->analysisId);

        if (!$analysis || !$analysis->isOpen() || $analysis->stage !== $stage) {
            return null; // finished, removed or already past this stage (idempotent)
        }

        $lead = SavedLead::find($analysis->saved_lead_id);
        $owner = User::find($analysis->owner_user_id);

        // Re-check ownership at run time: the lead must still belong to the account.
        if (!$lead || !$owner || !in_array((int) $lead->user_id, array_map('intval', $owner->getQuotaUserIds()), true)) {
            $analysis->markFailed('lead_unavailable', 'This lead is no longer available in your account.');
            return null;
        }

        if ($analysis->status === AiLeadAnalysis::STATUS_PENDING) {
            $analysis->markAnalyzing();
        }

        return $analysis;
    }

    protected function facts(AiLeadAnalysis $analysis): LeadFacts
    {
        $audit = $analysis->website_audit_id ? LeadWebsiteAudit::find($analysis->website_audit_id) : null;

        return new LeadFacts($analysis->lead, $analysis, $audit, $analysis->owner);
    }

    /** The user whose plan/quota applies: the requester if still in the account, else the owner. */
    protected function actor(AiLeadAnalysis $analysis): User
    {
        $creator = $analysis->created_by_user_id ? User::find($analysis->created_by_user_id) : null;
        $owner = $analysis->owner;

        if ($creator && $creator->getAccountOwner()?->id === $owner->id) {
            return $creator;
        }

        return $owner;
    }

    /**
     * Map AI-layer errors to item states. Retryable provider errors are retried
     * by the queue (bounded by $tries); everything else ends the item.
     */
    protected function handleAiError(AiLeadAnalysis $analysis, Throwable $e): void
    {
        if ($e instanceof AiQuotaExceededException) {
            $analysis->markFailed($e->errorType(), $e->getMessage(), AiLeadAnalysis::STATUS_QUOTA_EXHAUSTED);
            return;
        }
        if ($e instanceof ProfileIncompleteException) {
            $analysis->markFailed('profile_incomplete', $e->getMessage());
            return;
        }
        if ($e instanceof AiNotConfiguredException) {
            $analysis->markFailed('ai_not_configured', $e->getMessage());
            return;
        }
        if ($e instanceof AiProviderException && $e->isRetryable() && $this->attempts() < $this->tries) {
            $this->release($this->backoff()[min($this->attempts() - 1, 1)] ?? 60);
            return;
        }
        if ($e instanceof AiProviderException) {
            $analysis->markFailed($e->errorType(), $e->getMessage());
            return;
        }

        Log::error('Lead analysis stage crashed', ['analysis_id' => $analysis->id, 'exception' => get_class($e)]);
        $analysis->markFailed('internal_error', 'Something went wrong while analysing this lead. Please retry.');
    }

    /** Called by the queue when all attempts are used up or the job timed out. */
    public function failed(?Throwable $e): void
    {
        $analysis = AiLeadAnalysis::find($this->analysisId);
        if ($analysis && $analysis->isOpen()) {
            $analysis->markFailed('job_failed', 'Analysis did not finish. Please retry.');
        }
    }
}
