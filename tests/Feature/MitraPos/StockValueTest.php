<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockValueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);
    }

    public function test_stock_value_accessor_equals_current_stock_times_harga_satuan(): void
    {
        $mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $lallo = MitraMaterial::forMitra($mitra->id)->where('sku', 'SHB021')->firstOrFail();

        $expected = (float) $lallo->current_stock * (float) $lallo->harga_satuan;

        $this->assertEqualsWithDelta($expected, (float) $lallo->stock_value, 0.01);
    }

    public function test_stock_page_shows_total_inventory_value(): void
    {
        $mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();

        $expectedTotal = MitraMaterial::forMitra($mitra->id)->get()->sum('stock_value');

        $this->actingAs($owner)
            ->get(route('mitra-stock.index'))
            ->assertOk()
            ->assertSee('Total Nilai Inventory')
            ->assertSee(number_format($expectedTotal, 0, ',', '.'));
    }
}
