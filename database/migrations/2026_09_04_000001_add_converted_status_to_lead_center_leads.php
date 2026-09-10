<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // "Converted" = the client actually converted (won).
        // "Closed" keeps its existing value but now means dead / not interested (lost).
        DB::statement("ALTER TABLE lead_center_leads MODIFY COLUMN status ENUM('pending','connected','responded','follow_up_1','follow_up_2','follow_up_3','converted','closed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('lead_center_leads')->where('status', 'converted')->update(['status' => 'closed']);

        DB::statement("ALTER TABLE lead_center_leads MODIFY COLUMN status ENUM('pending','connected','responded','follow_up_1','follow_up_2','follow_up_3','closed') NOT NULL DEFAULT 'pending'");
    }
};
