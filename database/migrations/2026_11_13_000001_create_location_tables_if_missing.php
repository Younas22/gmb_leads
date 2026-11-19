<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * countries / states / cities were originally imported from an SQL dump and never
 * had migrations, so a fresh install had no location tables at all.
 *
 * This migration only creates a table when it is missing, using the same columns
 * as the original dump. On an existing database (production) every table already
 * exists, so it does nothing. Location rows must still be imported separately on
 * fresh installs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('name', 255);
                $table->char('iso3', 3)->nullable();
                $table->char('iso2', 2)->nullable();
                $table->string('phone_code', 10)->nullable();
                $table->string('capital', 255)->nullable();
                $table->char('currency', 3)->nullable();
                $table->string('currency_symbol', 10)->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('region', 100)->nullable();
                $table->string('subregion', 100)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('states')) {
            Schema::create('states', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('country_id')->index();
                $table->string('name', 255);
                $table->string('state_code', 10)->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('state_id')->index();
                $table->integer('country_id')->index();
                $table->string('name', 255);
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: on existing databases these tables hold imported
        // reference data that this migration did not create, so rolling back must
        // never drop them.
    }
};
