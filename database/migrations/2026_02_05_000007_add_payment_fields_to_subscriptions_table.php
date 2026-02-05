<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_method_id')->nullable()->after('company_id');
            $table->decimal('amount_paid', 10, 2)->nullable()->after('payment_method_id');
            $table->boolean('is_trial')->default(false)->after('status');
            $table->boolean('auto_renew')->default(false)->after('is_trial');
            $table->text('notes')->nullable()->after('auto_renew');

            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn(['payment_method_id', 'amount_paid', 'is_trial', 'auto_renew', 'notes']);
        });
    }
};
