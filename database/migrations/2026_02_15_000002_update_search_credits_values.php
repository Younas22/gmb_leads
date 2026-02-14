<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update search_credits values to reflect API call counting (not search count).
     */
    public function up(): void
    {
        // Get all packages and update their search_credits feature values
        $packages = DB::table('packages')->get();

        foreach ($packages as $package) {
            $newValue = match ($package->slug) {
                'starter' => '500',
                'growth' => '2000',
                'pro' => '5000',
                default => '500',
            };

            DB::table('package_features')
                ->where('package_id', $package->id)
                ->where('feature_key', 'search_credits')
                ->update(['feature_value' => $newValue]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $packages = DB::table('packages')->get();

        foreach ($packages as $package) {
            $oldValue = match ($package->slug) {
                'starter' => '50',
                'growth' => '150',
                'pro' => '250',
                default => '50',
            };

            DB::table('package_features')
                ->where('package_id', $package->id)
                ->where('feature_key', 'search_credits')
                ->update(['feature_value' => $oldValue]);
        }
    }
};
