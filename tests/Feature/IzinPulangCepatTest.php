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
 * Aturan izin hari ini:
 *  - Belum absen: izin ditutup N menit sebelum shift mulai (bawaan 10), setelah itu wajib absen dulu.
 *  - Sudah absen masuk: izin pulang cepat, absen masuk dan status Terlambat tidak ditimpa.
 * Waktu dibekukan pada 12:00.
 */
class IzinPulangCepatTest extends TestCase
{
    use RefreshDatabase;

    protected const KEY_MENIT = 'izin_tutup_menit_sebelum_shift';

    protected Divisi $divisi;

    protected Kantor $kantor;

    protected Pegawai $pegawai;

    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-14 12:00:00');

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('notifyIzinCreated')->andReturnNull();
        $telegram->shouldReceive('notifyIzinStatus')->andReturnNull();
        $this->app->instance(TelegramService::class, $telegram);

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();

        $this->divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);
        $this->kantor = Kantor::create([
            'nama' => 'Kantor Pusat',
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);
        $this->shift = $this->buatShift('08:00:00');
        $this->pegawai = $this->buatPegawai($this->shift);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function buatShift(string $jamMasuk): Shift
    {
        return Shift::create([
            'divisi_id' => $this->divisi->id,
            'nama' => 'Shift '.$jamMasuk,
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => '23:00:00',
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);
    }

    protected function buatPegawai(Shift $shift): Pegawai
    {
        return Pegawai::create([
            'user_id' => User::factory()->create()->id,
            'divisi_id' => $this->divisi->id,
            'kantor_id' => $this->kantor->id,
            'shift_id' => $shift->id,
            'nama_lengkap' => 'Pegawai Uji',
            'gender' => 'L',
            'status_aktif' => true,
        ]);
    }

    protected function absenMasuk(string $jamMasuk = '08:45:00', ?string $jamPulang = null, ?string $tanggal = null): Absensi
    {
        return Absensi::create([
            'pegawai_id' => $this->pegawai->id,
            'shift_id' => $this->shift->id,
            'tanggal' => $tanggal ?? today()->toDateString(),
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => $jamPulang,
            'status' => 'Terlambat',
        ]);
    }

    protected function ajukanSakit(?string $tglSelesai = null): Izin
    {
        $sakit = JenisIzin::firstOrCreate(['kode' => 'sakit'], [
            'nama' => 'Sakit',
            'butuh_surat' => false,
            'max_hari' => null,
            'is_aktif' => true,
        ]);

        return app(IzinService::class)->ajukanIzin($this->pegawai->id, [
            'jenis_izin_id' => $sakit->id,
            'tgl_mulai' => today()->toDateString(),
            'tgl_selesai' => $tglSelesai ?? today()->toDateString(),
            'alasan' => 'Demam mendadak',
        ]);
    }

    protected function setMenit(int $menit): void
    {
        app(SettingService::class)->setMany([self::KEY_MENIT => $menit]);
    }

    protected function pakaiShiftMulai(string $jamMasuk): void
    {
        $this->shift = $this->buatShift($jamMasuk);
        $this->pegawai->update(['shift_id' => $this->shift->id]);
    }

    // ---------- Belum absen: tutup N menit sebelum shift ----------

    public function test_izin_diterima_tepat_pada_batas_10_menit_sebelum_shift()
    {
        $this->pakaiShiftMulai('12:10:00');

        $this->assertNotNull($this->ajukanSakit()->id);
    }

    public function test_izin_ditolak_kurang_dari_10_menit_sebelum_shift()
    {
        $this->pakaiShiftMulai('12:09:00');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        $this->ajukanSakit();
    }

    public function test_izin_ditolak_detik_detik_terakhir_sebelum_shift()
    {
        $this->pakaiShiftMulai('12:01:00');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ditutup');

        $this->ajukanSakit();
    }

    public function test_pegawai_terlambat_belum_absen_ditolak_dan_diminta_absen_dulu()
    {
        // Shift 08:00, sekarang 12:00, belum absen.
        try {
            $this->ajukanSakit();
            $this->fail('Pengajuan seharusnya ditolak.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('sudah ditutup', $e->getMessage());
            $this->assertStringContainsString('absen masuk', $e->getMessage());
        }

        $this->assertSame(0, Izin::count());
    }

    public function test_batas_menit_mengikuti_setting_admin()
    {
        $this->pakaiShiftMulai('12:20:00');
        $this->setMenit(30); // tutup 11:50 -> sekarang 12:00 sudah lewat

        try {
            $this->ajukanSakit();
            $this->fail('Pengajuan seharusnya ditolak pada batas 30 menit.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('sudah ditutup', $e->getMessage());
        }

        $this->setMenit(0);
        $this->pakaiShiftMulai('12:00:00'); // tutup tepat saat shift mulai

        $this->assertNotNull($this->ajukanSakit()->id);
    }

    // ---------- Sudah absen: izin pulang cepat ----------

    public function test_sudah_absen_masuk_boleh_mengajukan_izin_walau_shift_sudah_lama_mulai()
    {
        $this->absenMasuk();

        $this->assertNotNull($this->ajukanSakit()->id);
    }

    public function test_sudah_absen_pulang_tidak_bisa_mengajukan_izin_hari_ini()
    {
        $this->absenMasuk('08:45:00', '15:00:00');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah absen pulang');

        $this->ajukanSakit();
    }

    public function test_approve_tidak_menimpa_absen_masuk_dan_mengisi_jam_pulang_dari_jam_pengajuan()
    {
        $absen = $this->absenMasuk();
        $izin = $this->ajukanSakit();

        app(IzinService::class)->approveIzin($izin->id);

        $absen->refresh();
        $this->assertSame('Terlambat', $absen->status);
        $this->assertSame('08:45:00', $absen->jam_masuk->format('H:i:s'));
        $this->assertSame('12:00:00', $absen->jam_pulang->format('H:i:s'));
        $this->assertStringContainsString('Pulang cepat', $absen->keterangan);
        $this->assertSame(Izin::STATUS_APPROVED, $izin->fresh()->status_approval);
    }

    public function test_approve_izin_beberapa_hari_hanya_hari_pengajuan_yang_dijadikan_pulang_cepat()
    {
        $absen = $this->absenMasuk();
        $izin = $this->ajukanSakit(today()->addDay()->toDateString());

        app(IzinService::class)->approveIzin($izin->id);

        $this->assertSame('Terlambat', $absen->fresh()->status);
        $besok = Absensi::where('pegawai_id', $this->pegawai->id)
            ->whereDate('tanggal', today()->addDay())
            ->first();
        $this->assertNotNull($besok);
        $this->assertSame('Sakit', $besok->status);
        $this->assertNull($besok->jam_masuk);
    }

    public function test_approve_tetap_ditolak_bila_pegawai_sudah_absen_pulang_setelah_mengajukan()
    {
        $absen = $this->absenMasuk();
        $izin = $this->ajukanSakit();
        $absen->update(['jam_pulang' => '13:00:00']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah absen');

        app(IzinService::class)->approveIzin($izin->id);
    }

    public function test_approve_tetap_ditolak_bila_ada_absen_pada_tanggal_lain_dalam_rentang_izin()
    {
        $this->absenMasuk();
        $izin = $this->ajukanSakit(today()->addDay()->toDateString());
        $this->absenMasuk('08:00:00', null, today()->addDay()->toDateString());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah absen masuk pada');

        app(IzinService::class)->approveIzin($izin->id);
    }

    // ---------- Tidak berubah ----------

    public function test_izin_untuk_besok_tidak_dibatasi()
    {
        $sakit = JenisIzin::create(['nama' => 'Sakit', 'kode' => 'sakit', 'butuh_surat' => false, 'max_hari' => null, 'is_aktif' => true]);

        $izin = app(IzinService::class)->ajukanIzin($this->pegawai->id, [
            'jenis_izin_id' => $sakit->id,
            'tgl_mulai' => today()->addDay()->toDateString(),
            'tgl_selesai' => today()->addDay()->toDateString(),
            'alasan' => 'Rencana kontrol',
        ]);

        $this->assertNotNull($izin->id);
    }

    public function test_pegawai_tanpa_shift_tetap_lolos()
    {
        $this->pegawai->update(['shift_id' => null]);

        $this->assertNotNull($this->ajukanSakit()->id);
    }

    // ---------- Pengaturan admin ----------

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

    protected function formPengaturan(array $override = []): array
    {
        $values = Setting::where('group', Setting::GROUP_ABSENSI)->pluck('key')
            ->mapWithKeys(fn ($key) => [$key => $key === self::KEY_MENIT ? 10 : 2])
            ->all();

        return ['settings' => array_merge($values, $override)];
    }

    public function test_admin_bisa_mengatur_menit_tutup_izin_termasuk_nol()
    {
        $admin = $this->adminPengaturan();

        $response = $this->actingAs($admin)->put(
            route('pengaturan-absensi.update'),
            $this->formPengaturan([self::KEY_MENIT => 0])
        );

        $response->assertRedirect(route('pengaturan-absensi.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame('0', (string) Setting::where('key', self::KEY_MENIT)->value('value'));
    }

    public function test_menit_tutup_izin_dibatasi_maksimal_120()
    {
        $admin = $this->adminPengaturan();

        $response = $this->actingAs($admin)->put(
            route('pengaturan-absensi.update'),
            $this->formPengaturan([self::KEY_MENIT => 121])
        );

        $response->assertSessionHasErrors('settings.'.self::KEY_MENIT);
    }

    public function test_batas_absen_masuk_tetap_tidak_boleh_nol()
    {
        $admin = $this->adminPengaturan();

        $response = $this->actingAs($admin)->put(
            route('pengaturan-absensi.update'),
            $this->formPengaturan([Setting::KEY_BATAS_ABSEN_MASUK_JAM => 0])
        );

        $response->assertSessionHasErrors('settings.'.Setting::KEY_BATAS_ABSEN_MASUK_JAM);
    }

    public function test_pengaturan_lama_batas_izin_jam_sudah_tidak_ada()
    {
        $this->assertNull(Setting::where('key', 'batas_izin_jam')->first());
    }
}
