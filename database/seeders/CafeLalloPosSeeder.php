<?php

namespace Database\Seeders;

use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\MitraMaterial;
use App\Models\MitraPosSetting;
use App\Models\MitraProduct;
use App\Models\Products;
use App\Models\Role;
use App\Models\User;
use App\Services\MitraPos\MitraMaterialService;
use App\Services\MitraPos\MitraProductService;
use App\Services\MitraPos\MitraStockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Manual-run-only seeder. Loads the real "Cafe Lallo Kendari" acceptance
 * fixture (from the AKUNTANSI - CAFE LALLO KENDARI - SAMPLE workbook) into
 * the Mitra POS module: one Mitra, one kasir user, the PERSEDIAAN sheet's
 * raw materials (with opening stock), and the COGS sheet's menu products
 * (with BOM ingredients).
 *
 * NOT registered in DatabaseSeeder — run explicitly, and only AFTER
 * MitraPosMenuSeeder (which creates the 'mitra' role/menus this seeder's
 * kasir user needs), and never in production (guarded below):
 *
 *   php artisan db:seed --class=MitraPosMenuSeeder
 *   php artisan db:seed --class=CafeLalloPosSeeder
 *
 * Idempotent: safe to re-run. find-or-create semantics for the mitra/user/
 * materials/products; opening stock is only written the first time a
 * material row is created (guarded so re-running never doubles stock).
 */
class CafeLalloPosSeeder extends Seeder
{
    /**
     * PERSEDIAAN sheet. Opening stock for SHB021/SES003/SHB001 is the real
     * spreadsheet figure. The sheet does not give an explicit opening stock
     * for MLK007/PPC003/SKK001/ABM001 — those use a sensible non-zero
     * placeholder (documented as a deviation in the phase report).
     */
    private const MATERIALS = [
        'SHB021' => ['name' => 'LALLO BLEND 1000 GR', 'unit' => 'GR', 'netto' => 1000, 'price_per_pack' => 180000, 'opening' => 2200, 'min_stock' => 200],
        'SES003' => ['name' => 'BOOMBUAH SPRO 1000 GR', 'unit' => 'GR', 'netto' => 1000, 'price_per_pack' => 350000, 'opening' => 875, 'min_stock' => 100],
        'SHB001' => ['name' => 'SOFIA BLEND 1000 GR', 'unit' => 'GR', 'netto' => 1000, 'price_per_pack' => 250000, 'opening' => 0, 'min_stock' => 100],
        // Opening stock not given by the sheet for the four materials below — placeholders.
        'MLK007' => ['name' => 'OMELA SKM 490GR', 'unit' => 'GR', 'netto' => 490, 'price_per_pack' => 16000, 'opening' => 5000, 'min_stock' => 500],
        'PPC003' => ['name' => 'NON DAIRY CREAMER 800GR', 'unit' => 'GR', 'netto' => 800, 'price_per_pack' => 55000, 'opening' => 4000, 'min_stock' => 400],
        'SKK001' => ['name' => 'PLASTIC CUP TAKEAWAY', 'unit' => 'PCS', 'netto' => 1, 'price_per_pack' => 1500, 'opening' => 500, 'min_stock' => 50],
        'ABM001' => ['name' => 'PRISTINE WATER 400ML', 'unit' => 'BTL', 'netto' => 1, 'price_per_pack' => 4500, 'opening' => 100, 'min_stock' => 10],
    ];

    /**
     * Materials with a Sofikopi-supplied `products` row to link via product_id.
     */
    private const PRODUCT_LINKED_SKUS = ['SHB021', 'SES003', 'SHB001'];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('❌ CafeLalloPosSeeder is a manual dev/test fixture and must not run in production.');

