<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Audit trail for Gmail connections, sends, cancellations and unsubscribes. No tokens or bodies. */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('email_audit_logs', ['users', 'outbound_emails']);

        Schema::create('email_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->foreignId('outbound_email_id')->nullable()->constrained('outbound_emails')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['owner_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_audit_logs');
    }
};
