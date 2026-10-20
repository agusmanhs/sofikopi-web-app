<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\MitraMaterial;
use App\Models\MitraPosSetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListPaginationLiveFilterTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
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

    private function addMaterials(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            MitraMaterial::create([
                'mitra_id' => $this->mitra->id,
                'sku' => sprintf('UJI%03d', $i),
                'name' => sprintf('Uji Paginasi %03d', $i),
                'unit' => 'gr',
                'netto' => 100,
                'price_per_pack' => 10000,
                'current_stock' => 10,
                'min_stock' => 1,
                'is_active' => true,
            ]);
        }
    }

    public function test_stock_list_is_paginated_at_15_rows(): void
    {
        $this->addMaterials(20);
        $total = MitraMaterial::forMitra($this->mitra->id)->count();

        $page1 = $this->actingAs($this->owner)->get(route('mitra-stock.index'));
        $page1->assertOk()
            ->assertSee('id="live-results"', false)
            ->assertSee('data-live-filter', false)
            ->assertSee("dari {$total} material");
        $this->assertCount(15, $page1->viewData('materials')->items());

        $page2 = $this->actingAs($this->owner)->get(route('mitra-stock.index', ['page' => 2]));
        $this->assertCount(min(15, $total - 15), $page2->viewData('materials')->items());
    }

    public function test_stock_search_narrows_the_list(): void
    {
        $this->addMaterials(20);

        $response = $this->actingAs($this->owner)->get(route('mitra-stock.index', ['q' => 'Uji Paginasi 007']));

        $response->assertOk();
        $items = $response->viewData('materials')->items();
        $this->assertCount(1, $items);
        $this->assertSame('UJI007', $items[0]->sku);

        $bySku = $this->actingAs($this->owner)->get(route('mitra-stock.index', ['q' => 'UJI01']));
        $this->assertCount(10, $bySku->viewData('materials')->items());
    }

    public function test_inventory_total_covers_all_materials_regardless_of_page_or_search(): void
    {
        $this->addMaterials(20);
        $expected = MitraMaterial::forMitra($this->mitra->id)->where('is_active', true)->get()->sum('stock_value');

        foreach ([[], ['page' => 2], ['q' => 'Uji Paginasi 007']] as $query) {
            $this->actingAs($this->owner)
                ->get(route('mitra-stock.index', $query))
                ->assertOk()
                ->assertSee('Total Nilai Inventory')
                ->assertSee('Rp '.number_format($expected, 0, ',', '.'));
        }
    }

    public function test_movements_page_uses_live_filter_and_select2(): void
    {
        $this->actingAs($this->owner)
            ->get(route('mitra-stock.movements'))
            ->assertOk()
            ->assertSee('data-live-filter', false)
            ->assertSee('id="live-results"', false)
            ->assertSee('js-select2', false);
    }

    public function test_manage_picker_search_and_pagination_for_super_admin(): void
    {
        $admin = $this->makeSuperAdmin('admin-picker-live@internal.test');
        $category = MitraCategory::firstOrCreate(['name' => 'Test'], ['is_active' => true]);

        for ($i = 1; $i <= 14; $i++) {
            $mitra = Mitra::create([
                'mitra_category_id' => $category->id,
                'code' => sprintf('MT-PG%02d', $i),
                'name' => sprintf('Mitra Paginasi %02d', $i),
                'is_active' => true,
            ]);
            MitraPosSetting::firstOrCreate(['mitra_id' => $mitra->id]);
        }

        $page1 = $this->actingAs($admin)->get(route('mitra-pos-manage.index'));
        $page1->assertOk()
            ->assertSee('data-live-filter', false)
            ->assertSee('id="live-results"', false)
            ->assertSee('dari 15 mitra');
        $this->assertCount(12, $page1->viewData('mitras')->items());

        $page2 = $this->actingAs($admin)->get(route('mitra-pos-manage.index', ['page' => 2]));
        $this->assertCount(3, $page2->viewData('mitras')->items());

        $byName = $this->actingAs($admin)->get(route('mitra-pos-manage.index', ['q' => 'Paginasi 05']));
        $this->assertCount(1, $byName->viewData('mitras')->items());

        $byCode = $this->actingAs($admin)->get(route('mitra-pos-manage.index', ['q' => 'CAFE-LALLO']));
        $this->assertCount(1, $byCode->viewData('mitras')->items());

        $none = $this->actingAs($admin)->get(route('mitra-pos-manage.index', ['q' => 'tidak-ada-sama-sekali']));
        $none->assertOk()->assertSee('Tidak ada mitra yang cocok');
    }
}
