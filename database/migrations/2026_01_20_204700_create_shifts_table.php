<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create shifts table
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('divisi_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('nama', 50);
            $table->time('jam_masuk');
            $table->time('jam_pulang');
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        // 2. Add shift_id to pegawais
        Schema::table('pegawais', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('divisi_id');
            $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
        });

        // 3. Migrate data: Create "Default Shift" from divisis table
        $divisis = DB::table('divisis')->get();
        foreach ($divisis as $divisi) {
            if ($divisi->jam_masuk && $divisi->jam_pulang) {
                $shiftId = DB::table('shifts')->insertGetId([
                    'divisi_id' => $divisi->id,
                    'nama' => 'Default Shift',
                    'jam_masuk' => $divisi->jam_masuk,
                    'jam_pulang' => $divisi->jam_pulang,
                    'is_aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Assign this shift to all pegawais in this division
                DB::table('pegawais')
                    ->where('divisi_id', $divisi->id)
                    ->update(['shift_id' => $shiftId]);
            }
        }

        // 4. Remove jam_masuk and jam_pulang from divisis
        Schema::table('divisis', function (Blueprint $table) {
            $table->dropColumn(['jam_masuk', 'jam_pulang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add back jam_masuk and jam_pulang to divisis
        Schema::table('divisis', function (Blueprint $table) {
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
        });

        // 2. Restore data from shifts back to divisis (optional, but good practice)
        $shifts = DB::table('shifts')->where('nama', 'Default Shift')->get();
        foreach ($shifts as $shift) {
            DB::table('divisis')
                ->where('id', $shift->divisi_id)
                ->update([
                    'jam_masuk' => $shift->jam_masuk,
                    'jam_pulang' => $shift->jam_pulang,
                ]);
        }

        // 3. Drop shift_id from pegawais
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });

        // 4. Drop shifts table
        Schema::dropIfExists('shifts');
    }
};
