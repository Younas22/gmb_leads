<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 20)->nullable()->unique()->after('extension_token');
            $table->string('referred_by', 20)->nullable()->after('referral_code');
            $table->boolean('affiliate_active')->default(true)->after('referred_by');
            $table->string('custom_commission_type', 10)->nullable()->after('affiliate_active'); // 'fixed' or 'percent'
            $table->decimal('custom_commission_value', 10, 2)->nullable()->after('custom_commission_type');

            $table->index('referral_code');
            $table->index('referred_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'referred_by', 'affiliate_active', 'custom_commission_type', 'custom_commission_value']);
        });
    }
};
