<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'package_for',
        'billing_type',
        'price',
        'currency',
        'max_users',
        'is_popular',
        'description',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_popular' => 'boolean',
        'max_users' => 'integer',
    ];

    /**
     * Get the features for this package.
     */
    public function features()
    {
        return $this->hasMany(PackageFeature::class);
    }

    /**
     * Get the subscriptions for this package.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Scope to get only active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get packages by type.
     */
    public function scopeForUser($query)
    {
        return $query->where('package_for', 'user');
    }

    public function scopeForCompany($query)
    {
        return $query->where('package_for', 'company');
    }
}
