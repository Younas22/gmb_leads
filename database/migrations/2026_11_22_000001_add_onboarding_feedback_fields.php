<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_feedback', function (Blueprint $table) {
            // Optional second box on the feedback form ("Feedback" stays required in `message`).
            $table->text('suggestion')->nullable()->after('message');
            // Answer to "Have you used CustomerNearMe before?" on the post-login prompt (null = manual feedback).
            $table->boolean('used_before')->nullable()->after('suggestion');
        });

        Schema::table('users', function (Blueprint $table) {
            // Set when a genuinely new user (no activity) closes the post-login feedback prompt.
            $table->timestamp('feedback_prompt_dismissed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_feedback', function (Blueprint $table) {
            $table->dropColumn(['suggestion', 'used_before']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('feedback_prompt_dismissed_at');
        });
    }
};
