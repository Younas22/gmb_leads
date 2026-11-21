<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * reply_checked_at: when the connected Gmail inbox was last checked for a reply (email:detect-replies).
 * open_tracking: the email carried the open pixel (tracking was on when it was queued).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('outbound_emails', 'reply_checked_at')) {
            Schema::table('outbound_emails', function (Blueprint $table) {
                $table->timestamp('reply_checked_at')->nullable();
            });
        }
        if (!Schema::hasColumn('outbound_emails', 'open_tracking')) {
            Schema::table('outbound_emails', function (Blueprint $table) {
                $table->boolean('open_tracking')->default(false);
            });
        }
    }

    public function down(): void
    {
        foreach (['reply_checked_at', 'open_tracking'] as $col) {
            if (Schema::hasColumn('outbound_emails', $col)) {
                Schema::table('outbound_emails', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }
};
