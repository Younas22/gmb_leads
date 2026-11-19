<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An offer a user builds in the AI Offer Builder. Belongs to one user (user_id is
 * set by the controller, never mass-assigned). generated_content is the version
 * the user keeps; every AI run is stored separately in builder_offer_generations.
 */
class BuilderOffer extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_GENERATED => 'Generated',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    protected $fillable = [
        'offer_strategy_id',
        'title',
        'business_inputs',
        'additional_instructions',
        'custom_prompt',
    ];

    protected $casts = [
        'business_inputs' => 'array',
        'generated_content' => 'array',
        'generation_metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(OfferStrategy::class, 'offer_strategy_id');
    }

    public function generations(): HasMany
    {
        return $this->hasMany(BuilderOfferGeneration::class)->latest('id');
    }

    public function scopeOwnedBy(Builder $q, User $user): Builder
    {
        return $q->where('user_id', $user->id);
    }

    public function hasOpenGeneration(): bool
    {
        return $this->generations()->whereIn('status', BuilderOfferGeneration::OPEN)->exists();
    }
}
