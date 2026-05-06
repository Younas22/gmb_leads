<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateEarning extends Model
{
    protected $fillable = [
        'user_id',
        'total_earned',
        'pending',
        'approved',
        'withdrawn',
    ];

    protected $casts = [
        'total_earned' => 'decimal:2',
        'pending'      => 'decimal:2',
        'approved'     => 'decimal:2',
        'withdrawn'    => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAvailableAttribute(): float
    {
        return max(0, $this->approved - $this->withdrawn);
    }
}
