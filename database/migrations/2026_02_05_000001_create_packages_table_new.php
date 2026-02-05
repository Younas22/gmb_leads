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
            $table->string('slug', 100)->unique();
            $table->enum('package_for', ['user', 'company'])->default('user')->index();
            $table->enum('billing_type', ['monthly', 'yearly', 'lifetime'])->default('monthly')->index();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->char('currency', 3)->default('USD');
            $table->integer('max_users')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
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
