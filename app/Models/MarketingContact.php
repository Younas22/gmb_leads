<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A person who agreed to receive email from this account. Saved leads never
 * become contacts automatically. Opt-outs, hard bounces and complaints are
 * permanent for sending purposes (re-importing does not undo them).
 */
class MarketingContact extends Model
{
    public const OPTED_IN = 'opted_in';
    public const WITHDRAWN = 'withdrawn';

    protected $guarded = ['id'];

    protected $casts = [
        'tags' => 'array',
        'properties' => 'array',
        'consent_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
    ];

    public static function hashFor(string $email): string
    {
        return EmailSuppression::hashFor($email);
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(MarketingContactList::class, 'marketing_contact_list_members', 'contact_id', 'list_id');
    }

    public function lead(): BelongsTo { return $this->belongsTo(SavedLead::class, 'saved_lead_id'); }

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->where('owner_user_id', $owner->id);
    }

    /** Contacts that may receive email right now (suppression is checked separately). */
    public function scopeSendable(Builder $query): Builder
    {
        return $query->where('consent_status', self::OPTED_IN)
            ->whereNotNull('consent_at')->whereNotNull('consent_evidence')
            ->whereNull('unsubscribed_at')->whereNull('bounced_at')->whereNull('complained_at');
    }

    public function isSendable(): bool
    {
        return $this->consent_status === self::OPTED_IN && $this->consent_at && filled($this->consent_evidence)
            && !$this->unsubscribed_at && !$this->bounced_at && !$this->complained_at;
    }

    public function blockReason(): ?string
    {
        return match (true) {
            (bool) $this->unsubscribed_at => 'unsubscribed',
            (bool) $this->bounced_at => 'bounced',
            (bool) $this->complained_at => 'complained',
            $this->consent_status !== self::OPTED_IN => 'no_consent',
            !$this->consent_at || blank($this->consent_evidence) => 'missing_evidence',
            default => null,
        };
    }

    public function displayName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: $this->email;
    }
}
