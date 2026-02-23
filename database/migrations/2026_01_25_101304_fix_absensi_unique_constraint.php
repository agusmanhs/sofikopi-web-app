<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // 1. Add new unique constraint including shift_id FIRST
            // This ensures a backing index exists for the foreign key
            $table->unique(['pegawai_id', 'tanggal', 'shift_id'], 'absensis_pegawai_tanggal_shift_unique');
            
            // 2. NOW drop old unique constraint 
            $table->dropUnique(['pegawai_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropUnique('absensis_pegawai_tanggal_shift_unique');
            $table->unique(['pegawai_id', 'tanggal']);
        });
    }
};
