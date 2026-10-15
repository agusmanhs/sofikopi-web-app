<?php

namespace Tests\Feature;

use App\Models\Divisi;
use App\Models\Kantor;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityLogAccessTest extends TestCase
{
    use RefreshDatabase;

    private Menu $menu;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));

        $this->menu = Menu::create([
            'name' => 'Activity Log',
            'slug' => 'activity-log.index',
            'path' => '/activity-log',
            'order_no' => 1,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeUser(string $slug, bool $withMenu): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);

        if ($withMenu) {
            DB::table('role_menu')->insert([
                'role_id' => $role->id,
                'menu_id' => $this->menu->id,
                'can_create' => false,
                'can_read' => true,
                'can_update' => false,
                'can_delete' => false,
            ]);
        }

        $user = User::factory()->create(['role_id' => $role->id]);
        $divisi = Divisi::firstOrCreate(['nama' => 'Operasional'], ['is_aktif' => true]);
        $kantor = Kantor::firstOrCreate(['nama' => 'Kantor Pusat'], [
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);
        Pegawai::create([
            'user_id' => $user->id,
            'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id,
            'nama_lengkap' => 'Pegawai '.$slug,
            'gender' => 'L',
            'status_aktif' => true,
        ]);

        return $user;
    }

    private function urls(): array
    {
        return [
            route('activity-log.index'),
            route('activity-log.data'),
            route('activity-log.statistics'),
        ];
    }

    public function test_user_biasa_tanpa_menu_mendapat_403_di_semua_route()
    {
        $user = $this->makeUser('user', false);

        foreach ($this->urls() as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_role_tanpa_baris_role_menu_mendapat_403()
    {
        $user = $this->makeUser('hrd', false);

        foreach ($this->urls() as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_hrd_dengan_izin_baca_mendapat_200()
    {
        $user = $this->makeUser('hrd', true);

        foreach ($this->urls() as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_admin_dengan_izin_baca_mendapat_200()
    {
        $user = $this->makeUser('admin', true);

        foreach ($this->urls() as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_super_admin_dengan_baris_role_menu_mendapat_200()
    {
        $user = $this->makeUser('super-admin', true);

        foreach ($this->urls() as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }
}
