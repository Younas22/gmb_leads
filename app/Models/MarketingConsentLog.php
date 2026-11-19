<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Audit trail of consent changes for one contact. */
class MarketingConsentLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = ['details' => 'array', 'created_at' => 'datetime'];

    public static function record(MarketingContact $contact, ?int $userId, string $action, ?string $from, ?string $to, array $details = []): void
    {
        static::create([
            'owner_user_id' => $contact->owner_user_id,
            'contact_id' => $contact->id,
            'user_id' => $userId,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'details' => $details ?: null,
        ]);
    }
}
