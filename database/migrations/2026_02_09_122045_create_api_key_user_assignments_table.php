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
        Schema::create('api_key_user_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_key_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('api_key_id')->references('id')->on('user_api_keys')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Prevent duplicate assignments
            $table->unique(['api_key_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_key_user_assignments');
    }
};
