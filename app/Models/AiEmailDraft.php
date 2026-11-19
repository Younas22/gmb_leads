<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Editable AI email draft. Never sent in Phase 2.
 */
class AiEmailDraft extends Model
{
    public const GENERATION_GENERATED = 'generated';
    public const GENERATION_FAILED = 'failed';

    public const REVIEW_DRAFT = 'draft';
    public const REVIEW_APPROVED = 'approved';
    public const REVIEW_DISCARDED = 'discarded';

    public const REVIEW_LABELS = [
        self::REVIEW_DRAFT => 'Draft',
        self::REVIEW_APPROVED => 'Approved',
        self::REVIEW_DISCARDED => 'Discarded',
    ];

    /** Only subject/body are ever filled from user input (see controller). */
    protected $fillable = ['subject', 'body'];

    protected $casts = [
        'evidence_snapshot' => 'array',
        'is_current' => 'boolean',
        'user_edited' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(SavedLead::class, 'saved_lead_id');
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(AiLeadAnalysis::class, 'analysis_id');
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(LeadWebsiteAudit::class, 'website_audit_id');
    }

    public function selectedOffer(): BelongsTo
    {
        return $this->belongsTo(UserOffer::class, 'selected_offer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->where('owner_user_id', $owner->id);
    }

    public function reviewLabel(): string
    {
        return self::REVIEW_LABELS[$this->review_status] ?? ucfirst($this->review_status);
    }
}
