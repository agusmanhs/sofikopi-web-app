<?php

namespace Tests\Feature;

use App\Models\Divisi;
use App\Models\Kantor;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\RoleAccessCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleAccessCacheTest extends TestCase
{
    use RefreshDatabase;

    private Role $role;

    private User $user;

    private int $shiftId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->role = Role::create(['name' => 'Karyawan', 'slug' => 'karyawan']);

        $parent = $this->makeMenu('Grup Satu', 'grup-satu', null);
        $this->makeMenu('Menu Anak Satu', 'anak-satu.index', $parent->id);

        $divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);
        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat', 'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100, 'is_aktif' => true,
        ]);
        $shift = Shift::create([
            'divisi_id' => $divisi->id, 'nama' => 'Shift Pagi', 'jam_masuk' => '08:00:00',
            'jam_pulang' => '16:00:00', 'is_aktif' => true, 'ikut_libur' => false,
            'hari_kerja' => null, 'is_cross_day' => false,
        ]);
        $this->shiftId = $shift->id;
        $this->user = User::factory()->create(['role_id' => $this->role->id]);
        Pegawai::create([
            'user_id' => $this->user->id, 'divisi_id' => $divisi->id, 'kantor_id' => $kantor->id,
            'shift_id' => $shift->id, 'nama_lengkap' => 'Pegawai Uji', 'gender' => 'L',
            'status_aktif' => true,
        ]);
    }

    private function makeMenu(string $name, string $slug, ?int $parentId, bool $canRead = true): Menu
    {
        $menu = Menu::create([
            'parent_id' => $parentId, 'name' => $name, 'slug' => $slug,
            'path' => $parentId ? "/{$slug}" : '#', 'icon' => 'ri-home-line',
            'order_no' => 1, 'is_active' => true,
        ]);

        DB::table('role_menu')->insert([
            'role_id' => $this->role->id, 'menu_id' => $menu->id,
            'can_create' => false, 'can_read' => $canRead, 'can_update' => false, 'can_delete' => false,
        ]);

        return $menu;
    }

    public function test_sidebar_dirender_dari_menu_role(): void
    {
        $this->actingAs($this->user)
            ->get(route('absensi.index', ['shift_id' => $this->shiftId]))
            ->assertOk()
            ->assertSee('Grup Satu')
            ->assertSee('Menu Anak Satu');
    }

    public function test_navbar_tetap_memakai_placeholder_menu_horizontal(): void
    {
        $this->actingAs($this->user)
            ->get(route('absensi.index', ['shift_id' => $this->shiftId]))
            ->assertOk()
            ->assertSee('me-xl-0 d-xl-none', false);
    }

    public function test_menu_baru_muncul_setelah_cache_dibersihkan_oleh_event_model(): void
    {
        $this->actingAs($this->user)->get(route('absensi.index', ['shift_id' => $this->shiftId]))->assertOk();

        $this->makeMenu('Menu Baru Muncul', 'menu-baru.index', Menu::where('slug', 'grup-satu')->value('id'));

        $this->actingAs($this->user)
            ->get(route('absensi.index', ['shift_id' => $this->shiftId]))
            ->assertSee('Menu Baru Muncul');
    }

    public function test_permission_di_cache_dan_flush_membacanya_ulang(): void
    {
        $cache = app(RoleAccessCache::class);

        $this->assertFalse($this->user->fresh()->hasPermission('anak-satu.index', 'update'));

        DB::table('role_menu')
            ->where('role_id', $this->role->id)
            ->update(['can_update' => true]);

        $this->assertFalse(
            $this->user->fresh()->hasPermission('anak-satu.index', 'update'),
            'Perubahan langsung di DB belum terbaca selama cache berlaku.'
        );

        $cache->flush();

        $this->assertTrue($this->user->fresh()->hasPermission('anak-satu.index', 'update'));
        $this->assertTrue($this->user->fresh()->hasPermission('anak-satu.index', 'read'));
        $this->assertFalse($this->user->fresh()->hasPermission('tidak-ada.index', 'read'));
    }

    public function test_user_tanpa_role_tidak_punya_izin(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->assertFalse($user->hasPermission('anak-satu.index', 'read'));
    }

    public function test_menu_tanpa_izin_baca_tidak_ditampilkan(): void
    {
        $this->makeMenu('Menu Tersembunyi', 'tersembunyi.index', Menu::where('slug', 'grup-satu')->value('id'), false);

        $this->actingAs($this->user)
            ->get(route('absensi.index', ['shift_id' => $this->shiftId]))
            ->assertOk()
            ->assertDontSee('Menu Tersembunyi');
    }
}
