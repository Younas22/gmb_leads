<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_center_leads', function (Blueprint $table) {
            $table->id();

            // Owning account (company owner or individual user) — matches lead_folders ownership model
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Link back to the original "My Leads" record when this was moved from there (nullable — CSV/paste imports have none)
            $table->foreignId('saved_lead_id')->nullable()->constrained('saved_leads')->nullOnDelete();

            $table->foreignId('folder_id')->nullable()->constrained('lead_center_folders')->nullOnDelete();

            $table->string('company_name');
            $table->string('website', 500)->nullable();

            // Reuse the existing countries/states/cities tables (same convention as saved_leads: id references)
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->unsignedBigInteger('state_id')->nullable()->index();
            $table->unsignedBigInteger('city_id')->nullable()->index();

            $table->enum('status', ['pending', 'connected', 'responded', 'follow_up', 'closed'])
                  ->default('pending')->index();

            // Normalized key used for duplicate detection (website host, or company name when no website)
            $table->string('dedupe_key', 255);

            $table->timestamps();

            $table->unique(['user_id', 'dedupe_key']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'folder_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_center_leads');
    }
};
