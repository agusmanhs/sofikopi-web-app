<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraProduct;
use App\Models\User;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kasir boleh melihat transaksi dan struk, tetapi bukan angka modal, laba,
 * pemakaian bahan, atau nomor jurnal — itu hanya untuk pemilik.
 */
class KasirProfitVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $kasir;

    private string $transactionNo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();

        $product = MitraProduct::forMitra($mitra->id)->where('sku', 'SLK048')->firstOrFail();
        $this->transactionNo = app(PosTransactionService::class)->checkout(
            mitraId: $mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        )['transaction']->transaction_no;
    }

    public function test_kasir_sees_the_sale_but_not_cost_profit_or_journal(): void
    {
        $response = $this->actingAs($this->kasir)->get(route('pos-transaction.show', $this->transactionNo));

        $response->assertOk();
        $response->assertSee('AIR MINERAL');
        $response->assertSee('Grand Total');
        foreach (['Laba Kotor', 'Biaya bahan', 'Untung (setelah Faktor Q)', 'Bahan yang keluar dari stok', 'Jurnal Akuntansi'] as $hidden) {
            $response->assertDontSee($hidden);
        }
    }

    public function test_owner_still_sees_cost_profit_and_journal(): void
    {
        $response = $this->actingAs($this->owner)->get(route('pos-transaction.show', $this->transactionNo));

        $response->assertOk();
        foreach (['Laba Kotor', 'Biaya bahan (resep)', 'Untung (setelah Faktor Q)', 'Bahan yang keluar dari stok', 'Jurnal Akuntansi'] as $shown) {
            $response->assertSee($shown);
        }
    }
}
