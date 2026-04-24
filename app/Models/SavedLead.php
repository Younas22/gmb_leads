<?php

namespace App\Models;

use Carbon\Carbon;
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

    /**
     * Get the country that owns the lead
     */
    public function countryRelation(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country', 'id');
    }

    /**
     * Get the state that owns the lead
     */
    public function stateRelation(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state', 'id');
    }

    /**
     * Get the city that owns the lead
     */
    public function cityRelation(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city', 'id');
    }

    /**
     * Categorize lead: inactive / hot / good / competitive
     */
    public function getLeadCategoryAttribute(): string
    {
        $now = Carbon::now();

        $reviewDate = null;
        if ($this->last_review_date && $this->last_review_date !== '0') {
            try {
                $reviewDate = Carbon::parse($this->last_review_date);
            } catch (\Exception $e) {}
        }

        if (!$reviewDate || $reviewDate->lt($now->copy()->subDays(365))) {
            return 'inactive';
        }

        $hasWebsite  = !empty($this->website);
        $isRecent180 = $reviewDate->gte($now->copy()->subDays(180));

        if (!$hasWebsite && $this->total_reviews < 50 && $isRecent180) {
            return 'hot';
        }

        if ($this->total_reviews <= 200 && $isRecent180) {
            return 'good';
        }

        return 'competitive';
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
