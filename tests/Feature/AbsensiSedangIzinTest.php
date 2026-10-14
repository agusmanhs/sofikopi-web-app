<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\Izin;
use App\Models\JenisIzin;
use App\Models\Kantor;
use App\Models\Pegawai;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\AbsensiService;
use App\Services\SettingService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsensiSedangIzinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));

        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('notifyAbsenMasuk')->andReturnNull();
            $mock->shouldReceive('notifyAbsenPulang')->andReturnNull();
            $mock->shouldReceive('notifyIzinCreated')->andReturnNull();
            $mock->shouldReceive('notifyIzinStatus')->andReturnNull();
            $mock->shouldReceive('notify')->andReturnNull();
        });

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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

    protected function makeApprovedIzin(Pegawai $pegawai): Izin
    {
        $jenis = JenisIzin::create([
            'nama' => 'Cuti Tahunan',
            'kode' => 'CT',
            'butuh_surat' => false,
            'is_aktif' => true,
        ]);

        return Izin::create([
            'pegawai_id' => $pegawai->id,
            'jenis_izin_id' => $jenis->id,
            'tgl_mulai' => today(),
            'tgl_selesai' => today(),
            'jumlah_hari' => 1,
            'alasan' => 'Keperluan keluarga',
            'status_approval' => Izin::STATUS_APPROVED,
        ]);
    }

    protected function absenData(Shift $shift): array
    {
        return [
            'shift_id' => $shift->id,
            'latitude' => -5.147665,
            'longitude' => 119.432732,
        ];
    }

    public function test_absen_masuk_rejected_when_sedang_izin()
    {
        [$pegawai, $shift] = $this->makePegawai(-1);
        $this->makeApprovedIzin($pegawai);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Anda sedang cuti/izin pada tanggal ini, tidak dapat melakukan absensi.');

        app(AbsensiService::class)->absenMasuk($pegawai, $this->absenData($shift));
    }

    public function test_absen_pulang_rejected_when_sedang_izin()
    {
        [$pegawai, $shift] = $this->makePegawai(-1);
        $this->makeApprovedIzin($pegawai);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Anda sedang cuti/izin pada tanggal ini, tidak dapat melakukan absensi.');

        app(AbsensiService::class)->absenPulang($pegawai, $this->absenData($shift));
    }

    public function test_index_shows_izin_info_instead_of_ditutup_alert()
    {
        [$pegawai, $shift] = $this->makePegawai(-6);
        $this->makeApprovedIzin($pegawai);

        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        $response->assertStatus(200);
        $this->assertFalse($response->viewData('absenDitutup'));
        $this->assertNotNull($response->viewData('sedangIzin'));
        $response->assertDontSee('Absen Masuk Sudah Ditutup');
        $response->assertSee('Anda sedang cuti/izin hari ini, jadi absensi tidak diperlukan.');
    }

    public function test_ditutup_alert_uses_setting_value()
    {
        [$pegawai, $shift] = $this->makePegawai(-6);
        app(SettingService::class)->setMany([Setting::KEY_BATAS_ABSEN_MASUK_JAM => 4]);

        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        $response->assertStatus(200);
        $this->assertTrue($response->viewData('absenDitutup'));
        $response->assertSee('Absen Masuk Sudah Ditutup');
        $response->assertSee('4 jam setelah jadwal masuk');
        $response->assertDontSee('3 jam setelah jadwal masuk');
    }

    public function test_future_cuti_row_does_not_hide_real_open_session_today()
    {
        [$pegawai, $shift] = $this->makePegawai(-1);

        $real = Absensi::create([
            'pegawai_id' => $pegawai->id,
            'shift_id' => $shift->id,
            'tanggal' => today(),
            'jam_masuk' => now()->subHours(3),
            'jam_pulang' => null,
            'status' => 'Tepat Waktu',
        ]);
        Absensi::create([
            'pegawai_id' => $pegawai->id,
            'shift_id' => $shift->id,
            'tanggal' => '2026-11-06',
            'jam_masuk' => null,
            'jam_pulang' => null,
            'status' => 'Cuti',
        ]);

        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        $response->assertStatus(200);
        $this->assertEquals($real->id, $response->viewData('absensiHariIni')->id);
    }

    public function test_cuti_today_without_real_session_falls_back_to_cuti_row()
    {
        [$pegawai, $shift] = $this->makePegawai(-1);
        $this->makeApprovedIzin($pegawai);

        $cuti = Absensi::create([
            'pegawai_id' => $pegawai->id,
            'shift_id' => $shift->id,
            'tanggal' => today(),
            'jam_masuk' => null,
            'jam_pulang' => null,
            'status' => 'Cuti',
        ]);

        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        $response->assertStatus(200);
        $this->assertEquals($cuti->id, $response->viewData('absensiHariIni')->id);
        $this->assertNotNull($response->viewData('sedangIzin'));
    }

    public function test_open_cross_day_session_from_yesterday_is_still_returned()
    {
        [$pegawai, $shift] = $this->makePegawai(-1);
        $shift->update([
            'jam_masuk' => '20:00:00',
            'jam_pulang' => '14:00:00',
            'is_cross_day' => true,
        ]);

        $yesterday = Absensi::create([
            'pegawai_id' => $pegawai->id,
            'shift_id' => $shift->id,
            'tanggal' => today()->subDay(),
            'jam_masuk' => today()->subDay()->setTime(20, 0),
            'jam_pulang' => null,
            'status' => 'Tepat Waktu',
        ]);

        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        $response->assertStatus(200);
        $this->assertEquals($yesterday->id, $response->viewData('absensiHariIni')->id);
    }
}
