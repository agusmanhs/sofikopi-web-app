<?php

namespace Tests\Feature;

use App\Exports\KunjunganExport;
use App\Models\Divisi;
use App\Models\Kantor;
use App\Models\Kunjungan;
use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class KunjunganExportTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        $category = MitraCategory::create(['name' => 'Kafe', 'is_active' => true]);
        $this->mitra = Mitra::create([
            'mitra_category_id' => $category->id, 'code' => 'MTR-001',
            'name' => 'Toko Sejahtera', 'is_active' => true,
        ]);

        $divisi = Divisi::create(['nama' => 'QC', 'is_aktif' => true]);
        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat', 'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100, 'is_aktif' => true,
        ]);
        $this->petugas = User::factory()->create();
        Pegawai::create([
            'user_id' => $this->petugas->id, 'divisi_id' => $divisi->id, 'kantor_id' => $kantor->id,
            'nama_lengkap' => 'Budi Petugas', 'gender' => 'L', 'status_aktif' => true,
        ]);
    }

    private function kunjungan(string $tanggal, string $tipe = 'routine'): Kunjungan
    {
        return Kunjungan::create([
            'user_id' => $this->petugas->id, 'mitra_id' => $this->mitra->id,
            'visit_type' => $tipe, 'tanggal_kunjungan' => $tanggal,
            'espresso_calibration' => "kalibrasi {$tanggal}",
            'taste_notes' => 'manis seimbang',
        ]);
    }

    private function csv(array $filters = []): string
    {
        return Excel::raw(new KunjunganExport($filters), ExcelFormat::CSV);
    }

    public function test_export_membaca_lewat_query_bukan_koleksi_penuh(): void
    {
        $this->assertInstanceOf(FromQuery::class, new KunjunganExport);
    }

    public function test_isi_export_berurutan_terbaru_dan_terpetakan(): void
    {
        $this->kunjungan('2026-10-01');
        $this->kunjungan('2026-10-03', 'by_request');
        $this->kunjungan('2026-10-02');

        $csv = $this->csv();

        $this->assertStringContainsString('Budi Petugas', $csv);
        $this->assertStringContainsString('Toko Sejahtera', $csv);
        $this->assertStringContainsString('By Request', $csv);
        $this->assertLessThan(strpos($csv, '02/10/2026'), strpos($csv, '03/10/2026'));
        $this->assertLessThan(strpos($csv, '01/10/2026'), strpos($csv, '02/10/2026'));
    }

    public function test_filter_tanggal_mencakup_hari_awal_dan_akhir(): void
    {
        $this->kunjungan('2026-09-30');
        $this->kunjungan('2026-10-01');
        $this->kunjungan('2026-10-31');
        $this->kunjungan('2026-11-01');

        $csv = $this->csv(['start_date' => '2026-10-01', 'end_date' => '2026-10-31']);

        $this->assertStringContainsString('01/10/2026', $csv);
        $this->assertStringContainsString('31/10/2026', $csv);
        $this->assertStringNotContainsString('30/09/2026', $csv);
        $this->assertStringNotContainsString('01/11/2026', $csv);
    }
}
