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
        Schema::create('packages', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 100);
            $table->string('slug', 50)->index();
            $table->enum('type', ['free', 'monthly', 'yearly'])->index();
            $table->decimal('price', 10, 2)->nullable()->default(0.00);
            $table->char('currency', 3)->nullable()->default('USD');

            $table->integer('leads_limit')->nullable()->default(500);
            $table->integer('api_keys_limit')->nullable()->default(1);
            $table->integer('searches_per_day')->nullable()->default(50);

            $table->boolean('export_data')->nullable()->default(0);
            $table->boolean('advanced_filters')->nullable()->default(0);
            $table->boolean('bulk_operations')->nullable()->default(0);
            $table->boolean('api_access')->nullable()->default(0);
            $table->boolean('priority_support')->nullable()->default(0);

            $table->text('description')->nullable();
            $table->longText('features_list')->nullable();

            $table->integer('billing_cycle')->nullable()->default(1);
            $table->integer('trial_days')->nullable()->default(0);

            $table->boolean('is_active')->nullable()->default(1)->index();
            $table->boolean('is_popular')->nullable()->default(0);
            $table->integer('sort_order')->nullable()->default(0)->index();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
