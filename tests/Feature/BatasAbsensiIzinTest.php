<?php

namespace Tests\Feature;

use App\Models\Divisi;
use App\Models\JenisIzin;
use App\Models\Kantor;
use App\Models\Pegawai;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\AbsensiService;
use App\Services\IzinService;
use App\Services\SettingService;
use App\Services\TelegramService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BatasAbsensiIzinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('notifyIzinCreated')->andReturnNull();
        $telegram->shouldReceive('notifyIzinStatus')->andReturnNull();
        $telegram->shouldReceive('notify')->andReturnNull();
        $telegram->shouldReceive('notifyAbsenMasuk')->andReturnNull();
        $this->app->instance(TelegramService::class, $telegram);

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();
    }

    /**
     * Buat pegawai + shift dengan jam masuk relatif terhadap "sekarang".
     *
     * @param  int  $jamMasukOffset  Selisih jam masuk shift dari sekarang (negatif = sudah lewat)
     */
    protected function makePegawai(int $jamMasukOffset = -1): array
    {
        $divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);

        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat',
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);

        $user = User::factory()->create();

        $shift = Shift::create([
            'divisi_id' => $divisi->id,
            'nama' => 'Shift Pagi',
            'jam_masuk' => now()->addHours($jamMasukOffset)->format('H:i:s'),
            'jam_pulang' => now()->addHours($jamMasukOffset + 8)->format('H:i:s'),
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);

        $pegawai = Pegawai::create([
            'user_id' => $user->id,
            'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id,
            'shift_id' => $shift->id,
            'nama_lengkap' => 'Pegawai Uji',
            'gender' => 'L',
            'status_aktif' => true,
        ]);

        return [$pegawai, $shift];
    }

    protected function absenData(Shift $shift): array
    {
        return [
            'shift_id' => $shift->id,
            'latitude' => -5.147665,
            'longitude' => 119.432732,
        ];
    }

    protected function setSetting(string $key, $value): void
    {
        app(SettingService::class)->setMany([$key => $value]);
    }

    // ---------- Batas absen masuk ----------

    public function test_absen_masuk_ditolak_setelah_melewati_batas_dari_setting()
    {
        // Shift mulai 3 jam lalu, batas setting 2 jam -> sudah ditutup.
        [$pegawai, $shift] = $this->makePegawai(-3);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Waktu absen masuk sudah ditutup');

        app(AbsensiService::class)->absenMasuk($pegawai, $this->absenData($shift));
    }

    public function test_absen_masuk_diterima_saat_masih_dalam_batas()
    {
        // Shift mulai 1 jam lalu, batas 2 jam -> masih boleh.
        [$pegawai, $shift] = $this->makePegawai(-1);

        $absensi = app(AbsensiService::class)->absenMasuk($pegawai, $this->absenData($shift));

        $this->assertNotNull($absensi->jam_masuk);
    }

    public function test_batas_absen_masuk_mengikuti_perubahan_setting()
    {
        // Shift mulai 3 jam lalu: ditolak pada batas 2 jam, diterima bila batas dinaikkan ke 4.
        [$pegawai, $shift] = $this->makePegawai(-3);

        $this->setSetting(Setting::KEY_BATAS_ABSEN_MASUK_JAM, 4);

        $absensi = app(AbsensiService::class)->absenMasuk($pegawai, $this->absenData($shift));

        $this->assertNotNull($absensi->jam_masuk);
    }

    // ---------- Batas pengajuan izin ----------

    protected function makeJenisIzin(string $kode, string $nama): JenisIzin
    {
        return JenisIzin::create([
            'nama' => $nama,
            'kode' => $kode,
            'butuh_surat' => false,
            'max_hari' => null,
            'is_aktif' => true,
        ]);
    }

    protected function izinData(JenisIzin $jenis, string $tanggal): array
    {
        return [
            'jenis_izin_id' => $jenis->id,
            'tgl_mulai' => $tanggal,
            'tgl_selesai' => $tanggal,
            'alasan' => 'Alasan pengujian',
        ];
    }

    public function test_pengajuan_sakit_hari_ini_ditolak_setelah_lewat_batas()
    {
        // Shift mulai 3 jam lalu, batas izin 1 jam -> sudah lewat.
        [$pegawai] = $this->makePegawai(-3);
        $sakit = $this->makeJenisIzin('sakit', 'Sakit');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        app(IzinService::class)->ajukanIzin(
            $pegawai->id,
            $this->izinData($sakit, today()->toDateString())
        );
    }

    public function test_pengajuan_izin_pribadi_hari_ini_diterima_sebelum_batas()
    {
        // Shift baru mulai (offset 0), batas 1 jam -> masih boleh.
        [$pegawai] = $this->makePegawai(0);
        $izin = $this->makeJenisIzin('izin', 'Izin Pribadi');

        $izinRecord = app(IzinService::class)->ajukanIzin(
            $pegawai->id,
            $this->izinData($izin, today()->toDateString())
        );

        $this->assertNotNull($izinRecord->id);
    }

    public function test_pengajuan_untuk_tanggal_besok_tidak_dibatasi_jam()
    {
        // Shift mulai 5 jam lalu (jauh lewat batas), tapi izin untuk BESOK -> tetap boleh.
        [$pegawai] = $this->makePegawai(-5);
        $sakit = $this->makeJenisIzin('sakit', 'Sakit');

        $izinRecord = app(IzinService::class)->ajukanIzin(
            $pegawai->id,
            $this->izinData($sakit, today()->addDay()->toDateString())
        );

        $this->assertNotNull($izinRecord->id);
    }

    public function test_semua_jenis_izin_terkena_batas_jam()
    {
        [$pegawai] = $this->makePegawai(-5);
        $dinas = $this->makeJenisIzin('dinas', 'Dinas Luar Kota');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        app(IzinService::class)->ajukanIzin(
            $pegawai->id,
            $this->izinData($dinas, today()->toDateString())
        );
    }

    public function test_pengajuan_izin_pribadi_dan_sakit_no_skd_ditolak_setelah_lewat_batas()
    {
        [$pegawai] = $this->makePegawai(-5);

        foreach ([['izin pribadi', 'Izin Pribadi'], ['sakit no skd', 'Sakit No SKD']] as [$kode, $nama]) {
            $jenis = $this->makeJenisIzin($kode, $nama);

            try {
                app(IzinService::class)->ajukanIzin(
                    $pegawai->id,
                    $this->izinData($jenis, today()->toDateString())
                );
                $this->fail("Jenis '{$kode}' seharusnya ditolak setelah lewat batas.");
            } catch (\Exception $e) {
                $this->assertStringContainsString('sudah ditutup', $e->getMessage());
            }
        }
    }

    public function test_batas_pengajuan_izin_mengikuti_perubahan_setting()
    {
        // Shift mulai 3 jam lalu: ditolak pada batas 1 jam, diterima bila batas dinaikkan ke 5.
        [$pegawai] = $this->makePegawai(-3);
        $sakit = $this->makeJenisIzin('sakit', 'Sakit');

        $this->setSetting(Setting::KEY_BATAS_IZIN_JAM, 5);

        $izinRecord = app(IzinService::class)->ajukanIzin(
            $pegawai->id,
            $this->izinData($sakit, today()->toDateString())
        );

        $this->assertNotNull($izinRecord->id);
    }

    public function test_pegawai_tanpa_shift_tidak_diblokir()
    {
        [$pegawai] = $this->makePegawai(-5);
        $pegawai->update(['shift_id' => null]);
        $sakit = $this->makeJenisIzin('sakit', 'Sakit');

        $izinRecord = app(IzinService::class)->ajukanIzin(
            $pegawai->fresh()->id,
            $this->izinData($sakit, today()->toDateString())
        );

        $this->assertNotNull($izinRecord->id);
    }
}
