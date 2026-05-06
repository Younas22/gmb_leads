<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateClick extends Model
{
    protected $fillable = [
        'referral_code',
        'ip',
        'user_agent',
        'utm_source',
        'utm_campaign',
        'landing_page',
        'converted',
        'converted_at',
    ];

    protected $casts = [
        'converted' => 'boolean',
        'converted_at' => 'datetime',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referral_code', 'referral_code');
    }
}
