<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->string('icon', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Insert default payment methods
        DB::table('payment_methods')->insert([
            ['name' => 'Credit/Debit Card', 'slug' => 'card', 'icon' => 'fa-credit-card', 'description' => 'Pay with Visa, Mastercard, etc.', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'JazzCash', 'slug' => 'jazzcash', 'icon' => 'fa-mobile-alt', 'description' => 'Pay via JazzCash mobile wallet', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Easypaisa', 'slug' => 'easypaisa', 'icon' => 'fa-mobile-alt', 'description' => 'Pay via Easypaisa mobile wallet', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Bank Transfer', 'slug' => 'bank', 'icon' => 'fa-university', 'description' => 'Direct bank transfer', 'is_active' => true, 'sort_order' => 4],
            ['name' => 'NayaPay', 'slug' => 'nayapay', 'icon' => 'fa-wallet', 'description' => 'Pay via NayaPay wallet', 'is_active' => true, 'sort_order' => 5],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
