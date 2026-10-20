<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\MitraProduct;
use App\Models\MitraStockMovement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengaman form Material & Produk: Q-Factor persen, resep tanpa duplikat,
 * nilai desimal database tidak berubah 100x, hapus material yang dipakai resep,
 * SKU terkunci, dan harga beli Rp 0 ditolak.
 */
class MaterialProductSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $admin;

    private MitraProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();

        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-safety@internal.test',
            'password' => bcrypt('password'),
            'role_id' => $superAdmin->id,
            'mitra_id' => null,
        ]);

        $this->product = MitraProduct::forMitra($this->mitra->id)->with('ingredients.material')->firstOrFail();
    }

    private function materials(int $count): array
    {
        return MitraMaterial::forMitra($this->mitra->id)->orderBy('id')->limit($count)->pluck('id')->all();
    }

    private function productPayload(array $overrides = []): array
    {
        [$materialId] = $this->materials(1);

        return array_merge([
            'sku' => 'SAFE-001',
            'name' => 'Produk Aman',
            'status' => 'active',
            'q_factor' => '20',
            'sale_price' => '22.000',
            'ingredients' => [['mitra_material_id' => $materialId, 'qty' => '10']],
        ], $overrides);
    }

    public function test_q_factor_typed_as_percent_is_stored_as_fraction_and_shown_back_as_percent(): void
    {
        $this->actingAs($this->admin)
            ->post(route('mitra-product.store', $this->mitra), $this->productPayload())
            ->assertSessionHasNoErrors();

        $created = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SAFE-001')->firstOrFail();
        $this->assertEqualsWithDelta(0.2, (float) $created->q_factor, 0.00001);

        $this->actingAs($this->admin)
            ->get(route('mitra-product.edit', [$this->mitra, $created]))
            ->assertOk()
            ->assertSee('name="q_factor" id="q_factor" class="form-control" value="20"', false);
    }

    public function test_q_factor_above_one_hundred_percent_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('mitra-product.store', $this->mitra), $this->productPayload(['q_factor' => '150']));

        $response->assertSessionHasErrors('q_factor');
        $this->assertNull(MitraProduct::forMitra($this->mitra->id)->where('sku', 'SAFE-001')->first());
    }

    public function test_duplicate_recipe_materials_are_rejected(): void
    {
        [$materialId] = $this->materials(1);

        $response = $this->actingAs($this->admin)
            ->post(route('mitra-product.store', $this->mitra), $this->productPayload([
                'ingredients' => [
                    ['mitra_material_id' => $materialId, 'qty' => '5'],
                    ['mitra_material_id' => $materialId, 'qty' => '7'],
                ],
            ]));

        $response->assertSessionHasErrors('ingredients.0.mitra_material_id');
        $this->assertNull(MitraProduct::forMitra($this->mitra->id)->where('sku', 'SAFE-001')->first());
    }

    public function test_database_decimal_sale_price_and_pack_price_round_trip_unchanged(): void
    {
        $ingredients = $this->product->ingredients
            ->map(fn ($i) => ['mitra_material_id' => $i->mitra_material_id, 'qty' => (float) $i->qty])
            ->all();

        $this->actingAs($this->admin)
            ->put(route('mitra-product.update', [$this->mitra, $this->product]), [
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'status' => 'active',
                'q_factor' => '20',
                'sale_price' => '6000.00',
                'ingredients' => $ingredients,
            ])
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(6000.0, (float) $this->product->fresh()->sale_price, 0.001);

        $material = MitraMaterial::forMitra($this->mitra->id)->firstOrFail();
        $this->actingAs($this->admin)
            ->put(route('mitra-material.update', [$this->mitra, $material]), [
                'sku' => $material->sku,
                'name' => $material->name,
                'unit' => $material->unit,
                'netto' => (float) $material->netto,
                'price_per_pack' => '6000.00',
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(6000.0, (float) $material->fresh()->price_per_pack, 0.001);
    }

    public function test_deleting_a_material_used_by_a_recipe_returns_an_error_and_keeps_the_row(): void
    {
        $ingredient = $this->product->ingredients->firstOrFail();
        $material = $ingredient->material;

        $response = $this->actingAs($this->admin)
            ->delete(route('mitra-material.destroy', [$this->mitra, $material]));

        $response->assertRedirect(route('mitra-material.index', $this->mitra));
        $response->assertSessionHas('error', fn ($message) => str_contains($message, 'Material dipakai di resep:')
            && str_contains($message, $this->product->name)
            && str_contains($message, 'Non-Aktif'));
        $this->assertNotNull(MitraMaterial::find($material->id));
    }

    public function test_deleting_an_unused_material_still_works(): void
    {
        $unused = MitraMaterial::create([
            'mitra_id' => $this->mitra->id,
            'sku' => 'UNUSED-001',
            'name' => 'Material Bebas',
            'unit' => 'GR',
            'netto' => 1000,
            'price_per_pack' => 10000,
            'current_stock' => 0,
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('mitra-material.destroy', [$this->mitra, $unused]))
            ->assertSessionHas('success');

        $this->assertNull(MitraMaterial::find($unused->id));
    }

    public function test_sku_change_on_product_edit_is_ignored(): void
    {
        $originalSku = $this->product->sku;
        $ingredients = $this->product->ingredients
            ->map(fn ($i) => ['mitra_material_id' => $i->mitra_material_id, 'qty' => (float) $i->qty])
            ->all();

        $this->actingAs($this->admin)
            ->put(route('mitra-product.update', [$this->mitra, $this->product]), [
                'sku' => 'SKU-BARU-123',
                'name' => 'Nama Baru',
                'status' => 'active',
                'q_factor' => '10',
                'sale_price' => '25.000',
                'ingredients' => $ingredients,
            ])
            ->assertSessionHasNoErrors();

        $fresh = $this->product->fresh();
        $this->assertSame($originalSku, $fresh->sku);
        $this->assertSame('Nama Baru', $fresh->name);
        $this->assertNull(MitraProduct::forMitra($this->mitra->id)->where('sku', 'SKU-BARU-123')->first());
    }

    public function test_purchase_with_zero_price_is_rejected_and_formatted_price_is_accepted(): void
    {
        $material = MitraMaterial::forMitra($this->mitra->id)->firstOrFail();
        $stockBefore = (float) $material->current_stock;

        $this->actingAs($this->admin)
            ->post(route('mitra-material.purchase', $this->mitra), [
                'material_id' => $material->id,
                'qty' => 5,
                'unit_price' => '0',
            ])
            ->assertSessionHasErrors('unit_price');

        $this->assertEqualsWithDelta($stockBefore, (float) $material->fresh()->current_stock, 0.001);

        $this->actingAs($this->admin)
            ->post(route('mitra-material.purchase', $this->mitra), [
                'material_id' => $material->id,
                'qty' => 5,
                'unit_price' => '1.500',
            ])
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta($stockBefore + 5, (float) $material->fresh()->current_stock, 0.001);
    }

    public function test_product_edit_page_shows_the_applies_from_now_on_notice(): void
    {
        $this->actingAs($this->admin)
            ->get(route('mitra-product.edit', [$this->mitra, $this->product]))
            ->assertOk()
            ->assertSee('Perubahan resep dan harga hanya berlaku untuk transaksi berikutnya. Transaksi yang sudah terjadi tidak berubah.');
    }

    public function test_fractional_purchase_price_is_kept_not_multiplied(): void
    {
        $material = MitraMaterial::forMitra($this->mitra->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('mitra-material.purchase', $this->mitra), [
                'material_id' => $material->id,
                'qty' => 10,
                'unit_price' => '18,5',
            ])
            ->assertSessionHasNoErrors();

        $movement = MitraStockMovement::where('mitra_material_id', $material->id)
            ->where('type', 'in')
            ->latest('id')
            ->firstOrFail();

        $this->assertEqualsWithDelta(18.5, (float) $movement->unit_cost, 0.001);
    }

    public function test_purchase_and_recipe_reject_a_soft_deleted_material(): void
    {
        $material = MitraMaterial::forMitra($this->mitra->id)->firstOrFail();
        $material->delete();

        $this->actingAs($this->admin)
            ->post(route('mitra-material.purchase', $this->mitra), [
                'material_id' => $material->id,
                'qty' => 1,
                'unit_price' => '1.500',
            ])
            ->assertSessionHasErrors('material_id');

        $this->actingAs($this->admin)
            ->post(route('mitra-product.store', $this->mitra), $this->productPayload([
                'ingredients' => [['mitra_material_id' => $material->id, 'qty' => '5']],
            ]))
            ->assertSessionHasErrors('ingredients.0.mitra_material_id');
    }

    public function test_saving_product_edit_with_prefilled_q_factor_leaves_it_unchanged(): void
    {
        $this->product->update(['q_factor' => 0.125]);

        $html = $this->actingAs($this->admin)
            ->get(route('mitra-product.edit', [$this->mitra, $this->product]))
            ->assertOk()
            ->getContent();
        preg_match('/name="q_factor"[^>]*value="([^"]*)"/', $html, $match);
        $this->assertSame('12.5', $match[1]);

        $ingredients = $this->product->ingredients
            ->map(fn ($i) => ['mitra_material_id' => $i->mitra_material_id, 'qty' => (float) $i->qty])
            ->values()
            ->all();

        $this->actingAs($this->admin)
            ->put(route('mitra-product.update', [$this->mitra, $this->product]), [
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'status' => 'active',
                'q_factor' => $match[1],
                'sale_price' => (string) $this->product->sale_price,
                'ingredients' => $ingredients,
            ])
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(0.125, (float) $this->product->fresh()->q_factor, 0.00001);
    }
}
