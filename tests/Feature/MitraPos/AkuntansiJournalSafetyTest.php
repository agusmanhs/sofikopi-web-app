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

class AkuntansiJournalSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $owner;

    private AkuntansiAccount $kas;

    private AkuntansiAccount $beban;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->kas = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'kas_kasir')->firstOrFail();
        $this->beban = AkuntansiAccount::forMitra($this->mitra->id)->where('code', '40167')->firstOrFail();
    }

    private function makeEntry(string $description, string $date, int $amount = 10000, ?AkuntansiAccount $debitAccount = null)
    {
        return app(AkuntansiJournalService::class)->createManual(
            mitraId: $this->mitra->id,
            userId: $this->owner->id,
            date: Carbon::parse($date),
            description: $description,
            lines: [
                ['account_id' => ($debitAccount ?? $this->beban)->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $this->kas->id, 'debit' => 0, 'credit' => $amount],
            ],
        );
    }

    public function test_future_date_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), [
            'entry_date' => now()->addDay()->format('Y-m-d'),
            'description' => 'Jurnal masa depan',
            'lines' => [
                ['account_id' => $this->beban->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->kas->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('entry_date');
        $this->assertStringContainsString('masa depan', session('errors')->first('entry_date'));
        $this->assertDatabaseMissing('akuntansi_journal_entries', ['description' => 'Jurnal masa depan']);
    }

    public function test_masked_amounts_are_accepted_and_stored_as_whole_numbers(): void
    {
        $response = $this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), [
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Bayar listrik bertopeng',
            'lines' => [
                ['account_id' => $this->beban->id, 'debit' => '1.500.000', 'credit' => ''],
                ['account_id' => $this->kas->id, 'debit' => '', 'credit' => '1.500.000'],
            ],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('akuntansi-jurnal.index'));
        $this->assertDatabaseHas('akuntansi_journal_lines', [
            'akuntansi_account_id' => $this->beban->id,
            'debit' => 1500000,
        ]);
        $this->assertDatabaseHas('akuntansi_journal_lines', [
            'akuntansi_account_id' => $this->kas->id,
            'credit' => 1500000,
        ]);
    }

    public function test_inactive_account_cannot_be_posted(): void
    {
        $bank = AkuntansiAccount::forMitra($this->mitra->id)->where('code', '10122')->firstOrFail();
        $this->assertFalse($bank->is_active);

        $response = $this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), [
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Ke akun nonaktif',
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->kas->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseMissing('akuntansi_journal_entries', ['description' => 'Ke akun nonaktif']);
    }

    public function test_create_page_shows_plain_account_labels_and_examples(): void
    {
        $this->actingAs($this->owner)->get(route('akuntansi-jurnal.create'))
            ->assertOk()
            ->assertSee('(Aset)', false)
            ->assertSee('Contoh jurnal umum')
            ->assertSee('Hutang BOP');
    }

    public function test_date_range_filter_returns_only_matching_entries(): void
    {
        $this->makeEntry('Jurnal awal bulan', '2026-10-02');
        $this->makeEntry('Jurnal akhir bulan', '2026-10-28');

        $this->actingAs($this->owner)
            ->get(route('akuntansi-jurnal.index', ['from' => '2026-10-01', 'to' => '2026-10-10']))
            ->assertOk()
            ->assertSee('Jurnal awal bulan')
            ->assertDontSee('Jurnal akhir bulan');
    }

    public function test_invalid_date_range_returns_error_instead_of_crash(): void
    {
        $this->actingAs($this->owner)
            ->get(route('akuntansi-jurnal.index', ['from' => '2026-10-10', 'to' => '2026-10-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_text_filter_matches_description(): void
    {
        $this->makeEntry('Bayar listrik Oktober', '2026-10-05');
        $this->makeEntry('Beli gas elpiji', '2026-10-06');

        $this->actingAs($this->owner)
            ->get(route('akuntansi-jurnal.index', ['q' => 'listrik']))
            ->assertOk()
            ->assertSee('Bayar listrik Oktober')
            ->assertDontSee('Beli gas elpiji');
    }

    public function test_account_filter_returns_only_entries_touching_that_account(): void
    {
        $lain = AkuntansiAccount::forMitra($this->mitra->id)->where('code', '10113')->firstOrFail();

        $this->makeEntry('Jurnal ke beban', '2026-10-05');
        app(AkuntansiJournalService::class)->createManual(
            mitraId: $this->mitra->id,
            userId: $this->owner->id,
            date: Carbon::parse('2026-10-06'),
            description: 'Jurnal kas kecil',
            lines: [
                ['account_id' => $lain->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $this->kas->id, 'debit' => 0, 'credit' => 5000],
            ],
        );

        $this->actingAs($this->owner)
            ->get(route('akuntansi-jurnal.index', ['account_id' => $lain->id]))
            ->assertOk()
            ->assertSee('Jurnal kas kecil')
            ->assertDontSee('Jurnal ke beban');
    }

    public function test_pagination_links_keep_the_filters(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $this->makeEntry("Biaya harian {$i}", '2026-10-05', 1000 + $i);
        }

        $response = $this->actingAs($this->owner)
            ->get(route('akuntansi-jurnal.index', ['q' => 'Biaya harian', 'from' => '2026-10-01']));

        $response->assertOk();
        $this->assertStringContainsString('q=Biaya%20harian', html_entity_decode($response->getContent()));
        $this->assertStringContainsString('from=2026-10-01', html_entity_decode($response->getContent()));
        $this->assertStringContainsString('page=2', html_entity_decode($response->getContent()));
    }

    public function test_index_shows_result_count_line(): void
    {
        $this->makeEntry('Bayar listrik', '2026-10-05');

        $this->actingAs($this->owner)
            ->get(route('akuntansi-jurnal.index', ['q' => 'listrik']))
            ->assertSee('Ditemukan')
            ->assertSee('jurnal sesuai filter');
    }

    private function storePayload(array $lines): array
    {
        return [
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Uji validasi',
            'lines' => $lines,
        ];
    }

    private function assertNothingSaved($response): void
    {
        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseMissing('akuntansi_journal_entries', ['description' => 'Uji validasi']);
    }

    public function test_http_rejects_unbalanced_entry_by_a_fraction(): void
    {
        $this->assertNothingSaved($this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), $this->storePayload([
            ['account_id' => $this->beban->id, 'debit' => '0,02', 'credit' => ''],
            ['account_id' => $this->kas->id, 'debit' => '', 'credit' => '0,01'],
        ])));
    }

    public function test_http_rejects_row_with_both_debit_and_credit(): void
    {
        $this->assertNothingSaved($this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), $this->storePayload([
            ['account_id' => $this->beban->id, 'debit' => '1.000', 'credit' => '500'],
            ['account_id' => $this->kas->id, 'debit' => '', 'credit' => '500'],
        ])));
    }

    public function test_http_rejects_zero_and_sub_cent_amounts(): void
    {
        $this->assertNothingSaved($this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), $this->storePayload([
            ['account_id' => $this->beban->id, 'debit' => '0', 'credit' => ''],
            ['account_id' => $this->kas->id, 'debit' => '', 'credit' => '0'],
        ])));

        $this->assertNothingSaved($this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), $this->storePayload([
            ['account_id' => $this->beban->id, 'debit' => '0,004', 'credit' => ''],
            ['account_id' => $this->kas->id, 'debit' => '', 'credit' => '0,004'],
        ])));
    }

    public function test_http_rejects_another_mitras_account(): void
    {
        $other = $this->mitra->replicate();
        $other->code = 'LAIN-01';
        $other->save();
        $foreign = AkuntansiAccount::create([
            'mitra_id' => $other->id,
            'code' => '99999',
            'name' => 'AKUN MITRA LAIN',
            'level' => 3,
            'account_type' => 'aset',
            'position' => 'neraca',
            'is_postable' => true,
            'is_active' => true,
        ]);

        $this->assertNothingSaved($this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), $this->storePayload([
            ['account_id' => $foreign->id, 'debit' => '1.000', 'credit' => ''],
            ['account_id' => $this->kas->id, 'debit' => '', 'credit' => '1.000'],
        ])));
    }

    public function test_http_rejects_non_postable_account(): void
    {
        $header = AkuntansiAccount::forMitra($this->mitra->id)->where('is_postable', false)->firstOrFail();

        $this->assertNothingSaved($this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), $this->storePayload([
            ['account_id' => $header->id, 'debit' => '1.000', 'credit' => ''],
            ['account_id' => $this->kas->id, 'debit' => '', 'credit' => '1.000'],
        ])));
    }

    public function test_account_filter_shows_summary_of_that_accounts_lines_only(): void
    {
        $this->beban->update(['opening_balance' => 7000]);
        $this->makeEntry('Bayar A', '2026-10-05', 10000);
        $this->makeEntry('Bayar B', '2026-10-06', 2500);

        $this->actingAs($this->owner)
            ->get(route('akuntansi-jurnal.index', ['account_id' => $this->beban->id]))
            ->assertOk()
            ->assertSee('Saldo awal')
            ->assertSee('Rp 7.000')
            ->assertSee('Rp 12.500');
    }
}
