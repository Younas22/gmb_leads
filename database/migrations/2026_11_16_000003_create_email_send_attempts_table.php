<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Every call to the Gmail API for an outbound email (no content, no tokens). */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('email_send_attempts', ['outbound_emails']);

        Schema::create('email_send_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_email_id')->constrained('outbound_emails')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_no');
            // success | failed | unknown
            $table->string('result', 20);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error_code', 40)->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_send_attempts');
    }
};
