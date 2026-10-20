<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiAccount;
use App\Models\Mitra;
use App\Models\MitraProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\MitraPos\AkuntansiJournalService;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Daftar jurnal harus menampilkan nilai transaksi (yang dibayar pelanggan),
 * bukan total semua baris debit yang ikut menghitung HPP dan overhead.
 */
class AkuntansiJournalNilaiTransaksiTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
    }

    public function test_sale_entry_value_is_what_the_customer_paid_not_the_sum_of_all_debit_lines(): void
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK048')->firstOrFail();

        $transaction = app(PosTransactionService::class)->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        )['transaction'];

        $entry = app(AkuntansiJournalService::class)->listForMitra($this->mitra->id)->first();

        $this->assertSame('pos_sale', $entry->source_type);
        $this->assertEqualsWithDelta((float) $transaction->grand_total, $entry->nilai_transaksi, 0.01);
        $this->assertGreaterThan($entry->nilai_transaksi, (float) $entry->lines->sum('debit'));
    }

    public function test_listing_loads_the_pos_transaction_items_for_the_detail_view(): void
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK048')->firstOrFail();

        app(PosTransactionService::class)->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 2]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );

        $entry = app(AkuntansiJournalService::class)->listForMitra($this->mitra->id)->first();

        $this->assertInstanceOf(PosTransaction::class, $entry->reference);
        $this->assertTrue($entry->reference->relationLoaded('items'));
        $this->assertSame('AIR MINERAL', $entry->reference->items->first()->product_name);
    }

    public function test_manual_entry_value_is_its_total_debit(): void
    {
        $accounts = AkuntansiAccount::forMitra($this->mitra->id)
            ->where('is_postable', true)
            ->limit(2)
            ->get();

        app(AkuntansiJournalService::class)->createManual(
            $this->mitra->id,
            $this->kasir->id,
            now(),
            'Jurnal manual uji',
            [
                ['account_id' => $accounts[0]->id, 'debit' => 25000, 'credit' => 0],
                ['account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => 25000],
            ],
        );

        $entry = app(AkuntansiJournalService::class)->listForMitra($this->mitra->id)->first();

        $this->assertSame('manual', $entry->source_type);
        $this->assertEqualsWithDelta(25000.0, $entry->nilai_transaksi, 0.01);
    }

    public function test_index_page_shows_transaction_value_and_expandable_detail(): void
    {
        $owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK048')->firstOrFail();

        $transaction = app(PosTransactionService::class)->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        )['transaction'];

        $response = $this->actingAs($owner)->get(route('akuntansi-jurnal.index'));

        $response->assertOk();
        $response->assertSee('Nilai Transaksi');
        $response->assertSee('Rp 6.000');
        $response->assertSee('Detail Transaksi '.$transaction->transaction_no);
        $response->assertSee('AIR MINERAL');
        $response->assertSee('Rincian Jurnal');
        $response->assertSee('Rp 11.400');
    }
}
