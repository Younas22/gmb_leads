<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_api_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key_name', 100);
            $table->text('api_key');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->decimal('text_search_price', 10, 4)->default(0);
            $table->decimal('details_price', 10, 4)->default(0);
            $table->unsignedInteger('text_search_count')->default(0);
            $table->unsignedInteger('details_count')->default(0);
            $table->decimal('text_search_total_cost', 12, 4)->default(0);
            $table->decimal('details_total_cost', 12, 4)->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_api_keys');
    }
};
