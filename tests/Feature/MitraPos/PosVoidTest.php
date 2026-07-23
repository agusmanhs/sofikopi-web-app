<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\MitraProduct;
use App\Models\MitraStockMovement;
use App\Models\User;
use App\Services\MitraPos\MitraDashboardService;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Covers void: reverses stock from the movement ledger (not a BOM replay),
 * marks the transaction voided with actor/reason, blocks double-void, and
 * enforces the owner-can-void / kasir-cannot RBAC split.
 */
class PosVoidTest extends TestCase
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

    private function kopiSusu(): MitraProduct
    {
        return MitraProduct::forMitra($this->mitra->id)
            ->where('sku', 'SLK011')
            ->with('ingredients.material')
            ->firstOrFail();
    }

    private function checkoutKopiSusu(float $qty): array
    {
        return $this->service->checkout(
            mitraId: $this->mitra->id,
            userId: $this->kasir->id,
            items: [['mitra_product_id' => $this->kopiSusu()->id, 'qty' => $qty]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );
    }

    public function test_void_reverses_every_material_deducted_at_checkout(): void
    {
        $lallo = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SHB021')->firstOrFail();
        $openingStock = (float) $lallo->current_stock;

        $transaction = $this->checkoutKopiSusu(2)['transaction']; // -36gr SHB021

        $this->service->void($this->mitra->id, $transaction->transaction_no, $this->owner->id, 'Salah input produk');

        $lallo->refresh();
        $this->assertEqualsWithDelta($openingStock, (float) $lallo->current_stock, 0.001);

        $inMovement = MitraStockMovement::forMitra($this->mitra->id)
            ->where('mitra_material_id', $lallo->id)
            ->where('type', 'in')
            ->where('reference_id', $transaction->id)
            ->where('reference_type', $transaction->getMorphClass())
            ->firstOrFail();

        $this->assertEqualsWithDelta(36.0, (float) $inMovement->qty, 0.001);
    }

    public function test_void_marks_status_actor_and_reason(): void
    {
        $transaction = $this->checkoutKopiSusu(1)['transaction'];

        $result = $this->service->void($this->mitra->id, $transaction->transaction_no, $this->owner->id, 'Pelanggan batal');
        $voided = $result['transaction'];

        $this->assertSame('voided', $voided->status);
        $this->assertSame($this->owner->id, $voided->voided_by);
        $this->assertSame('Pelanggan batal', $voided->void_reason);
        $this->assertNotNull($voided->voided_at);
    }

    public function test_voiding_an_already_voided_transaction_throws(): void
    {
        $transaction = $this->checkoutKopiSusu(1)['transaction'];
        $this->service->void($this->mitra->id, $transaction->transaction_no, $this->owner->id, 'Pertama');

        $this->expectException(RuntimeException::class);
        $this->service->void($this->mitra->id, $transaction->transaction_no, $this->owner->id, 'Kedua');
    }

    public function test_void_skips_soft_deleted_material_and_reports_it(): void
    {
        $skk = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SKK001')->firstOrFail();
        $transaction = $this->checkoutKopiSusu(1)['transaction'];

        $skk->delete(); // soft delete after the sale

        $result = $this->service->void($this->mitra->id, $transaction->transaction_no, $this->owner->id, 'Test skip');

        $this->assertNotEmpty($result['skipped_materials']);
        $this->assertSame($skk->id, $result['skipped_materials'][0]['material_id']);
        $this->assertSame('voided', $result['transaction']->status);
    }

    public function test_owner_can_void_via_http_and_dashboard_excludes_voided_revenue(): void
    {
        $transaction = $this->checkoutKopiSusu(2)['transaction'];

        $response = $this->actingAs($this->owner)->post(
            route('pos-transaction.void', $transaction->transaction_no),
            ['reason' => 'Void via HTTP test']
        );

        $response->assertRedirect(route('pos-transaction.show', $transaction->transaction_no));
        $this->assertDatabaseHas('pos_transactions', [
            'id' => $transaction->id,
            'status' => 'voided',
        ]);

        $stats = app(MitraDashboardService::class)->stats($this->mitra->id);
        $this->assertSame(0.0, $stats['revenue_today']);
    }

    public function test_kasir_cannot_void_via_http(): void
    {
        $transaction = $this->checkoutKopiSusu(1)['transaction'];

        $this->actingAs($this->kasir)->post(
            route('pos-transaction.void', $transaction->transaction_no),
            ['reason' => 'Coba void']
        )->assertForbidden();

        $this->assertDatabaseHas('pos_transactions', [
            'id' => $transaction->id,
            'status' => 'completed',
        ]);
    }

    public function test_void_requires_a_reason(): void
    {
        $transaction = $this->checkoutKopiSusu(1)['transaction'];

        $this->actingAs($this->owner)->post(
            route('pos-transaction.void', $transaction->transaction_no),
            ['reason' => '']
        )->assertSessionHasErrors('reason');
    }
}
