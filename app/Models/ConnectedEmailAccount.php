<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's own Gmail account connected for sending. Tokens are encrypted at
 * rest with APP_KEY and hidden from serialization.
 */
class ConnectedEmailAccount extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_NEEDS_RECONNECT = 'needs_reconnect';
    public const STATUS_DISCONNECTED = 'disconnected';

    protected $guarded = ['id'];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'scopes' => 'array',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && filled($this->refresh_token);
    }

    /** Read-only access granted, so replies can be found automatically. */
    public function canReadReplies(): bool
    {
        return $this->isUsable() && in_array((string) config('gmail.readonly_scope'), $this->scopes ?? [], true);
    }

    public function canSend(): bool
    {
        return $this->isUsable() && in_array('https://www.googleapis.com/auth/gmail.send', $this->scopes ?? [], true);
    }
}
