<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 30)->default('blue');
            $table->timestamps();
        });

        Schema::create('lead_folder_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('lead_folders')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('saved_leads')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['folder_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_folder_items');
        Schema::dropIfExists('lead_folders');
    }
};
