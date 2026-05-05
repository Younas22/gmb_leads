<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_leads', function (Blueprint $table) {
            $table->smallInteger('seo_score')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('saved_leads', function (Blueprint $table) {
            $table->dropColumn('seo_score');
        });
    }
};
