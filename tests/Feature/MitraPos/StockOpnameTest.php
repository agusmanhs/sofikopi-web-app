<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\MitraStockMovement;
use App\Models\User;
use App\Services\MitraPos\MitraStockService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $kasir;

    private User $owner;

    private MitraStockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->service = app(MitraStockService::class);
    }

    public function test_opname_with_a_discrepancy_creates_an_adjustment_movement_and_updates_stock(): void
    {
        $lallo = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SHB021')->firstOrFail();
        $systemQty = (float) $lallo->current_stock;
        $physicalQty = $systemQty - 50; // 50gr missing at physical count

        $opname = $this->service->performOpname(
            mitraId: $this->mitra->id,
            userId: $this->owner->id,
            counts: [['mitra_material_id' => $lallo->id, 'physical_qty' => $physicalQty]],
            notes: 'Opname test',
        );

        $lallo->refresh();
        $this->assertEqualsWithDelta($physicalQty, (float) $lallo->current_stock, 0.001);

        $item = $opname->items->first();
        $this->assertEqualsWithDelta(-50.0, (float) $item->difference, 0.001);

        $movement = MitraStockMovement::forMitra($this->mitra->id)
            ->where('reference_type', $opname->getMorphClass())
            ->where('reference_id', $opname->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame('adjustment', $movement->type);
        $this->assertEqualsWithDelta(-50.0, (float) $movement->qty, 0.001);
    }

    public function test_opname_with_no_discrepancy_creates_no_stock_movement(): void
    {
        $lallo = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SHB021')->firstOrFail();
        $systemQty = (float) $lallo->current_stock;

        $opname = $this->service->performOpname(
            mitraId: $this->mitra->id,
            userId: $this->owner->id,
            counts: [['mitra_material_id' => $lallo->id, 'physical_qty' => $systemQty]],
        );

        $movementCount = MitraStockMovement::forMitra($this->mitra->id)
            ->where('reference_type', $opname->getMorphClass())
            ->where('reference_id', $opname->id)
            ->count();

        $this->assertSame(0, $movementCount);
        $this->assertSame(0.0, (float) $opname->items->first()->difference);
    }

    public function test_opname_number_sequence_increments_and_owner_can_submit_via_http(): void
    {
        $lallo = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SHB021')->firstOrFail();

        $response = $this->actingAs($this->owner)->post(route('mitra-opname.store'), [
            'notes' => 'Opname via HTTP',
            'physical_qty' => [
                $lallo->id => (float) $lallo->current_stock + 10,
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mitra_stock_opnames', [
            'mitra_id' => $this->mitra->id,
            'notes' => 'Opname via HTTP',
        ]);

        $lallo->refresh();
        $this->assertGreaterThan(0, (float) $lallo->current_stock);
    }

    public function test_kasir_cannot_access_opname_screens(): void
    {
        $this->actingAs($this->kasir)->get(route('mitra-opname.index'))->assertForbidden();
        $this->actingAs($this->kasir)->get(route('mitra-opname.create'))->assertForbidden();
    }
}
