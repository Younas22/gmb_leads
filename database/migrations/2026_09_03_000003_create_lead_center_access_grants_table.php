<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_center_access_grants', function (Blueprint $table) {
            $table->id();
            // The account whose Lead Center is being shared
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            // The account being granted access to it
            $table->foreignId('grantee_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'declined', 'revoked'])->default('pending')->index();
            $table->timestamps();

            $table->unique(['owner_user_id', 'grantee_user_id']);
            $table->index(['grantee_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_center_access_grants');
    }
};
