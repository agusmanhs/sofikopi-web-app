<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guarded: these tables were already created on long-lived databases
        // by the original (since-deleted) 2026_03_16_220027_create_wilayah_tables
        // migration, which left this v2 file permanently stuck in Pending and
        // aborting every plain `php artisan migrate`. On such databases this
        // is a no-op that just marks itself Ran; on fresh installs (tests,
        // new environments) it creates the tables for real.
        if (!Schema::hasTable('provinces')) {
            Schema::create('provinces', function (Blueprint $table) {
                $table->string('code')->primary();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('regencies')) {
            Schema::create('regencies', function (Blueprint $table) {
                $table->string('code')->primary();
                $table->string('province_code');
                $table->string('name');
                $table->timestamps();

                $table->foreign('province_code')->references('code')->on('provinces')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('districts')) {
            Schema::create('districts', function (Blueprint $table) {
                $table->string('code')->primary();
                $table->string('regency_code');
                $table->string('name');
                $table->timestamps();

                $table->foreign('regency_code')->references('code')->on('regencies')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
};
