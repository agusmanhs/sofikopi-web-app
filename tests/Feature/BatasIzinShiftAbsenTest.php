<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\JenisIzin;
use App\Models\Kantor;
use App\Models\Pegawai;
use App\Models\Shift;
use App\Models\User;
use App\Services\IzinService;
use App\Services\SettingService;
use App\Services\TelegramService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class BatasIzinShiftAbsenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-14 12:00:00');

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('notifyIzinCreated')->andReturnNull();
        $this->app->instance(TelegramService::class, $telegram);

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function makeShift(Divisi $divisi, string $nama, int $jamMasukOffset): Shift
    {
        return Shift::create([
            'divisi_id' => $divisi->id,
            'nama' => $nama,
            'jam_masuk' => now()->addHours($jamMasukOffset)->format('H:i:s'),
            'jam_pulang' => now()->addHours($jamMasukOffset + 8)->format('H:i:s'),
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);
    }

    /**
     * Pegawai bershift default A, tetapi absen hari ini memakai shift B.
     */
    protected function pegawaiAbsenDenganShiftBerbeda(int $offsetDefault, int $offsetAbsen): Pegawai
    {
        $divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);
        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat',
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);

        $shiftDefault = $this->makeShift($divisi, 'Shift Default', $offsetDefault);
        $shiftAbsen = $this->makeShift($divisi, 'Shift Absen', $offsetAbsen);

        $pegawai = Pegawai::create([
            'user_id' => User::factory()->create()->id,
            'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id,
            'shift_id' => $shiftDefault->id,
            'nama_lengkap' => 'Pegawai Uji',
            'gender' => 'L',
            'status_aktif' => true,
        ]);

        Absensi::create([
            'pegawai_id' => $pegawai->id,
            'shift_id' => $shiftAbsen->id,
            'tanggal' => today()->toDateString(),
            'jam_masuk' => $shiftAbsen->jam_masuk->format('H:i:s'),
            'status' => 'Hadir',
        ]);

        return $pegawai;
    }

    protected function ajukanSakit(Pegawai $pegawai)
    {
        $sakit = JenisIzin::create([
            'nama' => 'Sakit',
            'kode' => 'sakit',
            'butuh_surat' => false,
            'max_hari' => null,
            'is_aktif' => true,
        ]);

        return app(IzinService::class)->ajukanIzin($pegawai->id, [
            'jenis_izin_id' => $sakit->id,
            'tgl_mulai' => today()->toDateString(),
            'tgl_selesai' => today()->toDateString(),
            'alasan' => 'Alasan pengujian',
        ]);
    }

    public function test_sakit_ditolak_bila_lewat_batas_dari_shift_yang_dipakai_absen()
    {
        // Shift default belum mulai (+3 jam), tetapi absen hari ini memakai shift
        // yang mulai 3 jam lalu. Batas 1 jam dihitung dari shift absen -> sudah lewat.
        $pegawai = $this->pegawaiAbsenDenganShiftBerbeda(3, -3);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        $this->ajukanSakit($pegawai);
    }

    public function test_sakit_diterima_bila_masih_dalam_batas_dari_shift_yang_dipakai_absen()
    {
        // Shift default sudah lewat jauh (-5 jam), tetapi shift absen baru mulai
        // (offset 0) -> masih dalam batas 1 jam, harus diterima.
        $pegawai = $this->pegawaiAbsenDenganShiftBerbeda(-5, 0);

        $izin = $this->ajukanSakit($pegawai);

        $this->assertNotNull($izin->id);
    }
}
