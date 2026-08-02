<?php

namespace Tests\Feature\MitraPos;

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

class AkuntansiLabaRugiTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $kasir;

    private PosTransactionService $posService;

    private AkuntansiJournalService $journalService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $this->posService = app(PosTransactionService::class);
        $this->journalService = app(AkuntansiJournalService::class);
    }

    public function test_laba_bersih_equals_revenue_minus_hpp_after_a_sale(): void
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        $result = $this->posService->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 2]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );
        $transaction = $result['transaction'];

        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();
        $labaRugi = $this->journalService->labaRugi($this->mitra->id, $from, $to);

        $expectedRevenue = (float) $transaction->subtotal - (float) $transaction->discount + (float) $transaction->service_charge;
        $expectedCogs = (float) $transaction->total_cogs;

        $this->assertEqualsWithDelta($expectedRevenue, $labaRugi['pendapatan_total'], 0.01);
        $this->assertEqualsWithDelta((float) $transaction->total_hpp, $labaRugi['hpp_total'], 0.01);
        $this->assertEqualsWithDelta($expectedRevenue - $expectedCogs, $labaRugi['laba_bersih'], 0.01);
    }

    public function test_sale_outside_the_date_range_is_excluded(): void
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        $this->posService->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );

        $labaRugi = $this->journalService->labaRugi(
            $this->mitra->id,
            Carbon::now()->addYear(),
            Carbon::now()->addYear()->endOfMonth(),
        );

        $this->assertSame(0.0, $labaRugi['pendapatan_total']);
        $this->assertSame(0.0, $labaRugi['laba_bersih']);
    }
}
