<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A permission-based campaign sent as one Resend Broadcast. */
class EmailCampaign extends Model
{
    public const DRAFT = 'draft';
    public const PREPARING = 'preparing';   // approved; contacts being synced to a Resend segment
    public const SCHEDULED = 'scheduled';   // broadcast created with a future send time
    public const SUBMITTED = 'submitted';   // broadcast accepted by Resend for sending
    public const CANCELLED = 'cancelled';
    public const FAILED = 'failed';
    public const UNKNOWN = 'unknown';       // broadcast request outcome unclear: check Resend, never re-created automatically

    public const LABELS = [
        self::DRAFT => 'Draft',
        self::PREPARING => 'Preparing',
        self::SCHEDULED => 'Scheduled',
        self::SUBMITTED => 'Sent to Resend',
        self::CANCELLED => 'Cancelled',
        self::FAILED => 'Failed',
        self::UNKNOWN => 'Unknown - check Resend',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'segment_filters' => 'array',
        'scheduled_at' => 'datetime',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function recipients(): HasMany { return $this->hasMany(EmailCampaignRecipient::class, 'campaign_id'); }
    public function emails(): HasMany { return $this->hasMany(OutboundEmail::class, 'campaign_id'); }
    public function sender(): BelongsTo { return $this->belongsTo(ResendSender::class, 'sender_id'); }
    public function list(): BelongsTo { return $this->belongsTo(MarketingContactList::class, 'list_id'); }
    public function integration(): BelongsTo { return $this->belongsTo(ResendIntegration::class, 'resend_integration_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->where('owner_user_id', $owner->id);
    }

    public function label(): string
    {
        return self::LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function isEditable(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::DRAFT, self::PREPARING, self::SCHEDULED], true);
    }
}
