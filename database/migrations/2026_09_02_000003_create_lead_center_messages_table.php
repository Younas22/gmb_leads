<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_center_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_center_lead_id')->constrained('lead_center_leads')->cascadeOnDelete();
            $table->enum('sender_type', ['our', 'client']);
            $table->text('message');
            $table->timestamps();

            $table->index(['lead_center_lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_center_messages');
    }
};
