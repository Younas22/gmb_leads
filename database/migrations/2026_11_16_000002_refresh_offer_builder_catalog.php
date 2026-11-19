<?php

use Database\Seeders\OfferBuilderCatalogSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Offer Builder catalog update: three examples per strategy (shown in the AI
 * Sales Profile) and shorter forms (at most two required fields per strategy).
 * Re-runs the idempotent catalog seeder (matched by slug); saved offers are not touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('offer_strategies')) {
            return;
        }
        if (!Schema::hasColumn('offer_strategies', 'examples')) {
            Schema::table('offer_strategies', function (Blueprint $table) {
                $table->json('examples')->nullable()->after('example');
            });
        }

        (new OfferBuilderCatalogSeeder())->run();
    }

    public function down(): void
    {
        if (Schema::hasColumn('offer_strategies', 'examples')) {
            Schema::table('offer_strategies', function (Blueprint $table) {
                $table->dropColumn('examples');
            });
        }
    }
};
