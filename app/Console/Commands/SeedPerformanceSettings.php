<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;

class SeedPerformanceSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:seed-performance {--force : Force update existing settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed performance-related settings into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding performance settings...');

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

        $force = $this->option('force');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($settings as $setting) {
            $existing = Setting::where('key', $setting['key'])->first();

            if ($existing && !$force) {
                $this->warn("⏭️  Skipped: {$setting['key']} (already exists)");
                $skipped++;
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'description' => $setting['description']
                ]
            );

            if ($existing) {
                $this->line("✅ Updated: {$setting['key']}");
                $updated++;
            } else {
                $this->info("✨ Created: {$setting['key']}");
                $created++;
            }
        }

        $this->newLine();
        $this->info("Performance settings seeded successfully!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Total', count($settings)]
            ]
        );

        return Command::SUCCESS;
    }
}
