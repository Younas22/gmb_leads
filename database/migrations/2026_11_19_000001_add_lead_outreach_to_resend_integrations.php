<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional, owner-acknowledged setting: allow one-to-one Resend emails to saved leads
 * (not opted-in contacts). Off unless the owner switches it on; the timestamp records
 * when (and by whom) the risk notice was accepted. Additive and nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resend_integrations', function (Blueprint $table) {
            if (!Schema::hasColumn('resend_integrations', 'lead_outreach_enabled_at')) {
                $table->timestamp('lead_outreach_enabled_at')->nullable()->after('connected_at');
            }
            if (!Schema::hasColumn('resend_integrations', 'lead_outreach_enabled_by')) {
                $table->unsignedBigInteger('lead_outreach_enabled_by')->nullable()->after('lead_outreach_enabled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resend_integrations', function (Blueprint $table) {
            $table->dropColumn(['lead_outreach_enabled_at', 'lead_outreach_enabled_by']);
        });
    }
};
