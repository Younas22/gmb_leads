<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_center_leads', function (Blueprint $table) {
            $table->string('email')->nullable()->after('website')->index();
        });
    }

    public function down(): void
    {
        Schema::table('lead_center_leads', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
