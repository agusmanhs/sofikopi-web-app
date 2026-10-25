<?php

namespace Tests\Feature;

use App\Models\Divisi;
use App\Models\Kantor;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\SettingService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Anggaran jumlah query per skenario di jalur yang dilewati ratusan user saat
 * jam absen. Kalau test ini gagal, ada perubahan yang menambah query di
 * jalur panas: cek dulu sebelum menaikkan angkanya.
 *
 * Set QUERY_BUDGET_REPORT=<path file> untuk menulis laporan jumlah query
 * dan statement yang paling sering berulang.
 */
class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private const BUDGET = [
        'login_guest' => 2,
        'absensi_index' => 15,
        'absen_masuk' => 18,
        'validate_location' => 4,
    ];

    private User $user;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));
        Storage::fake('public');

        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('notifyAbsenMasuk')->andReturnNull();
            $mock->shouldReceive('notifyAbsenPulang')->andReturnNull();
        });

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();

        $this->buildFixture();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function buildFixture(): void
    {
        $role = Role::create(['name' => 'Karyawan', 'slug' => 'karyawan']);

        foreach (range(1, 6) as $i) {
            $parent = Menu::create([
                'name' => "Grup {$i}", 'slug' => "grup-{$i}", 'path' => '#',
                'icon' => 'ri-home-line', 'order_no' => $i, 'is_active' => true,
            ]);
            $this->grant($role, $parent);

            foreach (range(1, 3) as $j) {
                $child = Menu::create([
                    'parent_id' => $parent->id, 'name' => "Menu {$i}.{$j}",
                    'slug' => "menu-{$i}-{$j}.index", 'path' => "/menu-{$i}-{$j}",
                    'order_no' => $j, 'is_active' => true,
                ]);
                $this->grant($role, $child);
            }
        }

        $divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);
        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat', 'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100, 'is_aktif' => true,
        ]);

        $this->shift = Shift::create([
            'divisi_id' => $divisi->id, 'nama' => 'Shift Pagi',
            'jam_masuk' => '11:00:00', 'jam_pulang' => '19:00:00',
            'is_aktif' => true, 'ikut_libur' => false, 'hari_kerja' => null, 'is_cross_day' => false,
        ]);

        $this->user = User::factory()->create(['role_id' => $role->id]);
        Pegawai::create([
            'user_id' => $this->user->id, 'divisi_id' => $divisi->id, 'kantor_id' => $kantor->id,
            'shift_id' => $this->shift->id, 'nama_lengkap' => 'Pegawai Uji', 'gender' => 'L',
            'status_aktif' => true,
        ]);
    }

    private function grant(Role $role, Menu $menu): void
    {
        DB::table('role_menu')->insert([
            'role_id' => $role->id, 'menu_id' => $menu->id,
            'can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true,
        ]);
    }

    private function measure(string $label, callable $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $this->report($label, $log);

        return count($log);
    }

    private function report(string $label, array $log): void
    {
        $path = getenv('QUERY_BUDGET_REPORT');
        if (! $path) {
            return;
        }

        $counts = [];
        foreach ($log as $entry) {
            $counts[$entry['query']] = ($counts[$entry['query']] ?? 0) + 1;
        }
        arsort($counts);

        $out = sprintf("== %s: %d query\n", $label, count($log));
        foreach (array_slice($counts, 0, 40, true) as $sql => $n) {
            $out .= sprintf("   %3dx %s\n", $n, mb_substr(preg_replace('/\s+/', ' ', $sql), 0, 150));
        }
        file_put_contents($path, $out, FILE_APPEND);
    }

    private function assertWithinBudget(string $key, int $count): void
    {
        $this->assertLessThanOrEqual(
            self::BUDGET[$key],
            $count,
            "Skenario {$key} memakai {$count} query, anggaran ".self::BUDGET[$key].'.'
        );
    }

    public function test_login_tamu(): void
    {
        $count = $this->measure('login_guest', fn () => $this->get('/login')->assertOk());

        $this->assertWithinBudget('login_guest', $count);
    }

    public function test_halaman_absensi(): void
    {
        $count = $this->measure('absensi_index', fn () => $this->actingAs($this->user)
            ->get(route('absensi.index', ['shift_id' => $this->shift->id]))
            ->assertOk());

        $this->assertWithinBudget('absensi_index', $count);
    }

    public function test_absen_masuk(): void
    {
        $count = $this->measure('absen_masuk', fn () => $this->actingAs($this->user)
            ->postJson(route('absensi.masuk'), [
                'foto' => UploadedFile::fake()->image('foto.jpg', 640, 480),
                'latitude' => -5.147665,
                'longitude' => 119.432732,
                'shift_id' => $this->shift->id,
            ])
            ->assertOk());

        $this->assertWithinBudget('absen_masuk', $count);
    }

    public function test_validate_location(): void
    {
        $count = $this->measure('validate_location', fn () => $this->actingAs($this->user)
            ->postJson(route('absensi.validate-location'), [
                'latitude' => -5.147665,
                'longitude' => 119.432732,
            ])
            ->assertOk());

        $this->assertWithinBudget('validate_location', $count);
    }
}
