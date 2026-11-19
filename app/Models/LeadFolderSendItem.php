<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One lead of a folder send: its own AI draft, when it is due, and what happened. */
class LeadFolderSendItem extends Model
{
    public const PENDING = 'pending';
    public const QUEUED = 'queued';     // handed to the normal Gmail / Resend send pipeline
    public const SKIPPED = 'skipped';   // not eligible when the run started (reason says why)
    public const FAILED = 'failed';     // refused when its turn came (reason says why)
    public const CANCELLED = 'cancelled';

    public const LABELS = [
        self::PENDING => 'Waiting',
        self::QUEUED => 'Sent to queue',
        self::SKIPPED => 'Skipped',
        self::FAILED => 'Not sent',
        self::CANCELLED => 'Stopped',
    ];

    protected $guarded = ['id'];

    protected $casts = ['due_at' => 'datetime', 'processed_at' => 'datetime'];

    public function send(): BelongsTo { return $this->belongsTo(LeadFolderSend::class, 'send_id'); }
    public function lead(): BelongsTo { return $this->belongsTo(SavedLead::class, 'saved_lead_id'); }
    public function draft(): BelongsTo { return $this->belongsTo(AiEmailDraft::class, 'draft_id'); }
    public function email(): BelongsTo { return $this->belongsTo(OutboundEmail::class, 'outbound_email_id'); }
}
