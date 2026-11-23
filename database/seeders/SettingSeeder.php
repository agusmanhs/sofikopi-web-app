<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => Setting::KEY_BATAS_ABSEN_DIBUKA_JAM,
                'value' => 2,
                'group' => Setting::GROUP_ABSENSI,
                'label' => 'Absen Masuk Dibuka (jam sebelum shift)',
                'type' => 'integer',
                'keterangan' => 'Pegawai sudah boleh absen masuk berapa jam sebelum shift mulai. Contoh: diisi 2, shift mulai jam 08:00, maka pegawai sudah bisa absen mulai jam 06:00.',
            ],
            [
                'key' => Setting::KEY_BATAS_ABSEN_MASUK_JAM,
                'value' => 2,
                'group' => Setting::GROUP_ABSENSI,
                'label' => 'Batas Absen Masuk (jam setelah shift)',
                'type' => 'integer',
                'keterangan' => 'Pegawai masih boleh absen masuk sampai berapa jam setelah shift mulai. Contoh: diisi 2, shift mulai jam 08:00, maka lewat jam 10:00 pegawai sudah tidak bisa absen masuk lagi.',
            ],
            [
                'key' => Setting::KEY_BATAS_ABSEN_PULANG_JAM,
                'value' => 2,
                'group' => Setting::GROUP_ABSENSI,
                'label' => 'Batas Absen Pulang (jam setelah shift selesai)',
                'type' => 'integer',
                'keterangan' => 'Pegawai masih boleh absen pulang sampai berapa jam setelah shift selesai. Contoh: diisi 2, shift selesai jam 16:00, maka lewat jam 18:00 absen pulang tidak bisa lagi dan hari itu dianggap Alpha.',
            ],
            [
                'key' => Setting::KEY_IZIN_TUTUP_MENIT,
                'value' => 10,
                'group' => Setting::GROUP_ABSENSI,
                'label' => 'Izin Hari Ini Ditutup Sebelum Shift (menit)',
                'type' => 'integer',
                'keterangan' => 'Izin untuk hari ini hanya bisa diajukan sampai sekian menit sebelum shift mulai, agar tidak dipakai di detik-detik terakhir. Contoh: diisi 10, shift mulai jam 08:00, maka lewat jam 07:50 pegawai harus absen masuk dulu baru bisa mengajukan izin (izin pulang cepat). Isi 0 untuk menutup tepat saat shift mulai. Izin untuk besok dan seterusnya tidak dibatasi.',
            ],
        ];

        // Pengaturan lama (batas izin dalam jam setelah shift) sudah digantikan aturan
        // "izin ditutup N menit sebelum shift"; hapus agar tidak tampil membingungkan.
        Setting::where('key', 'batas_izin_jam')->delete();

        foreach ($settings as $setting) {
            $existing = Setting::where('key', $setting['key'])->first();

            if ($existing) {
                // Metadata (label/keterangan/type) selalu disinkronkan, tapi
                // `value` TIDAK ditimpa — nilai yang sudah diubah admin dari
                // halaman Pengaturan harus bertahan saat seeder dijalankan ulang.
                $existing->update([
                    'group' => $setting['group'],
                    'label' => $setting['label'],
                    'type' => $setting['type'],
                    'keterangan' => $setting['keterangan'],
                ]);

                continue;
            }

            Setting::create($setting);
        }

        $this->command->info('✅ Setting absensi berhasil di-seed ('.count($settings).' setting).');
    }
}
