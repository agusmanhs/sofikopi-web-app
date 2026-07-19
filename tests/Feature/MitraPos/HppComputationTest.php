<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\MitraMaterial;
use App\Models\MitraProduct;
use App\Models\MitraProductIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-level tests for the MitraProduct::hpp/cogs/margin accessors and the
 * MitraMaterial::harga_satuan accessor. No Auth/tenancy involved here (see
 * TenantIsolationTest for that) — every row is created with an explicit
 * mitra_id since BelongsToMitra's creating hook is a no-op without an
 * authenticated mitra user.
 */
class HppComputationTest extends TestCase
{
    use RefreshDatabase;

    private function makeMitra(): Mitra
    {
        $category = MitraCategory::create(['name' => 'Test Category', 'is_active' => true]);

        return Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => 'TEST-MITRA-' . uniqid(),
            'name' => 'Test Mitra',
            'is_active' => true,
        ]);
    }

    /**
     * Air Mineral case: single ingredient, clean round numbers.
     * 1 btl @ harga_satuan 4.500 -> hpp 4.500; q_factor 0.2 -> cogs 5.400;
     * sale_price 6.000 -> margin 600.
     */
    public function test_single_ingredient_hpp_cogs_and_margin_match_hand_computed_values(): void
    {
        $mitra = $this->makeMitra();

        $material = MitraMaterial::create([
            'mitra_id' => $mitra->id,
            'sku' => 'ABM001',
            'name' => 'PRISTINE WATER 400ML',
            'unit' => 'BTL',
            'netto' => 1,
            'price_per_pack' => 4500,
            'current_stock' => 0,
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $product = MitraProduct::create([
            'mitra_id' => $mitra->id,
            'sku' => 'SLK048',
            'name' => 'AIR MINERAL',
            'q_factor' => 0.2,
            'sale_price' => 6000,
            'status' => 'active',
        ]);

        MitraProductIngredient::create([
            'mitra_product_id' => $product->id,
            'mitra_material_id' => $material->id,
            'qty' => 1,
        ]);

        $product = MitraProduct::with('ingredients.material')->findOrFail($product->id);

        $this->assertEqualsWithDelta(4500.0, (float) $product->hpp, 0.01);
        $this->assertSame(5400, (int) $product->cogs);
        $this->assertSame(600, (int) $product->margin);
    }

    /**
     * harga_satuan for a non-1000-netto pack: price_per_pack 16.000 / netto
     * 490 gr = 32,6530612245.../gr — not a round number, so assert with a
     * small float delta rather than exact equality.
     */
    public function test_harga_satuan_for_non_1000_netto_material(): void
    {
        $mitra = $this->makeMitra();

        $material = MitraMaterial::create([
            'mitra_id' => $mitra->id,
            'sku' => 'MLK007',
            'name' => 'OMELA SKM 490GR',
            'unit' => 'GR',
            'netto' => 490,
            'price_per_pack' => 16000,
            'current_stock' => 0,
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $expected = 16000 / 490; // 32.653061224489796

        $this->assertEqualsWithDelta($expected, (float) $material->harga_satuan, 0.01);
    }

    /**
     * Multi-ingredient product (Kopi-Susu-Bapak-Dingin-shaped BOM): hpp must
     * equal the plain sum of qty x harga_satuan across all ingredients.
     * Expected value is hand-computed from the exact fixture prices set up
     * below — deliberately NOT the Excel sheet's idealized 7.600 figure
     * (that ambiguity is documented and tolerance-banded only in the
     * CafeLalloPosSeeder, not in this deterministic accessor test).
     */
    public function test_multi_ingredient_hpp_is_sum_of_qty_times_harga_satuan(): void
    {
        $mitra = $this->makeMitra();

        $shb021 = MitraMaterial::create([
            'mitra_id' => $mitra->id, 'sku' => 'SHB021', 'name' => 'LALLO BLEND 1000 GR',
            'unit' => 'GR', 'netto' => 1000, 'price_per_pack' => 180000,
            'current_stock' => 0, 'min_stock' => 0, 'is_active' => true,
        ]);
        $mlk007 = MitraMaterial::create([
            'mitra_id' => $mitra->id, 'sku' => 'MLK007', 'name' => 'OMELA SKM 490GR',
            'unit' => 'GR', 'netto' => 490, 'price_per_pack' => 16000,
            'current_stock' => 0, 'min_stock' => 0, 'is_active' => true,
        ]);
        $ppc003 = MitraMaterial::create([
            'mitra_id' => $mitra->id, 'sku' => 'PPC003', 'name' => 'NON DAIRY CREAMER 800GR',
            'unit' => 'GR', 'netto' => 800, 'price_per_pack' => 55000,
            'current_stock' => 0, 'min_stock' => 0, 'is_active' => true,
        ]);
        $skk001 = MitraMaterial::create([
            'mitra_id' => $mitra->id, 'sku' => 'SKK001', 'name' => 'PLASTIC CUP TAKEAWAY',
            'unit' => 'PCS', 'netto' => 1, 'price_per_pack' => 1500,
            'current_stock' => 0, 'min_stock' => 0, 'is_active' => true,
        ]);

        $product = MitraProduct::create([
            'mitra_id' => $mitra->id,
            'sku' => 'SLK011',
            'name' => 'KOPI SUSU BAPAK DINGIN',
            'q_factor' => 0.2,
            'sale_price' => 22000,
            'status' => 'active',
        ]);

        MitraProductIngredient::create(['mitra_product_id' => $product->id, 'mitra_material_id' => $shb021->id, 'qty' => 18]);
        MitraProductIngredient::create(['mitra_product_id' => $product->id, 'mitra_material_id' => $mlk007->id, 'qty' => 40]);
        MitraProductIngredient::create(['mitra_product_id' => $product->id, 'mitra_material_id' => $ppc003->id, 'qty' => 20]);
        MitraProductIngredient::create(['mitra_product_id' => $product->id, 'mitra_material_id' => $skk001->id, 'qty' => 1]);

        $product = MitraProduct::with('ingredients.material')->findOrFail($product->id);

        $expectedHpp = (18 * (180000 / 1000))
            + (40 * (16000 / 490))
            + (20 * (55000 / 800))
            + (1 * (1500 / 1));

        $expectedCogs = (int) round($expectedHpp * 1.2);

        $this->assertEqualsWithDelta($expectedHpp, (float) $product->hpp, 0.01);
        $this->assertSame($expectedCogs, (int) $product->cogs);
        $this->assertSame(22000 - $expectedCogs, (int) $product->margin);
    }
}
