<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiAccount;
use App\Models\Mitra;
use App\Models\User;
use App\Services\MitraPos\AkuntansiJournalService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AkuntansiCoaSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
    }

    private function account(string $code): AkuntansiAccount
    {
        return AkuntansiAccount::forMitra($this->mitra->id)->where('code', $code)->firstOrFail();
    }

    public function test_account_with_journal_lines_cannot_be_deactivated(): void
    {
        $beban = $this->account('40167');
        $kas = $this->account('10112');

        app(AkuntansiJournalService::class)->createManual(
            mitraId: $this->mitra->id,
            userId: $this->owner->id,
            date: Carbon::now(),
            description: 'Bayar biaya',
            lines: [
                ['account_id' => $beban->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $kas->id, 'debit' => 0, 'credit' => 10000],
            ],
        );

        $response = $this->actingAs($this->owner)->put(route('akuntansi-coa.update'), [
            'opening_balance' => [$beban->id => '0'],
        ]);

        $response->assertSessionHasErrors('is_active.'.$beban->id);
        $this->assertStringContainsString($beban->name, session('errors')->first('is_active.'.$beban->id));
        $this->assertTrue($beban->fresh()->is_active);
    }

    public function test_account_with_opening_balance_cannot_be_deactivated(): void
    {
        $kecil = $this->account('10113');
        $kecil->update(['opening_balance' => 5000]);

        $response = $this->actingAs($this->owner)->put(route('akuntansi-coa.update'), [
            'opening_balance' => [$kecil->id => '5.000'],
        ]);

        $response->assertSessionHasErrors('is_active.'.$kecil->id);
        $this->assertTrue($kecil->fresh()->is_active);
    }

    public function test_system_role_account_cannot_be_deactivated(): void
    {
        $kas = $this->account('10112');

        $response = $this->actingAs($this->owner)->put(route('akuntansi-coa.update'), [
            'opening_balance' => [$kas->id => '0'],
        ]);

        $response->assertSessionHasErrors('is_active.'.$kas->id);
        $this->assertTrue($kas->fresh()->is_active);
    }

    public function test_plain_account_can_save_masked_opening_balance_and_stay_active(): void
    {
        $kecil = $this->account('10113');

        $response = $this->actingAs($this->owner)->put(route('akuntansi-coa.update'), [
            'opening_balance' => [$kecil->id => '1.500.000'],
            'is_active' => [$kecil->id => '1'],
        ]);

        $response->assertRedirect(route('akuntansi-coa.index'));
        $response->assertSessionDoesntHaveErrors();
        $this->assertEqualsWithDelta(1500000.0, (float) $kecil->fresh()->opening_balance, 0.01);
    }

    public function test_plain_unused_account_can_be_deactivated(): void
    {
        $bank = $this->account('10121');
        $this->assertTrue($bank->is_active);

        $this->actingAs($this->owner)->put(route('akuntansi-coa.update'), [
            'opening_balance' => [$bank->id => '0'],
        ])->assertSessionDoesntHaveErrors();

        $this->assertFalse($bank->fresh()->is_active);
    }

    public function test_invalid_opening_balance_error_names_the_account(): void
    {
        $kecil = $this->account('10113');

        $this->actingAs($this->owner)->put(route('akuntansi-coa.update'), [
            'opening_balance' => [$kecil->id => 'abc'],
            'is_active' => [$kecil->id => '1'],
        ])->assertSessionHasErrors('opening_balance.'.$kecil->id);

        $message = session('errors')->first('opening_balance.'.$kecil->id);
        $this->assertStringContainsString($kecil->name, $message);
        $this->assertStringNotContainsString('opening_balance', $message);
    }

    public function test_coa_page_uses_plain_title_and_jenis_column(): void
    {
        $this->actingAs($this->owner)->get(route('akuntansi-coa.index'))
            ->assertOk()
            ->assertSee('Daftar Akun (COA)')
            ->assertSee('Jenis')
            ->assertSee('Mengubah saldo awal langsung mengubah Neraca');
    }
}
