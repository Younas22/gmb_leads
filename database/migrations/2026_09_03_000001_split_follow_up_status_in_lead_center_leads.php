<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Any existing "follow_up" leads become "follow_up_1" before the old value is removed from the enum.
        DB::table('lead_center_leads')->where('status', 'follow_up')->update(['status' => 'follow_up_1']);

        DB::statement("ALTER TABLE lead_center_leads MODIFY COLUMN status ENUM('pending','connected','responded','follow_up_1','follow_up_2','follow_up_3','closed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('lead_center_leads')->whereIn('status', ['follow_up_1', 'follow_up_2', 'follow_up_3'])->update(['status' => 'follow_up']);

        DB::statement("ALTER TABLE lead_center_leads MODIFY COLUMN status ENUM('pending','connected','responded','follow_up','closed') NOT NULL DEFAULT 'pending'");
    }
};
