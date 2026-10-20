<?php

namespace Tests\Feature\MitraPos;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Akun mitra hanya boleh memakai portal mitra-pos. Area internal Sofikopi
 * (data master semua mitra, absensi, penjualan, user, API) harus tertutup.
 */
class MitraAreaBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function internalAreas(): array
    {
        return [
            'master mitra (data semua mitra)' => ['/master/mitra'],
            'master produk' => ['/master/products'],
            'kategori produk' => ['/master/product-category'],
            'kategori mitra' => ['/master/mitra-category'],
            'produksi' => ['/aktivitas/produksi'],
            'kunjungan' => ['/aktivitas/kunjungan'],
            'izin' => ['/izin'],
            'absensi' => ['/absensi'],
            'pegawai' => ['/pegawai'],
            'user' => ['/user'],
            'role' => ['/role'],
            'penjualan sales order' => ['/penjualan/sales-order'],
            'penjualan kelola order' => ['/penjualan/kelola-order'],
            'penjualan delivery' => ['/penjualan/delivery-order'],
            'penjualan invoice' => ['/penjualan/invoice'],
            'activity log' => ['/activity-log'],
            'backup' => ['/backup'],
        ];
    }

    /**
     * @dataProvider internalAreas
     */
    public function test_mitra_kasir_and_owner_cannot_open_internal_areas(string $uri): void
    {
        foreach ([$this->kasir, $this->owner] as $user) {
            $this->actingAs($user)->get($uri, ['Accept' => 'application/json'])->assertForbidden();
        }
    }

    public function test_mitra_user_cannot_change_or_delete_master_mitra_rows(): void
    {
        $mitra = $this->owner->mitra;

        $this->actingAs($this->owner)->putJson("/master/mitra/{$mitra->getRouteKey()}", ['name' => 'Diubah'])->assertForbidden();
        $this->actingAs($this->owner)->deleteJson("/master/mitra/{$mitra->getRouteKey()}")->assertForbidden();
        $this->actingAs($this->owner)->postJson('/master/mitra/import')->assertForbidden();

        $this->assertDatabaseHas('mitras', ['id' => $mitra->id, 'name' => $mitra->name]);
    }

    public function test_api_users_list_is_closed_to_mitra_tokens(): void
    {
        $this->actingAs($this->kasir, 'sanctum')->getJson('/api/users')->assertForbidden();
        $this->actingAs($this->owner, 'sanctum')->getJson('/api/users/'.$this->kasir->id)->assertForbidden();
        $this->actingAs($this->kasir, 'sanctum')->getJson('/api/me')->assertForbidden();
    }

    public function test_mitra_user_cannot_obtain_an_api_token(): void
    {
        $this->postJson('/api/login', ['email' => 'kasir@cafelallo.test', 'password' => 'password123'])
            ->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_home_redirects_mitra_users_to_their_own_portal(): void
    {
        $this->actingAs($this->owner)->get('/')->assertRedirect(route('mitra-dashboard.index'));

        $kasirHome = $this->actingAs($this->kasir)->get('/');
        $kasirHome->assertRedirect();
        $this->assertStringContainsString('/mitra-pos', $kasirHome->headers->get('Location'));
    }

    public function test_mitra_users_can_still_use_their_own_profile_pages(): void
    {
        $this->actingAs($this->kasir)->get(route('profile.edit'))->assertOk();
        $this->actingAs($this->kasir)->get(route('profile.password.edit'))->assertOk();
    }

    public function test_internal_super_admin_is_not_affected(): void
    {
        $superAdmin = User::create([
            'name' => 'Admin',
            'email' => 'admin-boundary@internal.test',
            'password' => bcrypt('password'),
            'role_id' => Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin'])->id,
            'mitra_id' => null,
        ]);

        $this->actingAs($superAdmin)->get('/master/mitra', ['Accept' => 'application/json'])->assertOk();
        $this->actingAs($superAdmin)->get('/user', ['Accept' => 'application/json'])->assertOk();
    }

    public function test_users_of_a_deactivated_mitra_lose_pos_access(): void
    {
        $this->actingAs($this->kasir)->get(route('pos.index'))->assertOk();

        $this->owner->mitra->update(['is_active' => false]);
        // Permintaan sungguhan memuat ulang user; di test objeknya perlu disegarkan.
        $this->kasir = $this->kasir->fresh();
        $this->owner = $this->owner->fresh();

        $this->actingAs($this->kasir)->get(route('pos.index'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('mitra-dashboard.index'))->assertForbidden();
    }
}
