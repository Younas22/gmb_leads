<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users.password_copy: the user's password, ENCRYPTED with APP_KEY (never plain text),
 * so an admin can look it up on /admin/users. Filled when the user registers, logs in,
 * resets or changes the password. Readable only through App\Support\AdminPasswordCopy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'password_copy')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('password_copy')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'password_copy')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_copy');
            });
        }
    }
};
