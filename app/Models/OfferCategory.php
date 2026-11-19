<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** AI Offer Builder: one of the four offer categories (seeded, read-only for users). */
class OfferCategory extends Model
{
    protected $fillable = ['name', 'slug', 'purpose', 'description', 'use_cases', 'icon', 'display_order', 'is_active'];

    protected $casts = [
        'use_cases' => 'array',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function strategies(): HasMany
    {
        return $this->hasMany(OfferStrategy::class)->orderBy('display_order');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('display_order');
    }
}
