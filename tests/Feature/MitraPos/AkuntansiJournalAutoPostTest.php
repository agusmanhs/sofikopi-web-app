<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiJournalEntry;
use App\Models\Mitra;
use App\Models\MitraPosSetting;
use App\Models\MitraProduct;
use App\Models\User;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers auto-posting a balanced double-entry journal from a completed POS
 * checkout — see AkuntansiJournalService::postForSale() and the Akuntansi
 * plan doc's account mapping table.
 */
class AkuntansiJournalAutoPostTest extends TestCase
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

    public function test_cash_checkout_posts_a_balanced_entry_to_kas_kasir_and_penjualan(): void
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        $result = $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 2]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );
        $transaction = $result['transaction'];

        $entry = AkuntansiJournalEntry::forMitra($this->mitra->id)
            ->where('reference_type', $transaction->getMorphClass())
            ->where('reference_id', $transaction->id)
            ->where('source_type', 'pos_sale')
            ->with('lines.account')
            ->firstOrFail();

        $totalDebit = round((float) $entry->lines->sum('debit'), 2);
        $totalCredit = round((float) $entry->lines->sum('credit'), 2);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);

        $kasLine = $entry->lines->firstWhere('debit', '>', 0);
        $this->assertSame('kas_kasir', $kasLine->account->system_role);
        $this->assertEqualsWithDelta((float) $transaction->grand_total, (float) $kasLine->debit, 0.01);

        $revenueLine = $entry->lines->first(fn ($l) => $l->account->system_role === 'penjualan');
        $this->assertNotNull($revenueLine);
        $this->assertEqualsWithDelta((float) $transaction->subtotal - (float) $transaction->discount, (float) $revenueLine->credit, 0.01);

        $hppLine = $entry->lines->first(fn ($l) => $l->account->system_role === 'harga_pokok_penjualan');
        $persediaanLine = $entry->lines->first(fn ($l) => $l->account->system_role === 'persediaan_bahan_baku');
        $this->assertEqualsWithDelta((float) $transaction->total_hpp, (float) $hppLine->debit, 0.01);
        $this->assertEqualsWithDelta((float) $transaction->total_hpp, (float) $persediaanLine->credit, 0.01);
    }

    public function test_qris_checkout_debits_piutang_elektronik_instead_of_kas(): void
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        $result = $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'qris',
        );
        $transaction = $result['transaction'];

        $entry = AkuntansiJournalEntry::forMitra($this->mitra->id)
            ->where('reference_id', $transaction->id)
            ->where('source_type', 'pos_sale')
            ->with('lines.account')
            ->firstOrFail();

        $debitLine = $entry->lines->firstWhere('debit', '>', 0);
        $this->assertSame('piutang_elektronik', $debitLine->account->system_role);
    }

    public function test_service_charge_tax_and_admin_fee_produce_a_still_balanced_entry(): void
    {
        MitraPosSetting::forMitra($this->mitra->id)->first()->update([
            'service_charge_percent' => 10,
            'tax_percent' => 11,
            'qris_fee_percent' => 2,
        ]);

        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        $result = $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'qris',
        );
        $transaction = $result['transaction'];

        $entry = AkuntansiJournalEntry::forMitra($this->mitra->id)
            ->where('reference_id', $transaction->id)
            ->where('source_type', 'pos_sale')
            ->with('lines.account')
            ->firstOrFail();

        $totalDebit = round((float) $entry->lines->sum('debit'), 2);
        $totalCredit = round((float) $entry->lines->sum('credit'), 2);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);

        $taxLine = $entry->lines->first(fn ($l) => $l->account->system_role === 'hutang_lain_lain');
        $this->assertNotNull($taxLine);
        $this->assertEqualsWithDelta((float) $transaction->tax, (float) $taxLine->credit, 0.01);

        $adminFeeLine = $entry->lines->first(fn ($l) => $l->account->system_role === 'beban_admin_bank');
        $this->assertNotNull($adminFeeLine);
        $this->assertEqualsWithDelta((float) $transaction->admin_fee, (float) $adminFeeLine->debit, 0.01);
    }
}
