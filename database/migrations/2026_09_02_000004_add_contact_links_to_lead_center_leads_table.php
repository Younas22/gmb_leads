<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_center_leads', function (Blueprint $table) {
            // Per-channel outreach links, e.g. {"email": "...", "facebook": "...\n...", "whatsapp": "..."}
            $table->json('contact_links')->nullable()->after('dedupe_key');
        });
    }

    public function down(): void
    {
        Schema::table('lead_center_leads', function (Blueprint $table) {
            $table->dropColumn('contact_links');
        });
    }
};
