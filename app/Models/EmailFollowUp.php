<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A reminder to follow up with a lead. Reminder only: nothing is sent automatically. */
class EmailFollowUp extends Model
{
    public const OPEN = 'open';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected $casts = ['due_date' => 'date', 'completed_at' => 'datetime'];

    /** Store a plain Y-m-d date on every driver so date comparisons behave the same. */
    public function setDueDateAttribute($value): void
    {
        $this->attributes['due_date'] = \Illuminate\Support\Carbon::parse($value)->toDateString();
    }

    public function lead(): BelongsTo { return $this->belongsTo(SavedLead::class, 'saved_lead_id'); }
    public function contact(): BelongsTo { return $this->belongsTo(MarketingContact::class, 'marketing_contact_id'); }
    public function email(): BelongsTo { return $this->belongsTo(OutboundEmail::class, 'outbound_email_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by_user_id'); }

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->where('owner_user_id', $owner->id);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::OPEN);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::OPEN && $this->due_date->lt(today());
    }
}
