<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AbsensiMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seeder Divisi
        $divisis = [
            [
                'kode' => 'IT',
                'nama' => 'Information Technology',
                'toleransi_terlambat' => 15,
                'is_aktif' => true,
            ],
            [
                'kode' => 'HR',
                'nama' => 'Human Resources',
                'toleransi_terlambat' => 10,
                'is_aktif' => true,
            ],
            [
                'kode' => 'FIN',
                'nama' => 'Finance & Accounting',
                'toleransi_terlambat' => 10,
                'is_aktif' => true,
            ],
            [
                'kode' => 'OPS',
                'nama' => 'Operations',
                'toleransi_terlambat' => 15,
                'is_aktif' => true,
            ],
            [
                'kode' => 'MKT',
                'nama' => 'Marketing',
                'toleransi_terlambat' => 15,
                'is_aktif' => true,
            ],
        ];

        foreach ($divisis as $divisi) {
            DB::table('divisis')->updateOrInsert(
                ['kode' => $divisi['kode']],
                array_merge($divisi, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 2. Seeder Kantor
        $kantors = [
            [
                'kode' => 'CV',
                'nama' => 'CV. Data Cipta Celebes',
                'alamat' => 'Jl. BTN Hamzy Blok Q',
                'titik_lokasi' => '-5.139368761612093, 119.47730509426326',
                'radius_meter' => 50,
                'is_aktif' => true,
            ],
            [
                'kode' => 'DIPA',
                'nama' => 'Dipa Office',
                'alamat' => 'Perintis Kemerdekaan',
                'titik_lokasi' => '-5.140306706417759, 119.48306966354427',
                'radius_meter' => 50,
                'is_aktif' => true,
            ],
            [
                'kode' => 'DCC',
                'nama' => 'Dipanegara Computer Club',
                'alamat' => 'BTN Hamzy Blok Q',
                'titik_lokasi' => '-5.13915449595361, 119.47738594929875',
                'radius_meter' => 50,
                'is_aktif' => true,
            ],
        ];

        foreach ($kantors as $kantor) {
            DB::table('kantors')->updateOrInsert(
                ['kode' => $kantor['kode']],
                array_merge($kantor, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 3. Seeder Jenis Izin
        $jenisIzins = [
            [
                'kode' => 'sakit',
                'nama' => 'Sakit',
                'butuh_surat' => true,
                'max_hari' => null,
                'keterangan' => 'Izin karena sakit, wajib melampirkan surat dokter',
                'is_aktif' => true,
            ],
            [
                'kode' => 'cuti',
                'nama' => 'Cuti Tahunan',
                'butuh_surat' => false,
                'max_hari' => 12,
                'keterangan' => 'Cuti tahunan pegawai, maksimal 12 hari per tahun',
                'is_aktif' => true,
            ],
            [
                'kode' => 'izin',
                'nama' => 'Izin Pribadi',
                'butuh_surat' => false,
                'max_hari' => 3,
                'keterangan' => 'Izin keperluan pribadi, maksimal 3 hari per pengajuan',
                'is_aktif' => true,
            ],
            [
                'kode' => 'melahirkan',
                'nama' => 'Cuti Melahirkan',
                'butuh_surat' => true,
                'max_hari' => 90,
                'keterangan' => 'Cuti melahirkan untuk pegawai wanita',
                'is_aktif' => true,
            ],
            [
                'kode' => 'menikah',
                'nama' => 'Cuti Menikah',
                'butuh_surat' => true,
                'max_hari' => 3,
                'keterangan' => 'Cuti pernikahan pegawai',
                'is_aktif' => true,
            ],
            [
                'kode' => 'duka',
                'nama' => 'Cuti Duka',
                'butuh_surat' => false,
                'max_hari' => 3,
                'keterangan' => 'Cuti karena keluarga inti meninggal dunia',
                'is_aktif' => true,
            ],
            [
                'kode' => 'dinas',
                'nama' => 'Dinas Luar Kota',
                'butuh_surat' => true,
                'max_hari' => null,
                'keterangan' => 'Perjalanan dinas ke luar kota',
                'is_aktif' => true,
            ],
        ];

        foreach ($jenisIzins as $jenisIzin) {
            DB::table('jenis_izins')->updateOrInsert(
                ['kode' => $jenisIzin['kode']],
                array_merge($jenisIzin, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ Master data absensi berhasil di-seed!');
        $this->command->info('   - ' . count($divisis) . ' Divisi');
        $this->command->info('   - ' . count($kantors) . ' Kantor');
        $this->command->info('   - ' . count($jenisIzins) . ' Jenis Izin');
    }
}
