<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per AI call attempt: who, which feature, provider/model, tokens,
 * outcome. Used for monthly quota enforcement and cost reporting.
 * Never stores prompts, responses or API keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('feature', 50);
            $table->string('provider', 30);
            $table->string('model', 80)->nullable();
            $table->string('status', 20); // success | failed | refused | rejected
            $table->string('error_type', 60)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->unsignedInteger('cache_write_tokens')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('subject_type', 60)->nullable(); // e.g. saved_lead (Phase 2)
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['owner_user_id', 'feature', 'status', 'created_at'], 'ai_usage_quota_idx');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
