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
        Schema::create('user_api_keys', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->index();
            $table->enum('api_provider', [
                'google_places',
                'google_maps',
                'bing_maps',
                'yahoo_places',
                'openstreetmap',
                'mapbox'
            ])->nullable()->default('google_places')->index();

            $table->string('api_key', 500);
            $table->string('key_name', 100);
            $table->string('google_email', 255)->nullable();

            $table->boolean('is_active')->nullable()->default(0)->index();
            $table->boolean('is_valid')->nullable()->default(1)->index();

            $table->integer('usage_count')->nullable()->default(0);
            $table->integer('daily_limit')->nullable()->default(1000);
            $table->integer('monthly_limit')->nullable()->default(30000);

            $table->dateTime('last_used')->nullable();
            $table->integer('error_count')->nullable()->default(0);
            $table->string('last_error', 500)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Foreign Key relation with users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_api_keys');
    }
};
