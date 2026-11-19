<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One customer-owned Resend account per CustomerNearMe account (company or single user).
 * The API key is stored with Laravel encryption (APP_KEY); only its last 4 characters
 * are kept in clear for display. Unrelated to the platform's own Resend key.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('resend_integrations', ['users']);

        Schema::create('resend_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('connected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('api_key')->nullable();            // encrypted
            $table->string('key_last4', 4)->nullable();
            // full_access | sending_access | unknown
            $table->string('key_permission', 20)->nullable();
            // active | invalid | restricted | disconnected
            $table->string('status', 20)->default('active');
            $table->string('last_error', 255)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resend_integrations');
    }
};
