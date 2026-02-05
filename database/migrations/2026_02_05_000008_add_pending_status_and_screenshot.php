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
        DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'active'");

        // payments mein screenshot column add karo
        Schema::table('payments', function (Blueprint $table) {
            $table->string('screenshot', 255)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('active', 'expired', 'cancelled') DEFAULT 'active'");

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('screenshot');
        });
    }
};
