<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/** Shortcut for "plan:coming-soon --group=ai" (locks or unlocks only the AI features). */
class LockAiOnly extends Command
{
    protected $signature = 'plan:lock-ai
        {--message=Coming soon : Message shown to users on the locked features}
        {--unlock : Remove the lock instead}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Lock or unlock only the AI features in every package (shown as "Coming soon").';

    public function handle(): int
    {
        return $this->call('plan:coming-soon', [
            '--group' => ['ai'],
            '--message' => $this->option('message'),
            '--unlock' => $this->option('unlock'),
            '--dry-run' => $this->option('dry-run'),
        ]);
    }
}
