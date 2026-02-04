<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedLead extends Model
{
    protected $fillable = [
        'user_id',
        'place_id',
        'name',
        'address',
        'phone',
        'website',
        'email',
        'latitude',
        'longitude',
        'city',
        'state',
        'country',
        'postal_code',
        'category',
        'rating',
        'total_reviews',
        'last_review_date',
        'price_level',
        'opening_hours',
        'google_profile_url',
        'social_links',
        'reviews_sample',
        'search_query',
        'search_location',
        'search_radius',
        'found_via_api',
        'is_contacted',
        'contact_status',
        'notes',
        'tags'
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'social_links' => 'array',
        'reviews_sample' => 'array',
        'is_contacted' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:1',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Check if lead already exists for user
    public static function existsForUser($userId, $placeId = null, $name = null, $address = null)
    {
        $query = static::where('user_id', $userId);
        
        if ($placeId) {
            $query->where('place_id', $placeId);
        } else {
            $query->where('name', $name)->where('address', $address);
        }
        
        return $query->exists();
    }
}
