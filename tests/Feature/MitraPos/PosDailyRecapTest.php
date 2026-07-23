<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\MitraPos\MitraReportService;
use App\Services\MitraPos\PosTransactionService;
use Carbon\Carbon;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosDailyRecapTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $kasir;

    private User $owner;

    private PosTransactionService $service;

    private MitraReportService $report;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->service = app(PosTransactionService::class);
        $this->report = app(MitraReportService::class);
    }

    private function checkoutOne(): PosTransaction
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        return $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'qris',
        )['transaction'];
    }

    public function test_recap_includes_todays_completed_transaction_in_the_right_columns(): void
    {
        $transaction = $this->checkoutOne();
        $today = Carbon::now()->startOfDay();

        $rows = $this->report->dailyRecap($this->mitra->id, $today, $today);
        $row = $rows->first();

        $this->assertEqualsWithDelta((float) $transaction->grand_total, $row['pendapatan_bersih'], 0.01);
        $this->assertEqualsWithDelta((float) $transaction->grand_total, $row['penerimaan_qris'], 0.01);
        $this->assertSame(0.0, $row['penerimaan_cash']);
        $this->assertSame(1, $row['jumlah_transaksi']);
        $this->assertSame(0.0, $row['void_total']);
    }

    public function test_voiding_a_backdated_transaction_counts_void_on_the_void_date_not_the_sale_date(): void
    {
        $transaction = $this->checkoutOne();

        // Backdate the sale itself to "yesterday" so we can prove the void
        // column groups by voided_at, not transacted_at.
        $yesterday = Carbon::now()->subDay();
        PosTransaction::forMitra($this->mitra->id)->where('id', $transaction->id)
            ->update(['transacted_at' => $yesterday]);

        $this->service->void($this->mitra->id, $transaction->transaction_no, $this->owner->id, 'Test void recap');

        $today = Carbon::now()->startOfDay();
        $rows = $this->report->dailyRecap($this->mitra->id, $yesterday->copy()->startOfDay(), $today);

        $yesterdayRow = $rows->firstWhere(fn ($r) => $r['tanggal']->isSameDay($yesterday));
        $todayRow = $rows->firstWhere(fn ($r) => $r['tanggal']->isSameDay($today));

        // Sale date: no completed revenue left (it's voided now).
        $this->assertSame(0, $yesterdayRow['jumlah_transaksi']);
        $this->assertSame(0.0, $yesterdayRow['void_total']);

        // Void date (today): the void shows up here.
        $this->assertEqualsWithDelta((float) $transaction->grand_total, $todayRow['void_total'], 0.01);
        $this->assertSame(1, $todayRow['void_count']);
    }

    public function test_owner_can_view_report_and_export_but_kasir_cannot(): void
    {
        $this->checkoutOne();

        $this->actingAs($this->owner)
            ->get(route('mitra-report.index'))
            ->assertOk();

        $this->actingAs($this->owner)
            ->get(route('mitra-report.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->kasir)
            ->get(route('mitra-report.index'))
            ->assertForbidden();
    }
}
