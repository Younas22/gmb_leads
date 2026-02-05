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
        Schema::create('package_features', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('package_id')->index();
            $table->string('feature_key', 100)->index();
            $table->string('feature_value', 255)->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
            $table->unique(['package_id', 'feature_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_features');
    }
};
