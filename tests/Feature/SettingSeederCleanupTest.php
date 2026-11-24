<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SettingService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingSeederCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_menghapus_setting_menit_sisa_aturan_yang_dibatalkan()
    {
        Setting::create([
            'key' => 'izin_tutup_menit_sebelum_shift',
            'value' => 10,
            'group' => Setting::GROUP_ABSENSI,
            'label' => 'Izin Hari Ini Ditutup Sebelum Shift (menit)',
            'type' => 'integer',
            'keterangan' => 'sisa',
        ]);

        $this->seed(SettingSeeder::class);

        $this->assertDatabaseMissing('settings', ['key' => 'izin_tutup_menit_sebelum_shift']);

        $keys = app(SettingService::class)->getByGroup(Setting::GROUP_ABSENSI)->pluck('key')->all();
        $this->assertCount(4, $keys);
    }

    public function test_seeder_tidak_menimpa_nilai_setting_yang_sudah_diubah_admin()
    {
        $this->seed(SettingSeeder::class);
        Setting::where('key', Setting::KEY_BATAS_IZIN_JAM)->update(['value' => 3]);

        $this->seed(SettingSeeder::class);

        $this->assertSame('3', (string) Setting::where('key', Setting::KEY_BATAS_IZIN_JAM)->value('value'));
    }
}
