<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiJournalEntry;
use App\Models\Mitra;
use App\Models\MitraMaterial;
use App\Models\MitraStockMovement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers "+ Stok" on Kelola Material — a real purchase (restocking), which
 * unlike the plain "Sesuaikan Stok" adjust action (see
 * MitraStockAdjustTest) takes an actual purchase price and posts a
 * standard-cost-with-variance journal via
 * AkuntansiJournalService::postForPurchase(). Kurangi/Tambah "Sesuaikan
 * Stok" itself is untouched by this feature — not re-tested here beyond
 * what MitraStockAdjustTest already covers.
 *
 * Kelola Material (mitra-material.*) is the admin-only material catalog
 * screen — reached by Sofikopi staff via the "Kelola Mitra POS" picker, not
 * by the mitra owner directly (mitra-material.index only has a super-admin
 * pivot row in MitraPosMenuSeeder). So, same as MitraStockAdjustTest's
 * mitra-material.adjust coverage, "+ Stok" purchases are exercised as a
 * super-admin here, not as the mitra owner.
 */
class MitraStockPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private MitraMaterial $material;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        // SHB021: netto 1000 GR, price_per_pack 180000 -> harga_satuan 180/GR.
        $this->material = MitraMaterial::forMitra($this->mitra->id)->where('sku', 'SHB021')->firstOrFail();
        $this->admin = $this->makeSuperAdmin('admin-purchase@internal.test');
    }

    private function makeSuperAdmin(string $email): User
    {
        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);

        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => bcrypt('password'),
            'role_id' => $superAdmin->id,
            'mitra_id' => null,
        ]);
    }

    private function purchase(float $qty, float $unitPrice, ?string $notes = null)
    {
        return $this->actingAs($this->admin)->post(
            route('mitra-material.purchase', $this->mitra),
            [
                'material_id' => $this->material->id,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'notes' => $notes,
            ]
        );
    }

    private function journalFor(int $movementId): AkuntansiJournalEntry
    {
        return AkuntansiJournalEntry::forMitra($this->mitra->id)
            ->where('reference_type', (new MitraStockMovement)->getMorphClass())
            ->where('reference_id', $movementId)
            ->where('source_type', 'stock_purchase')
            ->with('lines.account')
            ->firstOrFail();
    }

    public function test_purchase_at_standard_price_posts_a_balanced_two_line_journal_with_no_variance(): void
    {
        $harga = (float) $this->material->harga_satuan; // 180

        $response = $this->purchase(qty: 10, unitPrice: $harga, notes: 'beli sesuai harga standar');
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('mitra-material.index', $this->mitra));

        $movement = $this->material->stockMovements()->where('type', 'in')->latest('id')->firstOrFail();
        $entry = $this->journalFor($movement->id);

        $this->assertCount(2, $entry->lines);

        $totalDebit = round((float) $entry->lines->sum('debit'), 2);
        $totalCredit = round((float) $entry->lines->sum('credit'), 2);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);

        $persediaanLine = $entry->lines->first(fn ($l) => $l->account->system_role === 'persediaan_bahan_baku');
        $this->assertNotNull($persediaanLine);
        $this->assertEqualsWithDelta($harga * 10, (float) $persediaanLine->debit, 0.01);

        $kasLine = $entry->lines->first(fn ($l) => $l->account->system_role === 'kas_tunai');
        $this->assertNotNull($kasLine);
        $this->assertEqualsWithDelta($harga * 10, (float) $kasLine->credit, 0.01);
    }

    public function test_purchase_above_standard_price_debits_variance_to_beban_selisih_pembelian(): void
    {
        $harga = (float) $this->material->harga_satuan; // 180
        $unitPrice = $harga + 20; // 200 -> beli lebih mahal dari standar

        $response = $this->purchase(qty: 10, unitPrice: $unitPrice);
        $response->assertSessionHasNoErrors();

        $movement = $this->material->stockMovements()->where('type', 'in')->latest('id')->firstOrFail();
        $entry = $this->journalFor($movement->id);

        $this->assertCount(3, $entry->lines);

        $totalDebit = round((float) $entry->lines->sum('debit'), 2);
        $totalCredit = round((float) $entry->lines->sum('credit'), 2);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);

        $varianceLine = $entry->lines->first(fn ($l) => $l->account->system_role === 'beban_selisih_pembelian');
        $this->assertNotNull($varianceLine);
        $this->assertEqualsWithDelta(200.0, (float) $varianceLine->debit, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $varianceLine->credit, 0.01);
    }

    public function test_purchase_below_standard_price_credits_variance_to_beban_selisih_pembelian(): void
    {
        $harga = (float) $this->material->harga_satuan; // 180
        $unitPrice = $harga - 30; // 150 -> beli lebih murah dari standar

        $response = $this->purchase(qty: 10, unitPrice: $unitPrice);
        $response->assertSessionHasNoErrors();

        $movement = $this->material->stockMovements()->where('type', 'in')->latest('id')->firstOrFail();
        $entry = $this->journalFor($movement->id);

        $this->assertCount(3, $entry->lines);

        $totalDebit = round((float) $entry->lines->sum('debit'), 2);
        $totalCredit = round((float) $entry->lines->sum('credit'), 2);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);

        $varianceLine = $entry->lines->first(fn ($l) => $l->account->system_role === 'beban_selisih_pembelian');
        $this->assertNotNull($varianceLine);
        $this->assertEqualsWithDelta(300.0, (float) $varianceLine->credit, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $varianceLine->debit, 0.01);
    }

    public function test_purchase_increases_stock_and_records_actual_price_as_unit_cost(): void
    {
        $stockBefore = (float) $this->material->current_stock;
        $harga = (float) $this->material->harga_satuan;
        $actualPrice = $harga + 20;

        $this->purchase(qty: 15, unitPrice: $actualPrice)->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta($stockBefore + 15, (float) $this->material->fresh()->current_stock, 0.0001);

        $movement = $this->material->stockMovements()->where('type', 'in')->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta($actualPrice, (float) $movement->unit_cost, 0.0001);
        $this->assertUnitCostDiffersFromStandard($harga, (float) $movement->unit_cost);
    }

    public function test_kasir_cannot_purchase_stock(): void
    {
        $kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();

        $this->actingAs($kasir)->post(
            route('mitra-material.purchase', $this->mitra),
            [
                'material_id' => $this->material->id,
                'qty' => 5,
                'unit_price' => 180,
            ]
        )->assertForbidden();
    }

    private function assertUnitCostDiffersFromStandard(float $standard, float $actual): void
    {
        $this->assertTrue(abs($standard - $actual) > 0.0001, "Failed asserting that {$actual} (unit_cost) differs from standard price {$standard}.");
    }
}
