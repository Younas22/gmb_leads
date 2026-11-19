<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One AI Sales Profile per account owner (the individual user, or the company
 * account for team members — same ownership model as leads and Lead Center).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sales_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('company_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('website', 500)->nullable();
            $table->json('portfolio_urls')->nullable();

            $table->json('target_industries')->nullable();
            $table->json('target_locations')->nullable();

            $table->string('language', 10)->default('en');
            $table->string('tone', 30)->default('professional');
            $table->text('signature')->nullable();
            $table->string('sender_name', 120)->nullable();
            $table->string('sender_postal_address', 500)->nullable();
            $table->text('ai_instructions')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sales_profiles');
    }
};
