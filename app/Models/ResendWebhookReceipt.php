<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One verified webhook delivery (minimal metadata; also the replay guard). */
class ResendWebhookReceipt extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['payload' => 'array', 'event_created_at' => 'datetime', 'processed_at' => 'datetime'];

    public function config(): BelongsTo { return $this->belongsTo(ResendWebhookConfig::class, 'webhook_config_id'); }
}
