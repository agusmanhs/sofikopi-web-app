<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\Izin;
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
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class IzinApprovalAbsensiGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('notifyIzinCreated')->andReturnNull();
        $telegram->shouldReceive('notifyIzinStatus')->andReturnNull();
        $telegram->shouldReceive('notifyAbsenMasuk')->andReturnNull();
        $telegram->shouldReceive('notifyAbsenPulang')->andReturnNull();
        $telegram->shouldReceive('notify')->andReturnNull();
        $this->app->instance(TelegramService::class, $telegram);

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();
    }

    protected function makePegawai(): array
    {
        $divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);

        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat',
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);

        $shift = Shift::create([
            'divisi_id' => $divisi->id,
            'nama' => 'Shift Pagi',
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '16:00:00',
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);

        $pegawai = Pegawai::create([
            'user_id' => User::factory()->create()->id,
            'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id,
            'shift_id' => $shift->id,
            'nama_lengkap' => 'Pegawai Uji',
            'gender' => 'L',
            'status_aktif' => true,
        ]);

        return [$pegawai, $shift];
    }

    protected function makeIzin(Pegawai $pegawai, string $mulai, string $selesai): Izin
    {
        $jenis = JenisIzin::create([
            'nama' => 'Cuti Tahunan',
            'kode' => 'cuti',
            'butuh_surat' => false,
            'max_hari' => null,
            'is_aktif' => true,
        ]);

        return Izin::create([
            'pegawai_id' => $pegawai->id,
            'jenis_izin_id' => $jenis->id,
            'tgl_mulai' => $mulai,
            'tgl_selesai' => $selesai,
            'alasan' => 'Alasan pengujian',
            'status_approval' => Izin::STATUS_PENDING,
        ]);
    }

    protected function makeAbsensi(Pegawai $pegawai, Shift $shift, string $tanggal, ?string $jamMasuk, string $status): Absensi
    {
        $id = DB::table('absensis')->insertGetId([
            'pegawai_id' => $pegawai->id,
            'shift_id' => $shift->id,
            'tanggal' => $tanggal,
            'jam_masuk' => $jamMasuk ? $tanggal.' '.$jamMasuk : null,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Absensi::find($id);
    }

    public function test_approval_ditolak_bila_rentang_mencakup_tanggal_dengan_jam_masuk()
    {
        [$pegawai, $shift] = $this->makePegawai();
        $absensi = $this->makeAbsensi($pegawai, $shift, today()->toDateString(), '08:05:00', 'Hadir');
        $izin = $this->makeIzin($pegawai, today()->subDay()->toDateString(), today()->toDateString());

        try {
            app(IzinService::class)->approveIzin($izin->id);
            $this->fail('Approval seharusnya ditolak.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('sudah absen masuk', $e->getMessage());
        }

        $absensi->refresh();
        $this->assertNotNull($absensi->jam_masuk);
        $this->assertSame('Hadir', $absensi->status);
        $this->assertSame(Izin::STATUS_PENDING, $izin->fresh()->status_approval);
        $this->assertSame(1, Absensi::where('pegawai_id', $pegawai->id)->count());
    }

    public function test_cuti_mulai_besok_tetap_disetujui_dan_absensi_hari_ini_utuh()
    {
        [$pegawai, $shift] = $this->makePegawai();
        $absensi = $this->makeAbsensi($pegawai, $shift, today()->toDateString(), '08:05:00', 'Hadir');
        $izin = $this->makeIzin($pegawai, today()->addDay()->toDateString(), today()->addDays(2)->toDateString());

        $result = app(IzinService::class)->approveIzin($izin->id);

        $this->assertSame(Izin::STATUS_APPROVED, $result->status_approval);
        $absensi->refresh();
        $this->assertNotNull($absensi->jam_masuk);
        $this->assertSame('Hadir', $absensi->status);
        $this->assertSame('Cuti', Absensi::whereDate('tanggal', today()->addDay())->first()->status);
    }

    public function test_pesan_menyebut_tanggal_hari_ini_untuk_rentang_hari_ini_sampai_lusa()
    {
        [$pegawai, $shift] = $this->makePegawai();
        $this->makeAbsensi($pegawai, $shift, today()->toDateString(), '08:05:00', 'Hadir');
        $izin = $this->makeIzin($pegawai, today()->toDateString(), today()->addDays(2)->toDateString());

        try {
            app(IzinService::class)->approveIzin($izin->id);
            $this->fail('Approval seharusnya ditolak.');
        } catch (\Exception $e) {
            $this->assertStringContainsString(today()->format('d M Y'), $e->getMessage());
        }

        $this->assertSame(Izin::STATUS_PENDING, $izin->fresh()->status_approval);
    }

    public function test_baris_tanpa_jam_masuk_tetap_boleh_ditimpa()
    {
        [$pegawai, $shift] = $this->makePegawai();
        $this->makeAbsensi($pegawai, $shift, today()->toDateString(), null, 'Alpha');
        $izin = $this->makeIzin($pegawai, today()->toDateString(), today()->toDateString());

        $result = app(IzinService::class)->approveIzin($izin->id);

        $this->assertSame(Izin::STATUS_APPROVED, $result->status_approval);
        $this->assertSame('Cuti', Absensi::whereDate('tanggal', today())->first()->status);
    }
}
