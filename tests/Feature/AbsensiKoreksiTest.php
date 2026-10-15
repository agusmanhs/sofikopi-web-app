<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\ActivityLog;
use App\Models\Divisi;
use App\Models\Izin;
use App\Models\JenisIzin;
use App\Models\Kantor;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\AbsensiKoreksiService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AbsensiKoreksiTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $telegram;

    private User $superAdmin;

    private Pegawai $pegawai;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));

        $this->telegram = Mockery::mock(TelegramService::class);
        foreach (['notify', 'notifyAbsenMasuk', 'notifyAbsenPulang', 'notifyIzinCreated', 'notifyIzinStatus', 'notifyDeliveryCompleted'] as $method) {
            $this->telegram->shouldReceive($method)->andReturnNull()->byDefault();
        }
        $this->app->instance(TelegramService::class, $this->telegram);

        config(['services.telegram.backup_chat_id' => '']);

        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->superAdmin = User::factory()->create(['role_id' => $role->id]);

        $divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);
        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat',
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);
        $this->shift = $this->makeShift($divisi, false);
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

    private function makeShift(Divisi $divisi, bool $crossDay): Shift
    {
        return Shift::create([
            'divisi_id' => $divisi->id,
            'nama' => $crossDay ? 'Malam' : 'Pagi',
            'jam_masuk' => $crossDay ? '22:00:00' : '08:00:00',
            'jam_pulang' => $crossDay ? '06:00:00' : '16:00:00',
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => $crossDay,
        ]);
    }

    private function makeAbsensi(array $override = [], ?Shift $shift = null): Absensi
    {
        return Absensi::create(array_merge([
            'pegawai_id' => $this->pegawai->id,
            'shift_id' => ($shift ?? $this->shift)->id,
            'tanggal' => '2026-10-13',
            'jam_masuk' => '08:00:00',
            'jam_pulang' => null,
            'foto_masuk' => 'foto/masuk.jpg',
            'latitude_masuk' => '-5.1',
            'longitude_masuk' => '119.4',
            'lokasi_masuk' => 'Kantor',
            'device_masuk' => 'HP',
            'status' => 'Tepat Waktu',
        ], $override));
    }

    private function createPayload(array $override = []): array
    {
        return array_merge([
            'pegawai_id' => $this->pegawai->id,
            'shift_id' => $this->shift->id,
            'tanggal' => '2026-10-12',
            'jam_masuk' => '08:05',
            'jam_pulang' => '16:00:00',
            'status' => 'Terlambat',
            'keterangan' => 'Koreksi bug',
            'alasan' => 'Data hilang karena bug sistem',
        ], $override);
    }

    private function updatePayload(array $override = []): array
    {
        return array_merge([
            'jam_pulang' => '16:10',
            'alasan' => 'Pulang tidak tercatat karena bug',
        ], $override);
    }

    private function nonSuperAdmins(): array
    {
        $admin = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $menu = Menu::create(['name' => 'Rekap', 'slug' => 'absensi.rekap', 'path' => '/absensi/rekap', 'order_no' => 1, 'is_active' => true]);
        DB::table('role_menu')->insert([
            'role_id' => $admin->id,
            'menu_id' => $menu->id,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true,
        ]);

        return [
            User::factory()->create(['role_id' => $admin->id]),
            User::factory()->create(),
        ];
    }

    public function test_super_admin_dapat_mengoreksi_jam_pulang_dan_tercatat_audit()
    {
        $absensi = $this->makeAbsensi();

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $absensi->refresh();
        $this->assertSame('16:10:00', $absensi->jam_pulang->format('H:i:s'));
        $this->assertSame($this->superAdmin->id, $absensi->dikoreksi_oleh);
        $this->assertSame('Pulang tidak tercatat karena bug', $absensi->alasan_koreksi);

        $log = ActivityLog::where('action', 'koreksi')->where('module', 'absensi')->firstOrFail();
        $this->assertSame($absensi->id, $log->subject_id);
        $this->assertNull($log->old_values['jam_pulang']);
        $this->assertSame('16:10:00', $log->new_values['jam_pulang']);
        $this->assertSame('08:00:00', $log->old_values['jam_masuk']);
        $this->assertSame('Pulang tidak tercatat karena bug', $log->properties['alasan']);
    }

    public function test_super_admin_dapat_menambah_absensi_yang_hilang()
    {
        $this->actingAs($this->superAdmin)
            ->post(route('absensi.koreksi.store'), $this->createPayload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $row = Absensi::where('pegawai_id', $this->pegawai->id)->whereDate('tanggal', '2026-10-12')->firstOrFail();
        $this->assertSame('08:05:00', $row->jam_masuk->format('H:i:s'));
        $this->assertSame('Terlambat', $row->status);
        $this->assertSame($this->superAdmin->id, $row->dikoreksi_oleh);
        $this->assertNull($row->foto_masuk);
        $this->assertNull($row->latitude_masuk);
        $this->assertNull($row->lokasi_masuk);
        $this->assertNull($row->device_masuk);

        $log = ActivityLog::where('action', 'koreksi_tambah')->firstOrFail();
        $this->assertNull($log->old_values);
        $this->assertSame('Terlambat', $log->new_values['status']);
    }

    public function test_bukan_super_admin_mendapat_403_di_kedua_route()
    {
        $absensi = $this->makeAbsensi();

        foreach ($this->nonSuperAdmins() as $user) {
            $this->actingAs($user)
                ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload())
                ->assertForbidden();
            $this->actingAs($user)
                ->post(route('absensi.koreksi.store'), $this->createPayload())
                ->assertForbidden();
        }

        $this->assertNull($absensi->fresh()->jam_pulang);
        $this->assertSame(1, Absensi::count());
        $this->assertSame(0, ActivityLog::where('module', 'absensi')->count());
    }

    public function test_hrd_dengan_izin_rekap_dan_activity_log_mendapat_403_di_kedua_route()
    {
        $hrd = Role::create(['name' => 'HRD', 'slug' => 'hrd']);
        foreach ([['absensi.rekap', 'Rekap'], ['activity-log.index', 'Activity Log']] as $i => [$slug, $name]) {
            $menu = Menu::create(['name' => $name, 'slug' => $slug, 'path' => '/'.$slug, 'order_no' => $i + 1, 'is_active' => true]);
            DB::table('role_menu')->insert([
                'role_id' => $hrd->id,
                'menu_id' => $menu->id,
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
            ]);
        }
        $user = User::factory()->create(['role_id' => $hrd->id]);
        $absensi = $this->makeAbsensi();

        $this->actingAs($user)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload())
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('absensi.koreksi.store'), $this->createPayload())
            ->assertForbidden();

        $this->assertNull($absensi->fresh()->jam_pulang);
        $this->assertSame(1, Absensi::count());
        $this->assertSame(0, ActivityLog::where('module', 'absensi')->count());
    }

    public function test_alasan_wajib_dan_minimal_10_karakter()
    {
        $absensi = $this->makeAbsensi();

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), ['jam_pulang' => '16:00'])
            ->assertSessionHasErrors('alasan');
        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload(['alasan' => 'pendek']))
            ->assertSessionHasErrors('alasan');

        $this->assertNull($absensi->fresh()->jam_pulang);
    }

    public function test_tanggal_masa_depan_ditolak()
    {
        $this->actingAs($this->superAdmin)
            ->post(route('absensi.koreksi.store'), $this->createPayload(['tanggal' => '2026-10-15']))
            ->assertSessionHasErrors('tanggal');

        $this->assertSame(0, Absensi::count());
    }

    public function test_jam_pulang_sebelum_jam_masuk_ditolak_kecuali_shift_lintas_hari()
    {
        $absensi = $this->makeAbsensi();

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload(['jam_pulang' => '07:00']))
            ->assertSessionHas('error');
        $this->assertNull($absensi->fresh()->jam_pulang);

        $malam = $this->makeShift(Divisi::first(), true);
        $this->actingAs($this->superAdmin)
            ->post(route('absensi.koreksi.store'), $this->createPayload([
                'shift_id' => $malam->id,
                'jam_masuk' => '22:00',
                'jam_pulang' => '06:00',
                'status' => 'Tepat Waktu',
            ]))
            ->assertSessionHas('success');

        $this->assertSame(2, Absensi::count());
    }

    public function test_create_duplikat_ditolak()
    {
        $this->makeAbsensi(['tanggal' => '2026-10-12']);

        $this->actingAs($this->superAdmin)
            ->post(route('absensi.koreksi.store'), $this->createPayload())
            ->assertSessionHas('error');

        $this->assertSame(1, Absensi::count());
    }

    public function test_create_dengan_shift_divisi_lain_ditolak()
    {
        $lain = Divisi::create(['nama' => 'Lain', 'is_aktif' => true]);
        $shiftLain = $this->makeShift($lain, false);

        $this->actingAs($this->superAdmin)
            ->post(route('absensi.koreksi.store'), $this->createPayload(['shift_id' => $shiftLain->id]))
            ->assertSessionHas('error');

        $this->assertSame(0, Absensi::count());
    }

    public function test_izin_disetujui_pada_tanggal_tersebut_ditolak()
    {
        $jenis = JenisIzin::create(['nama' => 'Cuti', 'is_aktif' => true]);
        Izin::create([
            'pegawai_id' => $this->pegawai->id,
            'jenis_izin_id' => $jenis->id,
            'tgl_mulai' => '2026-10-11',
            'tgl_selesai' => '2026-10-13',
            'jumlah_hari' => 3,
            'alasan' => 'Cuti',
            'status_approval' => Izin::STATUS_APPROVED,
        ]);
        $absensi = $this->makeAbsensi();

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload())
            ->assertSessionHas('error');
        $this->actingAs($this->superAdmin)
            ->post(route('absensi.koreksi.store'), $this->createPayload(['tanggal' => '2026-10-12']))
            ->assertSessionHas('error');

        $this->assertNull($absensi->fresh()->jam_pulang);
        $this->assertSame(1, Absensi::count());
    }

    public function test_baris_bertipe_izin_tidak_dapat_diedit()
    {
        $absensi = $this->makeAbsensi(['status' => 'Sakit', 'jam_masuk' => null]);

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload())
            ->assertSessionHas('error');

        $this->assertNull($absensi->fresh()->jam_pulang);
    }

    public function test_foto_dan_lokasi_tidak_berubah()
    {
        $absensi = $this->makeAbsensi();

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload([
                'foto_masuk' => 'hack.jpg',
                'latitude_masuk' => '1',
                'lokasi_masuk' => 'Tempat lain',
                'device_masuk' => 'X',
            ]))
            ->assertSessionHas('success');

        $fresh = $absensi->fresh();
        $this->assertSame('foto/masuk.jpg', $fresh->foto_masuk);
        $this->assertEquals('-5.10000000', $fresh->latitude_masuk);
        $this->assertSame('Kantor', $fresh->lokasi_masuk);
        $this->assertSame('HP', $fresh->device_masuk);
    }

    public function test_telegram_tidak_dipanggil_saat_chat_id_kosong()
    {
        $this->telegram->shouldReceive('notify')->never();
        $absensi = $this->makeAbsensi();

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload())
            ->assertSessionHas('success');
    }

    public function test_telegram_dipanggil_sekali_dengan_chat_id_yang_diatur()
    {
        config(['services.telegram.backup_chat_id' => '-100123']);
        $this->telegram->shouldReceive('notify')
            ->once()
            ->withArgs(fn ($title, $fields, $icon, $photo, $chatId) => $title === 'KOREKSI ABSENSI'
                && $chatId === '-100123'
                && isset($fields['Oleh'], $fields['Pegawai'], $fields['Tanggal'], $fields['Aksi'], $fields['Sebelum'], $fields['Sesudah'], $fields['Alasan']));
        $absensi = $this->makeAbsensi();

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload())
            ->assertSessionHas('success');
    }

    public function test_exception_telegram_tidak_membatalkan_koreksi()
    {
        config(['services.telegram.backup_chat_id' => '-100123']);
        $this->telegram->shouldReceive('notify')->once()->andThrow(new \RuntimeException('down'));
        $absensi = $this->makeAbsensi();

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload())
            ->assertSessionHas('success');

        $this->assertSame('16:10:00', $absensi->fresh()->jam_pulang->format('H:i:s'));
        $this->assertSame(1, ActivityLog::where('action', 'koreksi')->count());
    }

    public function test_alasan_di_escape_untuk_telegram()
    {
        config(['services.telegram.backup_chat_id' => '-100123']);
        $alasan = 'Bug <b>besar</b> & "fatal"';
        $this->telegram->shouldReceive('notify')
            ->once()
            ->withArgs(fn ($title, $fields) => $fields['Alasan'] === htmlspecialchars($alasan, ENT_QUOTES)
                && ! str_contains($fields['Alasan'], '<b>'));
        $absensi = $this->makeAbsensi();

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload(['alasan' => $alasan]))
            ->assertSessionHas('success');
    }

    public function test_jam_masa_depan_hari_ini_ditolak_untuk_create_dan_update()
    {
        $this->actingAs($this->superAdmin)
            ->post(route('absensi.koreksi.store'), $this->createPayload(['tanggal' => '2026-10-14', 'jam_masuk' => '13:00', 'jam_pulang' => null]))
            ->assertSessionHas('error');
        $this->assertSame(0, Absensi::count());

        $absensi = $this->makeAbsensi(['tanggal' => '2026-10-14']);
        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload(['jam_pulang' => '13:00']))
            ->assertSessionHas('error');
        $this->assertNull($absensi->fresh()->jam_pulang);

        $this->actingAs($this->superAdmin)
            ->put(route('absensi.koreksi.update', $absensi), $this->updatePayload(['jam_pulang' => '11:00']))
            ->assertSessionHas('success');
    }

    public function test_create_ditolak_jika_ada_baris_lama_tanpa_shift()
    {
        $this->makeAbsensi(['tanggal' => '2026-10-12', 'shift_id' => null]);

        $this->actingAs($this->superAdmin)
            ->post(route('absensi.koreksi.store'), $this->createPayload())
            ->assertSessionHas('error');

        $this->assertSame(1, Absensi::count());
    }

    public function test_create_dengan_shift_nonaktif_ditolak()
    {
        $this->shift->update(['is_aktif' => false]);

        $this->actingAs($this->superAdmin)
            ->post(route('absensi.koreksi.store'), $this->createPayload())
            ->assertSessionHas('error');

        $this->assertSame(0, Absensi::count());
    }

    public function test_old_values_audit_berasal_dari_baris_terbaru()
    {
        config(['services.telegram.backup_chat_id' => '-100123']);
        $this->telegram->shouldReceive('notify')
            ->once()
            ->withArgs(fn ($title, $fields) => str_contains($fields['Sebelum'], 'Pulang 15:30:00'));
        $stale = $this->makeAbsensi();
        DB::table('absensis')->where('id', $stale->id)->update(['jam_pulang' => '15:30:00']);

        $result = app(AbsensiKoreksiService::class)
            ->ubah($stale, ['status' => 'Terlambat', 'alasan' => 'Koreksi dari data lama'], $this->superAdmin);

        $log = ActivityLog::where('action', 'koreksi')->firstOrFail();
        $this->assertSame('15:30:00', $log->old_values['jam_pulang']);
        $this->assertSame('Tepat Waktu', $log->old_values['status']);
        $this->assertSame('15:30:00', $log->new_values['jam_pulang']);
        $this->assertSame('Terlambat', $result->status);
    }
}
