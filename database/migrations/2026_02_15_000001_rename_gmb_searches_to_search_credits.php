<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('package_features')
            ->where('feature_key', 'gmb_searches')
            ->update(['feature_key' => 'search_credits']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('package_features')
            ->where('feature_key', 'search_credits')
            ->update(['feature_key' => 'gmb_searches']);
    }
};
