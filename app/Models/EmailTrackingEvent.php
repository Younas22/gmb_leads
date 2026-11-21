<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One verified activity event. Every event names its source; nothing here is
 * inferred (no "delivered" or "opened" events exist, because there is no data for them).
 */
class EmailTrackingEvent extends Model
{
    public const UPDATED_AT = null;

    // Event types
    public const QUEUED = 'queued';
    public const ACCEPTED = 'accepted';
    public const FAILED = 'send_failed';
    public const UNKNOWN = 'send_unknown';
    public const LINK_CLICK = 'link_click';
    public const REPLY_RECORDED = 'reply_recorded';
    public const REPLY_UPDATED = 'reply_updated';
    public const REPLY_REMOVED = 'reply_removed';
    public const OPTED_OUT = 'opted_out';
    public const FOLLOW_UP_SCHEDULED = 'follow_up_scheduled';
    public const FOLLOW_UP_COMPLETED = 'follow_up_completed';
    public const FOLLOW_UP_CANCELLED = 'follow_up_cancelled';
    // Phase 5: provider-reported (Resend webhooks), never inferred
    public const PROVIDER_SENT = 'provider_sent';
    public const DELIVERED = 'delivered';
    public const DELIVERY_DELAYED = 'delivery_delayed';
    public const BOUNCED = 'bounced';
    public const COMPLAINED = 'complained';
    public const PROVIDER_FAILED = 'provider_failed';
    public const PROVIDER_SUPPRESSED = 'provider_suppressed';
    public const PROVIDER_OPENED = 'provider_opened';
    public const PROVIDER_CLICKED = 'provider_clicked';
    // Gmail emails: open pixel + replies found in the connected inbox
    public const OPENED = 'opened';
    public const REPLY_DETECTED = 'reply_detected';

    // Sources
    public const SOURCE_GMAIL = 'gmail_api';
    public const SOURCE_REDIRECT = 'tracking_redirect';
    public const SOURCE_USER = 'user_action';
    public const SOURCE_UNSUBSCRIBE = 'unsubscribe_link';
    public const SOURCE_SYSTEM = 'system';
    public const SOURCE_RESEND_API = 'resend_api';
    public const SOURCE_RESEND_WEBHOOK = 'resend_webhook';
    public const SOURCE_OPEN_PIXEL = 'open_pixel';
    public const SOURCE_GMAIL_INBOX = 'gmail_inbox';

    // Click classifications
    public const HUMAN = 'probable_human';
    public const SCANNER = 'suspected_scanner';
    public const INTERNAL = 'internal';

    public const TYPE_LABELS = [
        self::QUEUED => 'Email queued for sending',
        self::ACCEPTED => 'Email accepted by Gmail',
        self::FAILED => 'Sending failed',
        self::UNKNOWN => 'Sending outcome unknown',
        self::LINK_CLICK => 'Tracked link request recorded',
        self::REPLY_RECORDED => 'Reply marked as received',
        self::REPLY_UPDATED => 'Reply record corrected',
        self::REPLY_REMOVED => 'Reply record removed',
        self::OPTED_OUT => 'Recipient unsubscribed',
        self::FOLLOW_UP_SCHEDULED => 'Follow-up scheduled',
        self::FOLLOW_UP_COMPLETED => 'Follow-up completed',
        self::FOLLOW_UP_CANCELLED => 'Follow-up cancelled',
        self::PROVIDER_SENT => 'Sent by Resend',
        self::DELIVERED => 'Delivered (reported by Resend)',
        self::DELIVERY_DELAYED => 'Delivery delayed',
        self::BOUNCED => 'Bounced',
        self::COMPLAINED => 'Marked as spam by recipient',
        self::PROVIDER_FAILED => 'Failed at Resend',
        self::PROVIDER_SUPPRESSED => 'Suppressed by Resend',
        self::PROVIDER_OPENED => 'Open reported by Resend',
        self::PROVIDER_CLICKED => 'Click reported by Resend',
        self::OPENED => 'Email opened (images loaded)',
        self::REPLY_DETECTED => 'Reply found in your Gmail inbox',
    ];

    public const SOURCE_LABELS = [
        self::SOURCE_GMAIL => 'Gmail API',
        self::SOURCE_REDIRECT => 'Tracking redirect',
        self::SOURCE_USER => 'User action',
        self::SOURCE_UNSUBSCRIBE => 'Unsubscribe link',
        self::SOURCE_SYSTEM => 'Application system',
        self::SOURCE_RESEND_API => 'Resend API',
        self::SOURCE_RESEND_WEBHOOK => 'Resend webhook (verified)',
        self::SOURCE_OPEN_PIXEL => 'Open pixel',
        self::SOURCE_GMAIL_INBOX => 'Gmail inbox check',
    ];

    public const CLASSIFICATION_LABELS = [
        self::HUMAN => 'Probable person',
        self::SCANNER => 'Likely security scanner',
        self::INTERNAL => 'Your own test',
    ];

    protected $guarded = ['id'];

    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime', 'created_at' => 'datetime'];

    public function email(): BelongsTo { return $this->belongsTo(OutboundEmail::class, 'outbound_email_id'); }
    public function link(): BelongsTo { return $this->belongsTo(EmailTrackingLink::class, 'tracking_link_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->where('owner_user_id', $owner->id);
    }

    public function typeLabel(): string
    {
        if ($this->event_type === self::ACCEPTED && $this->provider === OutboundEmail::PROVIDER_RESEND) {
            return 'Email accepted by Resend';
        }

        return self::TYPE_LABELS[$this->event_type] ?? ucfirst(str_replace('_', ' ', $this->event_type));
    }

    public function sourceLabel(): string
    {
        return self::SOURCE_LABELS[$this->event_source] ?? ucfirst($this->event_source);
    }
}
