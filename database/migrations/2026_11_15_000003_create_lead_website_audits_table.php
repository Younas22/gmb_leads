<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Result of a safe, bounded website check. No full HTML is stored: only
 * extracted signals, a short text excerpt and evidence-backed findings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_website_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('saved_lead_id')->constrained('saved_leads')->cascadeOnDelete();
            $table->foreignId('analysis_id')->nullable()->constrained('ai_lead_analyses')->nullOnDelete();

            // verified | unreachable | blocked | invalid_url
            $table->string('status', 20);
            $table->string('url_requested', 500);
            $table->string('url_final', 500)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->boolean('https_available')->nullable();
            $table->string('website_title', 255)->nullable();
            $table->json('extracted_signals')->nullable();
            $table->json('verified_findings')->nullable();
            $table->json('fetch_log')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->string('error_code', 40)->nullable();
            $table->string('error_message', 255)->nullable();
            $table->timestamp('audited_at')->nullable();
            $table->timestamps();

            $table->index(['owner_user_id', 'saved_lead_id']);
            $table->index('audited_at');
        });

        Schema::table('ai_lead_analyses', function (Blueprint $table) {
            $table->foreign('website_audit_id')->references('id')->on('lead_website_audits')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_lead_analyses', function (Blueprint $table) {
            $table->dropForeign(['website_audit_id']);
        });
        Schema::dropIfExists('lead_website_audits');
    }
};
