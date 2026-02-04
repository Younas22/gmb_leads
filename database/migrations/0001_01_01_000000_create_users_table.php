<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Basic info
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->string('plain_password', 255)->nullable();
            $table->string('avatar', 255)->nullable();

            // Google login
            $table->string('google_id', 255)->nullable();

            // Email & password verification
            $table->boolean('email_verified')->default(false);
            $table->string('email_verification_token', 255)->nullable();
            $table->string('password_reset_token', 255)->nullable();
            $table->timestamp('password_reset_expires')->nullable();

            // User status and role
            $table->json('preferences')->nullable();
            $table->enum('login_type', ['custom','google'])->default('custom');
            $table->enum('status', ['active','inactive','suspended'])->default('active');
            $table->enum('user_type', ['admin','user'])->default('user');

            $table->boolean('welcome_tutorial_seen')->default(false);
            $table->timestamp('welcome_tutorial_seen_at')->nullable();
            $table->timestamp('email_verified_at')->nullable()->after('email_verification_token');

            // Tracking
            $table->timestamp('last_login')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('google_id', 'idx_google_id');
            $table->index('email_verification_token', 'idx_email_verification_token');
            $table->index('password_reset_token', 'idx_password_reset_token');
            $table->index(['user_type', 'status'], 'idx_user_type_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
