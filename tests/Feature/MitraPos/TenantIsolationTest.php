<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\MitraMaterial;
use App\Models\MitraProduct;
use App\Models\PosTransaction;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hard tenant-isolation coverage per the Mitra POS build plan's
 * "Verification" section: global scope, forMitra() escape hatch,
 * ResolveMitraScope (admin 403), EnsureMitraUser (portal 403), and the
 * creating-hook auto-fill for mitra_id.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Role $mitraRole;
    private Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the real menus/role_menu pivots (not just a bare Role row) so
        // that check.permission middleware behaves exactly as it would in
        // production for a mitra user (e.g. read access to
        // pos-transaction.index), which several scenarios below depend on.
        $this->seed(MitraPosMenuSeeder::class);

        $this->mitraRole = Role::where('slug', 'mitra-owner')->firstOrFail();
        $this->superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
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

    private function makeMaterial(Mitra $mitra, string $sku): MitraMaterial
    {
        return MitraMaterial::create([
            'mitra_id' => $mitra->id,
            'sku' => $sku,
            'name' => "Material {$sku}",
            'unit' => 'GR',
            'netto' => 1000,
            'price_per_pack' => 100000,
            'current_stock' => 100,
            'min_stock' => 10,
            'is_active' => true,
        ]);
    }

    public function test_mitra_user_only_sees_own_materials_via_global_scope(): void
    {
        $mitraA = $this->makeMitra('MTA');
        $mitraB = $this->makeMitra('MTB');
        $userA = $this->makeMitraUser($mitraA, 'a@mitra.test');

        $this->makeMaterial($mitraA, 'A-MAT-1');
        $this->makeMaterial($mitraB, 'B-MAT-1');

        $this->actingAs($userA);

        $materials = MitraMaterial::all();

        $this->assertGreaterThan(0, $materials->count());
        $this->assertSame([$mitraA->id], $materials->pluck('mitra_id')->unique()->values()->all());
    }

    public function test_mitra_a_resolving_mitra_b_transaction_detail_returns_404(): void
    {
        $mitraA = $this->makeMitra('MTA');
        $mitraB = $this->makeMitra('MTB');
        $userA = $this->makeMitraUser($mitraA, 'a@mitra.test');
        $this->makeMitraUser($mitraB, 'b@mitra.test');

        $transactionB = PosTransaction::create([
            'mitra_id' => $mitraB->id,
            'transaction_no' => 'POS/MTB/20260101/0001',
            'sales_mode' => 'dine_in',
            'payment_method' => 'cash',
            'subtotal' => 10000,
            'discount' => 0,
            'grand_total' => 10000,
            'total_hpp' => 0,
            'total_cogs' => 0,
            'status' => 'completed',
            'transacted_at' => now(),
        ]);

        $this->actingAs($userA)
            ->get(route('pos-transaction.show', ['transaction' => $transactionB->transaction_no]))
            ->assertNotFound();
    }

    public function test_mitra_a_hitting_admin_manage_route_for_mitra_b_returns_403(): void
    {
        $mitraA = $this->makeMitra('MTA');
        $mitraB = $this->makeMitra('MTB');
        $userA = $this->makeMitraUser($mitraA, 'a@mitra.test');

        $this->actingAs($userA)
            ->get(route('mitra-material.index', ['mitra' => $mitraB->code]))
            ->assertForbidden();
    }

    public function test_admin_uses_for_mitra_explicitly_to_scope_each_mitra(): void
    {
        $mitraA = $this->makeMitra('MTA');
        $mitraB = $this->makeMitra('MTB');

        $this->makeMaterial($mitraA, 'A-MAT-1');
        $this->makeMaterial($mitraB, 'B-MAT-1');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@internal.test',
            'password' => bcrypt('password'),
            'role_id' => $this->superAdminRole->id,
            'mitra_id' => null,
        ]);

        $this->actingAs($admin);

        // Scope is inert for a null-mitra Auth: raw query returns both mitras' rows.
        $unscoped = MitraMaterial::all();
        $this->assertSame(2, $unscoped->pluck('mitra_id')->unique()->count());

        // forMitra() is the correct explicit mechanism for admin paths.
        $onlyA = MitraMaterial::forMitra($mitraA->id)->get();
        $this->assertSame([$mitraA->id], $onlyA->pluck('mitra_id')->unique()->values()->all());

        $onlyB = MitraMaterial::forMitra($mitraB->id)->get();
        $this->assertSame([$mitraB->id], $onlyB->pluck('mitra_id')->unique()->values()->all());
    }

    public function test_creating_as_mitra_user_auto_fills_mitra_id(): void
    {
        $mitraA = $this->makeMitra('MTA');
        $userA = $this->makeMitraUser($mitraA, 'a@mitra.test');

        $this->actingAs($userA);

        // Deliberately omit mitra_id — the creating hook must fill it in.
        $material = MitraMaterial::create([
            'sku' => 'AUTO-FILL-1',
            'name' => 'Auto Fill Material',
            'unit' => 'GR',
            'netto' => 1000,
            'price_per_pack' => 50000,
            'current_stock' => 0,
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $this->assertSame($userA->mitra_id, $material->mitra_id);
    }

    public function test_internal_user_without_mitra_id_and_without_mitra_role_is_forbidden_on_pos_index(): void
    {
        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);

        $internalUser = User::create([
            'name' => 'Internal Staff',
            'email' => 'staff@internal.test',
            'password' => bcrypt('password'),
            'role_id' => $staffRole->id,
            'mitra_id' => null,
        ]);

        $this->actingAs($internalUser)
            ->get(route('pos.index'))
            ->assertForbidden();
    }

    /**
     * Positive-path counterpart to the 404/403 slug tests above: the owning
     * mitra (and an admin acting on that mitra) must actually be able to
     * reach their own detail pages via the new SKU/transaction_no/code
     * slug URLs — not just get correctly denied on someone else's.
     */
    public function test_owning_mitra_and_admin_can_view_own_detail_pages_via_slug_urls(): void
    {
        $mitra = $this->makeMitra('MTA');
        $userA = $this->makeMitraUser($mitra, 'a@mitra.test');
        $material = $this->makeMaterial($mitra, 'A-MAT-1');
        $product = MitraProduct::create([
            'mitra_id' => $mitra->id,
            'sku' => 'A-PROD-1',
            'name' => 'Product A',
            'category' => 'Test',
            'q_factor' => 0.2,
            'sale_price' => 10000,
            'status' => 'active',
        ]);
        $transaction = PosTransaction::create([
            'mitra_id' => $mitra->id,
            'transaction_no' => 'POS/MTA/20260101/0001',
            'sales_mode' => 'dine_in',
            'payment_method' => 'cash',
            'subtotal' => 10000,
            'discount' => 0,
            'grand_total' => 10000,
            'total_hpp' => 0,
            'total_cogs' => 0,
            'status' => 'completed',
            'transacted_at' => now(),
        ]);

        // Mitra user viewing their own transaction via transaction_no.
        $this->actingAs($userA)
            ->get(route('pos-transaction.show', $transaction))
            ->assertOk();

        // Super-admin managing this mitra via its code, viewing material/product by sku.
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@internal.test',
            'password' => bcrypt('password'),
            'role_id' => $this->superAdminRole->id,
            'mitra_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('mitra-material.show', [$mitra, $material]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('mitra-product.show', [$mitra, $product]))
            ->assertOk();
    }

    /**
     * Role split: kasir gets the operational screens but must NOT see the
     * dashboard (profit figures); the '/' entry redirect must route each
     * mitra role to a page it is actually allowed to open.
     */
    public function test_kasir_role_is_forbidden_on_dashboard_and_redirected_to_pos(): void
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

        // Kasir can open the POS, but not the mitra dashboard.
        $this->actingAs($kasir)->get(route('pos.index'))->assertOk();
        $this->actingAs($kasir)->get(route('mitra-dashboard.index'))->assertForbidden();

        // '/' routes kasir to POS, owner to the dashboard.
        $this->actingAs($kasir)->get('/')->assertRedirect(route('pos.index'));

        $owner = $this->makeMitraUser($mitra, 'owner-a@mitra.test');
        $this->actingAs($owner)->get('/')->assertRedirect(route('mitra-dashboard.index'));
    }
}
