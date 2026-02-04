<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso3',
        'iso2', 
        'phone_code',
        'capital',
        'currency',
        'currency_symbol',
        'latitude',
        'longitude',
        'region',
        'subregion'
    ];

    /**
     * Get states for this country
     */
    public function states()
    {
        return $this->hasMany(State::class);
    }

    /**
     * Get cities for this country
     */
    public function cities()
    {
        return $this->hasMany(City::class);
    }
}