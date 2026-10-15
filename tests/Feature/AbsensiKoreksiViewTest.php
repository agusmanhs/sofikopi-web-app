<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\Kantor;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class AbsensiKoreksiViewTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private Pegawai $pegawai;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));

        $telegram = Mockery::mock(TelegramService::class);
        foreach (['notify', 'notifyAbsenMasuk', 'notifyAbsenPulang', 'notifyIzinCreated', 'notifyIzinStatus', 'notifyDeliveryCompleted'] as $method) {
            $telegram->shouldReceive($method)->andReturnNull()->byDefault();
        }
        $this->app->instance(TelegramService::class, $telegram);
        config(['services.telegram.backup_chat_id' => '']);

        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->superAdmin = User::factory()->create(['role_id' => $superRole->id]);

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $menu = Menu::create(['name' => 'Rekap', 'slug' => 'absensi.rekap', 'path' => '/absensi/rekap', 'order_no' => 1, 'is_active' => true]);
        foreach ([$adminRole, $superRole] as $role) {
            DB::table('role_menu')->insert([
                'role_id' => $role->id,
                'menu_id' => $menu->id,
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
            ]);
        }
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);

        $divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);
        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat',
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);
        $this->shift = Shift::create([
            'divisi_id' => $divisi->id,
            'nama' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '16:00:00',
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'is_cross_day' => false,
        ]);
        $this->pegawai = Pegawai::create([
            'user_id' => User::factory()->create()->id,
            'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id,
            'shift_id' => $this->shift->id,
            'nama_lengkap' => 'Pegawai Uji',
            'gender' => 'L',
            'status_aktif' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeAbsensi(array $override = []): Absensi
    {
        return Absensi::create(array_merge([
            'pegawai_id' => $this->pegawai->id,
            'shift_id' => $this->shift->id,
            'tanggal' => '2026-10-13',
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '16:00:00',
            'foto_masuk' => 'foto/masuk.jpg',
            'latitude_masuk' => '-5.1',
            'longitude_masuk' => '119.4',
            'lokasi_masuk' => 'Kantor',
            'device_masuk' => 'HP',
            'status' => 'Tepat Waktu',
        ], $override));
    }

    private function historyUrl(): string
    {
        return route('absensi.pegawai-history', $this->pegawai->id).'?bulan=10&tahun=2026';
    }

    public function test_super_admin_melihat_tombol_dan_modal_koreksi(): void
    {
        $absensi = $this->makeAbsensi();

        $response = $this->actingAs($this->superAdmin)->get($this->historyUrl());

        $response->assertOk();
        $response->assertSee('id="modalKoreksiAbsensi"', false);
        $response->assertSee('id="modalTambahAbsensi"', false);
        $response->assertSee('Tambah catatan absen');
        $response->assertSee('Koreksi');
        $response->assertSee(route('absensi.koreksi.update', $absensi->id), false);
        $response->assertSee(route('absensi.koreksi.store'), false);
    }

    public function test_admin_biasa_tidak_melihat_kontrol_koreksi(): void
    {
        $absensi = $this->makeAbsensi();

        $response = $this->actingAs($this->admin)->get($this->historyUrl());

        $response->assertOk();
        $response->assertDontSee('id="modalKoreksiAbsensi"', false);
        $response->assertDontSee('id="modalTambahAbsensi"', false);
        $response->assertDontSee('js-koreksi', false);
        $response->assertDontSee('Tambah catatan absen');
        $response->assertDontSee(route('absensi.koreksi.update', $absensi->id), false);
        $response->assertDontSee(route('absensi.koreksi.store'), false);
    }

    public function test_super_admin_melihat_badge_dengan_alasan_ter_escape(): void
    {
        $this->makeAbsensi([
            'dikoreksi_oleh' => $this->superAdmin->id,
            'alasan_koreksi' => '<script>alert(1)</script> data hilang',
        ]);

        $response = $this->actingAs($this->superAdmin)->get($this->historyUrl());

        $response->assertOk();
        $response->assertSee('Dikoreksi');
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; data hilang', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    private function makeKoreksiOlehPenyunting(): User
    {
        $corrector = User::factory()->create(['name' => 'Penyunting Unik Zeta']);
        $this->makeAbsensi([
            'dikoreksi_oleh' => $corrector->id,
            'alasan_koreksi' => 'ALASAN-RAHASIA-123',
        ]);

        return $corrector;
    }

    public function test_super_admin_melihat_nama_pengoreksi_dan_alasan(): void
    {
        $this->makeKoreksiOlehPenyunting();

        $response = $this->actingAs($this->superAdmin)->get($this->historyUrl());

        $response->assertOk();
        $response->assertSee('Dikoreksi');
        $response->assertSee('ALASAN-RAHASIA-123');
        $response->assertSee('Penyunting Unik Zeta');
    }

    public function test_admin_biasa_tidak_melihat_penanda_koreksi(): void
    {
        $this->makeKoreksiOlehPenyunting();

        $response = $this->actingAs($this->admin)->get($this->historyUrl());

        $response->assertOk();
        $response->assertDontSee('Dikoreksi');
        $response->assertDontSee('ALASAN-RAHASIA-123');
        $response->assertDontSee('Penyunting Unik Zeta');
    }

    public function test_pegawai_tidak_melihat_penanda_koreksi_di_riwayat_sendiri(): void
    {
        $this->makeKoreksiOlehPenyunting();

        $response = $this->actingAs($this->pegawai->user)->get(route('absensi.history').'?bulan=10&tahun=2026');

        $response->assertOk();
        $response->assertDontSee('Dikoreksi');
        $response->assertDontSee('ALASAN-RAHASIA-123');
        $response->assertDontSee('Penyunting Unik Zeta');
    }
}
