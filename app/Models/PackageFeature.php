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

    /**
     * Get human-readable feature name from feature_key
     */
    public function getDisplayNameAttribute()
    {
        $names = [
            'leads_per_month' => 'Monthly Leads',
            'search_credits' => 'Credits',
            'searches_per_day' => 'Daily Searches',
            'api_keys' => 'API Keys',
            'export_csv' => 'Export to CSV/Excel',
            'priority_support' => 'Priority Support',
            'basic_support' => 'Basic Support',
            'advanced_analytics' => 'Advanced Analytics',
            'custom_reports' => 'Custom Reports',
            'team_collaboration' => 'Team Collaboration',
            'white_label' => 'White Label',
        ];

        return $names[$this->feature_key] ?? ucwords(str_replace('_', ' ', $this->feature_key));
    }

    /**
     * Get formatted feature display with value
     */
    public function getFormattedDisplayAttribute()
    {
        if ($this->is_unlimited) {
            return $this->display_name;
        }

        if ($this->feature_value) {
            return $this->feature_value . ' ' . $this->display_name;
        }

        return $this->display_name;
    }
}
