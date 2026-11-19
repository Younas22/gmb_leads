<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Follow-up reminders. saved_leads has only one follow_up_date (no notes, no
 * completion state, no link to a sent message), so reminders live here and the
 * earliest open one is mirrored to saved_leads.follow_up_date for the Leads pages.
 * Nothing is ever sent automatically from a follow-up.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('email_follow_ups', ['users', 'outbound_emails', 'saved_leads']);

        Schema::create('email_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('saved_lead_id')->constrained('saved_leads')->cascadeOnDelete();
            $table->foreignId('outbound_email_id')->nullable()->constrained('outbound_emails')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date');
            $table->text('notes')->nullable();
            // open | completed | cancelled
            $table->string('status', 20)->default('open');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 40)->nullable();
            $table->timestamps();

            $table->index(['owner_user_id', 'status', 'due_date']);
            $table->index(['saved_lead_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_follow_ups');
    }
};
