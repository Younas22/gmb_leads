<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'state_code',
        'latitude',
        'longitude'
    ];

    /**
     * Get the country that owns the state
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get cities for this state
     */
    public function cities()
    {
        return $this->hasMany(City::class);
    }
}