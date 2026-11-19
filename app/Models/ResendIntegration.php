<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A customer's own Resend account (BYOK). The API key is encrypted at rest and
 * hidden from serialisation; it is only ever sent to api.resend.com.
 */
class ResendIntegration extends Model
{
    public const ACTIVE = 'active';
    public const INVALID = 'invalid';
    public const RESTRICTED = 'restricted';
    public const DISCONNECTED = 'disconnected';

    protected $guarded = ['id'];

    protected $hidden = ['api_key'];

    protected $casts = [
        'api_key' => 'encrypted',
        'last_checked_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'connected_at' => 'datetime',
        'lead_outreach_enabled_at' => 'datetime',
    ];

    /**
     * The owner chose to allow one-to-one emails to saved leads (not opted-in contacts)
     * through this Resend account, after accepting the risk notice. Campaigns are never
     * affected: they always go to opted-in contacts only.
     */
    public function allowsLeadOutreach(): bool
    {
        return $this->lead_outreach_enabled_at !== null;
    }

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_user_id'); }
    public function domains(): HasMany { return $this->hasMany(ResendDomain::class, 'integration_id'); }
    public function webhook(): HasOne { return $this->hasOne(ResendWebhookConfig::class, 'integration_id'); }

    public static function forOwner(User $owner): ?self
    {
        return static::where('owner_user_id', $owner->id)->first();
    }

    public function isUsable(): bool
    {
        return $this->status === self::ACTIVE && filled($this->api_key);
    }

    public function maskedKey(): string
    {
        return $this->key_last4 ? 're_••••••••' . $this->key_last4 : '—';
    }
}
