<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One user-confirmed email built from an approved AI draft.
 *
 * Duplicate protection:
 *  - idempotency_key (unique): one row per confirmation form submission;
 *  - draft_lock (unique, nullable): "{draft_id}" while pending/sending/sent, so a
 *    draft can only be sent once unless the user explicitly re-sends after a failure.
 *
 * gmail_message_id means Gmail ACCEPTED the message; it does not prove delivery.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('outbound_emails', ['users', 'connected_email_accounts', 'saved_leads', 'ai_email_drafts']);

        Schema::create('outbound_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('connected_account_id')->nullable()->constrained('connected_email_accounts')->nullOnDelete();
            $table->foreignId('saved_lead_id')->nullable()->constrained('saved_leads')->nullOnDelete();
            $table->foreignId('draft_id')->nullable()->constrained('ai_email_drafts')->nullOnDelete();

            $table->string('idempotency_key', 64)->unique();
            $table->string('draft_lock', 40)->nullable()->unique();

            $table->string('from_email', 255);
            $table->string('to_email', 255);
            $table->string('subject', 200);
            $table->text('body');

            // pending | sending | sent | failed | cancelled
            $table->string('status', 20)->default('pending');
            $table->boolean('outcome_unknown')->default(false);
            $table->string('gmail_message_id', 64)->nullable()->index();
            $table->string('gmail_thread_id', 64)->nullable();
            $table->string('rfc_message_id', 255)->nullable();
            $table->string('error_code', 40)->nullable();
            $table->string('error_message', 255)->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['owner_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_emails');
    }
};
