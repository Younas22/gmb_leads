<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A reply the user confirmed by hand. Not detected automatically. */
class EmailReplyRecord extends Model
{
    public const STATUSES = [
        'interested' => 'Interested',
        'not_interested' => 'Not interested',
        'needs_follow_up' => 'Needs follow-up',
        'meeting_requested' => 'Meeting requested',
        'converted' => 'Converted',
        'other' => 'Other',
        // Found automatically in the connected Gmail inbox; the user can set the real outcome
        'replied' => 'Replied (not reviewed yet)',
    ];

    protected $guarded = ['id'];

    protected $casts = ['reply_received_at' => 'datetime'];

    public function email(): BelongsTo { return $this->belongsTo(OutboundEmail::class, 'outbound_email_id'); }
    public function lead(): BelongsTo { return $this->belongsTo(SavedLead::class, 'saved_lead_id'); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by_user_id'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by_user_id'); }

    public function label(): string
    {
        return self::STATUSES[$this->reply_status] ?? ucfirst($this->reply_status);
    }
}
