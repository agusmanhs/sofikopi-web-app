<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\MitraProduct;
use App\Models\User;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementPageTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $kasir;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
    }

    public function test_owner_can_view_movement_history_filtered_by_material(): void
    {
        $lallo = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SHB021')->firstOrFail();
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        app(PosTransactionService::class)->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );

        $response = $this->actingAs($this->owner)
            ->get(route('mitra-stock.movements', ['material_id' => $lallo->id, 'type' => 'out']));

        $response->assertOk()
            ->assertSee($lallo->name)
            ->assertSee('Keluar')
            ->assertSee('Transaksi POS');
    }

    public function test_kasir_can_also_view_movement_history(): void
    {
        $this->actingAs($this->kasir)
            ->get(route('mitra-stock.movements'))
            ->assertOk();
    }
}
