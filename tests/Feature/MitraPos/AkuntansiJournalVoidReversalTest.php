<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiJournalEntry;
use App\Models\Mitra;
use App\Models\MitraProduct;
use App\Models\User;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers AkuntansiJournalService::reverseForTransaction() wired into
 * PosTransactionService::void() — voiding a sale must produce a NEW mirrored
 * (debit/credit swapped) journal entry, never edit/delete the original.
 */
class AkuntansiJournalVoidReversalTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $kasir;

    private User $owner;

    private PosTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->service = app(PosTransactionService::class);
    }

    public function test_void_creates_a_mirrored_reversal_entry_leaving_the_original_untouched(): void
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        $checkout = $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );
        $transaction = $checkout['transaction'];

        $originalEntry = AkuntansiJournalEntry::forMitra($this->mitra->id)
            ->where('reference_id', $transaction->id)
            ->where('source_type', 'pos_sale')
            ->with('lines')
            ->firstOrFail();
        $originalLines = $originalEntry->lines->map(fn ($l) => [(float) $l->debit, (float) $l->credit])->all();

        $this->service->void($this->mitra->id, $transaction->transaction_no, $this->owner->id, 'Salah input');

        // Original entry's own lines must be byte-for-byte untouched.
        $originalEntry->refresh();
        $stillSameLines = $originalEntry->lines->map(fn ($l) => [(float) $l->debit, (float) $l->credit])->all();
        $this->assertEquals($originalLines, $stillSameLines);

        $reversalEntry = AkuntansiJournalEntry::forMitra($this->mitra->id)
            ->where('reference_id', $transaction->id)
            ->where('source_type', 'pos_void')
            ->with('lines')
            ->firstOrFail();

        $this->assertCount(count($originalEntry->lines), $reversalEntry->lines);

        // Every reversal line must be the exact debit/credit mirror of some
        // original line on the same account.
        $originalByAccount = $originalEntry->lines->keyBy('akuntansi_account_id');
        foreach ($reversalEntry->lines as $line) {
            $counterpart = $originalByAccount->get($line->akuntansi_account_id);
            $this->assertNotNull($counterpart);
            $this->assertEqualsWithDelta((float) $counterpart->credit, (float) $line->debit, 0.01);
            $this->assertEqualsWithDelta((float) $counterpart->debit, (float) $line->credit, 0.01);
        }

        $totalDebit = round((float) $reversalEntry->lines->sum('debit'), 2);
        $totalCredit = round((float) $reversalEntry->lines->sum('credit'), 2);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
    }
}
