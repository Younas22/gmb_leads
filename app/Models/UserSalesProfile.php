<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The AI Sales Profile of an account owner (individual user or company account).
 * Team members read their company's profile; only the owner edits it.
 */
class UserSalesProfile extends Model
{
    /**
     * owner_user_id / updated_by_user_id are set by the controller from the
     * authenticated account — never from request input.
     */
    protected $fillable = [
        'company_name',
        'description',
        'website',
        'portfolio_urls',
        'target_industries',
        'target_locations',
        'language',
        'tone',
        'signature',
        'sender_name',
        'sender_postal_address',
        'ai_instructions',
    ];

    protected $casts = [
        'portfolio_urls' => 'array',
        'target_industries' => 'array',
        'target_locations' => 'array',
    ];

    protected $attributes = [
        'language' => 'en',
        'tone' => 'professional',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(UserOffer::class, 'owner_user_id', 'owner_user_id');
    }

    /** The profile for an account owner, or an unsaved blank one. */
    public static function forOwner(User $owner): self
    {
        $profile = static::where('owner_user_id', $owner->id)->first();
        if ($profile) {
            return $profile;
        }

        $profile = new static();
        $profile->owner_user_id = $owner->id;

        return $profile;
    }

    /**
     * Rough completeness score (0-100) used by the UI to nudge users.
     */
    public function completeness(int $activeOfferCount = 0): int
    {
        $checks = [
            filled($this->company_name),
            filled($this->description),
            filled($this->website) || !empty($this->portfolio_urls),
            !empty($this->target_industries),
            !empty($this->target_locations),
            filled($this->sender_name),
            filled($this->signature),
            filled($this->sender_postal_address),
            $activeOfferCount > 0,
        ];

        return (int) round(count(array_filter($checks)) / count($checks) * 100);
    }
}
