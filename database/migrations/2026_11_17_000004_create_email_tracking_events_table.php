<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only activity events with a verified source (gmail_api, tracking_redirect,
 * user_action, unsubscribe_link, system). dedupe_key (unique) stops double recording.
 * No IP addresses or user-agent strings are stored: client_hash is a short keyed hash
 * that changes daily and is only used to estimate unique clicks.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('email_tracking_events', ['users', 'outbound_emails', 'saved_leads', 'email_tracking_links']);

        Schema::create('email_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('outbound_email_id')->nullable()->constrained('outbound_emails')->cascadeOnDelete();
            $table->foreignId('saved_lead_id')->nullable()->constrained('saved_leads')->nullOnDelete();
            $table->foreignId('tracking_link_id')->nullable()->constrained('email_tracking_links')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 30);
            $table->string('event_source', 20);
            // link clicks only: probable_human | suspected_scanner | internal
            $table->string('classification', 20)->nullable();
            $table->char('client_hash', 16)->nullable();
            $table->timestamp('occurred_at');
            $table->string('dedupe_key', 120)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['owner_user_id', 'event_type', 'occurred_at']);
            $table->index(['saved_lead_id', 'occurred_at']);
            $table->index(['outbound_email_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_tracking_events');
    }
};
