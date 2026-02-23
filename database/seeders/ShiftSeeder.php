<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Divisi;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Divisi IT (Multi Shift)
        $it = Divisi::where('kode', 'IT')->first();
        if ($it) {
            $itShifts = [
                ['nama' => 'IT Pagi', 'jam_masuk' => '08:00', 'jam_pulang' => '17:00'],
                ['nama' => 'IT Sore', 'jam_masuk' => '14:00', 'jam_pulang' => '22:00'],
                ['nama' => 'IT Malam', 'jam_masuk' => '22:00', 'jam_pulang' => '06:00'],
            ];

            foreach ($itShifts as $shift) {
                DB::table('shifts')->insert(array_merge($shift, [
                    'divisi_id' => $it->id,
                    'is_aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 2. Divisi Security / OPS (Multi Shift)
        $ops = Divisi::where('kode', 'OPS')->first();
        if ($ops) {
            $opsShifts = [
                ['nama' => 'Security Pagi', 'jam_masuk' => '07:00', 'jam_pulang' => '15:00'],
                ['nama' => 'Security Sore', 'jam_masuk' => '15:00', 'jam_pulang' => '23:00'],
                ['nama' => 'Security Malam', 'jam_masuk' => '23:00', 'jam_pulang' => '07:00'],
            ];

            foreach ($opsShifts as $shift) {
                DB::table('shifts')->insert(array_merge($shift, [
                    'divisi_id' => $ops->id,
                    'is_aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 3. Divisi HR (Single Shift / Office Hour)
        $hr = Divisi::where('kode', 'HR')->first();
        if ($hr) {
            DB::table('shifts')->insert([
                'divisi_id' => $hr->id,
                'nama' => 'Office Hour HR',
                'jam_masuk' => '08:00',
                'jam_pulang' => '17:00',
                'is_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Divisi Finance (Single Shift)
        $fin = Divisi::where('kode', 'FIN')->first();
        if ($fin) {
            DB::table('shifts')->insert([
                'divisi_id' => $fin->id,
                'nama' => 'Reguler Finance',
                'jam_masuk' => '08:30',
                'jam_pulang' => '17:30',
                'is_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Catatan: Divisi Marketing sengaja tidak diberi shift untuk simulasi "Divisi tanpa shift"

        $this->command->info('✅ Shift data successfully seeded!');
    }
}
