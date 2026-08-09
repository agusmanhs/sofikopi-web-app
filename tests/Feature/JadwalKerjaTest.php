<?php

namespace Tests\Feature;

use App\Models\Divisi;
use App\Models\JadwalKerja;
use App\Models\JadwalOverride;
use App\Models\Kantor;
use App\Models\Pegawai;
use App\Models\Shift;
use App\Models\User;
use App\Services\AbsensiService;
use App\Services\JadwalKerjaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalKerjaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Buat 1 pegawai lengkap dengan divisi, kantor (lokasi absen valid), dan 1 shift aktif
     * yang jendela waktunya mencakup "sekarang" agar absenMasuk tidak ditolak oleh guard waktu/lokasi.
     */
    protected function makePegawaiDenganShift(array $shiftOverrides = []): array
    {
        $divisi = Divisi::create([
            'nama' => 'Operasional',
            'is_aktif' => true,
        ]);

        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat',
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);

        $user = User::factory()->create();

        $pegawai = Pegawai::create([
            'user_id' => $user->id,
            'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id,
            'nama_lengkap' => 'Pegawai Uji',
            'gender' => 'L',
            'status_aktif' => true,
        ]);

        $shift = Shift::create(array_merge([
            'divisi_id' => $divisi->id,
            'nama' => 'Shift Pagi',
            'jam_masuk' => now()->subHour()->format('H:i:s'),
            'jam_pulang' => now()->addHours(5)->format('H:i:s'),
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ], $shiftOverrides));

        return [$pegawai, $shift, $divisi, $kantor];
    }

    protected function absenData(Shift $shift): array
    {
        // Sama persis dengan titik kantor -> jarak 0, selalu dalam radius.
        return [
            'shift_id' => $shift->id,
            'latitude' => -5.147665,
            'longitude' => 119.432732,
        ];
    }

    /**
     * Presedensi resolver: tanpa jadwal -> pola mingguan -> override (override menang).
     */
    public function test_resolver_precedence_none_then_pattern_then_override()
    {
        [$pegawai, $shiftPattern] = $this->makePegawaiDenganShift();
        $shiftOverride = Shift::create([
            'divisi_id' => $pegawai->divisi_id,
            'nama' => 'Shift Override',
            'jam_masuk' => '00:00:00',
            'jam_pulang' => '23:59:00',
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);

        /** @var JadwalKerjaService $service */
        $service = app(JadwalKerjaService::class);
        $today = now();

        // 1. Belum ada jadwal sama sekali -> scheduled false
        $result = $service->resolveShiftFor($pegawai, $today);
        $this->assertFalse($result['scheduled']);
        $this->assertNull($result['shift']);

        // 2. Ada pola mingguan untuk hari ini -> scheduled true, shift = pola
        JadwalKerja::create([
            'pegawai_id' => $pegawai->id,
            'day_of_week' => $today->dayOfWeek,
            'shift_id' => $shiftPattern->id,
        ]);

        $result = $service->resolveShiftFor($pegawai, $today);
        $this->assertTrue($result['scheduled']);
        $this->assertEquals($shiftPattern->id, $result['shift']->id);

        // 3. Ada override untuk tanggal ini -> override menang atas pola
        JadwalOverride::create([
            'pegawai_id' => $pegawai->id,
            'tanggal' => $today->toDateString(),
            'shift_id' => $shiftOverride->id,
        ]);

        $result = $service->resolveShiftFor($pegawai, $today);
        $this->assertTrue($result['scheduled']);
        $this->assertEquals($shiftOverride->id, $result['shift']->id);
    }

    /**
     * Override dengan shift null harus dibedakan dari "tidak punya jadwal" -> dianggap libur terjadwal.
     */
    public function test_resolver_distinguishes_libur_terjadwal_dari_tanpa_jadwal()
    {
        [$pegawai] = $this->makePegawaiDenganShift();

        JadwalOverride::create([
            'pegawai_id' => $pegawai->id,
            'tanggal' => now()->toDateString(),
            'shift_id' => null,
        ]);

        /** @var JadwalKerjaService $service */
        $service = app(JadwalKerjaService::class);
        $result = $service->resolveShiftFor($pegawai, now());

        $this->assertTrue($result['scheduled']);
        $this->assertNull($result['shift']);
    }

    /**
     * Pegawai dengan jadwal libur terjadwal hari ini harus ditolak saat absen masuk.
     */
    public function test_absen_masuk_ditolak_saat_libur_terjadwal()
    {
        [$pegawai, $shift] = $this->makePegawaiDenganShift();

        JadwalOverride::create([
            'pegawai_id' => $pegawai->id,
            'tanggal' => now()->toDateString(),
            'shift_id' => null,
        ]);

        /** @var AbsensiService $absensiService */
        $absensiService = app(AbsensiService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hari ini bukan jadwal kerja Anda.');

        $absensiService->absenMasuk($pegawai, $this->absenData($shift));
    }

    /**
     * Pegawai yang mencoba absen di shift yang berbeda dari jadwal terjadwalnya harus ditolak.
     */
    public function test_absen_masuk_ditolak_saat_shift_tidak_sesuai_jadwal()
    {
        [$pegawai, $shiftTerjadwal] = $this->makePegawaiDenganShift();
        $shiftLain = Shift::create([
            'divisi_id' => $pegawai->divisi_id,
            'nama' => 'Shift Lain',
            'jam_masuk' => now()->subHour()->format('H:i:s'),
            'jam_pulang' => now()->addHours(5)->format('H:i:s'),
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);

        JadwalKerja::create([
            'pegawai_id' => $pegawai->id,
            'day_of_week' => now()->dayOfWeek,
            'shift_id' => $shiftTerjadwal->id,
        ]);

        /** @var AbsensiService $absensiService */
        $absensiService = app(AbsensiService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Shift tidak sesuai jadwal Anda hari ini');

        $absensiService->absenMasuk($pegawai, $this->absenData($shiftLain));
    }

    /**
     * Pegawai tanpa jadwal terkonfigurasi sama sekali tetap bisa absen bebas (perilaku lama, backward compatible).
     */
    public function test_absen_masuk_lolos_jika_pegawai_tidak_punya_jadwal()
    {
        [$pegawai, $shift] = $this->makePegawaiDenganShift();

        /** @var AbsensiService $absensiService */
        $absensiService = app(AbsensiService::class);

        $absensi = $absensiService->absenMasuk($pegawai, $this->absenData($shift));

        $this->assertNotNull($absensi->id);
        $this->assertEquals($pegawai->id, $absensi->pegawai_id);
        $this->assertEquals($shift->id, $absensi->shift_id);
    }

    /**
     * savePatternForPegawai harus idempotent: dipanggil dua kali dengan payload yang sama
     * tidak boleh membuat duplikat baris atau melempar unique-constraint error.
     */
    public function test_save_pattern_idempotent()
    {
        [$pegawai, $shift] = $this->makePegawaiDenganShift();

        /** @var JadwalKerjaService $service */
        $service = app(JadwalKerjaService::class);

        $days = [0 => null, 1 => $shift->id, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null];

        $service->savePatternForPegawai($pegawai->id, $days);
        $service->savePatternForPegawai($pegawai->id, $days);

        $this->assertEquals(7, JadwalKerja::where('pegawai_id', $pegawai->id)->count());
        $this->assertEquals(
            $shift->id,
            JadwalKerja::where('pegawai_id', $pegawai->id)->where('day_of_week', 1)->first()->shift_id
        );
    }
}
