<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\MitraProduct;
use App\Models\MitraStockMovement;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\MitraPos\MitraProductService;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Exercises the real checkout algorithm (PosTransactionService::checkout)
 * against the real Cafe Lallo Kendari fixture data, loaded via the actual
 * seeders (cleanest way to get realistic numbers matching the plan's
 * assertions — see CafeLalloPosSeeder for the acceptance-fixture figures).
 */
class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;
    private User $kasir;
    private PosTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $this->service = app(PosTransactionService::class);
    }

    private function kopiSusu(): MitraProduct
    {
        return MitraProduct::forMitra($this->mitra->id)
            ->where('sku', 'SLK011')
            ->with('ingredients.material')
            ->firstOrFail();
    }

    private function checkoutKopiSusu(float $qty): array
    {
        return $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $this->kopiSusu()->id, 'qty' => $qty]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );
    }

    public function test_checkout_two_kopi_susu_totals_match_product_snapshot(): void
    {
        $product = $this->kopiSusu();
        // Compute expected values from the product's own hpp/cogs at test
        // time — do not hardcode the sheet's idealized 7.600/9.120 numbers,
        // since the seeder's real computed HPP may differ (see its
        // tolerance-band deviation note).
        $expectedHpp = 2 * (float) $product->hpp;
        $expectedCogs = 2 * (float) $product->cogs;

        $result = $this->checkoutKopiSusu(2);
        $transaction = $result['transaction'];

        $this->assertEqualsWithDelta(44000.0, (float) $transaction->grand_total, 0.01);
        $this->assertEqualsWithDelta($expectedHpp, (float) $transaction->total_hpp, 0.01);
        $this->assertEqualsWithDelta($expectedCogs, (float) $transaction->total_cogs, 0.01);
    }

    public function test_checkout_creates_one_out_movement_per_distinct_ingredient_material(): void
    {
        $result = $this->checkoutKopiSusu(2);
        $transaction = $result['transaction'];

        $movements = MitraStockMovement::forMitra($this->mitra->id)
            ->where('type', 'out')
            ->where('reference_id', $transaction->id)
            ->where('reference_type', $transaction->getMorphClass())
            ->get();

        $this->assertCount(4, $movements);

        $movedSkus = $movements->pluck('mitra_material_id')
            ->map(fn ($id) => MitraMaterial::find($id)->sku)
            ->sort()
            ->values()
            ->all();

        $expectedSkus = ['MLK007', 'PPC003', 'SHB021', 'SKK001'];
        sort($expectedSkus);

        $this->assertSame($expectedSkus, $movedSkus);
    }

    public function test_checkout_decrements_lallo_stock_by_exact_bom_qty_and_balance_after_matches_current_stock(): void
    {
        $lallo = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SHB021')->firstOrFail();
        $openingStock = (float) $lallo->current_stock; // 2.200 gr per seeder

        $result = $this->checkoutKopiSusu(2); // 2 x 18gr SHB021 = 36gr

        $lallo->refresh();

        $this->assertEqualsWithDelta($openingStock - 36, (float) $lallo->current_stock, 0.001);

        $movement = MitraStockMovement::forMitra($this->mitra->id)
            ->where('mitra_material_id', $lallo->id)
            ->where('type', 'out')
            ->where('reference_id', $result['transaction']->id)
            ->firstOrFail();

        $this->assertEqualsWithDelta((float) $lallo->current_stock, (float) $movement->balance_after, 0.001);
    }

    public function test_transaction_no_sequence_increments_across_sequential_checkouts(): void
    {
        $first = $this->checkoutKopiSusu(1)['transaction'];
        $second = $this->checkoutKopiSusu(1)['transaction'];

        [$prefixFirst, $seqFirst] = $this->splitTransactionNo($first->transaction_no);
        [$prefixSecond, $seqSecond] = $this->splitTransactionNo($second->transaction_no);

        $this->assertSame($prefixFirst, $prefixSecond);
        $this->assertSame($seqFirst + 1, $seqSecond);
    }

    private function splitTransactionNo(string $transactionNo): array
    {
        // POS/{code}/{Ymd}/{seq4}
        $lastSlash = strrpos($transactionNo, '/');
        $prefix = substr($transactionNo, 0, $lastSlash + 1);
        $seq = (int) substr($transactionNo, $lastSlash + 1);

        return [$prefix, $seq];
    }

    public function test_short_stock_checkout_succeeds_with_warnings_and_allows_negative_stock(): void
    {
        // SOFIA BLEND (SHB001) has 0 opening stock per the seeder — build a
        // throwaway product around it so checkout demands more than exists.
        $sofia = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SHB001')->firstOrFail();

        $productService = app(MitraProductService::class);
        $shortStockProduct = $productService->createForMitra($this->mitra->id, [
            'sku' => 'TEST-SHORT-STOCK',
            'name' => 'Test Short Stock Product',
            'q_factor' => 0.2,
            'sale_price' => 15000,
            'status' => 'active',
            'ingredients' => [
                ['mitra_material_id' => $sofia->id, 'qty' => 50],
            ],
        ]);

        $result = $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $shortStockProduct->id, 'qty' => 3]], // needs 150gr, has 0
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );

        $this->assertNotEmpty($result['stock_warnings']);

        $sofia->refresh();
        $this->assertLessThanOrEqual(0, (float) $sofia->current_stock);
    }

    public function test_checkout_failure_rolls_back_and_creates_zero_transactions(): void
    {
        $this->assertSame(0, PosTransaction::forMitra($this->mitra->id)->count());

        $thrown = false;

        try {
            $this->service->checkout(
                mitraId: $this->mitra->id,
                userId: $this->kasir->id,
                items: [['mitra_product_id' => 999999, 'qty' => 1]], // non-existent product id
                discount: 0,
                salesMode: 'dine_in',
                paymentMethod: 'cash',
            );
        } catch (NotFoundHttpException $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown, 'Expected NotFoundHttpException was not thrown.');
        $this->assertSame(0, PosTransaction::forMitra($this->mitra->id)->count());
    }
}
