<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'country_id',
        'name',
        'latitude',
        'longitude'
    ];

    /**
     * Get the state that owns the city
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the country that owns the city
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}