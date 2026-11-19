<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Per-sender tracking preferences. No row = tracking OFF (the default for everyone). */
return new class extends Migration
{
    public function up(): void
    {
        \App\Support\MigrationGuard::prepare('email_tracking_settings', ['users']);

        Schema::create('email_tracking_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('link_tracking_enabled')->default(false);
            $table->unsignedSmallInteger('retention_days')->default(90);
            $table->timestamp('disclosure_accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_tracking_settings');
    }
};
