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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('package_id')->nullable()->index();

            $table->enum('package_type', ['free', 'monthly', 'yearly'])->default('free')->index();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->index();

            $table->integer('leads_limit')->default(500)->nullable();
            $table->integer('api_keys_limit')->default(1)->nullable();

            $table->longText('features')->nullable();

            $table->dateTime('starts_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->dateTime('expires_at')->nullable()->index();

            $table->string('payment_id', 255)->nullable();
            $table->string('payment_method', 50)->nullable();

            $table->decimal('amount', 10, 2)->default(0.00)->nullable();
            $table->char('currency', 3)->default('USD')->nullable();

            $table->boolean('auto_renewal')->default(0)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
