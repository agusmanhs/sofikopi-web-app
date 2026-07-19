<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\MitraMaterial;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards two form-layer behaviors that unit-level service tests can't see:
 * 1. Updating a material WITHOUT changing its SKU must pass the unique rule
 *    (regression: slug routes made Rule::unique()->ignore() compare the SKU
 *    string against the id column, so every same-SKU update was rejected).
 * 2. Money inputs arrive Indonesian-formatted ("200.000") from the
 *    rupiah-input JS and must be normalized server-side before validation.
 */
class MaterialFormTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);

        $category = MitraCategory::firstOrCreate(['name' => 'Test Category'], ['is_active' => true]);
        $this->mitra = Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => 'MT-FORM',
            'name' => 'Mitra Form Test',
            'is_active' => true,
        ]);

        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-form@internal.test',
            'password' => bcrypt('password'),
            'role_id' => $superAdmin->id,
            'mitra_id' => null,
        ]);
    }

    private function makeMaterial(): MitraMaterial
    {
        return MitraMaterial::create([
            'mitra_id' => $this->mitra->id,
            'sku' => 'MAT-001',
            'name' => 'Material Satu',
            'unit' => 'GR',
            'netto' => 1000,
            'price_per_pack' => 180000,
            'current_stock' => 0,
            'min_stock' => 0,
            'is_active' => true,
        ]);
    }

    public function test_update_keeping_same_sku_passes_unique_rule_and_parses_formatted_price(): void
    {
        $material = $this->makeMaterial();

        $response = $this->actingAs($this->admin)->put(
            route('mitra-material.update', [$this->mitra, $material]),
            [
                'sku' => 'MAT-001', // unchanged — must NOT trip the unique rule
                'name' => 'Material Satu Update',
                'unit' => 'GR',
                'netto' => 1000,
                'price_per_pack' => '200.000', // Indonesian-formatted money input
                'min_stock' => 100,
                'is_active' => 1,
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('mitra-material.index', $this->mitra));

        $material->refresh();
        $this->assertSame('Material Satu Update', $material->name);
        $this->assertSame(200000.0, (float) $material->price_per_pack);
    }

    public function test_update_to_another_materials_sku_is_rejected(): void
    {
        $this->makeMaterial();
        $other = MitraMaterial::create([
            'mitra_id' => $this->mitra->id,
            'sku' => 'MAT-002',
            'name' => 'Material Dua',
            'unit' => 'GR',
            'netto' => 500,
            'price_per_pack' => 90000,
            'current_stock' => 0,
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(
            route('mitra-material.update', [$this->mitra, $other]),
            [
                'sku' => 'MAT-001', // collides with the first material
                'name' => 'Material Dua',
                'unit' => 'GR',
                'netto' => 500,
                'price_per_pack' => '90.000',
                'is_active' => 1,
            ]
        );

        $response->assertSessionHasErrors('sku');
    }
}
