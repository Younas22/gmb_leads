<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** AI Offer Builder: one of the 16 strategies (seeded, read-only for users). */
class OfferStrategy extends Model
{
    protected $fillable = [
        'offer_category_id', 'name', 'slug', 'purpose', 'objective', 'description', 'when_to_use',
        'business_types', 'example', 'required_fields', 'prompt_extension', 'display_order', 'is_active',
    ];

    protected $casts = [
        'when_to_use' => 'array',
        'business_types' => 'array',
        'examples' => 'array',
        'required_fields' => 'array',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(OfferCategory::class, 'offer_category_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('display_order');
    }

    /**
     * Three examples for the "Examples" button. Falls back to the catalog file when the
     * examples column is missing or empty (code can be deployed before its migration runs).
     *
     * @return list<string>
     */
    public function exampleList(): array
    {
        $file = self::catalogEntry($this->slug)['examples'] ?? [];
        if ($file !== []) {
            return array_values($file);
        }
        $fromDb = $this->getAttributes()['examples'] ?? null;

        return array_values(is_string($fromDb) ? (json_decode($fromDb, true) ?: []) : (array) $fromDb);
    }

    /**
     * Form fields. The catalog file (shipped with the code) wins over the seeded copy,
     * so a field change is live on deploy even before the catalog migration has run.
     *
     * @return list<array{key: string, token: string, label: string, type: string, required: bool, example?: string, options?: list<string>}>
     */
    public function fields(): array
    {
        return array_values(self::catalogEntry($this->slug)['fields'] ?? $this->required_fields ?? []);
    }

    private static function catalogEntry(?string $slug): array
    {
        static $catalog = null;
        $catalog ??= collect((require database_path('seeders/data/offer_builder_catalog.php'))['strategies'])->keyBy('slug')->all();

        return $catalog[$slug] ?? [];
    }
}
