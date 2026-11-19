<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One "Analyze with AI" request (single or bulk) by a user. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analysis_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_items')->default(0);
            $table->timestamps();

            $table->index(['owner_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_batches');
    }
};
