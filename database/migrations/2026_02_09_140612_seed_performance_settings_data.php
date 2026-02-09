<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            // System Performance Settings
            [
                'key' => 'session_timeout',
                'value' => '120',
                'type' => 'integer',
                'group' => 'performance',
                'description' => 'Session timeout duration in minutes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'cache_duration',
                'value' => '60',
                'type' => 'integer',
                'group' => 'performance',
                'description' => 'Cache duration in minutes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_query_cache',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'performance',
                'description' => 'Enable database query caching',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_route_cache',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'performance',
                'description' => 'Enable route caching',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_view_cache',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'performance',
                'description' => 'Enable view caching',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_config_cache',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'performance',
                'description' => 'Enable configuration caching',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pagination_limit',
                'value' => '50',
                'type' => 'integer',
                'group' => 'performance',
                'description' => 'Default pagination limit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_query_execution_time',
                'value' => '30',
                'type' => 'integer',
                'group' => 'performance',
                'description' => 'Maximum query execution time in seconds',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($settings as $setting) {
            // Check if setting already exists
            $exists = DB::table('settings')->where('key', $setting['key'])->exists();

            if (!$exists) {
                DB::table('settings')->insert($setting);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete all performance settings
        DB::table('settings')->where('group', 'performance')->delete();
    }
};
