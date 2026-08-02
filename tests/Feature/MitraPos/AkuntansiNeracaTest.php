<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiAccount;
use App\Models\Mitra;
use App\Models\MitraProduct;
use App\Models\User;
use App\Services\MitraPos\AkuntansiJournalService;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Covers AkuntansiJournalService::neraca() — the fundamental accounting
 * equation (Aset = Kewajiban + Modal) must hold after any mix of postings,
 * since every journal entry is itself balanced (debit == credit).
 */
class AkuntansiNeracaTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $kasir;

    private User $owner;

    private PosTransactionService $posService;

    private AkuntansiJournalService $journalService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->posService = app(PosTransactionService::class);
        $this->journalService = app(AkuntansiJournalService::class);
    }

    public function test_balance_sheet_balances_after_a_sale(): void
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        $this->posService->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 3]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );

        $neraca = $this->journalService->neraca($this->mitra->id, Carbon::now());

        $this->assertEqualsWithDelta($neraca['aset_total'], $neraca['total_pasiva'], 0.01);
    }

    public function test_balance_sheet_still_balances_after_a_void(): void
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        $checkout = $this->posService->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 3]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );

        $this->posService->void($this->mitra->id, $checkout['transaction']->transaction_no, $this->owner->id, 'Batal');

        $neraca = $this->journalService->neraca($this->mitra->id, Carbon::now());

        $this->assertEqualsWithDelta($neraca['aset_total'], $neraca['total_pasiva'], 0.01);
        // A fully voided sale nets to zero revenue/cost impact.
        $this->assertEqualsWithDelta(0.0, $neraca['laba_berjalan'], 0.01);
    }

    public function test_opening_balance_feeds_into_saldo_akhir_before_any_transactions(): void
    {
        $account = AkuntansiAccount::forMitra($this->mitra->id)
            ->where('system_role', 'kas_kasir')
            ->firstOrFail();
        $account->update(['opening_balance' => 1000000]);

        $neraca = $this->journalService->neraca($this->mitra->id, Carbon::now());

        $kasRow = collect($neraca['aset'])->first(fn ($r) => $r['account']->id === $account->id);
        $this->assertEqualsWithDelta(1000000.0, $kasRow['saldo_akhir'], 0.01);
    }

    public function test_owner_can_view_neraca_and_laba_rugi_kasir_cannot(): void
    {
        $this->actingAs($this->owner)->get(route('akuntansi-neraca.index'))->assertOk();
        $this->actingAs($this->owner)->get(route('akuntansi-laba-rugi.index'))->assertOk();

        $this->actingAs($this->kasir)->get(route('akuntansi-neraca.index'))->assertForbidden();
        $this->actingAs($this->kasir)->get(route('akuntansi-laba-rugi.index'))->assertForbidden();
    }
}
