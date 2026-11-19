<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One "Analyze with AI" request. Progress is derived from its items. */
class AiAnalysisBatch extends Model
{
    protected $guarded = ['id'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AiLeadAnalysis::class, 'batch_id');
    }

    /** @return array{total:int, pending:int, analyzing:int, completed:int, failed:int, quota_exhausted:int, finished:int, percent:int} */
    public function progress(): array
    {
        $counts = $this->items()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $total = (int) $counts->sum();
        $finished = (int) (($counts[AiLeadAnalysis::STATUS_COMPLETED] ?? 0)
            + ($counts[AiLeadAnalysis::STATUS_FAILED] ?? 0)
            + ($counts[AiLeadAnalysis::STATUS_QUOTA_EXHAUSTED] ?? 0));

        return [
            'total' => $total,
            'pending' => (int) ($counts[AiLeadAnalysis::STATUS_PENDING] ?? 0),
            'analyzing' => (int) ($counts[AiLeadAnalysis::STATUS_ANALYZING] ?? 0),
            'completed' => (int) ($counts[AiLeadAnalysis::STATUS_COMPLETED] ?? 0),
            'failed' => (int) ($counts[AiLeadAnalysis::STATUS_FAILED] ?? 0),
            'quota_exhausted' => (int) ($counts[AiLeadAnalysis::STATUS_QUOTA_EXHAUSTED] ?? 0),
            'finished' => $finished,
            'percent' => $total > 0 ? (int) floor($finished / $total * 100) : 100,
        ];
    }
}
