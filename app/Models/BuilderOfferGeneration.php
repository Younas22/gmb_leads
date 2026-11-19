<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One AI run for a BuilderOffer. Kept forever (until the offer is deleted). */
class BuilderOfferGeneration extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const OPEN = [self::STATUS_QUEUED, self::STATUS_RUNNING];

    /** error_message of a run the user stopped (kept as "failed", so it no longer counts as open). */
    public const STOPPED_MESSAGE = 'Stopped by you.';

    protected $fillable = [];

    protected $casts = [
        'content' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(BuilderOffer::class, 'builder_offer_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    /** Open for longer than the worker could ever take: the worker is down or the job was lost. */
    public function isStale(): bool
    {
        $minutes = (int) config('offer_builder.stale_after_minutes', 15);

        return $this->isOpen() && $this->created_at && $this->created_at->lt(now()->subMinutes($minutes));
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'error_message' => mb_substr($message, 0, 500),
            'completed_at' => now(),
        ])->save();
    }
}
