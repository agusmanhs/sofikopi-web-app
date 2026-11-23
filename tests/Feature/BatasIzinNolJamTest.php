<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\Izin;
use App\Models\JenisIzin;
use App\Models\Kantor;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\IzinService;
use App\Services\SettingService;
use App\Services\TelegramService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Batas pengajuan izin 0 jam: izin untuk hari ini hanya boleh sampai jam masuk shift,
 * agar pegawai yang terlambat tidak bisa mengajukan izin/sakit untuk menghindari Alpha.
 */
class BatasIzinNolJamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-14 12:00:00');

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('notifyIzinCreated')->andReturnNull();
        $this->app->instance(TelegramService::class, $telegram);

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->setMany([Setting::KEY_BATAS_IZIN_JAM => 0]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function pegawaiDenganShiftMulai(string $jamMasuk): Pegawai
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
            'nama' => 'Shift Uji',
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => '20:00:00',
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);

        return Pegawai::create([
            'user_id' => User::factory()->create()->id,
            'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id,
            'shift_id' => $shift->id,
            'nama_lengkap' => 'Pegawai Uji',
            'gender' => 'L',
            'status_aktif' => true,
        ]);
    }

    protected function ajukanSakit(Pegawai $pegawai): Izin
    {
        $sakit = JenisIzin::firstOrCreate(['kode' => 'sakit'], [
            'nama' => 'Sakit',
            'butuh_surat' => false,
            'max_hari' => null,
            'is_aktif' => true,
        ]);

        return app(IzinService::class)->ajukanIzin($pegawai->id, [
            'jenis_izin_id' => $sakit->id,
            'tgl_mulai' => today()->toDateString(),
            'tgl_selesai' => today()->toDateString(),
            'alasan' => 'Uji',
        ]);
    }

    public function test_batas_nol_menolak_izin_satu_menit_setelah_shift_mulai()
    {
        // Shift mulai 11:59, sekarang 12:00, batas 0 jam -> sudah ditutup.
        $pegawai = $this->pegawaiDenganShiftMulai('11:59:00');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        $this->ajukanSakit($pegawai);
    }

    public function test_batas_nol_menerima_izin_tepat_saat_shift_mulai()
    {
        $pegawai = $this->pegawaiDenganShiftMulai('12:00:00');

        $this->assertNotNull($this->ajukanSakit($pegawai)->id);
    }

    public function test_batas_nol_menerima_izin_sebelum_shift_mulai()
    {
        $pegawai = $this->pegawaiDenganShiftMulai('13:00:00');

        $this->assertNotNull($this->ajukanSakit($pegawai)->id);
    }

    public function test_pegawai_terlambat_belum_absen_tidak_bisa_pura_pura_sakit()
    {
        // Bangun kesiangan: shift 08:00, sekarang 12:00, belum absen -> ditolak.
        $pegawai = $this->pegawaiDenganShiftMulai('08:00:00');

        try {
            $this->ajukanSakit($pegawai);
            $this->fail('Pengajuan seharusnya ditolak.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('sudah ditutup', $e->getMessage());
        }

        $this->assertSame(0, Izin::count());
        $this->assertSame(0, Absensi::count());
    }

    protected function adminPengaturan(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $menu = Menu::create([
            'name' => 'Pengaturan Absensi',
            'slug' => 'pengaturan-absensi.index',
            'path' => '/pengaturan-absensi',
            'order_no' => 1,
            'is_active' => true,
        ]);
        DB::table('role_menu')->insert([
            'role_id' => $role->id,
            'menu_id' => $menu->id,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_admin_bisa_menyimpan_batas_izin_nol_dari_halaman_pengaturan()
    {
        $admin = $this->adminPengaturan();

        $response = $this->actingAs($admin)->put(route('pengaturan-absensi.update'), [
            'settings' => [
                Setting::KEY_BATAS_ABSEN_DIBUKA_JAM => 2,
                Setting::KEY_BATAS_ABSEN_MASUK_JAM => 2,
                Setting::KEY_BATAS_ABSEN_PULANG_JAM => 2,
                Setting::KEY_BATAS_IZIN_JAM => 0,
            ],
        ]);

        $response->assertRedirect(route('pengaturan-absensi.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame(0, (int) Setting::where('key', Setting::KEY_BATAS_IZIN_JAM)->value('value'));
    }

    public function test_batas_absen_tetap_tidak_boleh_nol()
    {
        $admin = $this->adminPengaturan();

        $response = $this->actingAs($admin)->put(route('pengaturan-absensi.update'), [
            'settings' => [
                Setting::KEY_BATAS_ABSEN_DIBUKA_JAM => 2,
                Setting::KEY_BATAS_ABSEN_MASUK_JAM => 0,
                Setting::KEY_BATAS_ABSEN_PULANG_JAM => 2,
                Setting::KEY_BATAS_IZIN_JAM => 1,
            ],
        ]);

        $response->assertSessionHasErrors('settings.'.Setting::KEY_BATAS_ABSEN_MASUK_JAM);
    }
}
