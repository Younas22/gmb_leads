<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Addresses an account must never email again (unsubscribes, manual blocks). */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('email_suppressions', ['users', 'outbound_emails']);

        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email_hash', 64);
            $table->string('email', 255);
            // unsubscribed | manual | bounced | complaint
            $table->string('reason', 20);
            $table->foreignId('outbound_email_id')->nullable()->constrained('outbound_emails')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['owner_user_id', 'email_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
    }
};
