<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // subscriptions status enum mein 'pending' add karo
        self::alterEnum('subscriptions', 'status', ['active', 'expired', 'cancelled', 'pending'], 'active', true, fn () => DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'active'"));

        // payments mein screenshot column add karo
        Schema::table('payments', function (Blueprint $table) {
            $table->string('screenshot', 255)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        self::alterEnum('subscriptions', 'status', ['active', 'expired', 'cancelled'], 'active', true, fn () => DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('active', 'expired', 'cancelled') DEFAULT 'active'"));

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('screenshot');
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
