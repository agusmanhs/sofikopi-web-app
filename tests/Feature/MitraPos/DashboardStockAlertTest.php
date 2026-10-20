<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\Role;
use App\Models\User;
use App\Services\MitraPos\MitraDashboardService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Peringatan Stok Bahan Baku di dashboard: urut paling mendesak, hanya bahan
 * aktif, dibatasi 5 baris dengan ringkasan jumlah, dan tombol aksi yang sesuai
 * portal (admin: + Stok, pemilik mitra: Atur stok).
 */
class DashboardStockAlertTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $owner;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();

        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-alert@internal.test',
            'password' => bcrypt('password'),
            'role_id' => $superAdmin->id,
            'mitra_id' => null,
        ]);

        // Mulai dari kondisi bersih supaya hanya bahan di test ini yang bermasalah.
        MitraMaterial::forMitra($this->mitra->id)->update(['current_stock' => 1000, 'min_stock' => 10]);
    }

    private function material(string $sku, string $name, float $stock, float $min, bool $active = true): MitraMaterial
    {
        return MitraMaterial::create([
            'mitra_id' => $this->mitra->id,
            'sku' => $sku,
            'name' => $name,
            'category' => 'TEST',
            'unit' => 'GR',
            'netto' => 1000,
            'price_per_pack' => 100000,
            'current_stock' => $stock,
            'min_stock' => $min,
            'is_active' => $active,
        ]);
    }

    public function test_alerts_are_sorted_by_urgency_with_empty_first_and_by_ratio_not_raw_amount(): void
    {
        $this->material('ALR-A', 'Bahan Habis', 0, 500);
        $this->material('ALR-B', 'Bahan Hampir Habis', 50, 1000);   // 5%
        $this->material('ALR-C', 'Bahan Sedikit Kurang', 900, 1000); // 90%
        $this->material('ALR-D', 'Bahan Kecil Tapi Lumayan', 4, 5);  // 80%, angka mentah paling kecil

        $names = app(MitraDashboardService::class)->stockAlerts($this->mitra->id)->pluck('name')->all();

        $this->assertSame(
            ['Bahan Habis', 'Bahan Hampir Habis', 'Bahan Kecil Tapi Lumayan', 'Bahan Sedikit Kurang'],
            $names
        );
    }

    public function test_alert_carries_shortage_and_fill_percent(): void
    {
        $this->material('ALR-E', 'Bahan E', 1200, 4000);

        $alert = app(MitraDashboardService::class)->stockAlerts($this->mitra->id)->firstWhere('sku', 'ALR-E');

        $this->assertSame('yellow', $alert->alert_level);
        $this->assertEqualsWithDelta(2800.0, $alert->shortage, 0.001);
        $this->assertSame(30, $alert->fill_percent);
    }

    public function test_inactive_materials_are_not_alerted(): void
    {
        $this->material('ALR-F', 'Bahan Nonaktif', 0, 500, active: false);

        $skus = app(MitraDashboardService::class)->stockAlerts($this->mitra->id)->pluck('sku')->all();

        $this->assertNotContains('ALR-F', $skus);
    }

    public function test_dashboard_shows_only_top_five_with_counts_and_see_all_link(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->material("ALR-H{$i}", "Habis {$i}", 0, 100);
        }
        for ($i = 1; $i <= 4; $i++) {
            $this->material("ALR-M{$i}", "Menipis {$i}", 10 * $i, 100);
        }

        $response = $this->actingAs($this->owner)->get(route('mitra-dashboard.index'));

        $response->assertOk();
        $response->assertSee('4 habis');
        $response->assertSee('4 menipis');
        $response->assertSee('Lihat semua 8 bahan');
        $response->assertSee(route('mitra-stock.index', ['stok' => 'perlu']), false);
        $this->assertSame(5, $response->viewData('stockAlerts')->count());
    }

    public function test_empty_state_is_a_short_positive_message(): void
    {
        $response = $this->actingAs($this->owner)->get(route('mitra-dashboard.index'));

        $response->assertOk();
        $response->assertSee('Semua stok bahan baku dalam kondisi aman.');
        $response->assertDontSee('Lihat semua');
    }

    public function test_owner_row_button_goes_to_the_stock_page_for_that_material(): void
    {
        $this->material('ALR-O', 'Bahan Owner', 0, 100);

        $response = $this->actingAs($this->owner)->get(route('mitra-dashboard.index'));

        $response->assertSee('Atur stok');
        $response->assertSee(route('mitra-stock.index', ['q' => 'ALR-O']), false);
    }

    public function test_admin_row_button_opens_the_purchase_modal_for_that_material(): void
    {
        $material = $this->material('ALR-P', 'Bahan Admin', 0, 100);

        $dashboard = $this->actingAs($this->admin)->get(route('mitra-pos-manage.dashboard.index', $this->mitra));
        $dashboard->assertOk();
        $dashboard->assertSee('+ Stok');
        $dashboard->assertSee(route('mitra-material.index', ['mitra' => $this->mitra, 'beli' => 'ALR-P']), false);

        $page = $this->actingAs($this->admin)->get(route('mitra-material.index', ['mitra' => $this->mitra, 'beli' => 'ALR-P']));
        $page->assertOk();
        $this->assertSame($material->id, $page->viewData('openPurchaseId'));
    }

    public function test_purchase_preselect_ignores_unknown_or_inactive_sku(): void
    {
        $this->material('ALR-Q', 'Bahan Mati', 0, 100, active: false);

        $unknown = $this->actingAs($this->admin)->get(route('mitra-material.index', ['mitra' => $this->mitra, 'beli' => 'TIDAK-ADA']));
        $inactive = $this->actingAs($this->admin)->get(route('mitra-material.index', ['mitra' => $this->mitra, 'beli' => 'ALR-Q']));

        $this->assertNull($unknown->viewData('openPurchaseId'));
        $this->assertNull($inactive->viewData('openPurchaseId'));
    }

    public function test_stock_and_material_lists_filter_by_stock_status(): void
    {
        $this->material('ALR-R', 'Bahan Habis R', 0, 100);
        $this->material('ALR-S', 'Bahan Menipis S', 10, 100);
        $this->material('ALR-T', 'Bahan Aman T', 500, 100);

        $perlu = $this->actingAs($this->owner)->get(route('mitra-stock.index', ['stok' => 'perlu']));
        $perlu->assertSee('Bahan Habis R')->assertSee('Bahan Menipis S')->assertDontSee('Bahan Aman T');

        $habis = $this->actingAs($this->owner)->get(route('mitra-stock.index', ['stok' => 'habis']));
        $habis->assertSee('Bahan Habis R')->assertDontSee('Bahan Menipis S');

        $material = $this->actingAs($this->admin)->get(route('mitra-material.index', ['mitra' => $this->mitra, 'stok' => 'perlu']));
        // Pilihan modal + Stok memuat semua bahan, jadi periksa isi tabelnya.
        $listed = $material->viewData('materials')->pluck('name')->all();
        $this->assertContains('Bahan Habis R', $listed);
        $this->assertNotContains('Bahan Aman T', $listed);
    }
}
