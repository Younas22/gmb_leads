<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sending domains as reported by Resend (status and DNS records are copied from the
 * API, never invented) and the sender identities a customer may send from.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('resend_domains', ['users', 'resend_integrations']);
        Schema::create('resend_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('resend_integrations')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider_domain_id', 64);
            $table->string('name', 253);
            // Resend status: not_started | pending | verified | partially_verified | partially_failed | temporary_failure | failed
            $table->string('status', 30)->default('not_started');
            $table->string('region', 30)->nullable();
            $table->json('records')->nullable();
            $table->boolean('open_tracking')->default(false);
            $table->boolean('click_tracking')->default(false);
            $table->boolean('present')->default(true); // false when no longer returned by Resend
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['integration_id', 'provider_domain_id']);
            $table->index(['owner_user_id', 'status']);
        });

        \App\Support\MigrationGuard::prepare('resend_senders', ['resend_domains']);
        Schema::create('resend_senders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained('resend_domains')->cascadeOnDelete();
            $table->string('from_name', 100);
            $table->string('from_email', 254);
            $table->string('reply_to', 254)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['owner_user_id', 'from_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resend_senders');
        Schema::dropIfExists('resend_domains');
    }
};
