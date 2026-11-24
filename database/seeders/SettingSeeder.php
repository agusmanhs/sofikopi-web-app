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
                'key' => Setting::KEY_BATAS_IZIN_JAM,
                'value' => 1,
                'group' => Setting::GROUP_ABSENSI,
                'label' => 'Batas Pengajuan Izin/Sakit Hari Ini (jam setelah shift)',
                'type' => 'integer',
                'keterangan' => 'Batas waktu mengajukan izin untuk hari ini, dihitung dari jam shift mulai. Berlaku untuk semua jenis izin. Contoh: diisi 1, shift mulai jam 08:00, maka lewat jam 09:00 izin untuk hari ini tidak bisa diajukan lagi. Izin untuk besok dan seterusnya tidak dibatasi.',
            ],
        ];

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
