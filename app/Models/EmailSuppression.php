<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An address this account must not email (scoped per account owner). */
class EmailSuppression extends Model
{
    public const REASON_UNSUBSCRIBED = 'unsubscribed';
    public const REASON_MANUAL = 'manual';
    public const REASON_BOUNCED = 'bounced';
    public const REASON_COMPLAINT = 'complaint';
    // Phase 4: the user recorded a "not interested" reply
    public const REASON_NOT_INTERESTED = 'not_interested';

    protected $guarded = ['id'];

    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function hashFor(string $email): string
    {
        return hash('sha256', self::normalize($email));
    }

    public static function isSuppressed(int $ownerId, string $email): bool
    {
        return static::where('owner_user_id', $ownerId)->where('email_hash', self::hashFor($email))->exists();
    }

    // Phase 5
    public const REASON_PROVIDER_SUPPRESSED = 'provider_suppressed';

    /** Reasons a user may reverse (a mistaken entry). Everything else is permanent. */
    public const REVERSIBLE = [self::REASON_MANUAL, self::REASON_NOT_INTERESTED];

    public static function suppress(int $ownerId, string $email, string $reason, ?int $outboundId = null, ?int $byUserId = null): self
    {
        $row = static::firstOrCreate(
            ['owner_user_id' => $ownerId, 'email_hash' => self::hashFor($email)],
            ['email' => self::normalize($email), 'reason' => $reason, 'outbound_email_id' => $outboundId, 'created_by_user_id' => $byUserId]
        );

        // A permanent reason (unsubscribe, bounce, complaint) replaces a reversible one, so
        // correcting a "not interested" reply later can never remove a real opt-out.
        if (!$row->wasRecentlyCreated && in_array($row->reason, self::REVERSIBLE, true) && !in_array($reason, self::REVERSIBLE, true)) {
            $row->forceFill(['reason' => $reason, 'outbound_email_id' => $outboundId ?? $row->outbound_email_id])->save();
        }

        // Keep opted-in contacts in step (they can no longer receive email)
        $column = match ($reason) {
            self::REASON_UNSUBSCRIBED => 'unsubscribed_at',
            self::REASON_BOUNCED => 'bounced_at',
            self::REASON_COMPLAINT => 'complained_at',
            default => null,
        };
        if ($column) {
            MarketingContact::where('owner_user_id', $ownerId)->where('email_hash', self::hashFor($email))->whereNull($column)
                ->update([$column => now(), 'updated_at' => now()]);
        }

        // Approved campaigns that still include this address must not reach it (Phase 5.1)
        \App\Services\Resend\ScheduledCampaignGuard::addressSuppressed($ownerId, $email);

        return $row;
    }
}
