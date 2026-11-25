<?php

namespace Tests\Feature;

use App\Models\Divisi;
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

class IzinShiftDivisiFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-14 12:00:00');

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('notifyIzinCreated')->andReturnNull();
        $telegram->shouldReceive('notifyIzinStatus')->andReturnNull();
        $telegram->shouldReceive('notify')->andReturnNull();
        $this->app->instance(TelegramService::class, $telegram);

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function makeDivisi(): Divisi
    {
        return Divisi::create(['nama' => 'Divisi '.uniqid(), 'is_aktif' => true]);
    }

    protected function makeShift(Divisi $divisi, string $nama, int $jamMasukOffset, bool $aktif = true): Shift
    {
        return Shift::create([
            'divisi_id' => $divisi->id,
            'nama' => $nama,
            'jam_masuk' => now()->addHours($jamMasukOffset)->format('H:i:s'),
            'jam_pulang' => now()->addHours($jamMasukOffset + 8)->format('H:i:s'),
            'is_aktif' => $aktif,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);
    }

    protected function makePegawai(Divisi $divisi, ?Shift $shiftTerdaftar = null): Pegawai
    {
        $kantor = Kantor::firstOrCreate(
            ['nama' => 'Kantor Pusat'],
            ['titik_lokasi' => '-5.147665,119.432732', 'radius_meter' => 100, 'is_aktif' => true]
        );

        return Pegawai::create([
            'user_id' => User::factory()->create()->id,
            'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id,
            'shift_id' => $shiftTerdaftar?->id,
            'nama_lengkap' => 'Pegawai Uji',
            'gender' => 'L',
            'status_aktif' => true,
        ]);
    }

    protected function jenis(string $kode, string $nama): JenisIzin
    {
        return JenisIzin::create([
            'nama' => $nama,
            'kode' => $kode,
            'butuh_surat' => false,
            'max_hari' => null,
            'is_aktif' => true,
        ]);
    }

    protected function ajukan(Pegawai $pegawai, JenisIzin $jenis, ?string $tanggal = null)
    {
        $tanggal ??= today()->toDateString();

        return app(IzinService::class)->ajukanIzin($pegawai->id, [
            'jenis_izin_id' => $jenis->id,
            'tgl_mulai' => $tanggal,
            'tgl_selesai' => $tanggal,
            'alasan' => 'Alasan pengujian',
        ]);
    }

    public function test_pegawai_tanpa_shift_terdaftar_di_divisi_satu_shift_ditolak_setelah_lewat_batas()
    {
        $divisi = $this->makeDivisi();
        $this->makeShift($divisi, 'Reguler', -3);
        $pegawai = $this->makePegawai($divisi);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        $this->ajukan($pegawai, $this->jenis('sakit no skd', 'Sakit No SKD'));
    }

    public function test_pegawai_tanpa_shift_terdaftar_di_divisi_satu_shift_diterima_dalam_batas()
    {
        $divisi = $this->makeDivisi();
        $this->makeShift($divisi, 'Reguler', 0);
        $pegawai = $this->makePegawai($divisi);

        $izin = $this->ajukan($pegawai, $this->jenis('izin pribadi', 'Izin Pribadi'));

        $this->assertNotNull($izin->id);
    }

    public function test_batas_fallback_mengikuti_setting_admin()
    {
        $divisi = $this->makeDivisi();
        $this->makeShift($divisi, 'Reguler', -3);
        $pegawai = $this->makePegawai($divisi);

        app(SettingService::class)->setMany([Setting::KEY_BATAS_IZIN_JAM => 4]);

        $izin = $this->ajukan($pegawai, $this->jenis('sakit no skd', 'Sakit No SKD'));

        $this->assertNotNull($izin->id);
    }

    public function test_shift_nonaktif_divisi_tidak_dijadikan_acuan()
    {
        $divisi = $this->makeDivisi();
        $this->makeShift($divisi, 'Lama', -6, false);
        $this->makeShift($divisi, 'Reguler', 0);
        $pegawai = $this->makePegawai($divisi);

        $izin = $this->ajukan($pegawai, $this->jenis('sakit no skd', 'Sakit No SKD'));

        $this->assertNotNull($izin->id);
    }

    public function test_divisi_dua_shift_diterima_selama_masih_ada_shift_yang_belum_lewat_batas()
    {
        $divisi = $this->makeDivisi();
        $this->makeShift($divisi, 'Pagi', -3);
        $this->makeShift($divisi, 'Malam', 4);
        $pegawai = $this->makePegawai($divisi);

        $izin = $this->ajukan($pegawai, $this->jenis('sakit no skd', 'Sakit No SKD'));

        $this->assertNotNull($izin->id);
    }

    public function test_divisi_dua_shift_ditolak_setelah_semua_shift_lewat_batas()
    {
        $divisi = $this->makeDivisi();
        $this->makeShift($divisi, 'Pagi', -6);
        $this->makeShift($divisi, 'Malam', -3);
        $pegawai = $this->makePegawai($divisi);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        $this->ajukan($pegawai, $this->jenis('sakit no skd', 'Sakit No SKD'));
    }

    public function test_shift_terdaftar_pegawai_tetap_didahulukan_dari_shift_divisi()
    {
        $divisi = $this->makeDivisi();
        $shiftPegawai = $this->makeShift($divisi, 'Pagi', -5);
        $this->makeShift($divisi, 'Malam', 4);
        $pegawai = $this->makePegawai($divisi, $shiftPegawai);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        $this->ajukan($pegawai, $this->jenis('sakit no skd', 'Sakit No SKD'));
    }

    public function test_divisi_tanpa_shift_aktif_tidak_diblokir()
    {
        $divisi = $this->makeDivisi();
        $pegawai = $this->makePegawai($divisi);

        $izin = $this->ajukan($pegawai, $this->jenis('sakit no skd', 'Sakit No SKD'));

        $this->assertNotNull($izin->id);
    }

    public function test_izin_prioritas_tidak_dibatasi_jam()
    {
        $divisi = $this->makeDivisi();
        $this->makeShift($divisi, 'Reguler', -5);
        $pegawai = $this->makePegawai($divisi);

        $izin = $this->ajukan($pegawai, $this->jenis('izin prioritas', 'Izin Prioritas'));

        $this->assertNotNull($izin->id);
    }

    public function test_izin_prioritas_dengan_shift_terdaftar_juga_tidak_dibatasi_jam()
    {
        $divisi = $this->makeDivisi();
        $shift = $this->makeShift($divisi, 'Reguler', -5);
        $pegawai = $this->makePegawai($divisi, $shift);

        $izin = $this->ajukan($pegawai, $this->jenis('izin prioritas', 'Izin Prioritas'));

        $this->assertNotNull($izin->id);
    }

    public function test_pengajuan_untuk_besok_tidak_dibatasi_pada_fallback()
    {
        $divisi = $this->makeDivisi();
        $this->makeShift($divisi, 'Reguler', -5);
        $pegawai = $this->makePegawai($divisi);

        $izin = $this->ajukan($pegawai, $this->jenis('sakit no skd', 'Sakit No SKD'), today()->addDay()->toDateString());

        $this->assertNotNull($izin->id);
    }

    public function test_jenis_lain_yang_bukan_prioritas_tetap_dibatasi()
    {
        $divisi = $this->makeDivisi();
        $this->makeShift($divisi, 'Reguler', -5);
        $pegawai = $this->makePegawai($divisi);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        $this->ajukan($pegawai, $this->jenis('izin pribadi', 'Izin Pribadi'));
    }
}
