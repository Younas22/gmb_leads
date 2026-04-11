<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserExtensionDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'device_name',
        'last_seen_at',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function touchSeen()
    {
        $this->update(['last_seen_at' => now()]);
    }
}
