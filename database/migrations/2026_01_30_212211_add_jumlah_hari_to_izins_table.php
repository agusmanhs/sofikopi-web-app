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
        Schema::table('izins', function (Blueprint $table) {
            if (!Schema::hasColumn('izins', 'jumlah_hari')) {
                $table->integer('jumlah_hari')->after('tgl_selesai')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('izins', function (Blueprint $table) {
            $table->dropColumn('jumlah_hari');
        });
    }
};
