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
        // Update shifts table
        Schema::table('shifts', function (Blueprint $table) {
            $table->json('hari_kerja')->nullable()->after('ikut_libur');
            $table->boolean('is_cross_day')->default(false)->after('hari_kerja');
        });

        // Seed default working days for existing shifts
        // Usually: Monday (1) to Saturday (6). Sunday is 0 or 7.
        // We use string names for clarity: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
        $defaultDays = json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
        \DB::table('shifts')->where('ikut_libur', true)->update(['hari_kerja' => $defaultDays]);
        
        // For shift Lallo/Full week (if any)
        if (Schema::hasColumn('shifts', 'nama')) {
            $fullDays = json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            \DB::table('shifts')
                ->where('nama', 'like', '%Lallo%')
                ->orWhere('nama', 'like', '%Full week%')
                ->orWhere('ikut_libur', false)
                ->update(['hari_kerja' => $fullDays]);
        }

        // Update hari_liburs table
        Schema::table('hari_liburs', function (Blueprint $table) {
            $table->boolean('is_all_divisi')->default(true)->after('is_cuti_bersama');
            $table->json('divisi_ids')->nullable()->after('is_all_divisi');
            $table->json('shift_ids')->nullable()->after('divisi_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['hari_kerja', 'is_cross_day']);
        });

        Schema::table('hari_liburs', function (Blueprint $table) {
            $table->dropColumn(['is_all_divisi', 'divisi_ids', 'shift_ids']);
        });
    }
};
