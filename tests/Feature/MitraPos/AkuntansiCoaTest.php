<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiAccount;
use App\Models\Mitra;
use App\Models\User;
use App\Services\MitraPos\AkuntansiCoaService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkuntansiCoaTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $kasir;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
    }

    public function test_enrollment_seeds_190_accounts_with_expected_system_roles(): void
    {
        $accounts = AkuntansiAccount::forMitra($this->mitra->id)->get();

        $this->assertCount(190, $accounts);

        $roles = $accounts->pluck('system_role')->filter()->all();
        $this->assertEqualsCanonicalizing([
            'kas_kasir', 'piutang_elektronik', 'penjualan', 'persediaan_bahan_baku',
            'harga_pokok_penjualan', 'beban_overhead_produksi', 'hutang_bop',
            'beban_admin_bank', 'hutang_lain_lain',
        ], $roles);
    }

    public function test_seed_for_mitra_is_idempotent(): void
    {
        $service = app(AkuntansiCoaService::class);
        $service->seedForMitra($this->mitra->id);
        $service->seedForMitra($this->mitra->id);

        $this->assertSame(190, AkuntansiAccount::forMitra($this->mitra->id)->count());
    }

    public function test_owner_can_view_and_update_coa_via_http(): void
    {
        $account = AkuntansiAccount::forMitra($this->mitra->id)
            ->where('system_role', 'kas_kasir')
            ->firstOrFail();

        $this->actingAs($this->owner)->get(route('akuntansi-coa.index'))->assertOk();

        $response = $this->actingAs($this->owner)->put(route('akuntansi-coa.update'), [
            'opening_balance' => [$account->id => '500000'],
            'is_active' => [$account->id => '1'],
        ]);

        $response->assertRedirect(route('akuntansi-coa.index'));

        $account->refresh();
        $this->assertEqualsWithDelta(500000.0, (float) $account->opening_balance, 0.01);
        $this->assertTrue($account->is_active);
    }

    public function test_unchecked_account_is_deactivated_on_submit(): void
    {
        $account = AkuntansiAccount::forMitra($this->mitra->id)
            ->where('system_role', 'kas_kasir')
            ->firstOrFail();
        $this->assertTrue($account->is_active);

        // is_active omitted entirely from the payload, as an unticked
        // checkbox would be — every postable account must still turn off.
        $this->actingAs($this->owner)->put(route('akuntansi-coa.update'), [
            'opening_balance' => [$account->id => '0'],
        ]);

        $account->refresh();
        $this->assertFalse($account->is_active);
    }

    public function test_kasir_cannot_view_or_update_coa(): void
    {
        $this->actingAs($this->kasir)->get(route('akuntansi-coa.index'))->assertForbidden();
        $this->actingAs($this->kasir)->put(route('akuntansi-coa.update'), [])->assertForbidden();
    }
}
