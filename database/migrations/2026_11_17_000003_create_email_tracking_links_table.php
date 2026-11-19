<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per tracked link in one outbound email. The redirect endpoint only ever
 * sends visitors to original_url stored here, never to a URL taken from the request.
 * Only a SHA-256 hash of the random token is stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('email_tracking_links', ['users', 'outbound_emails']);

        Schema::create('email_tracking_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('outbound_email_id')->constrained('outbound_emails')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('original_url', 2048);
            $table->unsignedSmallInteger('link_position');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();

            $table->index(['outbound_email_id', 'link_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_tracking_links');
    }
};
