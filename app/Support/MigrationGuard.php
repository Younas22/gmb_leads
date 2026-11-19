<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Makes "create table" migrations safe to re-run on MySQL, where DDL is not
 * transactional: if an earlier run created the table and then failed (e.g. a
 * foreign key to a table that did not exist yet), the half-built table is left
 * behind and the migration is not recorded, so every retry fails with
 * "Base table or view already exists".
 */
class MigrationGuard
{
    /**
     * @param  string        $table     table the migration is about to create
     * @param  list<string>  $requires  tables its foreign keys point to
     */
    public static function prepare(string $table, array $requires = []): void
    {
        $missing = array_values(array_filter($requires, fn (string $t) => !Schema::hasTable($t)));
        if ($missing !== []) {
            throw new RuntimeException(sprintf(
                'Cannot create "%s": required table(s) %s do not exist yet. Run all pending migrations in order with "php artisan migrate --force" (do not use --path for a single file).',
                $table,
                implode(', ', $missing)
            ));
        }

        if (!Schema::hasTable($table)) {
            return;
        }

        if (DB::table($table)->exists()) {
            throw new RuntimeException(sprintf(
                'Table "%s" already exists and contains data, but this migration is not recorded. Check it manually before continuing.',
                $table
            ));
        }

        // Empty leftover from a failed run: rebuild it with the full definition.
        Schema::disableForeignKeyConstraints();
        try {
            Schema::drop($table);
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
