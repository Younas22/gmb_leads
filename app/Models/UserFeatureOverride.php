<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One user's change to a package feature (set by an admin in Subscriptions → Custom features).
 * Replaces the package's row for that key: another value, unlimited, or locked with a message.
 */
class UserFeatureOverride extends Model
{
    protected $fillable = ['user_id', 'feature_key', 'feature_value', 'is_unlimited', 'is_locked', 'lock_message'];

    protected $casts = [
        'is_unlimited' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
