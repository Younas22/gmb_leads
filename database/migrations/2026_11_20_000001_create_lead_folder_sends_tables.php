<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Send to a lead folder": every lead in a folder gets ITS OWN AI email draft, one at a
 * time with a pause in between (Gmail or Resend). One row per run + one row per lead.
 * New tables only.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lead_folder_sends')) {
            Schema::create('lead_folder_sends', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // who started it
                $table->foreignId('folder_id')->nullable()->constrained('lead_folders')->nullOnDelete();
                $table->string('folder_name', 150);
                $table->string('provider', 20); // gmail | resend
                $table->unsignedBigInteger('resend_sender_id')->nullable();
                $table->unsignedInteger('interval_seconds')->default(180);
                $table->string('status', 20)->default('running'); // running | completed | cancelled
                $table->unsignedInteger('total')->default(0);
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();

                $table->index(['owner_user_id', 'status']);
            });
        }

        if (!Schema::hasTable('lead_folder_send_items')) {
            Schema::create('lead_folder_send_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('send_id')->constrained('lead_folder_sends')->cascadeOnDelete();
                $table->foreignId('saved_lead_id')->nullable()->constrained('saved_leads')->nullOnDelete();
                $table->unsignedBigInteger('draft_id')->nullable();
                $table->string('lead_name', 255)->nullable();
                $table->string('recipient', 254)->nullable();
                $table->string('status', 20)->default('pending'); // pending | queued | skipped | failed | cancelled
                $table->string('reason', 255)->nullable();
                $table->unsignedBigInteger('outbound_email_id')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'due_at']);
                $table->unique(['send_id', 'saved_lead_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_folder_send_items');
        Schema::dropIfExists('lead_folder_sends');
    }
};
