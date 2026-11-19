<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI-generated, user-editable email drafts. Phase 2 never sends email.
 * Each regeneration adds a new row; is_current marks the latest per lead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_email_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('saved_lead_id')->constrained('saved_leads')->cascadeOnDelete();
            $table->foreignId('analysis_id')->nullable()->constrained('ai_lead_analyses')->nullOnDelete();
            $table->foreignId('website_audit_id')->nullable()->constrained('lead_website_audits')->nullOnDelete();
            $table->foreignId('selected_offer_id')->nullable()->constrained('user_offers')->nullOnDelete();

            $table->string('subject', 200)->nullable();
            $table->text('body')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('tone', 30)->nullable();

            // generated | failed
            $table->string('generation_status', 20)->default('generated');
            // draft | approved | discarded
            $table->string('review_status', 20)->default('draft');
            $table->boolean('is_current')->default(true);
            $table->string('personalization_level', 10)->nullable(); // specific | generic
            $table->json('evidence_snapshot')->nullable(); // findings / points the draft relied on
            $table->string('model', 80)->nullable();
            $table->string('prompt_version', 30)->nullable();
            $table->boolean('user_edited')->default(false);
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['owner_user_id', 'saved_lead_id', 'is_current']);
            $table->index(['owner_user_id', 'review_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_email_drafts');
    }
};
