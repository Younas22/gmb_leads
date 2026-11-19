<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A tracked link: fixed destination, opaque token (only its hash is stored). */
class EmailTrackingLink extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'created_at' => 'datetime'];

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function email(): BelongsTo { return $this->belongsTo(OutboundEmail::class, 'outbound_email_id'); }
    public function events(): HasMany { return $this->hasMany(EmailTrackingEvent::class, 'tracking_link_id'); }
}
