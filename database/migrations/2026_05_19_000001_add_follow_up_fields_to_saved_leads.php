<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_leads', function (Blueprint $table) {
            $table->string('follow_up_source', 50)->nullable()->after('notes');
            $table->date('follow_up_date')->nullable()->after('follow_up_source');
        });

        // Extend enum to include all statuses
        DB::statement("ALTER TABLE saved_leads MODIFY COLUMN contact_status ENUM('not_contacted','contacted','responded','converted','not_interested','closed','follow_up') NOT NULL DEFAULT 'not_contacted'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE saved_leads MODIFY COLUMN contact_status ENUM('not_contacted','contacted','responded','closed') NOT NULL DEFAULT 'not_contacted'");

        Schema::table('saved_leads', function (Blueprint $table) {
            $table->dropColumn(['follow_up_source', 'follow_up_date']);
        });
    }
};
