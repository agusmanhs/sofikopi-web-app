<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\Izin;
use App\Models\JadwalKerja;
use App\Models\JadwalOverride;
use App\Models\JenisIzin;
use App\Models\Kantor;
use App\Models\Pegawai;
use App\Models\Setting;
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

/**
 * Simulasi skenario nyata batas pengajuan izin/sakit untuk HARI INI.
 * Waktu dibekukan pada 12:00; batas bawaan 1 jam setelah jam masuk shift.
 */
class SimulasiBatasIzinTest extends TestCase
{
    use RefreshDatabase;

    protected Divisi $divisi;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-14 12:00:00');

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('notifyIzinCreated')->andReturnNull();
        $this->app->instance(TelegramService::class, $telegram);

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();

        $this->divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function shift(int $offsetJam, int $offsetMenit = 0): Shift
    {
        $masuk = now()->addHours($offsetJam)->addMinutes($offsetMenit);

        return Shift::create([
            'divisi_id' => $this->divisi->id,
            'nama' => 'Shift '.$masuk->format('H:i'),
            'jam_masuk' => $masuk->format('H:i:s'),
            'jam_pulang' => $masuk->copy()->addHours(8)->format('H:i:s'),
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);
    }

    protected function pegawai(?Shift $shiftDefault): Pegawai
    {
        $kantor = Kantor::first() ?? Kantor::create([
            'nama' => 'Kantor Pusat',
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);

        return Pegawai::create([
            'user_id' => User::factory()->create()->id,
            'divisi_id' => $this->divisi->id,
            'kantor_id' => $kantor->id,
            'shift_id' => $shiftDefault?->id,
            'nama_lengkap' => 'Pegawai Uji',
            'gender' => 'L',
            'status_aktif' => true,
        ]);
    }

    protected function sudahAbsen(Pegawai $pegawai, Shift $shift, bool $denganJamMasuk = true): void
    {
        Absensi::create([
            'pegawai_id' => $pegawai->id,
            'shift_id' => $shift->id,
            'tanggal' => today()->toDateString(),
            'jam_masuk' => $denganJamMasuk ? $shift->jam_masuk->format('H:i:s') : null,
            'status' => 'Hadir',
        ]);
    }

    protected function jenis(string $kode = 'sakit', string $nama = 'Sakit'): JenisIzin
    {
        return JenisIzin::firstOrCreate(['kode' => $kode], [
            'nama' => $nama,
            'butuh_surat' => false,
            'max_hari' => null,
            'is_aktif' => true,
        ]);
    }

    protected function ajukan(Pegawai $pegawai, ?JenisIzin $jenis = null, ?string $tanggal = null): Izin
    {
        $jenis ??= $this->jenis();
        $tanggal ??= today()->toDateString();

        return app(IzinService::class)->ajukanIzin($pegawai->id, [
            'jenis_izin_id' => $jenis->id,
            'tgl_mulai' => $tanggal,
            'tgl_selesai' => $tanggal,
            'alasan' => 'Simulasi',
        ]);
    }

    protected function assertDitolak(Pegawai $pegawai, ?JenisIzin $jenis = null): void
    {
        try {
            $this->ajukan($pegawai, $jenis);
        } catch (\Exception $e) {
            $this->assertStringContainsString('sudah ditutup', $e->getMessage());
            $this->assertSame(0, Izin::count(), 'Izin yang ditolak tidak boleh tersimpan.');

            return;
        }

        $this->fail('Pengajuan seharusnya ditolak, tetapi lolos.');
    }

    protected function assertDiterima(Pegawai $pegawai, ?JenisIzin $jenis = null): void
    {
        $izin = $this->ajukan($pegawai, $jenis);

        $this->assertNotNull($izin->id);
        $this->assertSame(1, Izin::count());
    }

    // ---------- Belum absen ----------

    public function test_belum_absen_shift_lewat_batas_ditolak()
    {
        $this->assertDitolak($this->pegawai($this->shift(-3)));
    }

    public function test_belum_absen_shift_belum_mulai_diterima()
    {
        $this->assertDiterima($this->pegawai($this->shift(2)));
    }

    public function test_tepat_pada_batas_diterima_semenit_setelahnya_ditolak()
    {
        // Shift mulai 11:00, batas 1 jam = 12:00. Sekarang 12:00 -> masih boleh.
        $pegawai = $this->pegawai($this->shift(-1));
        $this->assertDiterima($pegawai);

        Izin::query()->delete();

        Carbon::setTestNow('2026-10-14 12:01:00');
        $this->assertDitolak($pegawai);
    }

    // ---------- Sudah absen ----------

    public function test_sudah_absen_lalu_sakit_lewat_batas_ditolak()
    {
        $shift = $this->shift(-3);
        $pegawai = $this->pegawai($shift);
        $this->sudahAbsen($pegawai, $shift);

        $this->assertDitolak($pegawai);
    }

    public function test_sudah_absen_lalu_sakit_dalam_batas_diterima()
    {
        $shift = $this->shift(0);
        $pegawai = $this->pegawai($shift);
        $this->sudahAbsen($pegawai, $shift);

        $this->assertDiterima($pegawai);
    }

    public function test_sudah_absen_dengan_shift_berbeda_memakai_shift_absen()
    {
        $default = $this->shift(3);
        $dipakai = $this->shift(-3);
        $pegawai = $this->pegawai($default);
        $this->sudahAbsen($pegawai, $dipakai);

        $this->assertDitolak($pegawai);
    }

    public function test_baris_absen_tanpa_jam_masuk_tidak_dianggap_sudah_absen()
    {
        // Ada baris absensi tetapi belum absen masuk: acuan tetap shift default (lewat batas).
        $default = $this->shift(-3);
        $lain = $this->shift(3);
        $pegawai = $this->pegawai($default);
        $this->sudahAbsen($pegawai, $lain, denganJamMasuk: false);

        $this->assertDitolak($pegawai);
    }

    // ---------- Jadwal per pegawai ----------

    public function test_override_shift_mengalahkan_shift_default()
    {
        $default = $this->shift(-5);
        $pegawai = $this->pegawai($default);
        JadwalOverride::create([
            'pegawai_id' => $pegawai->id,
            'tanggal' => today()->toDateString(),
            'shift_id' => $this->shift(2)->id,
        ]);

        $this->assertDiterima($pegawai);
    }

    public function test_pola_mingguan_hari_ini_dipakai_sebagai_acuan()
    {
        $pegawai = $this->pegawai($this->shift(2));
        JadwalKerja::create([
            'pegawai_id' => $pegawai->id,
            'day_of_week' => today()->dayOfWeek,
            'shift_id' => $this->shift(-4)->id,
        ]);

        $this->assertDitolak($pegawai);
    }

    public function test_libur_terjadwal_belum_absen_tidak_diblokir()
    {
        $pegawai = $this->pegawai($this->shift(-5));
        JadwalOverride::create([
            'pegawai_id' => $pegawai->id,
            'tanggal' => today()->toDateString(),
            'shift_id' => null,
        ]);

        $this->assertDiterima($pegawai);
    }

    public function test_libur_terjadwal_tetapi_sudah_absen_memakai_shift_absen()
    {
        $shift = $this->shift(-3);
        $pegawai = $this->pegawai($shift);
        JadwalOverride::create([
            'pegawai_id' => $pegawai->id,
            'tanggal' => today()->toDateString(),
            'shift_id' => null,
        ]);
        $this->sudahAbsen($pegawai, $shift);

        $this->assertDitolak($pegawai);
    }

    public function test_tanpa_shift_sama_sekali_tidak_diblokir()
    {
        $this->assertDiterima($this->pegawai(null));
    }

    // ---------- Setting admin ----------

    public function test_batas_mengikuti_setting_admin_baik_naik_maupun_turun()
    {
        $shift = $this->shift(-3);
        $pegawai = $this->pegawai($shift);
        $this->sudahAbsen($pegawai, $shift);
        $settings = app(SettingService::class);

        $this->assertDitolak($pegawai);               // batas 1 jam: shift 09:00 -> tutup 10:00

        $settings->setMany([Setting::KEY_BATAS_IZIN_JAM => 4]);   // tutup 13:00
        $this->assertDiterima($pegawai);

        Izin::query()->delete();

        $settings->setMany([Setting::KEY_BATAS_IZIN_JAM => 2]);   // tutup 11:00
        $this->assertDitolak($pegawai);
    }

    // ---------- Jenis izin dan tanggal ----------

    public function test_semua_jenis_izin_terkena_batas()
    {
        $pegawai = $this->pegawai($this->shift(-5));

        foreach ([['sakit', 'Sakit'], ['sakit no skd', 'Sakit No SKD'], ['izin', 'Izin Pribadi'], ['dinas', 'Dinas Luar']] as [$kode, $nama]) {
            $this->assertDitolak($pegawai, $this->jenis($kode, $nama));
        }
    }

    public function test_pengajuan_untuk_besok_tidak_dibatasi_walau_sudah_absen_dan_lewat_batas()
    {
        $shift = $this->shift(-5);
        $pegawai = $this->pegawai($shift);
        $this->sudahAbsen($pegawai, $shift);

        $izin = $this->ajukan($pegawai, null, today()->addDay()->toDateString());

        $this->assertNotNull($izin->id);
    }

    // ---------- Banyak pegawai palsu sekaligus ----------

    public function test_simulasi_kantor_sehari_banyak_pegawai_dengan_kondisi_berbeda()
    {
        $lewat = $this->shift(-4);
        $baru = $this->shift(0);
        $nanti = $this->shift(3);

        $kasus = [];

        $p = $this->pegawai($lewat);
        $kasus['belum absen, shift lewat'] = [$p, false];

        $p = $this->pegawai($baru);
        $kasus['belum absen, shift baru mulai'] = [$p, true];

        $p = $this->pegawai($lewat);
        $this->sudahAbsen($p, $lewat);
        $kasus['sudah absen, shift lewat'] = [$p, false];

        $p = $this->pegawai($nanti);
        $this->sudahAbsen($p, $lewat);
        $kasus['sudah absen shift lain yang lewat'] = [$p, false];

        $p = $this->pegawai($lewat);
        $this->sudahAbsen($p, $baru);
        $kasus['sudah absen shift lain yang baru mulai'] = [$p, true];

        $p = $this->pegawai($lewat);
        JadwalOverride::create(['pegawai_id' => $p->id, 'tanggal' => today()->toDateString(), 'shift_id' => null]);
        $kasus['libur terjadwal, belum absen'] = [$p, true];

        $p = $this->pegawai(null);
        $kasus['tanpa shift'] = [$p, true];

        $p = $this->pegawai($nanti);
        JadwalKerja::create(['pegawai_id' => $p->id, 'day_of_week' => today()->dayOfWeek, 'shift_id' => $lewat->id]);
        $kasus['pola mingguan lewat mengalahkan shift default'] = [$p, false];

        foreach ($kasus as $nama => [$pegawai, $seharusnyaLolos]) {
            try {
                $this->ajukan($pegawai);
                $lolos = true;
            } catch (\Exception $e) {
                $this->assertStringContainsString('sudah ditutup', $e->getMessage(), $nama);
                $lolos = false;
            }

            $this->assertSame($seharusnyaLolos, $lolos, "Skenario '{$nama}'");
        }

        $this->assertSame(
            count(array_filter($kasus, fn ($k) => $k[1])),
            Izin::count(),
            'Jumlah izin tersimpan harus sama dengan jumlah pengajuan yang lolos.'
        );
    }

    // ---------- Lewat HTTP seperti pegawai sungguhan ----------

    public function test_http_pegawai_ditolak_lewat_batas_dan_tidak_ada_data_tersimpan()
    {
        $shift = $this->shift(-3);
        $pegawai = $this->pegawai($shift);
        $this->sudahAbsen($pegawai, $shift);
        $jenis = $this->jenis();

        $response = $this->actingAs($pegawai->user)->post(route('izin.store'), [
            'jenis_izin_id' => $jenis->id,
            'tgl_mulai' => today()->toDateString(),
            'tgl_selesai' => today()->toDateString(),
            'alasan' => 'Sakit demam',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('sudah ditutup', session('error'));
        $this->assertSame(0, Izin::count());
    }

    public function test_http_pegawai_diterima_dalam_batas()
    {
        $shift = $this->shift(0);
        $pegawai = $this->pegawai($shift);
        $this->sudahAbsen($pegawai, $shift);
        $jenis = $this->jenis();

        $response = $this->actingAs($pegawai->user)->post(route('izin.store'), [
            'jenis_izin_id' => $jenis->id,
            'tgl_mulai' => today()->toDateString(),
            'tgl_selesai' => today()->toDateString(),
            'alasan' => 'Sakit demam',
        ]);

        $response->assertSessionHas('success');
        $this->assertSame(1, Izin::count());
    }
}
