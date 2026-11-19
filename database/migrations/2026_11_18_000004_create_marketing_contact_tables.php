<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opted-in contacts for Resend sending. Deliberately separate from saved_leads:
 * a lead found on Google Maps is NOT a contact who agreed to receive email.
 * Every consent change is logged in marketing_consent_logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('marketing_contacts', ['users', 'saved_leads']);
        Schema::create('marketing_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('saved_lead_id')->nullable()->constrained('saved_leads')->nullOnDelete();
            $table->string('email', 254);
            $table->char('email_hash', 64);
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('organization', 150)->nullable();
            // manual | csv
            $table->string('source', 20);
            // opted_in | withdrawn
            $table->string('consent_status', 20);
            $table->string('consent_source', 40)->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->text('consent_evidence')->nullable();
            $table->string('consent_purpose', 150)->nullable();
            $table->foreignId('consent_recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('tags')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_user_id', 'email_hash']);
            $table->index(['owner_user_id', 'consent_status']);
        });

        \App\Support\MigrationGuard::prepare('marketing_contact_lists', ['users']);
        Schema::create('marketing_contact_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['owner_user_id', 'name']);
        });

        \App\Support\MigrationGuard::prepare('marketing_contact_list_members', ['marketing_contacts', 'marketing_contact_lists']);
        Schema::create('marketing_contact_list_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('marketing_contact_lists')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('marketing_contacts')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['list_id', 'contact_id']);
        });

        \App\Support\MigrationGuard::prepare('marketing_consent_logs', ['marketing_contacts']);
        Schema::create('marketing_consent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('marketing_contacts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['contact_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_consent_logs');
        Schema::dropIfExists('marketing_contact_list_members');
        Schema::dropIfExists('marketing_contact_lists');
        Schema::dropIfExists('marketing_contacts');
    }
};
