<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\HariLibur;
use App\Models\Kantor;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\AbsensiService;
use App\Services\SettingService;
use Carbon\Carbon;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RekapAbsensiPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const REKAP_QUERY_BUDGET = 40;

    private Divisi $divisi;

    private Shift $shift;

    private User $hrd;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();

        $this->divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true, 'toleransi_terlambat' => 5]);
        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat', 'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100, 'is_aktif' => true,
        ]);
        $this->shift = Shift::create([
            'divisi_id' => $this->divisi->id, 'nama' => 'Shift Pagi',
            'jam_masuk' => '08:00:00', 'jam_pulang' => '16:00:00', 'is_aktif' => true,
            'ikut_libur' => true, 'is_cross_day' => false,
            'hari_kerja' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ]);

        HariLibur::create([
            'nama' => 'Libur Uji', 'tanggal' => '2026-10-05', 'is_all_divisi' => true,
        ]);

        $menu = Menu::create([
            'name' => 'Rekap Absensi', 'slug' => 'absensi.rekap', 'path' => '/absensi/rekap',
            'order_no' => 1, 'is_active' => true,
        ]);
        $role = Role::create(['name' => 'Hrd', 'slug' => 'hrd']);
        DB::table('role_menu')->insert([
            'role_id' => $role->id, 'menu_id' => $menu->id,
            'can_create' => false, 'can_read' => true, 'can_update' => false, 'can_delete' => false,
        ]);

        $this->hrd = User::factory()->create(['role_id' => $role->id]);
        $this->makePegawai($this->hrd, 'Petugas HRD', $kantor);

        foreach (range(1, 25) as $i) {
            $pegawai = $this->makePegawai(User::factory()->create(), "Pegawai {$i}", $kantor);
            $this->isiAbsensi($pegawai);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makePegawai(User $user, string $nama, Kantor $kantor): Pegawai
    {
        return Pegawai::create([
            'user_id' => $user->id, 'divisi_id' => $this->divisi->id, 'kantor_id' => $kantor->id,
            'shift_id' => $this->shift->id, 'nama_lengkap' => $nama, 'gender' => 'L',
            'status_aktif' => true,
        ]);
    }

    private function isiAbsensi(Pegawai $pegawai): void
    {
        $baris = [
            ['2026-10-01', '07:55:00', '16:05:00', 'Tepat Waktu'],
            ['2026-10-02', '08:20:00', '16:00:00', 'Terlambat'],
            ['2026-10-03', '08:00:00', null, 'Tepat Waktu'],
            ['2026-10-06', '08:00:00', '15:00:00', 'Tepat Waktu'],
            ['2026-10-07', null, null, 'Sakit'],
        ];

        foreach ($baris as [$tanggal, $masuk, $pulang, $status]) {
            Absensi::create([
                'pegawai_id' => $pegawai->id, 'shift_id' => $this->shift->id,
                'tanggal' => $tanggal, 'jam_masuk' => $masuk, 'jam_pulang' => $pulang,
                'status' => $status,
            ]);
        }
    }

    public function test_statistik_dengan_data_termuat_sama_dengan_versi_query_per_pegawai(): void
    {
        $service = app(AbsensiService::class);
        $pegawai = Pegawai::with(['shift', 'absensis' => fn ($q) => $q->inMonth(10, 2026)->with('shift')])
            ->where('nama_lengkap', 'Pegawai 3')
            ->firstOrFail();

        $versiLama = $service->getStatistikPegawai($pegawai->id, 10, 2026);
        $versiBaru = $service->getStatistikPegawai($pegawai->id, 10, 2026, $pegawai, $pegawai->absensis);

        $this->assertSame($versiLama, $versiBaru);
        $this->assertGreaterThan(0, $versiBaru['hadir']);
        $this->assertGreaterThan(0, $versiBaru['alfa']);
    }

    public function test_statistik_harian_dengan_data_termuat_sama_dengan_versi_query(): void
    {
        $service = app(AbsensiService::class);

        $versiLama = $service->getStatistik('2026-10-01');

        $absensis = app(\App\Repositories\AbsensiRepository::class)->getAbsensiHariIni('2026-10-01');
        $belum = app(\App\Repositories\AbsensiRepository::class)->getBelumAbsenHariIni('2026-10-01');
        $versiBaru = $service->getStatistik('2026-10-01', $absensis, $belum->count());

        $this->assertSame($versiLama, $versiBaru);
    }

    public function test_halaman_rekap_tidak_menjalankan_query_per_pegawai(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->hrd)->get(route('absensi.rekap', ['bulan' => 10, 'tahun' => 2026]));

        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertOk()->assertSee('Pegawai 1')->assertSee('Pegawai 25');

        if ($path = getenv('QUERY_BUDGET_REPORT')) {
            file_put_contents($path, "rekap 26 pegawai: {$jumlah} query\n", FILE_APPEND);
        }

        $this->assertLessThanOrEqual(self::REKAP_QUERY_BUDGET, $jumlah);
    }
}
