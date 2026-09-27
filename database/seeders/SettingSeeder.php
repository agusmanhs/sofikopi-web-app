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
                'keterangan' => 'Berapa jam sebelum jam masuk shift pegawai sudah boleh absen. Contoh: 2 = shift 08:00 bisa absen mulai 06:00.',
            ],
            [
                'key' => Setting::KEY_BATAS_ABSEN_MASUK_JAM,
                'value' => 2,
                'group' => Setting::GROUP_ABSENSI,
                'label' => 'Batas Absen Masuk (jam setelah shift)',
                'type' => 'integer',
                'keterangan' => 'Batas akhir absen masuk dihitung dari jam masuk shift. Contoh: 2 = shift 08:00 tidak bisa absen lagi setelah 10:00.',
            ],
            [
                'key' => Setting::KEY_BATAS_ABSEN_PULANG_JAM,
                'value' => 2,
                'group' => Setting::GROUP_ABSENSI,
                'label' => 'Batas Absen Pulang (jam setelah shift selesai)',
                'type' => 'integer',
                'keterangan' => 'Batas akhir absen pulang dihitung dari jam pulang shift. Lewat batas ini sesi dianggap hangus (Alpha).',
            ],
            [
                'key' => Setting::KEY_BATAS_IZIN_JAM,
                'value' => 1,
                'group' => Setting::GROUP_ABSENSI,
                'label' => 'Batas Pengajuan Izin/Sakit Hari Ini (jam setelah shift)',
                'type' => 'integer',
                'keterangan' => 'Hanya untuk izin yang mulai HARI INI, jenis Sakit & Izin Pribadi. Contoh: 1 = shift 08:00 tidak bisa mengajukan setelah 09:00. Pengajuan untuk tanggal berikutnya tidak dibatasi.',
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
