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
        if ($this->status === 'partially_verified' && $this->present && $this->sendingRecordsVerified()) {
            return 'Verified · tracking DNS pending';
        }

        return config("resend_user.domain_statuses.{$this->status}.0") ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    /**
     * Verified domains may send. A "partially verified" domain may send too when every
     * sending record (DKIM, SPF, MX) is verified and only the open/click tracking record
     * is still pending: turning tracking on must not stop sending while that DNS record
     * propagates.
     */
    public function canSend(): bool
    {
        if (!$this->present) {
            return false;
        }
        if ((bool) config("resend_user.domain_statuses.{$this->status}.1", false)) {
            return true;
        }

        return $this->status === 'partially_verified' && $this->sendingRecordsVerified();
    }

    private function sendingRecordsVerified(): bool
    {
        $sending = collect((array) $this->records)
            ->reject(fn ($r) => str_contains(mb_strtolower((string) ($r['record'] ?? '')), 'track'));

        return $sending->isNotEmpty()
            && $sending->every(fn ($r) => mb_strtolower((string) ($r['status'] ?? '')) === 'verified');
    }
}
