<?php

namespace Tests\Feature\MitraPos;

use App\Models\Menu;
use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Mitra-owner self-service coverage: an owner whose role has been granted
 * role_menu pivots on mitra-material.index / mitra-product.index (e.g. via
 * the Permission screen) can manage their OWN mitra's catalog through the
 * /mitra-pos/manage/{mitra}/... routes, while owners without the pivot stay
 * locked out and cross-tenant access remains forbidden. Also guards the
 * stock page's conditional "Kelola Material/Produk" nav buttons.
 */
class MitraOwnerSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private Role $mitraRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the real menus/role_menu pivots so check.permission middleware
        // behaves exactly as it would in production for mitra users.
        $this->seed(MitraPosMenuSeeder::class);

        $this->mitraRole = Role::where('slug', 'mitra-owner')->firstOrFail();
    }

    private function makeMitra(string $code): Mitra
    {
        $category = MitraCategory::firstOrCreate(['name' => 'Test Category'], ['is_active' => true]);

        return Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => $code,
            'name' => "Mitra {$code}",
            'is_active' => true,
        ]);
    }

    private function makeMitraUser(Mitra $mitra, string $email): User
    {
        return User::create([
            'name' => "User {$email}",
            'email' => $email,
            'password' => bcrypt('password'),
            'role_id' => $this->mitraRole->id,
            'mitra_id' => $mitra->id,
        ]);
    }

    /**
     * Grant the mitra-owner role a role_menu pivot on the given menu slug —
     * the same mechanism (updateOrInsert on role_id + menu_id) used by
     * MitraPosMenuSeeder and the Permission screen.
     */
    private function grantOwnerPivot(string $menuSlug, array $perms): void
    {
        $menu = Menu::where('slug', $menuSlug)->firstOrFail();

        DB::table('role_menu')->updateOrInsert(
            ['role_id' => $this->mitraRole->id, 'menu_id' => $menu->id],
            [
                'can_create' => $perms['create'] ?? false,
                'can_read' => $perms['read'] ?? false,
                'can_update' => $perms['update'] ?? false,
                'can_delete' => $perms['delete'] ?? false,
            ]
        );
    }

    public function test_should_allow_owner_to_open_own_material_index_when_read_pivot_granted(): void
    {
        $mitra = $this->makeMitra('MTA');
        $owner = $this->makeMitraUser($mitra, 'owner-a@mitra.test');

        $this->grantOwnerPivot('mitra-material.index', ['read' => true]);

        $this->actingAs($owner)
            ->get(route('mitra-material.index', $mitra))
            ->assertOk();
    }

    public function test_should_allow_owner_to_store_material_for_own_mitra_when_create_pivot_granted(): void
    {
        $mitra = $this->makeMitra('MTA');
        $owner = $this->makeMitraUser($mitra, 'owner-a@mitra.test');

        $this->grantOwnerPivot('mitra-material.index', ['create' => true, 'read' => true]);

        $response = $this->actingAs($owner)->post(
            route('mitra-material.store', $mitra),
            [
                'sku' => 'OWN-MAT-1',
                'name' => 'Material Milik Owner',
                'unit' => 'GR',
                'netto' => 1000,
                'price_per_pack' => '200.000', // Indonesian-formatted money input
                'min_stock' => 10,
                'is_active' => 1,
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('mitra-material.index', $mitra));

        $this->assertDatabaseHas('mitra_materials', [
            'sku' => 'OWN-MAT-1',
            'mitra_id' => $mitra->id,
        ]);
    }

    public function test_should_forbid_owner_on_own_material_index_when_no_pivot_granted(): void
    {
        $mitra = $this->makeMitra('MTA');
        $owner = $this->makeMitraUser($mitra, 'owner-a@mitra.test');

        $this->actingAs($owner)
            ->get(route('mitra-material.index', $mitra))
            ->assertForbidden();
    }

    public function test_should_forbid_owner_on_other_mitras_material_index_even_with_read_pivot(): void
    {
        $mitraA = $this->makeMitra('MTA');
        $otherMitra = $this->makeMitra('MTB');
        $owner = $this->makeMitraUser($mitraA, 'owner-a@mitra.test');

        $this->grantOwnerPivot('mitra-material.index', ['read' => true]);

        $this->actingAs($owner)
            ->get(route('mitra-material.index', ['mitra' => $otherMitra->code]))
            ->assertForbidden();
    }

    public function test_should_show_kelola_buttons_on_stock_page_only_when_owner_has_matching_pivot(): void
    {
        $mitra = $this->makeMitra('MTA');
        $owner = $this->makeMitraUser($mitra, 'owner-a@mitra.test');

        // Material pivot only — the "Kelola Produk" variant must stay hidden.
        $this->grantOwnerPivot('mitra-material.index', ['read' => true]);

        $this->actingAs($owner)
            ->get(route('mitra-stock.index'))
            ->assertOk()
            ->assertSee('Kelola Material')
            ->assertSee('mitra-pos/manage/'.$mitra->code.'/material', false)
            ->assertDontSee('Kelola Produk');
    }

    public function test_should_hide_kelola_buttons_and_show_read_only_note_when_kasir_opens_stock_page(): void
    {
        $kasirRole = Role::where('slug', 'mitra-kasir')->firstOrFail();
        $mitra = $this->makeMitra('MTA');

        $kasir = User::create([
            'name' => 'Kasir A',
            'email' => 'kasir-a@mitra.test',
            'password' => bcrypt('password'),
            'role_id' => $kasirRole->id,
            'mitra_id' => $mitra->id,
        ]);

        $this->actingAs($kasir)
            ->get(route('mitra-stock.index'))
            ->assertOk()
            ->assertDontSee('Kelola Material')
            ->assertDontSee('Kelola Produk')
            ->assertSee('hubungi admin Sofikopi');
    }
}
