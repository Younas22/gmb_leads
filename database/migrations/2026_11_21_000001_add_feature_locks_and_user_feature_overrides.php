<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature locks and per-user package customisation.
 *  - package_features.is_locked / lock_message: the admin can lock a feature in a package and
 *    show a message instead (the sidebar shows the item with a lock and that message).
 *  - user_feature_overrides: the admin can change one user's package features (another value,
 *    unlimited, or locked with a message). Keyed by user, so it survives renewals.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('package_features', 'is_locked')) {
            Schema::table('package_features', function (Blueprint $table) {
                $table->boolean('is_locked')->default(false)->after('is_unlimited');
                $table->string('lock_message', 255)->nullable()->after('is_locked');
            });
        }

        if (!Schema::hasTable('user_feature_overrides')) {
            Schema::create('user_feature_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('feature_key', 100);
                $table->string('feature_value', 255)->nullable();
                $table->boolean('is_unlimited')->default(false);
                $table->boolean('is_locked')->default(false);
                $table->string('lock_message', 255)->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'feature_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_feature_overrides');

        if (Schema::hasColumn('package_features', 'is_locked')) {
            Schema::table('package_features', function (Blueprint $table) {
                $table->dropColumn(['is_locked', 'lock_message']);
            });
        }
    }
};
