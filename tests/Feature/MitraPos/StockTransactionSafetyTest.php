<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\MitraProduct;
use App\Models\MitraStockMovement;
use App\Models\User;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransactionSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $owner;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
    }

    private function checkoutSale(float $qty = 1)
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        return app(PosTransactionService::class)->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => $qty]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        )['transaction'];
    }

    private function material(string $sku = 'SHB021'): MitraMaterial
    {
        return MitraMaterial::forMitra($this->mitra->id)->where('sku', $sku)->firstOrFail();
    }

    private function rowContaining(string $html, string $needle): string
    {
        foreach (explode('<tr', $html) as $row) {
            if (str_contains($row, $needle)) {
                return $row;
            }
        }

        $this->fail("Tidak ada baris yang memuat {$needle}");
    }

    private function fmtQty($qty): string
    {
        return rtrim(rtrim(number_format((float) $qty, 3, ',', '.'), '0'), ',');
    }

    public function test_void_with_too_short_reason_is_rejected(): void
    {
        $transaction = $this->checkoutSale();

        $this->actingAs($this->owner)
            ->post(route('pos-transaction.void', $transaction->transaction_no), ['reason' => 'abc'])
            ->assertSessionHasErrors('reason');

        $this->assertSame('completed', $transaction->fresh()->status);
    }

    public function test_void_with_valid_reason_succeeds(): void
    {
        $transaction = $this->checkoutSale();

        $this->actingAs($this->owner)
            ->post(route('pos-transaction.void', $transaction->transaction_no), ['reason' => 'Salah input produk'])
            ->assertSessionHasNoErrors();

        $this->assertSame('voided', $transaction->fresh()->status);
    }

    public function test_decrease_above_current_stock_is_rejected(): void
    {
        $material = $this->material();
        $stockBefore = (float) $material->current_stock;

        $this->actingAs($this->owner)
            ->post(route('mitra-stock.adjust', $material->sku), [
                'delta' => -($stockBefore + 100),
                'notes' => 'Bahan rusak',
            ])
            ->assertSessionHasErrors('delta');

        $this->assertEqualsWithDelta($stockBefore, (float) $material->fresh()->current_stock, 0.0001);
    }

    public function test_adjustment_without_reason_is_rejected(): void
    {
        $material = $this->material();
        $stockBefore = (float) $material->current_stock;

        $this->actingAs($this->owner)
            ->post(route('mitra-stock.adjust', $material->sku), ['delta' => 5])
            ->assertSessionHasErrors('notes');

        $this->assertEqualsWithDelta($stockBefore, (float) $material->fresh()->current_stock, 0.0001);
    }

    public function test_opname_skips_blank_rows_and_adjusts_counted_rows(): void
    {
        $materials = MitraMaterial::forMitra($this->mitra->id)->where('is_active', true)->orderBy('id')->take(2)->get();
        [$counted, $blank] = [$materials[0], $materials[1]];
        $countedBefore = (float) $counted->current_stock;
        $blankBefore = (float) $blank->current_stock;

        $this->actingAs($this->owner)
            ->post(route('mitra-opname.store'), [
                'physical_qty' => [
                    $counted->id => $countedBefore + 7,
                    $blank->id => '',
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta($countedBefore + 7, (float) $counted->fresh()->current_stock, 0.0001);
        $this->assertEqualsWithDelta($blankBefore, (float) $blank->fresh()->current_stock, 0.0001);
        $this->assertDatabaseCount('mitra_stock_opname_items', 1);
    }

    public function test_opname_with_all_rows_blank_is_rejected(): void
    {
        $material = $this->material();

        $this->actingAs($this->owner)
            ->post(route('mitra-opname.store'), ['physical_qty' => [$material->id => '']])
            ->assertSessionHasErrors('physical_qty');
    }

    public function test_report_rejects_invalid_and_reversed_dates(): void
    {
        $this->actingAs($this->owner)
            ->get(route('mitra-report.index', ['from' => 'bukan-tanggal']))
            ->assertSessionHasErrors('from');

        $this->actingAs($this->owner)
            ->get(route('mitra-report.index', ['from' => '2026-05-10', 'to' => '2026-05-01']))
            ->assertSessionHasErrors('to');

        $this->actingAs($this->owner)
            ->get(route('mitra-report.index', ['from' => '2024-01-01', 'to' => '2026-01-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_transaction_list_filters_by_status_and_date(): void
    {
        $voided = $this->checkoutSale();
        $active = $this->checkoutSale();
        $this->actingAs($this->owner)
            ->post(route('pos-transaction.void', $voided->transaction_no), ['reason' => 'Salah input produk']);

        $response = $this->actingAs($this->owner)
            ->get(route('pos-transaction.index', ['status' => 'voided']))
            ->assertOk()
            ->assertSee($voided->transaction_no)
            ->assertDontSee($active->transaction_no);
        $this->assertStringContainsString('badge bg-label-danger">Dibatalkan', $this->rowContaining($response->getContent(), $voided->transaction_no));

        $this->actingAs($this->owner)
            ->get(route('pos-transaction.index', ['from' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertDontSee($active->transaction_no);

        $response = $this->actingAs($this->owner)
            ->get(route('pos-transaction.index', ['from' => now()->toDateString(), 'status' => 'completed']))
            ->assertOk()
            ->assertSee($active->transaction_no);
        $this->assertStringContainsString('badge bg-label-success">Selesai', $this->rowContaining($response->getContent(), $active->transaction_no));
    }

    public function test_movements_page_shows_indonesian_labels_and_signed_quantities(): void
    {
        $transaction = $this->checkoutSale(2);
        $this->actingAs($this->owner)
            ->post(route('pos-transaction.void', $transaction->transaction_no), ['reason' => 'Salah input produk']);

        $response = $this->actingAs($this->owner)->get(route('mitra-stock.movements'));

        $response->assertOk()
            ->assertSee('Penjualan POS '.$transaction->transaction_no)
            ->assertSee('Void '.$transaction->transaction_no);

        $outQty = $this->fmtQty(MitraStockMovement::where('reference_id', $transaction->id)->where('type', 'out')->firstOrFail()->qty);
        $inQty = $this->fmtQty(MitraStockMovement::where('reference_id', $transaction->id)->where('type', 'in')->firstOrFail()->qty);

        $saleRow = $this->rowContaining($response->getContent(), 'Penjualan POS '.$transaction->transaction_no);
        $this->assertStringContainsString('bg-label-danger">Keluar', $saleRow);
        $this->assertStringContainsString('-'.$outQty.' ', $saleRow);

        $voidRow = $this->rowContaining($response->getContent(), 'Void '.$transaction->transaction_no);
        $this->assertStringContainsString('bg-label-success">Masuk', $voidRow);
        $this->assertStringContainsString('+'.$inQty.' ', $voidRow);
    }
}
