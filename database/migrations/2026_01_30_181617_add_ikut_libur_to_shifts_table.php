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
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('ikut_libur')->default(true)->after('is_aktif');
        });

        // Update default values: only "Full time" and "Reguler" follow holidays
        \DB::table('shifts')->update(['ikut_libur' => false]);
        \DB::table('shifts')
            ->where('nama', 'like', '%Full time%')
            ->orWhere('nama', 'like', '%Reguler%')
            ->update(['ikut_libur' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('ikut_libur');
        });
    }
};
