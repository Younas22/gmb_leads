<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replies confirmed MANUALLY by the user (Phase 4 does not read the mailbox).
 * One record per outbound email; corrections update it and are logged as events.
 * No inbox contents are stored, only the user's own short notes.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('email_reply_records', ['users', 'outbound_emails', 'saved_leads']);

        Schema::create('email_reply_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('outbound_email_id')->unique()->constrained('outbound_emails')->cascadeOnDelete();
            $table->foreignId('saved_lead_id')->nullable()->constrained('saved_leads')->nullOnDelete();
            // interested | not_interested | needs_follow_up | meeting_requested | converted | other
            $table->string('reply_status', 30);
            $table->timestamp('reply_received_at');
            $table->text('notes')->nullable();
            // "manual" in Phase 4; a future authorised Gmail sync would use another value
            $table->string('source', 20)->default('manual');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['owner_user_id', 'reply_received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_reply_records');
    }
};
