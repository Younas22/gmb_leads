<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Work item for one lead: website audit -> AI opportunity analysis -> email draft.
 * All writes to status go through the helpers below so active_key stays in sync.
 */
class AiLeadAnalysis extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ANALYZING = 'analyzing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_QUOTA_EXHAUSTED = 'quota_exhausted';

    public const STAGE_AUDIT = 'audit';
    public const STAGE_ANALYSIS = 'analysis';
    public const STAGE_DRAFT = 'draft';
    public const STAGE_DONE = 'done';

    public const WEBSITE_PENDING = 'pending';
    public const WEBSITE_NONE = 'no_website';
    public const WEBSITE_INVALID = 'invalid_url';
    public const WEBSITE_VERIFIED = 'verified';
    public const WEBSITE_UNREACHABLE = 'unreachable';
    public const WEBSITE_BLOCKED = 'blocked';

    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_ANALYZING];
    public const RETRYABLE_STATUSES = [self::STATUS_FAILED, self::STATUS_QUOTA_EXHAUSTED];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ANALYZING => 'Analyzing',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_QUOTA_EXHAUSTED => 'AI quota exhausted',
    ];

    public const WEBSITE_LABELS = [
        self::WEBSITE_PENDING => 'Not checked yet',
        self::WEBSITE_NONE => 'No website URL on file',
        self::WEBSITE_INVALID => 'Invalid website URL',
        self::WEBSITE_VERIFIED => 'Website checked',
        self::WEBSITE_UNREACHABLE => 'Website could not be loaded',
        self::WEBSITE_BLOCKED => 'Website check not allowed',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'likely_needs' => 'array',
        'personalization_points' => 'array',
        'supporting_offer_ids' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public static function activeKey(int $ownerId, int $leadId): string
    {
        return $ownerId . ':' . $leadId;
    }

    // ── Relations ────────────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(AiAnalysisBatch::class, 'batch_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(SavedLead::class, 'saved_lead_id');
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(LeadWebsiteAudit::class, 'website_audit_id');
    }

    public function recommendedOffer(): BelongsTo
    {
        return $this->belongsTo(UserOffer::class, 'recommended_offer_id');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(AiEmailDraft::class, 'analysis_id');
    }

    public function currentDraft(): HasOne
    {
        return $this->hasOne(AiEmailDraft::class, 'analysis_id')
            ->ofMany(['id' => 'max'], fn ($query) => $query->where('is_current', true));
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->where('owner_user_id', $owner->id);
    }

    // ── State transitions ────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function markAnalyzing(): void
    {
        $this->forceFill([
            'status' => self::STATUS_ANALYZING,
            'started_at' => $this->started_at ?? now(),
            'attempts' => $this->attempts + 1,
        ])->save();
    }

    public function advanceTo(string $stage): void
    {
        $this->forceFill(['stage' => $stage])->save();
    }

    public function markCompleted(): void
    {
        $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'stage' => self::STAGE_DONE,
            'active_key' => null,
            'error_code' => null,
            'error_message' => null,
            'finished_at' => now(),
        ])->save();
    }

    public function markFailed(string $code, string $message, string $status = self::STATUS_FAILED): void
    {
        $this->forceFill([
            'status' => $status,
            'active_key' => null,
            'error_code' => mb_substr($code, 0, 40),
            'error_message' => mb_substr($message, 0, 255),
            'finished_at' => now(),
        ])->save();
    }

    /**
     * Re-open a finished item for another attempt. Returns false when another
     * open item already exists for the same account + lead (unique active_key).
     */
    public function reopen(): bool
    {
        try {
            $this->forceFill([
                'status' => self::STATUS_PENDING,
                'active_key' => self::activeKey($this->owner_user_id, $this->saved_lead_id),
                'error_code' => null,
                'error_message' => null,
                'finished_at' => null,
            ])->save();
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            $this->refresh();
            return false;
        }

        return true;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function websiteLabel(): string
    {
        return self::WEBSITE_LABELS[$this->website_status] ?? ucfirst(str_replace('_', ' ', $this->website_status));
    }
}
