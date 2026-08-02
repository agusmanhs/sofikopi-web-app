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
use RuntimeException;
use Tests\TestCase;

class AkuntansiManualJournalTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $kasir;

    private User $owner;

    private AkuntansiJournalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->service = app(AkuntansiJournalService::class);
    }

    public function test_balanced_manual_entry_is_saved(): void
    {
        $kas = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'kas_kasir')->firstOrFail();
        $beban = AkuntansiAccount::forMitra($this->mitra->id)->where('code', '40167')->firstOrFail();

        $entry = $this->service->createManual(
            mitraId: $this->mitra->id,
            userId: $this->owner->id,
            date: Carbon::now(),
            description: 'Bayar biaya admin bank',
            lines: [
                ['account_id' => $beban->id, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $kas->id, 'debit' => 0, 'credit' => 50000],
            ],
        );

        $this->assertSame('manual', $entry->source_type);
        $this->assertCount(2, $entry->lines);
        $this->assertDatabaseHas('akuntansi_journal_lines', [
            'journal_entry_id' => $entry->id,
            'akuntansi_account_id' => $kas->id,
            'credit' => 50000,
        ]);
    }

    public function test_unbalanced_manual_entry_is_rejected(): void
    {
        $kas = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'kas_kasir')->firstOrFail();
        $beban = AkuntansiAccount::forMitra($this->mitra->id)->where('code', '40167')->firstOrFail();

        $this->expectException(RuntimeException::class);

        $this->service->createManual(
            mitraId: $this->mitra->id,
            userId: $this->owner->id,
            date: Carbon::now(),
            description: 'Tidak balance',
            lines: [
                ['account_id' => $beban->id, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $kas->id, 'debit' => 0, 'credit' => 40000],
            ],
        );
    }

    public function test_owner_can_submit_manual_journal_via_http(): void
    {
        $kas = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'kas_kasir')->firstOrFail();
        $beban = AkuntansiAccount::forMitra($this->mitra->id)->where('code', '40167')->firstOrFail();

        $this->actingAs($this->owner)->get(route('akuntansi-jurnal.create'))->assertOk();

        $response = $this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), [
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Bayar biaya admin bank via HTTP',
            'lines' => [
                ['account_id' => $beban->id, 'debit' => 25000, 'credit' => 0],
                ['account_id' => $kas->id, 'debit' => 0, 'credit' => 25000],
            ],
        ]);

        $response->assertRedirect(route('akuntansi-jurnal.index'));
        $this->assertDatabaseHas('akuntansi_journal_entries', [
            'mitra_id' => $this->mitra->id,
            'source_type' => 'manual',
            'description' => 'Bayar biaya admin bank via HTTP',
        ]);
    }

    public function test_http_rejects_unbalanced_manual_journal_with_error_not_a_crash(): void
    {
        $kas = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'kas_kasir')->firstOrFail();
        $beban = AkuntansiAccount::forMitra($this->mitra->id)->where('code', '40167')->firstOrFail();

        $response = $this->actingAs($this->owner)->post(route('akuntansi-jurnal.store'), [
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Tidak balance via HTTP',
            'lines' => [
                ['account_id' => $beban->id, 'debit' => 25000, 'credit' => 0],
                ['account_id' => $kas->id, 'debit' => 0, 'credit' => 10000],
            ],
        ]);

        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseMissing('akuntansi_journal_entries', [
            'description' => 'Tidak balance via HTTP',
        ]);
    }

    public function test_kasir_cannot_view_or_submit_manual_journal(): void
    {
        $this->actingAs($this->kasir)->get(route('akuntansi-jurnal.create'))->assertForbidden();
        $this->actingAs($this->kasir)->post(route('akuntansi-jurnal.store'), [])->assertForbidden();
    }
}
