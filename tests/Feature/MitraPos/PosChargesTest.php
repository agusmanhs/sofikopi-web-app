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

/**
 * Covers server-computed service charge / tax / payment admin-fee at
 * checkout (sheet DATA's "potongan administrasi"), and the owner-only
 * settings screen that configures the percents behind them.
 */
class PosChargesTest extends TestCase
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

    public function test_checkout_computes_service_charge_tax_and_admin_fee_from_settings(): void
    {
        MitraPosSetting::forMitra($this->mitra->id)->first()->update([
            'service_charge_percent' => 10,
            'tax_percent' => 11,
            'qris_fee_percent' => 2,
        ]);

        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();
        $unitPrice = (float) $product->sale_price;

        $result = $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'qris',
        );
        $transaction = $result['transaction'];

        $expectedServiceCharge = round($unitPrice * 0.10, 2);
        $expectedTax = round(($unitPrice + $expectedServiceCharge) * 0.11, 2);
        $expectedGrandTotal = $unitPrice + $expectedServiceCharge + $expectedTax;
        $expectedAdminFee = round($expectedGrandTotal * 0.02, 2);

        $this->assertEqualsWithDelta($expectedServiceCharge, (float) $transaction->service_charge, 0.01);
        $this->assertEqualsWithDelta($expectedTax, (float) $transaction->tax, 0.01);
        $this->assertEqualsWithDelta($expectedGrandTotal, (float) $transaction->grand_total, 0.01);
        $this->assertEqualsWithDelta($expectedAdminFee, (float) $transaction->admin_fee, 0.01);
    }

    public function test_cash_payment_never_incurs_an_admin_fee_even_with_percents_configured(): void
    {
        MitraPosSetting::forMitra($this->mitra->id)->first()->update([
            'qris_fee_percent' => 5,
            'transfer_fee_percent' => 5,
            'edc_fee_percent' => 5,
        ]);

        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        $result = $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );

        $this->assertSame(0.0, (float) $result['transaction']->admin_fee);
    }

    public function test_checkout_accepts_transfer_and_edc_payment_methods(): void
    {
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        foreach (['transfer', 'edc'] as $method) {
            $transaction = $this->service->checkout(
                mitraId: $this->mitra->id,
                userId: $this->kasir->id,
                items: [['mitra_product_id' => $product->id, 'qty' => 1]],
                discount: 0,
                salesMode: 'dine_in',
                paymentMethod: $method,
            )['transaction'];

            $this->assertSame($method, $transaction->payment_method);
        }
    }

    public function test_owner_can_update_settings_via_http(): void
    {
        $response = $this->actingAs($this->owner)->put(route('mitra-setting.update'), [
            'monthly_revenue_target' => '15.000.000',
            'receipt_footer' => 'Sampai jumpa lagi!',
            'service_charge_percent' => 5,
            'tax_percent' => 11,
            'qris_fee_percent' => 1.5,
            'transfer_fee_percent' => 0,
            'edc_fee_percent' => 2,
        ]);

        $response->assertRedirect(route('mitra-setting.index'));
        $this->assertDatabaseHas('mitra_pos_settings', [
            'mitra_id' => $this->mitra->id,
            'monthly_revenue_target' => 15000000.00,
            'receipt_footer' => 'Sampai jumpa lagi!',
            'service_charge_percent' => 5.00,
            'qris_fee_percent' => 1.50,
        ]);
    }

    public function test_kasir_cannot_view_or_update_settings(): void
    {
        $this->actingAs($this->kasir)->get(route('mitra-setting.index'))->assertForbidden();

        $this->actingAs($this->kasir)->put(route('mitra-setting.update'), [
            'service_charge_percent' => 5,
            'tax_percent' => 11,
            'qris_fee_percent' => 0,
            'transfer_fee_percent' => 0,
            'edc_fee_percent' => 0,
        ])->assertForbidden();
    }
}
