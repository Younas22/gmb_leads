<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skills, services and special offers of an account (one table, typed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20); // skill | service | offer
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('price_hint', 100)->nullable();
            $table->string('ideal_for', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['owner_user_id', 'type', 'is_active']);
            $table->index(['owner_user_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_offers');
    }
};
