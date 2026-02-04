<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('saved_leads', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->index();
            $table->string('place_id', 255)->nullable()->index();

            $table->string('name', 255);
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website', 500)->nullable();
            $table->string('email', 255)->nullable();

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->string('city', 100)->nullable()->index();
            $table->string('state', 100)->nullable()->index();
            $table->string('country', 100)->nullable()->index();
            $table->string('postal_code', 20)->nullable();

            $table->string('category', 100)->nullable()->index();
            $table->decimal('rating', 2, 1)->nullable();
            $table->integer('total_reviews')->default(0)->nullable();
            $table->string('last_review_date', 100)->default(0)->nullable();
            $table->integer('price_level')->nullable();

            $table->longText('opening_hours')->nullable();
            $table->string('google_profile_url', 500)->nullable();
            $table->longText('social_links')->nullable();
            $table->longText('reviews_sample')->nullable();

            $table->string('search_query', 255)->nullable()->index();
            $table->string('search_location', 255)->nullable();
            $table->integer('search_radius')->nullable();
            $table->string('found_via_api', 50)->nullable();

            $table->boolean('is_contacted')->default(0)->nullable();
            $table->enum('contact_status', ['not_contacted', 'contacted', 'responded', 'closed'])
                  ->default('not_contacted')->nullable()->index();

            $table->text('notes')->nullable();
            $table->string('tags', 500)->nullable();

            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_leads');
    }
};
