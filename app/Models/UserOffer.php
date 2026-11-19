<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A skill, service or special offer belonging to an account owner.
 */
class UserOffer extends Model
{
    public const TYPE_SKILL = 'skill';
    public const TYPE_SERVICE = 'service';
    public const TYPE_OFFER = 'offer';

    public const TYPES = [
        self::TYPE_SKILL => 'Skill',
        self::TYPE_SERVICE => 'Service',
        self::TYPE_OFFER => 'Offer',
    ];

    /** owner_user_id is assigned by the controller, never mass-assigned. */
    protected $fillable = [
        'type',
        'title',
        'description',
        'price_hint',
        'ideal_for',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * How many items of a type an account must have (min) / may have (max).
     * Skills 3–10 (config/skills.php), services 1–5 (config/ai.php), offers 0–max_offers_per_type.
     *
     * @return array{min:int, max:int}
     */
    public static function limits(string $type): array
    {
        return match ($type) {
            self::TYPE_SKILL => ['min' => \App\Support\SkillCatalog::min(), 'max' => \App\Support\SkillCatalog::max()],
            self::TYPE_SERVICE => ['min' => (int) config('ai.services.min', 1), 'max' => (int) config('ai.services.max', 5)],
            default => ['min' => 0, 'max' => (int) config('ai.max_offers_per_type', 50)],
        };
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function scopeOwnedBy(Builder $query, User $owner): Builder
    {
        return $query->where('owner_user_id', $owner->id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
