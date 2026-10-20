<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiAccount;
use App\Models\Mitra;
use App\Models\PosTransaction;
use App\Models\Role;
use App\Models\User;
use App\Services\MitraPos\AkuntansiJournalService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LiveFilterSelect2Test extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();

        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-livefilter@internal.test',
            'password' => bcrypt('password'),
            'role_id' => $superAdmin->id,
            'mitra_id' => null,
        ]);
    }

    private function results(string $html): string
    {
        $position = strpos($html, 'id="live-results"');
        $this->assertNotFalse($position, 'Kotak hasil live-results tidak ditemukan.');

        return substr($html, $position);
    }

    private function assertLiveShell($response): void
    {
        $response->assertOk();
        $response->assertSee('data-live-filter', false);
        $response->assertSee('id="live-results"', false);
        $response->assertDontSee('>Cari</button>', false);
        $response->assertDontSee('>Reset</a>', false);
        $response->assertDontSee('>Tampilkan</button>', false);
    }

    public function test_material_page_has_live_filter_and_select2(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mitra-material.index', $this->mitra));

        $this->assertLiveShell($response);
        $response->assertSee('js-select2', false);
        $response->assertSee('id="purchase_material_id"', false);
    }

    public function test_material_filter_returns_only_matching_rows_in_results(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('mitra-material.index', ['mitra' => $this->mitra, 'q' => 'SHB021']))
            ->assertOk()
            ->getContent();

        $results = explode('<!-- Adjust Stock Modal', $this->results($html))[0];
        $this->assertStringContainsString('LALLO BLEND 1000 GR', $results);
        $this->assertStringNotContainsString('PRISTINE WATER 400ML', $results);
    }

    public function test_product_page_has_live_filter(): void
    {
        $this->assertLiveShell($this->actingAs($this->admin)->get(route('mitra-product.index', $this->mitra)));
    }

    public function test_product_filter_returns_only_matching_rows_in_results(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('mitra-product.index', ['mitra' => $this->mitra, 'q' => 'AIR MINERAL']))
            ->assertOk()
            ->getContent();

        $results = $this->results($html);
        $this->assertStringContainsString('AIR MINERAL', $results);
        $this->assertStringNotContainsString('KOPI SUSU BAPAK DINGIN', $results);
    }

    public function test_transaction_page_has_live_filter_and_select2(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mitra-pos-manage.transaction.index', $this->mitra));

        $this->assertLiveShell($response);
        $response->assertSee('js-select2', false);
    }

    public function test_transaction_filter_returns_only_matching_rows_in_results(): void
    {
        foreach (['0001', '0002'] as $suffix) {
            PosTransaction::create([
                'mitra_id' => $this->mitra->id,
                'transaction_no' => 'POS/LALLO/20260101/'.$suffix,
                'sales_mode' => 'dine_in',
                'payment_method' => 'cash',
                'subtotal' => 10000,
                'discount' => 0,
                'grand_total' => 10000,
                'total_hpp' => 0,
                'total_cogs' => 0,
                'status' => 'completed',
                'transacted_at' => now(),
            ]);
        }

        $html = $this->actingAs($this->admin)
            ->get(route('mitra-pos-manage.transaction.index', ['mitra' => $this->mitra, 'q' => '0002']))
            ->assertOk()
            ->getContent();

        $results = $this->results($html);
        $this->assertStringContainsString('POS/LALLO/20260101/0002', $results);
        $this->assertStringNotContainsString('POS/LALLO/20260101/0001', $results);
    }

    public function test_journal_page_has_live_filter_and_select2(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mitra-pos-manage.akuntansi-jurnal.index', $this->mitra));

        $this->assertLiveShell($response);
        $response->assertSee('js-select2', false);
        $response->assertSee('id="filter-account"', false);
    }

    public function test_journal_filter_returns_only_matching_rows_and_summary_inside_results(): void
    {
        $kas = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'kas_kasir')->firstOrFail();
        $beban = AkuntansiAccount::forMitra($this->mitra->id)->where('code', '40167')->firstOrFail();

        foreach (['Bayar listrik bulan ini', 'Beli galon air'] as $description) {
            app(AkuntansiJournalService::class)->createManual(
                mitraId: $this->mitra->id,
                userId: $this->admin->id,
                date: Carbon::parse('2026-10-05'),
                description: $description,
                lines: [
                    ['account_id' => $beban->id, 'debit' => 10000, 'credit' => 0],
                    ['account_id' => $kas->id, 'debit' => 0, 'credit' => 10000],
                ],
            );
        }

        $html = $this->actingAs($this->admin)
            ->get(route('mitra-pos-manage.akuntansi-jurnal.index', [
                'mitra' => $this->mitra,
                'q' => 'listrik',
                'account_id' => $beban->id,
            ]))
            ->assertOk()
            ->getContent();

        $results = $this->results($html);
        $this->assertStringContainsString('Bayar listrik bulan ini', $results);
        $this->assertStringNotContainsString('Beli galon air', $results);
        $this->assertStringContainsString('Saldo awal', $results);
    }
}
