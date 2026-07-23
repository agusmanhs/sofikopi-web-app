<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraPosSetting;
use App\Models\MitraProduct;
use App\Models\User;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReceiptTest extends TestCase
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

    private function checkoutOne(): object
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        return $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        )['transaction'];
    }

    public function test_kasir_can_view_receipt_of_own_mitra_transaction(): void
    {
        $transaction = $this->checkoutOne();

        $this->actingAs($this->kasir)
            ->get(route('pos-transaction.receipt', $transaction->transaction_no))
            ->assertOk()
            ->assertSee($transaction->transaction_no)
            ->assertSee($this->mitra->name);
    }

    public function test_receipt_shows_void_stamp_for_voided_transaction(): void
    {
        $transaction = $this->checkoutOne();
        $this->service->void($this->mitra->id, $transaction->transaction_no, $this->owner->id, 'Test void');

        $this->actingAs($this->owner)
            ->get(route('pos-transaction.receipt', $transaction->transaction_no))
            ->assertOk()
            ->assertSee('VOID');
    }

    public function test_receipt_uses_custom_footer_from_settings(): void
    {
        $transaction = $this->checkoutOne();

        MitraPosSetting::forMitra($this->mitra->id)->first()->update([
            'receipt_footer' => 'Sampai jumpa lagi di Cafe Lallo!',
        ]);

        $this->actingAs($this->kasir)
            ->get(route('pos-transaction.receipt', $transaction->transaction_no))
            ->assertOk()
            ->assertSee('Sampai jumpa lagi di Cafe Lallo!');
    }
}
