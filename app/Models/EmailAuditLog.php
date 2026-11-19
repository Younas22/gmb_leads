<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Audit trail entry. Never contains tokens or message bodies. */
class EmailAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    public static function record(int $ownerId, ?int $userId, string $action, ?int $outboundId = null, array $meta = []): void
    {
        static::create([
            'owner_user_id' => $ownerId,
            'user_id' => $userId,
            'action' => $action,
            'outbound_email_id' => $outboundId,
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
