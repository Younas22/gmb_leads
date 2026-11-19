<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A sender's link-tracking preference. Missing row = tracking off. */
class EmailTrackingSetting extends Model
{
    public const RETENTION_OPTIONS = [30, 90, 180];

    protected $guarded = ['id'];

    protected $casts = [
        'link_tracking_enabled' => 'boolean',
        'retention_days' => 'integer',
        'disclosure_accepted_at' => 'datetime',
    ];

    public static function for(User $user): self
    {
        return static::firstOrNew(['user_id' => $user->id], [
            'owner_user_id' => $user->getAccountOwner()->id,
            'link_tracking_enabled' => false,
            'retention_days' => (int) config('email_tracking.default_retention_days', 90),
        ]);
    }

    public function retentionDays(): int
    {
        $days = (int) ($this->retention_days ?: config('email_tracking.default_retention_days', 90));

        return min(max($days, 1), (int) config('email_tracking.max_retention_days', 180));
    }
}
