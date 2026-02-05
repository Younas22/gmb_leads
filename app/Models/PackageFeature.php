<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageFeature extends Model
{
    protected $fillable = [
        'package_id',
        'feature_key',
        'feature_value',
        'is_unlimited',
    ];

    protected $casts = [
        'is_unlimited' => 'boolean',
    ];

    /**
     * Get the package that owns the feature.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