            return;
        }

        $ownerRole = Role::where('slug', 'mitra-owner')->first();
        $kasirRole = Role::where('slug', 'mitra-kasir')->first();

        if (! $ownerRole || ! $kasirRole) {
            $this->command->error('❌ Roles "mitra-owner"/"mitra-kasir" not found. Run MitraPosMenuSeeder first: php artisan db:seed --class=MitraPosMenuSeeder');

            return;
        }

        DB::transaction(function () use ($ownerRole, $kasirRole) {
            $mitra = $this->seedMitra();
            $this->seedUsers($mitra, $ownerRole, $kasirRole);

            $materials = $this->seedMaterials($mitra);
            [$kopiSusu, $airMineral] = $this->seedProducts($mitra, $materials);

            $this->validateAgainstSheet($kopiSusu, $airMineral);

            $this->command->info('✅ Cafe Lallo Kendari mitra, materials, and products seeded successfully!');
            $this->command->info("   Kopi Susu Bapak Dingin: HPP={$kopiSusu->hpp} COGS={$kopiSusu->cogs} Margin={$kopiSusu->margin}");
            $this->command->info("   Air Mineral: HPP={$airMineral->hpp} COGS={$airMineral->cogs} Margin={$airMineral->margin}");
        });
    }

    private function seedMitra(): Mitra
    {
        $category = MitraCategory::firstOrCreate(
            ['name' => 'Cafe'],
            ['is_active' => true]
        );

        $mitra = Mitra::updateOrCreate(
            ['code' => 'CAFE-LALLO-KDI'],
            [
                'mitra_category_id' => $category->id,
                'pic' => 'Kasir Cafe Lallo',
                'name' => 'Cafe Lallo Kendari',
                'phone' => null,
                'address' => 'Kendari, Sulawesi Tenggara',
                'is_active' => true,
            ]
        );

        // Sample/test data implies enrollment — otherwise the mitra it just
        // seeded material/product data for wouldn't even show up on the
        // Kelola Mitra POS screen (index() only lists enrolled mitras).
        MitraPosSetting::firstOrCreate(['mitra_id' => $mitra->id]);

        return $mitra;
    }

    private function seedUsers(Mitra $mitra, Role $ownerRole, Role $kasirRole): void
    {
        User::updateOrCreate(
            ['email' => 'owner@cafelallo.test'],
            [
                'name' => 'Owner Cafe Lallo',
                'password' => Hash::make('password123'),
                'role_id' => $ownerRole->id,
                'mitra_id' => $mitra->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'kasir@cafelallo.test'],
            [
                'name' => 'Kasir Cafe Lallo',
                'password' => Hash::make('password123'),
                'role_id' => $kasirRole->id,
                'mitra_id' => $mitra->id,
            ]
        );
    }

    /**
     * @return array<string, MitraMaterial> keyed by sku
     */
    private function seedMaterials(Mitra $mitra): array
    {
        $materialService = app(MitraMaterialService::class);
        $stockService = app(MitraStockService::class);

        $materials = [];

        foreach (self::MATERIALS as $sku => $def) {
            $productId = in_array($sku, self::PRODUCT_LINKED_SKUS, true)
                ? Products::where('sku', $sku)->value('id')
                : null;

            $existing = MitraMaterial::forMitra($mitra->id)->where('sku', $sku)->first();

            if ($existing) {
                $existing->update([
                    'product_id' => $productId,
                    'name' => $def['name'],
                    'unit' => $def['unit'],
                    'netto' => $def['netto'],
                    'price_per_pack' => $def['price_per_pack'],
                    'min_stock' => $def['min_stock'],
                    'is_active' => true,
                ]);

                $materials[$sku] = $existing;

                continue;
            }

            $material = $materialService->createForMitra($mitra->id, [
                'product_id' => $productId,
                'sku' => $sku,
                'name' => $def['name'],
                'unit' => $def['unit'],
                'netto' => $def['netto'],
                'price_per_pack' => $def['price_per_pack'],
                'min_stock' => $def['min_stock'],
                'is_active' => true,
            ]);

            // Opening stock only on first creation — a re-run must never
            // double the stock for a material that already existed.
            if ($def['opening'] > 0) {
                $stockService->applyMovement(
                    mitraId: $mitra->id,
                    materialId: $material->id,
                    type: 'in',
                    qty: $def['opening'],
                    unitCost: $material->harga_satuan,
                    notes: 'Opening stock (Cafe Lallo Kendari seeder)',
                    reference: null,
                    userId: null,
                );
            }

            $materials[$sku] = $material->fresh();
        }

        return $materials;
    }

    /**
     * @param  array<string, MitraMaterial>  $materials
     * @return array{0: MitraProduct, 1: MitraProduct} [kopiSusu, airMineral]
     */
    private function seedProducts(Mitra $mitra, array $materials): array
    {
        $productService = app(MitraProductService::class);

        $kopiSusu = $this->createOrUpdateProduct($productService, $mitra->id, [
            'sku' => 'SLK011',
            'name' => 'KOPI SUSU BAPAK DINGIN',
            'category' => 'Kopi Susu',
            'q_factor' => 0.2,
            'sale_price' => 22000,
            'status' => 'active',
            'ingredients' => [
                ['mitra_material_id' => $materials['SHB021']->id, 'qty' => 18],
                ['mitra_material_id' => $materials['MLK007']->id, 'qty' => 40],
                ['mitra_material_id' => $materials['PPC003']->id, 'qty' => 20],
                ['mitra_material_id' => $materials['SKK001']->id, 'qty' => 1],
            ],
        ]);

        $airMineral = $this->createOrUpdateProduct($productService, $mitra->id, [
            'sku' => 'SLK048',
            'name' => 'AIR MINERAL',
            'category' => 'Minuman',
            'q_factor' => 0.2,
            'sale_price' => 6000,
            'status' => 'active',
            'ingredients' => [
                ['mitra_material_id' => $materials['ABM001']->id, 'qty' => 1],
            ],
        ]);

        return [
            MitraProduct::forMitra($mitra->id)->with('ingredients.material')->findOrFail($kopiSusu->id),
            MitraProduct::forMitra($mitra->id)->with('ingredients.material')->findOrFail($airMineral->id),
        ];
    }

    private function createOrUpdateProduct(MitraProductService $service, int $mitraId, array $data): MitraProduct
    {
        $existing = MitraProduct::forMitra($mitraId)->where('sku', $data['sku'])->first();

        if ($existing) {
            return $service->updateForMitra($mitraId, $existing->sku, $data);
        }

        return $service->createForMitra($mitraId, $data);
    }

    /**
     * Self-validating checks against the COGS sheet.
     *
     * Air Mineral is a single-ingredient product whose numbers reduce
     * cleanly (1 btl @ 4.500/btl), so it is asserted with exact equality.
     *
     * Kopi Susu Bapak Dingin's sheet total (HPP 7.600) was built from
     * per-line figures (3.300 + 1.400 + 1.400 + 1.500) that round more
     * aggressively than raw qty x harga_satuan gives us from the exact
     * PERSEDIAAN prices seeded above (~7.421,12) — a known Excel-vs-formula
     * rounding ambiguity flagged in the build plan itself. We do not fudge
     * material prices/qtys to force an exact match; we assert a tolerance
     * band instead, wide enough to accept that known deviation but narrow
     * enough to still catch a genuine computation bug.
     */
    private function validateAgainstSheet(MitraProduct $kopiSusu, MitraProduct $airMineral): void
    {
        if (abs((float) $airMineral->hpp - 4500) > 0.01 || (int) $airMineral->cogs !== 5400) {
            throw new \RuntimeException(
                "Air Mineral HPP/COGS mismatch: hpp={$airMineral->hpp} cogs={$airMineral->cogs} (expected hpp=4500 cogs=5400)"
            );
        }

        $hppDeviation = abs((float) $kopiSusu->hpp - 7600);
        if ($hppDeviation > 300) {
            throw new \RuntimeException(
                "Kopi Susu Bapak Dingin HPP too far from sheet target: hpp={$kopiSusu->hpp} (expected ~7.600, tolerance 300)"
            );
        }
    }
}
