<?php

use App\Support\MigrationGuard;
use Database\Seeders\OfferBuilderCatalogSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Offer Builder: strategy catalog (4 categories, 16 strategies) + the offers
 * users build from them and every AI generation of each offer.
 *
 * Named builder_offers (not user_offers): user_offers already holds the AI Sales
 * Profile skills / services / offers.
 */
return new class extends Migration
{
    public function up(): void
    {
        MigrationGuard::prepare('offer_categories', []);
        Schema::create('offer_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 60)->unique();
            $table->string('purpose', 300);
            $table->text('description')->nullable();
            $table->json('use_cases');
            $table->string('icon', 60)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        MigrationGuard::prepare('offer_strategies', ['offer_categories']);
        Schema::create('offer_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_category_id')->constrained('offer_categories')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 80)->unique();
            $table->string('purpose', 300);           // short description
            $table->string('objective', 300);         // main objective
            $table->text('description');              // how the strategy works
            $table->json('when_to_use');
            $table->json('business_types');
            $table->text('example');
            $table->json('required_fields');          // strategy-specific fields (key, token, label, type, required, example)
            $table->longText('prompt_extension');     // strategy-specific AI instructions with [Tokens]
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['offer_category_id', 'is_active', 'display_order']);
        });

        MigrationGuard::prepare('builder_offers', ['users', 'offer_strategies']);
        Schema::create('builder_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('offer_strategy_id')->constrained('offer_strategies')->restrictOnDelete();
            $table->string('title', 150);
            $table->json('business_inputs')->nullable();
            $table->text('additional_instructions')->nullable();
            // Set only when the user edited the prompt in "Advanced Prompt" mode; null = built from the form
            $table->longText('custom_prompt')->nullable();
            $table->longText('generated_prompt')->nullable();   // prompt text of the last generation request
            $table->json('generated_content')->nullable();      // the offer the user keeps (chosen version, may be edited)
            $table->unsignedBigInteger('current_generation_id')->nullable();
            $table->string('status', 20)->default('draft');     // draft | generated | archived
            $table->json('generation_metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'updated_at']);
            $table->index(['user_id', 'offer_strategy_id']);
        });

        // Every AI run is kept: regenerating never overwrites an earlier result.
        MigrationGuard::prepare('builder_offer_generations', ['builder_offers', 'users']);
        Schema::create('builder_offer_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('builder_offer_id')->constrained('builder_offers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('queued');     // queued | running | completed | failed
            $table->longText('prompt');                          // what was sent (data part)
            $table->json('content')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['builder_offer_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });

        // Catalog data ships with the migration so production gets it on deploy.
        (new OfferBuilderCatalogSeeder())->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_offer_generations');
        Schema::dropIfExists('builder_offers');
        Schema::dropIfExists('offer_strategies');
        Schema::dropIfExists('offer_categories');
    }
};
