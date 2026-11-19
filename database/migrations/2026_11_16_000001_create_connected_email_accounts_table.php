<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A user's own Gmail account connected for sending (gmail.send scope only).
 * Tokens are stored encrypted (Laravel "encrypted" cast, APP_KEY).
 * One row per user; reconnecting updates it.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('connected_email_accounts', ['users']);

        Schema::create('connected_email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('provider', 20)->default('gmail');
            $table->string('email', 255)->nullable();
            $table->string('google_sub', 64)->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            // active | needs_reconnect | disconnected
            $table->string('status', 20)->default('active');
            $table->string('last_error', 255)->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['owner_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connected_email_accounts');
    }
};
