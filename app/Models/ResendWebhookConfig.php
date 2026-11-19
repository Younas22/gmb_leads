<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** The customer's Resend webhook endpoint (signing secret encrypted, never shown again). */
class ResendWebhookConfig extends Model
{
    public const NEEDS_SETUP = 'needs_setup';
    public const ACTIVE = 'active';
    public const FAILING = 'failing';

    protected $guarded = ['id'];

    protected $hidden = ['signing_secret'];

    protected $casts = [
        'signing_secret' => 'encrypted',
        'last_event_at' => 'datetime',
        'last_failure_at' => 'datetime',
    ];

    public function integration(): BelongsTo { return $this->belongsTo(ResendIntegration::class, 'integration_id'); }
    public function receipts(): HasMany { return $this->hasMany(ResendWebhookReceipt::class, 'webhook_config_id'); }

    public function endpointUrl(): string
    {
        // Registered at Resend: must be the public APP_URL host, not whichever host the user browses on
        return \App\Support\CanonicalUrl::make(fn () => route('customer-resend.webhook', ['token' => $this->endpoint_token]));
    }
}
