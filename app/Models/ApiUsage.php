<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiUsage extends Model
{
    protected $table = 'api_usages';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'company_id',
        'date',
        'searches_used',
        'leads_fetched',
    ];

    protected $casts = [
        'date' => 'date',
        'searches_used' => 'integer',
        'leads_fetched' => 'integer',
    ];

    /**
     * Get the user for this usage record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
