<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-customer webhook endpoint. The URL carries a random endpoint token that only
 * SELECTS the configuration; every request must still carry a valid Svix signature
 * made with that customer's signing secret (stored encrypted).
 *
 * Receipts keep minimal metadata (no message bodies, no IP / user agent) and give
 * replay protection: a given svix-id is processed once per endpoint.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('resend_webhook_configs', ['users', 'resend_integrations']);
        Schema::create('resend_webhook_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->unique()->constrained('resend_integrations')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('endpoint_token', 64)->unique();
            $table->string('provider_webhook_id', 64)->nullable();
            $table->text('signing_secret')->nullable(); // encrypted
            // needs_setup | active | failing
            $table->string('status', 20)->default('needs_setup');
            $table->timestamp('last_event_at')->nullable();
            $table->string('last_event_type', 40)->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->string('last_failure_reason', 120)->nullable();
            $table->unsignedInteger('verified_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);
            $table->timestamps();
        });

        \App\Support\MigrationGuard::prepare('resend_webhook_receipts', ['resend_webhook_configs']);
        Schema::create('resend_webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_config_id')->constrained('resend_webhook_configs')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('svix_id', 100);
            $table->string('event_type', 40);
            $table->timestamp('event_created_at')->nullable();
            // received | processed | unmatched | ignored | failed
            $table->string('status', 20)->default('received');
            $table->json('payload')->nullable(); // minimal subset, see ResendWebhookService::minimal()
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('note', 120)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['webhook_config_id', 'svix_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resend_webhook_receipts');
        Schema::dropIfExists('resend_webhook_configs');
    }
};
