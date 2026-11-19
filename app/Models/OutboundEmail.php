<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A user-confirmed email sent through the user's own Gmail.
 * "sent" = Gmail accepted it (has gmail_message_id); delivery is not proven.
 */
class OutboundEmail extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_SENDING => 'Sending',
        self::STATUS_SENT => 'Sent',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    // Phase 5: who sent it. Existing rows are all Gmail.
    public const PROVIDER_GMAIL = 'gmail';
    public const PROVIDER_RESEND = 'resend';

    /** Resend delivery evidence (from verified webhooks), lowest to highest precedence. */
    public const DELIVERY_RANK = ['sent' => 1, 'delivery_delayed' => 2, 'delivered' => 3, 'bounced' => 4, 'failed' => 4, 'suppressed' => 4];

    public const DELIVERY_LABELS = [
        'sent' => 'Sent by Resend',
        'delivery_delayed' => 'Delivery delayed',
        'delivered' => 'Delivered',
        'bounced' => 'Bounced',
        'failed' => 'Failed at Resend',
        'suppressed' => 'Suppressed by Resend',
    ];

    /** Statuses that count toward the daily limit and hold the draft lock. */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_SENDING, self::STATUS_SENT];

    protected $guarded = ['id'];

    protected $casts = [
        'outcome_unknown' => 'boolean',
        'link_tracking' => 'boolean',
        'delivered_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_user_id'); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function account(): BelongsTo { return $this->belongsTo(ConnectedEmailAccount::class, 'connected_account_id'); }
    public function lead(): BelongsTo { return $this->belongsTo(SavedLead::class, 'saved_lead_id'); }
    public function draft(): BelongsTo { return $this->belongsTo(AiEmailDraft::class, 'draft_id'); }
    public function attempts(): HasMany { return $this->hasMany(EmailSendAttempt::class); }
    public function trackingLinks(): HasMany { return $this->hasMany(EmailTrackingLink::class); }
    public function events(): HasMany { return $this->hasMany(EmailTrackingEvent::class); }
    public function reply(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(EmailReplyRecord::class); }
    public function followUps(): HasMany { return $this->hasMany(EmailFollowUp::class); }

    /** The exact body handed to Gmail (tracked links included when tracking was on). */
    public function bodyToSend(): string
    {
        return $this->sent_body ?? $this->body;
    }

    public function campaign(): BelongsTo { return $this->belongsTo(EmailCampaign::class, 'campaign_id'); }
    public function contact(): BelongsTo { return $this->belongsTo(MarketingContact::class, 'marketing_contact_id'); }
    public function resendSender(): BelongsTo { return $this->belongsTo(ResendSender::class, 'resend_sender_id'); }

    public function isResend(): bool
    {
        return $this->provider === self::PROVIDER_RESEND;
    }

    public function providerLabel(): string
    {
        return $this->isResend() ? ($this->campaign_id ? 'Resend campaign' : 'Resend') : 'Gmail';
    }

    /** Delivery as evidenced by the provider. Gmail gives no delivery data at all. */
    public function deliveryLabel(): string
    {
        if (!$this->isResend()) {
            return 'Not available';
        }

        return self::DELIVERY_LABELS[$this->delivery_status] ?? 'No delivery event yet';
    }

    /** "Unknown" emails are failed rows whose outcome could not be confirmed. */
    public function isUnknown(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->outcome_unknown;
    }

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->where('owner_user_id', $owner->id);
    }

    public const UNKNOWN_LABEL = 'Unknown - check Gmail Sent';

    public function label(): string
    {
        if ($this->status === self::STATUS_FAILED && $this->outcome_unknown) {
            return self::UNKNOWN_LABEL;
        }

        return self::LABELS[$this->status] ?? ucfirst($this->status);
    }

    /** Failed sends whose outcome is known (Gmail rejected it) can be retried directly. */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
