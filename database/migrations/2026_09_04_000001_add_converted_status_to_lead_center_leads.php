<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Converted" = the client actually converted (won).
        // "Closed" keeps its existing value but now means dead / not interested (lost).
        self::alterEnum('lead_center_leads', 'status', ['pending','connected','responded','follow_up_1','follow_up_2','follow_up_3','converted','closed'], 'pending', false, fn () => DB::statement("ALTER TABLE lead_center_leads MODIFY COLUMN status ENUM('pending','connected','responded','follow_up_1','follow_up_2','follow_up_3','converted','closed') NOT NULL DEFAULT 'pending'"));
    }

    public function down(): void
    {
        DB::table('lead_center_leads')->where('status', 'converted')->update(['status' => 'closed']);

        self::alterEnum('lead_center_leads', 'status', ['pending','connected','responded','follow_up_1','follow_up_2','follow_up_3','closed'], 'pending', false, fn () => DB::statement("ALTER TABLE lead_center_leads MODIFY COLUMN status ENUM('pending','connected','responded','follow_up_1','follow_up_2','follow_up_3','closed') NOT NULL DEFAULT 'pending'"));
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
