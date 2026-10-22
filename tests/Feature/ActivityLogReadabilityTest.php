<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Divisi;
use App\Models\Kantor;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\AbsensiService;
use App\Services\SettingService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityLogReadabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));

        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('notifyAbsenMasuk')->andReturnNull();
            $mock->shouldReceive('notifyAbsenPulang')->andReturnNull();
        });

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makePegawai(?User $user = null, string $nama = 'Pegawai Uji'): array
    {
        $divisi = Divisi::firstOrCreate(['nama' => 'Operasional'], ['is_aktif' => true]);
        $kantor = Kantor::firstOrCreate(['nama' => 'Kantor Pusat'], [
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);
        $shift = Shift::create([
            'divisi_id' => $divisi->id,
            'nama' => 'Shift Pagi',
            'jam_masuk' => '11:00:00',
            'jam_pulang' => '19:00:00',
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);
        $pegawai = Pegawai::create([
            'user_id' => ($user ?? User::factory()->create())->id,
            'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id,
            'shift_id' => $shift->id,
            'nama_lengkap' => $nama,
            'gender' => 'L',
            'status_aktif' => true,
        ]);

        return [$pegawai, $shift];
    }

    private function absenData(Shift $shift, array $extra = []): array
    {
        return $extra + [
            'shift_id' => $shift->id,
            'latitude' => -5.147665,
            'longitude' => 119.432732,
        ];
    }

    private function makeViewer(): User
    {
        $menu = Menu::create([
            'name' => 'Activity Log',
            'slug' => 'activity-log.index',
            'path' => '/activity-log',
            'order_no' => 1,
            'is_active' => true,
        ]);
        $role = Role::firstOrCreate(['slug' => 'hrd'], ['name' => 'Hrd']);
        DB::table('role_menu')->insert([
            'role_id' => $role->id,
            'menu_id' => $menu->id,
            'can_create' => false,
            'can_read' => true,
            'can_update' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $this->makePegawai($user, 'Petugas HRD');

        return $user;
    }

    public function test_absen_masuk_dan_pulang_tercatat_di_activity_log()
    {
        [$pegawai, $shift] = $this->makePegawai();

        app(AbsensiService::class)->absenMasuk($pegawai, $this->absenData($shift));
        app(AbsensiService::class)->absenPulang($pegawai, $this->absenData($shift, ['keterangan' => 'Urusan keluarga']));

        $masuk = ActivityLog::where('action', 'absen_masuk')->firstOrFail();
        $this->assertSame('absensi', $masuk->module);
        $this->assertSame($pegawai->user_id, $masuk->user_id);
        $this->assertSame('Pegawai Uji', $masuk->user_name);
        $this->assertStringContainsString('Pegawai Uji absen masuk 12:00:00 (Terlambat)', $masuk->description);
        $this->assertSame('Kantor Pusat', $masuk->properties['lokasi']);
        $this->assertSame('Terlambat', $masuk->properties['status']);

        $pulang = ActivityLog::where('action', 'absen_pulang')->firstOrFail();
        $this->assertSame('absensi', $pulang->module);
        $this->assertSame('Urusan keluarga', $pulang->properties['keterangan']);
    }

    public function test_absen_ditolak_tidak_membuat_log()
    {
        [$pegawai, $shift] = $this->makePegawai();
        $shift->update(['is_aktif' => false]);

        try {
            app(AbsensiService::class)->absenMasuk($pegawai, $this->absenData($shift));
        } catch (\Exception $e) {
            // diharapkan
        }

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_halaman_log_menampilkan_label_modul_dan_filter_modul()
    {
        $viewer = $this->makeViewer();

        ActivityLog::create([
            'user_id' => $viewer->id,
            'user_name' => 'Budi Santoso',
            'action' => 'absen_masuk',
            'module' => 'absensi',
            'description' => 'Budi Santoso absen masuk 08:00:00 (Tepat Waktu)',
        ]);
        ActivityLog::create([
            'user_id' => $viewer->id,
            'user_name' => 'Sari Wulandari',
            'action' => 'created',
            'module' => 'products',
            'description' => 'Menambah produk Kopi Susu',
        ]);

        $semua = $this->actingAs($viewer)->get(route('activity-log.index'));
        $semua->assertOk()
            ->assertSee('Absen Masuk')
            ->assertSee('Budi Santoso')
            ->assertSee('Sari Wulandari');

        $absensi = $this->actingAs($viewer)->get(route('activity-log.index', ['module' => 'absensi']));
        $absensi->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Menambah produk Kopi Susu');

        $cari = $this->actingAs($viewer)->get(route('activity-log.index', ['search' => 'Sari']));
        $cari->assertOk()
            ->assertSee('Sari Wulandari')
            ->assertDontSee('Budi Santoso absen masuk');
    }

    public function test_halaman_log_dipaginasi_di_server()
    {
        $viewer = $this->makeViewer();

        foreach (range(1, 30) as $i) {
            ActivityLog::create([
                'user_name' => 'User '.$i,
                'action' => 'login',
                'module' => 'auth',
                'description' => "Login nomor {$i}",
            ]);
        }

        $page1 = $this->actingAs($viewer)->get(route('activity-log.index'));
        $page1->assertOk()->assertSee('Login nomor 30')->assertDontSee('Login nomor 1<');
        $this->assertCount(25, $page1->viewData('logs')->items());

        $page2 = $this->actingAs($viewer)->get(route('activity-log.index', ['page' => 2]));
        $this->assertCount(5, $page2->viewData('logs')->items());
    }

    public function test_login_gagal_tidak_masuk_activity_log()
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $this->from('/login')
            ->post('/login', ['email' => 'budi@example.com', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertSame(0, ActivityLog::count());
    }
}
