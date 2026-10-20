<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiAccount;
use App\Models\Mitra;
use App\Models\Role;
use App\Models\User;
use App\Services\MitraPos\AkuntansiJournalService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AkuntansiReportSafetyTest extends TestCase
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

    private function postSale(): void
    {
        $kas = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'kas_kasir')->firstOrFail();
        $penjualan = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'penjualan')->firstOrFail();

        app(AkuntansiJournalService::class)->createManual(
            mitraId: $this->mitra->id,
            userId: $this->owner->id,
            date: Carbon::parse('2026-10-10'),
            description: 'Penjualan manual',
            lines: [
                ['account_id' => $kas->id, 'debit' => 250000, 'credit' => 0],
                ['account_id' => $penjualan->id, 'debit' => 0, 'credit' => 250000],
            ],
        );
    }

    public function test_neraca_shows_period_heading_balanced_badge_and_subtotal_kas(): void
    {
        $this->postSale();

        $this->actingAs($this->owner)
            ->get(route('akuntansi-neraca.index', ['as_of_date' => '2026-10-20']))
            ->assertOk()
            ->assertSee('Neraca per 20 Okt 2026')
            ->assertSee('Seimbang')
            ->assertDontSee('Tidak seimbang')
            ->assertSee('Subtotal Kas')
            ->assertSee('Akumulasi laba/rugi sejak awal pembukuan');
    }

    public function test_neraca_flags_unbalanced_books_with_the_difference(): void
    {
        AkuntansiAccount::forMitra($this->mitra->id)
            ->where('system_role', 'kas_kasir')
            ->firstOrFail()
            ->update(['opening_balance' => 1000]);

        $this->actingAs($this->owner)
            ->get(route('akuntansi-neraca.index', ['as_of_date' => '2026-10-20']))
            ->assertOk()
            ->assertSee('Tidak seimbang (selisih Rp 1.000)');
    }

    public function test_neraca_account_amounts_link_to_filtered_journal_list(): void
    {
        $this->postSale();
        $kas = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'kas_kasir')->firstOrFail();

        $this->actingAs($this->owner)
            ->get(route('akuntansi-neraca.index', ['as_of_date' => '2026-10-20']))
            ->assertSee('account_id='.$kas->id.'&amp;to=2026-10-20', false);
    }

    public function test_neraca_invalid_date_returns_validation_error_not_a_crash(): void
    {
        $this->actingAs($this->owner)
            ->get(route('akuntansi-neraca.index', ['as_of_date' => 'bukan-tanggal']))
            ->assertRedirect(route('akuntansi-neraca.index'))
            ->assertSessionHasErrors('as_of_date');
    }

    public function test_laba_rugi_shows_period_heading(): void
    {
        $this->actingAs($this->owner)
            ->get(route('akuntansi-laba-rugi.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('Laba Rugi periode 1 Okt - 31 Okt 2026');
    }

    public function test_laba_rugi_rejects_reversed_range(): void
    {
        $this->actingAs($this->owner)
            ->get(route('akuntansi-laba-rugi.index', ['from' => '2026-10-31', 'to' => '2026-10-01']))
            ->assertRedirect(route('akuntansi-laba-rugi.index'))
            ->assertSessionHasErrors('to');
    }

    public function test_laba_rugi_rejects_unparseable_dates(): void
    {
        $this->actingAs($this->owner)
            ->get(route('akuntansi-laba-rugi.index', ['from' => 'abc', 'to' => 'xyz']))
            ->assertSessionHasErrors(['from', 'to']);
    }

    public function test_laba_rugi_rejects_range_longer_than_a_year(): void
    {
        $this->actingAs($this->owner)
            ->get(route('akuntansi-laba-rugi.index', ['from' => '2024-01-01', 'to' => '2026-10-01']))
            ->assertRedirect(route('akuntansi-laba-rugi.index'))
            ->assertSessionHasErrors('to');
    }

    public function test_admin_pages_show_the_mitra_name(): void
    {
        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-safety@internal.test',
            'password' => bcrypt('password'),
            'role_id' => $superAdmin->id,
            'mitra_id' => null,
        ]);

        foreach (['akuntansi-coa', 'akuntansi-jurnal', 'akuntansi-neraca', 'akuntansi-laba-rugi'] as $name) {
            $this->actingAs($admin)
                ->get(route("mitra-pos-manage.{$name}.index", $this->mitra))
                ->assertOk()
                ->assertSee('Mitra: '.$this->mitra->name);
        }
    }

    public function test_laba_rugi_with_only_from_after_default_end_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->get(route('akuntansi-laba-rugi.index', ['from' => now()->addMonths(2)->format('Y-m-d')]))
            ->assertRedirect(route('akuntansi-laba-rugi.index'))
            ->assertSessionHasErrors('to');
    }

    public function test_laba_rugi_with_only_to_before_default_start_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->get(route('akuntansi-laba-rugi.index', ['to' => now()->subMonths(2)->format('Y-m-d')]))
            ->assertRedirect(route('akuntansi-laba-rugi.index'))
            ->assertSessionHasErrors('to');
    }

    public function test_laba_rugi_with_only_from_in_range_works(): void
    {
        $this->actingAs($this->owner)
            ->get(route('akuntansi-laba-rugi.index', ['from' => now()->startOfMonth()->format('Y-m-d')]))
            ->assertOk();
    }

    public function test_neraca_warns_about_inactive_account_that_still_has_balance(): void
    {
        $bank = AkuntansiAccount::forMitra($this->mitra->id)->where('code', '10122')->firstOrFail();
        $bank->update(['opening_balance' => 4000, 'is_active' => false]);

        $this->actingAs($this->owner)
            ->get(route('akuntansi-neraca.index', ['as_of_date' => '2026-10-20']))
            ->assertOk()
            ->assertSee('Akun nonaktif masih punya saldo')
            ->assertSee($bank->name)
            ->assertSee('Aktifkan kembali di');
    }

    public function test_neraca_has_no_inactive_warning_when_nothing_is_stuck(): void
    {
        $this->actingAs($this->owner)
            ->get(route('akuntansi-neraca.index', ['as_of_date' => '2026-10-20']))
            ->assertDontSee('Akun nonaktif masih punya saldo');
    }
}
