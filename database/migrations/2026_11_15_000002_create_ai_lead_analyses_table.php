<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Work item: analyse one lead (website audit -> AI opportunity analysis -> draft).
 *
 * active_key = "{owner}:{lead}" while the item is pending/analyzing and NULL
 * afterwards. Its UNIQUE index makes duplicate concurrent work for the same
 * account + lead impossible at the database level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_lead_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('ai_analysis_batches')->nullOnDelete();
            $table->foreignId('saved_lead_id')->constrained('saved_leads')->cascadeOnDelete();

            // pending | analyzing | completed | failed | quota_exhausted
            $table->string('status', 20)->default('pending');
            // audit | analysis | draft | done
            $table->string('stage', 20)->default('audit');
            // pending | no_website | invalid_url | verified | unreachable | blocked
            $table->string('website_status', 20)->default('pending');

            $table->string('active_key', 64)->nullable()->unique();

            $table->unsignedBigInteger('website_audit_id')->nullable();
            $table->text('business_summary')->nullable();
            $table->json('likely_needs')->nullable();
            $table->json('personalization_points')->nullable();
            $table->unsignedBigInteger('recommended_offer_id')->nullable();
            $table->json('supporting_offer_ids')->nullable();
            $table->text('match_reason')->nullable();
            $table->string('match_confidence', 10)->nullable();

            $table->string('error_code', 40)->nullable();
            $table->string('error_message', 255)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['owner_user_id', 'status']);
            $table->index(['owner_user_id', 'saved_lead_id']);
            $table->index(['batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_lead_analyses');
    }
};
