<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clears the legacy `users.plain_password` column (readable copies of user
 * passwords). Hashed passwords in `users.password` are NOT touched, so nobody
 * is logged out or locked out by running this.
 *
 * Dry-run by default; pass --force to actually clear the values.
 * The column itself is left in place — drop it in a later, separate migration
 * once this release is stable.
 */
class PurgePlainPasswords extends Command
{
    protected $signature = 'security:purge-plain-passwords {--force : Actually clear the values (otherwise only reports the count)}';

    protected $description = 'Clear readable passwords stored in users.plain_password (hashed passwords are kept)';

    public function handle(): int
    {
        if (!Schema::hasColumn('users', 'plain_password')) {
            $this->info('Column users.plain_password does not exist. Nothing to do.');
            return self::SUCCESS;
        }

        $count = DB::table('users')->whereNotNull('plain_password')->count();

        $this->info("Users with a readable password stored: {$count}");

        if ($count === 0) {
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            $this->warn('Dry run only. Re-run with --force to clear these values.');
            return self::SUCCESS;
        }

        $cleared = DB::table('users')->whereNotNull('plain_password')->update(['plain_password' => null]);

        $this->info("Cleared plain_password for {$cleared} user(s). Hashed passwords were not changed.");

        return self::SUCCESS;
    }
}
