<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A sending domain exactly as Resend reports it (status and DNS records from the API). */
class ResendDomain extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'records' => 'array',
        'open_tracking' => 'boolean',
        'click_tracking' => 'boolean',
        'present' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function integration(): BelongsTo { return $this->belongsTo(ResendIntegration::class, 'integration_id'); }
    public function senders(): HasMany { return $this->hasMany(ResendSender::class, 'domain_id'); }

    public function statusLabel(): string
    {
        return config("resend_user.domain_statuses.{$this->status}.0") ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    /** Only domains Resend reports as fully verified may be used to send. */
    public function canSend(): bool
    {
        return $this->present && (bool) config("resend_user.domain_statuses.{$this->status}.1", false);
    }
}
