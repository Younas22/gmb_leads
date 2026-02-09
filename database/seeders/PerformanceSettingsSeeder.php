<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class PerformanceSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // System Performance Settings
            [
                'key' => 'session_timeout',
                'value' => '120',
                'type' => 'integer',
                'group' => 'performance',
                'description' => 'Session timeout duration in minutes'
            ],
            [
                'key' => 'cache_duration',
                'value' => '60',
                'type' => 'integer',
                'group' => 'performance',
                'description' => 'Cache duration in minutes'
            ],
            [
                'key' => 'enable_query_cache',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'performance',
                'description' => 'Enable database query caching'
            ],
            [
                'key' => 'enable_route_cache',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'performance',
                'description' => 'Enable route caching'
            ],
            [
                'key' => 'enable_view_cache',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'performance',
                'description' => 'Enable view caching'
            ],
            [
                'key' => 'enable_config_cache',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'performance',
                'description' => 'Enable configuration caching'
            ],
            [
                'key' => 'pagination_limit',
                'value' => '50',
                'type' => 'integer',
                'group' => 'performance',
                'description' => 'Default pagination limit'
            ],
            [
                'key' => 'max_query_execution_time',
                'value' => '30',
                'type' => 'integer',
                'group' => 'performance',
                'description' => 'Maximum query execution time in seconds'
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'description' => $setting['description']
                ]
            );
        }

        $this->command->info('Performance settings seeded successfully!');
    }
}
