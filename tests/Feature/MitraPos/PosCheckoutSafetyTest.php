<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiJournalEntry;
use App\Models\Mitra;
use App\Models\MitraPosSetting;
use App\Models\MitraProduct;
use App\Models\MitraStockMovement;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCheckoutSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $kasir;

    private MitraProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $this->product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();
    }

    private function checkoutPayload(float $discount, float $qty = 1): array
    {
        return [
            'items' => [['mitra_product_id' => $this->product->id, 'qty' => $qty]],
            'discount' => $discount,
            'sales_mode' => 'dine_in',
            'payment_method' => 'cash',
        ];
    }

    public function test_discount_larger_than_subtotal_is_rejected_with_plain_message(): void
    {
        $subtotal = (float) $this->product->sale_price;

        $response = $this->actingAs($this->kasir)
            ->postJson(route('pos.store'), $this->checkoutPayload($subtotal + 1000));

        $response->assertStatus(422)->assertJsonPath('success', false);
        $message = $response->json('message');
        $this->assertStringContainsString('Diskon Rp'.number_format($subtotal + 1000, 0, ',', '.'), $message);
        $this->assertStringContainsString('subtotal Rp'.number_format($subtotal, 0, ',', '.'), $message);
        $this->assertSame(0, PosTransaction::count());
    }

    public function test_discount_equal_to_subtotal_is_allowed(): void
    {
        $subtotal = (float) $this->product->sale_price;

        $this->actingAs($this->kasir)
            ->postJson(route('pos.store'), $this->checkoutPayload($subtotal))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, PosTransaction::count());
    }

    public function test_unexpected_failure_returns_generic_message_without_leaking_details(): void
    {
        $this->mock(PosTransactionService::class, function ($mock) {
            $mock->shouldReceive('checkout')->andThrow(new \RuntimeException('SQLSTATE[HY000] rahasia internal'));
        });

        $response = $this->actingAs($this->kasir)
            ->postJson(route('pos.store'), $this->checkoutPayload(0));

        $response->assertStatus(422)->assertJsonPath(
            'message',
            'Checkout gagal karena kesalahan sistem. Transaksi belum tersimpan. Silakan coba lagi atau hubungi admin.'
        );
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
    }

    public function test_page_exposes_service_charge_and_tax_percents(): void
    {
        MitraPosSetting::forMitra($this->mitra->id)->firstOrFail()->update([
            'service_charge_percent' => 5,
            'tax_percent' => 11,
        ]);

        $response = $this->actingAs($this->kasir)->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee('"service_charge_percent":5', false);
        $response->assertSee('"tax_percent":11', false);
        $response->assertSee('Total bayar');
    }

    public function test_stock_warnings_include_material_unit(): void
    {
        $response = $this->actingAs($this->kasir)
            ->postJson(route('pos.store'), $this->checkoutPayload(0, 999));

        $response->assertOk();
        $warnings = $response->json('data.stock_warnings');
        $this->assertNotEmpty($warnings);
        foreach ($warnings as $warning) {
            $this->assertArrayHasKey('unit', $warning);
            $this->assertNotEmpty($warning['unit']);
        }
    }

    public function test_same_client_token_twice_records_only_one_sale(): void
    {
        $payload = $this->checkoutPayload(0) + ['client_token' => 'token-abc-123'];

        $first = $this->actingAs($this->kasir)->postJson(route('pos.store'), $payload)->assertOk();
        $second = $this->actingAs($this->kasir)->postJson(route('pos.store'), $payload)->assertOk();

        $this->assertSame(1, PosTransaction::count());
        $this->assertSame(1, AkuntansiJournalEntry::count());
        $this->assertSame(
            $first->json('data.transaction.transaction_no'),
            $second->json('data.transaction.transaction_no')
        );
        $this->assertSame([], $second->json('data.stock_warnings'));
    }

    public function test_stock_is_deducted_only_once_for_repeated_token(): void
    {
        $payload = $this->checkoutPayload(0) + ['client_token' => 'token-stock'];

        $this->actingAs($this->kasir)->postJson(route('pos.store'), $payload)->assertOk();
        $movementsAfterFirst = MitraStockMovement::count();
        $this->actingAs($this->kasir)->postJson(route('pos.store'), $payload)->assertOk();

        $this->assertSame($movementsAfterFirst, MitraStockMovement::count());
    }

    public function test_different_client_tokens_create_separate_sales(): void
    {
        $this->actingAs($this->kasir)->postJson(route('pos.store'), $this->checkoutPayload(0) + ['client_token' => 'token-1'])->assertOk();
        $this->actingAs($this->kasir)->postJson(route('pos.store'), $this->checkoutPayload(0) + ['client_token' => 'token-2'])->assertOk();

        $this->assertSame(2, PosTransaction::count());
    }

    public function test_checkout_without_client_token_behaves_as_before(): void
    {
        $this->actingAs($this->kasir)->postJson(route('pos.store'), $this->checkoutPayload(0))->assertOk();
        $this->actingAs($this->kasir)->postJson(route('pos.store'), $this->checkoutPayload(0))->assertOk();

        $this->assertSame(2, PosTransaction::count());
    }
}
