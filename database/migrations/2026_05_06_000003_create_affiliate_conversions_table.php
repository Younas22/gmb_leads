<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();       // who was referred
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete(); // who referred
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('referral_code', 20);
            $table->decimal('sale_amount', 10, 2)->default(0);
            $table->string('commission_type', 10)->default('percent'); // 'fixed' or 'percent'
            $table->decimal('commission_rate', 10, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('available_at')->nullable(); // delay for refund protection
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
            $table->index(['referral_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_conversions');
    }
};
