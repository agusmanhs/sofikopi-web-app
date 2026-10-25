<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\Izin;
use App\Models\JenisIzin;
use App\Models\Kantor;
use App\Models\Pegawai;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AbsensiDateScopeTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        $divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);
        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat', 'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100, 'is_aktif' => true,
        ]);
        $this->pegawai = Pegawai::create([
            'user_id' => User::factory()->create()->id, 'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id, 'nama_lengkap' => 'Pegawai Uji', 'gender' => 'L',
            'status_aktif' => true,
        ]);
    }

    private function absen(string $tanggal): Absensi
    {
        return Absensi::create([
            'pegawai_id' => $this->pegawai->id,
            'tanggal' => $tanggal,
            'jam_masuk' => '08:00:00',
            'status' => 'Tepat Waktu',
        ]);
    }

    public function test_on_date_hanya_mengambil_hari_itu(): void
    {
        $this->absen('2026-10-30');
        $target = $this->absen('2026-10-31');
        $this->absen('2026-11-01');

        $this->assertSame([$target->id], Absensi::onDate('2026-10-31')->pluck('id')->all());
        $this->assertSame([$target->id], Absensi::onDate(Carbon::parse('2026-10-31 15:30:00'))->pluck('id')->all());
        $this->assertSame([$target->id], Absensi::tanggal('2026-10-31')->pluck('id')->all());
    }

    public function test_in_month_mencakup_hari_pertama_dan_terakhir_saja(): void
    {
        $this->absen('2026-09-30');
        $awal = $this->absen('2026-10-01');
        $akhir = $this->absen('2026-10-31');
        $this->absen('2026-11-01');

        $ids = Absensi::inMonth(10, 2026)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$awal->id, $akhir->id], $ids);
        $this->assertEqualsCanonicalizing([$awal->id, $akhir->id], Absensi::inMonth('10', '2026')->pluck('id')->all());
    }

    public function test_in_month_menangani_pergantian_tahun(): void
    {
        $this->absen('2026-11-30');
        $desember = $this->absen('2026-12-31');
        $this->absen('2027-01-01');

        $this->assertSame([$desember->id], Absensi::inMonth(12, 2026)->pluck('id')->all());
    }

    public function test_approved_on_mencakup_batas_awal_dan_akhir_izin(): void
    {
        $jenis = JenisIzin::create(['nama' => 'Cuti Tahunan', 'kode' => 'CT', 'butuh_surat' => false, 'is_aktif' => true]);
        Izin::create([
            'pegawai_id' => $this->pegawai->id, 'jenis_izin_id' => $jenis->id,
            'tgl_mulai' => '2026-10-10', 'tgl_selesai' => '2026-10-12', 'jumlah_hari' => 3,
            'alasan' => 'Keperluan keluarga', 'status_approval' => Izin::STATUS_APPROVED,
        ]);

        $this->assertFalse(Izin::approvedOn('2026-10-09')->exists());
        $this->assertTrue(Izin::approvedOn('2026-10-10')->exists());
        $this->assertTrue(Izin::approvedOn(Carbon::parse('2026-10-11'))->exists());
        $this->assertTrue(Izin::approvedOn('2026-10-12')->exists());
        $this->assertFalse(Izin::approvedOn('2026-10-13')->exists());
    }

    public function test_approved_on_mengabaikan_izin_yang_belum_disetujui(): void
    {
        $jenis = JenisIzin::create(['nama' => 'Cuti Tahunan', 'kode' => 'CT', 'butuh_surat' => false, 'is_aktif' => true]);
        Izin::create([
            'pegawai_id' => $this->pegawai->id, 'jenis_izin_id' => $jenis->id,
            'tgl_mulai' => '2026-10-10', 'tgl_selesai' => '2026-10-12', 'jumlah_hari' => 3,
            'alasan' => 'Keperluan keluarga', 'status_approval' => Izin::STATUS_PENDING,
        ]);

        $this->assertFalse(Izin::approvedOn('2026-10-11')->exists());
    }

    public function test_index_performa_terpasang(): void
    {
        $this->assertTrue(Schema::hasIndex('absensis', 'absensis_tanggal_index'));
        $this->assertTrue(Schema::hasIndex('izins', 'izins_pegawai_status_mulai_index'));
        $this->assertTrue(Schema::hasIndex('activity_logs', 'activity_logs_module_created_at_index'));
    }
}
