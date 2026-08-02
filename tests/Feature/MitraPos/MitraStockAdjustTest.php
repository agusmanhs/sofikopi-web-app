<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the manual "Sesuaikan Stok" quick-adjust action
 * (MitraStockController::adjust -> MitraStockService::adjustStock). The
 * original bug: adjustStock() never passed unitCost, so applyMovement()
 * inserted an explicit NULL into mitra_stock_movements.unit_cost (NOT NULL,
 * default 0) — every manual adjustment 500'd with SQLSTATE[23000].
 */
class MitraStockAdjustTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private MitraMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->material = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SHB021')->firstOrFail();
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

    public function test_manual_adjustment_records_movement_with_material_unit_cost(): void
    {
        $admin = $this->makeSuperAdmin('admin-adjust@internal.test');

        $response = $this->actingAs($admin)->post(
            route('mitra-material.adjust', [$this->mitra, $this->material->sku]),
            ['delta' => 10, 'notes' => 'beli 10 stok']
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('mitra-material.index', $this->mitra));

        $this->assertDatabaseHas('mitra_stock_movements', [
            'mitra_id' => $this->mitra->id,
            'mitra_material_id' => $this->material->id,
            'type' => 'adjustment',
            'notes' => 'beli 10 stok',
            'user_id' => $admin->id,
        ]);

        $movement = $this->material->stockMovements()->where('type', 'adjustment')->latest('id')->firstOrFail();
        $this->assertNotNull($movement->unit_cost);
        $this->assertEqualsWithDelta((float) $this->material->harga_satuan, (float) $movement->unit_cost, 0.0001);
    }

    public function test_owner_can_adjust_stock_via_portal_route(): void
    {
        $owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $stockBefore = (float) $this->material->current_stock;

        $response = $this->actingAs($owner)->post(
            route('mitra-stock.adjust', $this->material->sku),
            ['delta' => 5, 'notes' => 'restock mingguan']
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('mitra-stock.index'));

        $this->assertEqualsWithDelta(
            $stockBefore + 5,
            (float) $this->material->fresh()->current_stock,
            0.0001
        );

        $movement = $this->material->stockMovements()->where('type', 'adjustment')->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta((float) $this->material->harga_satuan, (float) $movement->unit_cost, 0.0001);
    }

    public function test_kasir_cannot_adjust_stock_via_portal_route(): void
    {
        $kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();

        $this->actingAs($kasir)->post(
            route('mitra-stock.adjust', $this->material->sku),
            ['delta' => 5, 'notes' => 'coba-coba']
        )->assertForbidden();
    }

    public function test_negative_adjustment_updates_cached_stock_balance(): void
    {
        $admin = $this->makeSuperAdmin('admin-adjust-neg@internal.test');
        $stockBefore = (float) $this->material->current_stock;

        $this->actingAs($admin)->post(
            route('mitra-material.adjust', [$this->mitra, $this->material->sku]),
            ['delta' => -2, 'notes' => 'koreksi stok rusak']
        )->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(
            $stockBefore - 2,
            (float) $this->material->fresh()->current_stock,
            0.0001
        );
    }
}
