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
        'seo_score',
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
        'follow_up_source',
        'follow_up_date',
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
        'follow_up_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folders()
    {
        return $this->belongsToMany(LeadFolder::class, 'lead_folder_items', 'lead_id', 'folder_id');
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
     * SEO score (when available) takes priority over review-based logic.
     */
    public function getLeadCategoryAttribute(): string
    {
        // SEO-based categorization (when website checked and score stored)
        if (!empty($this->website) && $this->seo_score !== null && $this->seo_score >= 0) {
            if ($this->seo_score <= 50) return 'hot';
            if ($this->seo_score <= 70) return 'good';
            return 'competitive';
        }

        // No website → always hot (SEO weak)
        if (empty($this->website)) {
            $now = Carbon::now();
            $reviewDate = null;
            if ($this->last_review_date && $this->last_review_date !== '0') {
                try {
                    $reviewDate = Carbon::parse($this->last_review_date);
                } catch (\Exception $e) {}
            }
            if ($reviewDate && $reviewDate->gte($now->copy()->subDays(365))
                && $this->total_reviews < 50
                && $reviewDate->gte($now->copy()->subDays(180))) {
                return 'hot';
            }
        }

        // Fallback: review-based logic
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

        $isRecent180 = $reviewDate->gte($now->copy()->subDays(180));

        if (empty($this->website) && $this->total_reviews < 50 && $isRecent180) {
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
