<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('query');
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('radius')->default(5000); // in meters
            $table->integer('results_count')->nullable();
            $table->string('api_used')->default('Google Places');
            $table->decimal('execution_time', 8, 3)->nullable(); // in seconds
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->decimal('response_time', 8, 3)->nullable(); // API response time
            $table->json('results_data')->nullable(); // Store search results
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'created_at']);
            $table->index(['status']);
            $table->index(['query']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_history');
    }
};