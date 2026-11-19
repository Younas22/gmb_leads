<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: widen the enum first — keep the OLD 'follow_up' value alongside the new
        // ones, so any existing 'follow_up' rows stay valid while we migrate them below.
        // (Doing the UPDATE before this would truncate/reject the new value under strict SQL mode.)
        self::alterEnum('lead_center_leads', 'status', ['pending','connected','responded','follow_up','follow_up_1','follow_up_2','follow_up_3','closed'], 'pending', false, fn () => DB::statement("ALTER TABLE lead_center_leads MODIFY COLUMN status ENUM('pending','connected','responded','follow_up','follow_up_1','follow_up_2','follow_up_3','closed') NOT NULL DEFAULT 'pending'"));

        // Step 2: migrate existing "follow_up" leads to "follow_up_1".
        DB::table('lead_center_leads')->where('status', 'follow_up')->update(['status' => 'follow_up_1']);

        // Step 3: now that nothing uses the old value, narrow the enum to the final list.
        self::alterEnum('lead_center_leads', 'status', ['pending','connected','responded','follow_up_1','follow_up_2','follow_up_3','closed'], 'pending', false, fn () => DB::statement("ALTER TABLE lead_center_leads MODIFY COLUMN status ENUM('pending','connected','responded','follow_up_1','follow_up_2','follow_up_3','closed') NOT NULL DEFAULT 'pending'"));
    }

    public function down(): void
    {
        self::alterEnum('lead_center_leads', 'status', ['pending','connected','responded','follow_up','follow_up_1','follow_up_2','follow_up_3','closed'], 'pending', false, fn () => DB::statement("ALTER TABLE lead_center_leads MODIFY COLUMN status ENUM('pending','connected','responded','follow_up','follow_up_1','follow_up_2','follow_up_3','closed') NOT NULL DEFAULT 'pending'"));

        DB::table('lead_center_leads')->whereIn('status', ['follow_up_1', 'follow_up_2', 'follow_up_3'])->update(['status' => 'follow_up']);

        self::alterEnum('lead_center_leads', 'status', ['pending','connected','responded','follow_up','closed'], 'pending', false, fn () => DB::statement("ALTER TABLE lead_center_leads MODIFY COLUMN status ENUM('pending','connected','responded','follow_up','closed') NOT NULL DEFAULT 'pending'"));
    }

    /**
     * MySQL/MariaDB run the original raw ALTER statement unchanged (this is what
     * production already ran). Other drivers — e.g. SQLite for tests and fresh
     * local installs — get the equivalent change through the schema builder.
     */
    private static function alterEnum(string $table, string $column, array $values, string $default, bool $nullable, \Closure $mysqlStatement): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $mysqlStatement();
            return;
        }

        Schema::table($table, function (\Illuminate\Database\Schema\Blueprint $table) use ($column, $values, $default, $nullable) {
            $definition = $table->enum($column, $values)->default($default);
            if ($nullable) {
                $definition->nullable();
            }
            $definition->change();
        });
    }
};
