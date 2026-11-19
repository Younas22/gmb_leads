<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permission-based campaigns sent as Resend Broadcasts to a per-campaign Resend
 * Segment. Recipients are snapshotted at approval; each one also gets an
 * outbound_emails row so history, replies, follow-ups and analytics are shared
 * with single emails.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('user_email_templates', ['users']);
        Schema::create('user_email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->string('subject', 200);
            $table->text('body');
            $table->timestamps();
        });

        \App\Support\MigrationGuard::prepare('email_campaigns', ['users', 'resend_integrations', 'resend_senders', 'marketing_contact_lists']);
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resend_integration_id')->nullable()->constrained('resend_integrations')->nullOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('resend_senders')->nullOnDelete();
            $table->foreignId('list_id')->nullable()->constrained('marketing_contact_lists')->nullOnDelete();
            $table->json('segment_filters')->nullable();
            $table->string('name', 120);
            $table->string('subject', 200);
            $table->text('body');
            // draft | preparing | scheduled | submitted | cancelled | failed | unknown
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('reserved_quota')->default(0);
            $table->string('provider_segment_id', 64)->nullable();
            $table->string('provider_broadcast_id', 64)->nullable()->index();
            $table->unsignedSmallInteger('test_sends')->default(0);
            $table->string('last_error', 255)->nullable();
            $table->timestamps();

            $table->index(['owner_user_id', 'status']);
        });

        \App\Support\MigrationGuard::prepare('email_campaign_recipients', ['email_campaigns', 'marketing_contacts']);
        Schema::create('email_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_campaigns')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('marketing_contacts')->nullOnDelete();
            $table->unsignedBigInteger('outbound_email_id')->nullable()->index();
            $table->string('email', 254);
            // pending | synced | skipped
            $table->string('sync_status', 20)->default('pending');
            $table->string('skip_reason', 40)->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'email']);
        });

        // Atomic quota reservations shared by a company and its team members
        \App\Support\MigrationGuard::prepare('resend_usage_counters', ['users']);
        Schema::create('resend_usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('period', 20); // d:2026-11-18 | m:2026-11 | c:2026-11
            $table->unsignedInteger('used')->default(0);
            $table->timestamps();

            $table->unique(['owner_user_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resend_usage_counters');
        Schema::dropIfExists('email_campaign_recipients');
        Schema::dropIfExists('email_campaigns');
        Schema::dropIfExists('user_email_templates');
    }
};
