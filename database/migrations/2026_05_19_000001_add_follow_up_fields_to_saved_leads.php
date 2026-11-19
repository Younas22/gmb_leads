<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_leads', function (Blueprint $table) {
            $table->string('follow_up_source', 50)->nullable()->after('notes');
            $table->date('follow_up_date')->nullable()->after('follow_up_source');
        });

        // Extend enum to include all statuses
        self::alterEnum('saved_leads', 'contact_status', ['not_contacted','contacted','responded','converted','not_interested','closed','follow_up'], 'not_contacted', false, fn () => DB::statement("ALTER TABLE saved_leads MODIFY COLUMN contact_status ENUM('not_contacted','contacted','responded','converted','not_interested','closed','follow_up') NOT NULL DEFAULT 'not_contacted'"));
    }

    public function down(): void
    {
        self::alterEnum('saved_leads', 'contact_status', ['not_contacted','contacted','responded','closed'], 'not_contacted', false, fn () => DB::statement("ALTER TABLE saved_leads MODIFY COLUMN contact_status ENUM('not_contacted','contacted','responded','closed') NOT NULL DEFAULT 'not_contacted'"));

        Schema::table('saved_leads', function (Blueprint $table) {
            $table->dropColumn(['follow_up_source', 'follow_up_date']);
        });
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
