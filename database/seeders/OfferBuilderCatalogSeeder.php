<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the AI Offer Builder catalog (4 categories, 16 strategies) from
 * database/seeders/data/offer_builder_catalog.php. Idempotent: matched by slug,
 * so re-running updates texts without touching saved offers.
 */
class OfferBuilderCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = require __DIR__ . '/data/offer_builder_catalog.php';
        $now = now();
        $categoryIds = [];

        foreach ($catalog['categories'] as $i => $c) {
            DB::table('offer_categories')->updateOrInsert(['slug' => $c['slug']], [
                'name' => $c['name'],
                'purpose' => $c['purpose'],
                'description' => $c['description'],
                'use_cases' => json_encode($c['use_cases']),
                'icon' => $c['icon'],
                'display_order' => $i + 1,
                'is_active' => true,
                'updated_at' => $now,
            ]);
            DB::table('offer_categories')->where('slug', $c['slug'])->whereNull('created_at')->update(['created_at' => $now]);
            $categoryIds[$c['slug']] = (int) DB::table('offer_categories')->where('slug', $c['slug'])->value('id');
        }

        $order = [];
        // The examples column arrives with a later migration (2026_11_16_000002)
        $hasExamples = \Illuminate\Support\Facades\Schema::hasColumn('offer_strategies', 'examples');
        foreach ($catalog['strategies'] as $s) {
            $order[$s['category']] = ($order[$s['category']] ?? 0) + 1;
            DB::table('offer_strategies')->updateOrInsert(['slug' => $s['slug']], ($hasExamples ? ['examples' => json_encode($s['examples'] ?? [])] : []) + [
                'offer_category_id' => $categoryIds[$s['category']],
                'name' => $s['name'],
                'purpose' => $s['purpose'],
                'objective' => $s['objective'],
                'description' => $s['description'],
                'when_to_use' => json_encode($s['when_to_use']),
                'business_types' => json_encode($s['business_types']),
                'example' => $s['example'],
                'required_fields' => json_encode($s['fields']),
                'prompt_extension' => $s['prompt_extension'],
                'display_order' => $order[$s['category']],
                'is_active' => true,
                'updated_at' => $now,
            ]);
            DB::table('offer_strategies')->where('slug', $s['slug'])->whereNull('created_at')->update(['created_at' => $now]);
        }
    }
}
