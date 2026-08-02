<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\MitraProduct;
use App\Models\MitraStockOpname;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers full read+write admin access for Sofikopi staff across the
 * remaining Mitra POS portal features (Dashboard, Kasir/POS, Stok Bahan,
 * Laporan Harian, Pengaturan, Stock Opname) via mitra-pos/manage/{mitra}/...
 * routes — extending the same pattern already verified for
 * Transaksi/Akuntansi in AkuntansiAdminAccessTest. The tenant-portal routes
 * (mitra.user middleware) 403 anyone with mitra_id === null regardless of
 * permission pivots — these admin routes are the actual access path for
 * super-admin/staff.
 */
class SuperAdminFullAccessTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
    }

    private function makeSuperAdmin(string $email): User
    {
        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);

        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => bcrypt('password'),
            'role_id' => $superAdmin->id,
            'mitra_id' => null,
        ]);
    }

    public function test_super_admin_can_view_dashboard(): void
    {
        $admin = $this->makeSuperAdmin('admin-dashboard@internal.test');

        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.dashboard.index', $this->mitra))
            ->assertOk();
    }

    public function test_super_admin_can_view_kasir_and_process_a_checkout(): void
    {
        $admin = $this->makeSuperAdmin('admin-pos@internal.test');
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.pos.index', $this->mitra))
            ->assertOk();

        $this->actingAs($admin)
            ->getJson(route('mitra-pos-manage.pos.products', $this->mitra))
            ->assertOk();

        $response = $this->actingAs($admin)->postJson(route('mitra-pos-manage.pos.store', $this->mitra), [
            'items' => [['mitra_product_id' => $product->id, 'qty' => 1]],
            'discount' => 0,
            'sales_mode' => 'dine_in',
            'payment_method' => 'cash',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('pos_transactions', [
            'mitra_id' => $this->mitra->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_super_admin_can_view_stock_index_and_movements(): void
    {
        $admin = $this->makeSuperAdmin('admin-stock@internal.test');

        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.stock.index', $this->mitra))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.stock.movements', $this->mitra))
            ->assertOk();
    }

    public function test_super_admin_can_view_daily_report_and_export(): void
    {
        $admin = $this->makeSuperAdmin('admin-report@internal.test');

        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.report.index', $this->mitra))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.report.export', $this->mitra))
            ->assertOk();
    }

    public function test_super_admin_can_view_and_update_settings(): void
    {
        $admin = $this->makeSuperAdmin('admin-setting@internal.test');

        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.setting.index', $this->mitra))
            ->assertOk();

        $response = $this->actingAs($admin)->put(route('mitra-pos-manage.setting.update', $this->mitra), [
            'monthly_revenue_target' => '20.000.000',
            'receipt_footer' => 'Diatur oleh admin',
            'service_charge_percent' => 5,
            'tax_percent' => 11,
            'qris_fee_percent' => 1,
            'transfer_fee_percent' => 0,
            'edc_fee_percent' => 0,
        ]);

        $response->assertRedirect(route('mitra-pos-manage.setting.index', $this->mitra));
        $this->assertDatabaseHas('mitra_pos_settings', [
            'mitra_id' => $this->mitra->id,
            'receipt_footer' => 'Diatur oleh admin',
        ]);
    }

    public function test_super_admin_can_view_create_submit_and_view_opname(): void
    {
        $admin = $this->makeSuperAdmin('admin-opname@internal.test');
        $material = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SHB021')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.opname.index', $this->mitra))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.opname.create', $this->mitra))
            ->assertOk();

        $response = $this->actingAs($admin)->post(route('mitra-pos-manage.opname.store', $this->mitra), [
            'notes' => 'Opname oleh admin',
            'physical_qty' => [$material->id => (float) $material->current_stock + 5],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mitra_stock_opnames', [
            'mitra_id' => $this->mitra->id,
            'user_id' => $admin->id,
            'notes' => 'Opname oleh admin',
        ]);

        $opnameNo = MitraStockOpname::where('mitra_id', $this->mitra->id)->latest('id')->firstOrFail()->opname_no;

        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.opname.show', [$this->mitra, $opnameNo]))
            ->assertOk();
    }

    public function test_super_admin_gets_403_on_tenant_portal_routes_for_all_six_features(): void
    {
        $admin = $this->makeSuperAdmin('admin-portal-403@internal.test');

        $this->actingAs($admin)->get(route('mitra-dashboard.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('pos.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('mitra-stock.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('mitra-report.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('mitra-setting.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('mitra-opname.index'))->assertForbidden();
    }
}
