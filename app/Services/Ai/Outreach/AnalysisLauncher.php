<?php

namespace App\Services\Ai\Outreach;

use App\Jobs\AuditLeadWebsite;
use App\Jobs\GeneratePersonalizedEmailDraft;
use App\Jobs\AnalyzeLeadOpportunity;
use App\Models\AiAnalysisBatch;
use App\Models\AiLeadAnalysis;
use App\Models\SavedLead;
use App\Models\User;
use App\Models\UserOffer;
use App\Services\Ai\AiService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Creates analysis work items (task creation) and queues them (processing
 * happens in queue workers). Lead ids from the browser are never trusted:
 * only leads inside the user's account (owner + team) are used.
 */
class AnalysisLauncher
{
    public function __construct(private readonly AiService $ai)
    {
    }

    /** Leads the user may analyse (same scope as My Leads). */
    public function ownedLeads(User $user, array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));

        return SavedLead::whereIn('user_id', $user->getQuotaUserIds())->whereIn('id', $ids)->get();
    }

    /**
     * What would happen if these leads were analysed now.
     *
     * @return array<string, mixed>
     */
    public function estimate(User $user, array $ids): array
    {
        $owner = $user->getAccountOwner();
        $leads = $this->ownedLeads($user, $ids);
        $openLeadIds = AiLeadAnalysis::ownedBy($owner)
            ->whereIn('status', AiLeadAnalysis::OPEN_STATUSES)
            ->whereIn('saved_lead_id', $leads->pluck('id'))
            ->pluck('saved_lead_id')->all();

        $toQueue = $leads->reject(fn ($l) => in_array($l->id, $openLeadIds, true));
        $count = $toQueue->count();
        $quota = $this->ai->quota();

        $features = [];
        foreach ([LeadOpportunityAnalyzer::FEATURE, EmailDraftWriter::FEATURE] as $feature) {
            $remaining = $quota->remaining($user, $feature);
            $features[$feature] = [
                'needed' => $count,
                'limit' => $quota->limit($user, $feature),
                'used' => $quota->used($owner, $feature),
                'remaining' => $remaining, // null = unlimited
            ];
        }

        $problems = [];
        $maxBatch = (int) config('ai.analysis.max_batch_size', 25);
        $open = AiLeadAnalysis::ownedBy($owner)->whereIn('status', AiLeadAnalysis::OPEN_STATUSES)->count();

        if (!$this->ai->isAvailable()) {
            $problems[] = 'AI is not configured on this server yet.';
        }
        if (!UserOffer::ownedBy($owner)->active()->whereIn('type', [UserOffer::TYPE_SERVICE, UserOffer::TYPE_OFFER])->exists()) {
            $problems[] = 'Add at least one active service or offer to your AI Sales Profile first.';
        }
        if ($count === 0) {
            $problems[] = $leads->isEmpty() ? 'No valid leads were selected.' : 'The selected leads are already being analysed.';
        }
        if ($count > $maxBatch) {
            $problems[] = "Select at most {$maxBatch} leads at a time.";
        }
        if ($open + $count > (int) config('ai.analysis.max_open_items', 100)) {
            $problems[] = 'You already have many analyses waiting. Please wait for them to finish.';
        }
        foreach ($features as $feature => $f) {
            if ($f['remaining'] !== null && $f['remaining'] < $count) {
                $label = $feature === LeadOpportunityAnalyzer::FEATURE ? 'AI analyses' : 'AI email drafts';
                $problems[] = $f['limit'] === 0
                    ? "Your plan does not include {$label}."
                    : "Not enough {$label} left this month ({$f['remaining']} left, {$count} needed).";
            }
        }
        if ((self::queueDriver() === null || self::queueDriver() === 'sync') && !app()->runningUnitTests()) {
            $problems[] = 'Background processing is not configured (QUEUE_CONNECTION=sync).';
        }

        return [
            'selected' => count(array_unique(array_filter(array_map('intval', $ids)))),
            'valid' => $leads->count(),
            'with_website' => $toQueue->filter(fn ($l) => trim((string) $l->website) !== '')->count(),
            'already_running' => count($openLeadIds),
            'to_queue' => $count,
            'quota' => $features,
            'ai_available' => $this->ai->isAvailable(),
            'problems' => $problems,
            'can_start' => $problems === [],
        ];
    }

    /**
     * @return array{batch: ?AiAnalysisBatch, estimate: array}
     */
    public function start(User $user, array $ids): array
    {
        $estimate = $this->estimate($user, $ids);
        if (!$estimate['can_start']) {
            return ['batch' => null, 'estimate' => $estimate];
        }

        $owner = $user->getAccountOwner();
        $leads = $this->ownedLeads($user, $ids);
        $created = [];

        $batch = DB::transaction(function () use ($user, $owner, $leads, &$created) {
            $batch = AiAnalysisBatch::create(['owner_user_id' => $owner->id, 'created_by_user_id' => $user->id]);

            foreach ($leads as $lead) {
                try {
                    $created[] = DB::transaction(fn () => AiLeadAnalysis::create([
                        'owner_user_id' => $owner->id,
                        'created_by_user_id' => $user->id,
                        'batch_id' => $batch->id,
                        'saved_lead_id' => $lead->id,
                        'status' => AiLeadAnalysis::STATUS_PENDING,
                        'stage' => AiLeadAnalysis::STAGE_AUDIT,
                        'active_key' => AiLeadAnalysis::activeKey($owner->id, $lead->id),
                    ]));
                } catch (UniqueConstraintViolationException) {
                    // Already queued by a parallel request: skip (no duplicate job)
                }
            }

            $batch->update(['total_items' => count($created)]);

            return $batch;
        });

        foreach ($created as $analysis) {
            self::dispatchFor($analysis);
        }

        return ['batch' => $batch, 'estimate' => $estimate];
    }

    /** Queue the job for the item's current stage (used for start, retry and regenerate). */
    public static function dispatchFor(AiLeadAnalysis $analysis): void
    {
        self::queue(match ($analysis->stage) {
            AiLeadAnalysis::STAGE_ANALYSIS => new AnalyzeLeadOpportunity($analysis->id),
            AiLeadAnalysis::STAGE_DRAFT => new GeneratePersonalizedEmailDraft($analysis->id),
            default => new AuditLeadWebsite($analysis->id),
        });
    }

    /** Every AI job goes to the dedicated AI connection + queue. */
    public static function queue(object $job): void
    {
        dispatch($job)
            ->onConnection(config('ai.analysis.connection', 'ai_database'))
            ->onQueue(config('ai.analysis.queue', 'ai'));
    }

    /** Driver of the connection AI jobs use ("sync" would run AI inside web requests). */
    public static function queueDriver(): ?string
    {
        return config('queue.connections.' . config('ai.analysis.connection', 'ai_database') . '.driver');
    }
}
