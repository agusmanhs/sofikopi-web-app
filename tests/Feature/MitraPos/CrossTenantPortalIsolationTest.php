<?php

namespace Tests\Feature\MitraPos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\MitraPos\Concerns\BuildsCrossTenantFixture;
use Tests\TestCase;

/**
 * Matrix 1, 5, 6 and 7: mitra A acting inside the tenant portal
 * (/mitra-pos/*) must never read or change anything that belongs to mitra B.
 */
class CrossTenantPortalIsolationTest extends TestCase
{
    use BuildsCrossTenantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildCrossTenantFixture();
    }

    public static function portalChildRoutes(): array
    {
        $names = ['pos-transaction.show', 'pos-transaction.receipt', 'pos-transaction.void', 'mitra-stock.adjust', 'mitra-opname.show', 'mitra-kasir-user.update', 'mitra-kasir-user.destroy'];

        return array_combine($names, array_map(fn ($name) => [$name], $names));
    }

    public static function portalListRoutes(): array
    {
        $names = [
            'mitra-dashboard.index', 'pos.index', 'pos.products', 'pos-transaction.index', 'mitra-stock.index',
            'mitra-stock.movements', 'mitra-setting.index', 'mitra-report.index', 'mitra-opname.index',
            'mitra-opname.create', 'mitra-kasir-user.index', 'akuntansi-coa.index', 'akuntansi-jurnal.index',
            'akuntansi-jurnal.create', 'akuntansi-neraca.index', 'akuntansi-laba-rugi.index',
        ];

        return array_combine($names, array_map(fn ($name) => [$name], $names));
    }

    public static function foreignFilterRequests(): array
    {
        return [
            'transactions filtered by B transaction_no' => ['pos-transaction.index', ['q' => 'ZZB-MITRA']],
            'transactions filtered by B kasir id' => ['pos-transaction.index', ['user_id' => '{kasir_b}']],
            'stock list searching B sku' => ['mitra-stock.index', ['q' => 'ZZB-MAT-001']],
            'stock list searching B name' => ['mitra-stock.index', ['q' => 'ZZB-RAHASIA']],
            'movements filtered by B material id' => ['mitra-stock.movements', ['material_id' => '{material_b}']],
            'movements searching B marker' => ['mitra-stock.movements', ['q' => 'ZZB-RAHASIA']],
            'journal filtered by B account id' => ['akuntansi-jurnal.index', ['account_id' => '{account_b}']],
            'journal filtered by B transaction_no' => ['akuntansi-jurnal.index', ['transaction' => '{transaction_b}']],
            'journal searching B description' => ['akuntansi-jurnal.index', ['q' => 'ZZB-RAHASIA']],
        ];
    }

    public function test_fixture_sanity_owner_b_can_open_every_b_row_that_a_must_not_see(): void
    {
        $this->actingAs($this->ownerB)
            ->get($this->urlFor('pos-transaction.show', null, $this->keys($this->idsB)))
            ->assertOk()
            ->assertSee('ZZB-RAHASIA');

        $this->actingAs($this->ownerB)
            ->get($this->urlFor('mitra-opname.show', null, $this->keys($this->idsB)))
            ->assertOk();

        $this->actingAs($this->ownerB)->get(route('mitra-kasir-user.index'))->assertOk()->assertSee('Kasir ZZB-RAHASIA');
        $this->actingAs($this->ownerB)->get(route('mitra-stock.index'))->assertOk()->assertSee('ZZB-MAT-001');
    }

    #[DataProvider('portalChildRoutes')]
    public function test_mitra_a_owner_cannot_reach_mitra_b_child_row_in_portal(string $name): void
    {
        $before = ['A' => $this->tenantFingerprint($this->mitraA->id), 'B' => $this->tenantFingerprint($this->mitraB->id)];
        $method = $this->routeMethod($name);

        $response = $this->send(
            $this->ownerA,
            $method,
            $this->urlFor($name, null, $this->keys($this->idsB)),
            $this->payloadFor($name, $this->idsB)
        );

        $this->assertBlockedOrRejected($response, "{$method} {$name} as owner A with B identifiers", $method === 'GET');
        $this->assertNoTenantMarkerLeak($response, "{$method} {$name} as owner A");
        $this->assertSame($before['B'], $this->tenantFingerprint($this->mitraB->id), "{$name}: mitra B data was modified by mitra A");
        $this->assertSame($before['A'], $this->tenantFingerprint($this->mitraA->id), "{$name}: mitra A data changed unexpectedly");
    }

    #[DataProvider('portalChildRoutes')]
    public function test_mitra_a_kasir_cannot_reach_mitra_b_child_row_in_portal(string $name): void
    {
        $before = $this->tenantFingerprint($this->mitraB->id);
        $method = $this->routeMethod($name);

        $response = $this->send(
            $this->kasirA,
            $method,
            $this->urlFor($name, null, $this->keys($this->idsB)),
            $this->payloadFor($name, $this->idsB)
        );

        $this->assertBlockedOrRejected($response, "{$method} {$name} as kasir A with B identifiers", $method === 'GET');
        $this->assertSame($before, $this->tenantFingerprint($this->mitraB->id), "{$name}: mitra B data was modified by mitra A kasir");
    }

    #[DataProvider('portalListRoutes')]
    public function test_portal_pages_show_only_mitra_a_data_to_its_owner(string $name): void
    {
        $response = $this->actingAs($this->ownerA)->get($this->urlFor($name, null, []));

        $response->assertOk();
        $this->assertNoTenantMarkerLeak($response, "GET {$name} as owner A");
        $this->assertListScopedToMitraA($name, $response);
    }

    #[DataProvider('foreignFilterRequests')]
    public function test_filters_pointing_at_mitra_b_rows_return_nothing_for_mitra_a(string $name, array $query): void
    {
        $query = array_map(fn ($value) => $this->resolveFilterToken($value), $query);

        $response = $this->actingAs($this->ownerA)->get(route($name, $query));

        $response->assertOk();
        $this->assertNoTenantMarkerLeak($response, "GET {$name} ".json_encode($query), array_map('strval', array_values($query)));
    }

    public function test_foreign_account_filter_produces_no_account_summary(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('akuntansi-jurnal.index', ['account_id' => $this->idsB['account_kas_id']]));

        $response->assertOk();
        $this->assertNull($response->viewData('accountSummary'));
    }

    public function test_kasir_user_list_contains_only_own_mitra_kasir(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('mitra-kasir-user.index'));

        $response->assertOk()->assertDontSee('kasir@zzb-rahasia.test')->assertDontSee('Kasir ZZB-RAHASIA');
        $this->assertSame([$this->kasirA->id], $response->viewData('kasirs')->pluck('id')->all());
        $this->assertSame([$this->kasirA->id], array_keys($response->viewData('transactionCounts')));
    }

    public function test_owner_a_cannot_reset_password_or_rename_mitra_b_kasir(): void
    {
        $passwordBefore = $this->kasirB->fresh()->password;

        $response = $this->actingAs($this->ownerA)->put(route('mitra-kasir-user.update', $this->kasirB), [
            'name' => 'Diretas Owner A',
            'email' => $this->kasirB->email,
            'password' => 'password-baru-diretas',
        ]);

        $response->assertNotFound();
        $this->assertSame($passwordBefore, $this->kasirB->fresh()->password);
        $this->assertSame('Kasir ZZB-RAHASIA', $this->kasirB->fresh()->name);
    }

    public function test_owner_a_cannot_delete_mitra_b_kasir(): void
    {
        $this->actingAs($this->ownerA)->delete(route('mitra-kasir-user.destroy', $this->kasirB))->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $this->kasirB->id, 'mitra_id' => $this->mitraB->id]);
    }

    public function test_owner_a_cannot_edit_or_delete_mitra_b_owner_account(): void
    {
        $this->actingAs($this->ownerA)->put(route('mitra-kasir-user.update', $this->ownerB), [
            'name' => 'Diretas',
            'email' => $this->ownerB->email,
            'password' => 'password-baru-diretas',
        ])->assertNotFound();

        $this->actingAs($this->ownerA)->delete(route('mitra-kasir-user.destroy', $this->ownerB))->assertNotFound();
        $this->assertSame('Owner ZZB-RAHASIA', $this->ownerB->fresh()->name);
    }

    public function test_dashboard_numbers_only_include_mitra_a(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('mitra-dashboard.index'));

        $stats = $response->viewData('stats');
        $expected = (float) DB::table('pos_transactions')->where('mitra_id', $this->mitraA->id)->where('status', 'completed')->sum('grand_total');

        $this->assertEqualsWithDelta($expected, $stats['revenue_today'], 0.01);
        $this->assertEqualsWithDelta($expected, $stats['revenue_month'], 0.01);
        $this->assertSame(1, $stats['tx_count_today']);
        $this->assertSame(1, $stats['tx_count_month']);
        $this->assertLessThan(777777, $stats['revenue_month']);
    }

    public function test_neraca_numbers_only_include_mitra_a_ledger(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('akuntansi-neraca.index', ['as_of_date' => '2026-10-22']));
        $neraca = $response->viewData('neraca');

        $this->assertEqualsWithDelta($this->ledgerCash($this->mitraA->id), $neraca['total_kas'], 0.01);
        $this->assertNotEqualsWithDelta($this->ledgerCash($this->mitraB->id), $neraca['total_kas'], 0.01);
    }

    public function test_laba_rugi_numbers_only_include_mitra_a_ledger(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('akuntansi-laba-rugi.index'));
        $report = $response->viewData('labaRugi');

        $this->assertEqualsWithDelta($this->ledgerRevenue($this->mitraA->id), $report['pendapatan_total'], 0.01);
        $this->assertNotEqualsWithDelta($this->ledgerRevenue($this->mitraB->id), $report['pendapatan_total'], 0.01);
    }

    public function test_stock_movement_ledger_only_contains_mitra_a_movements(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('mitra-stock.movements'));

        $expected = DB::table('mitra_stock_movements')->where('mitra_id', $this->mitraA->id)->count();
        $this->assertSame($expected, $response->viewData('movements')->total());
        $this->assertSame([], array_diff($response->viewData('materials')->pluck('sku')->all(), DB::table('mitra_materials')->where('mitra_id', $this->mitraA->id)->pluck('sku')->all()));
    }

    public function test_pos_product_feed_never_lists_mitra_b_products(): void
    {
        $response = $this->actingAs($this->ownerA)->getJson(route('pos.products'));

        $response->assertOk();
        $this->assertStringNotContainsString('ZZB-', $response->getContent());
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($this->idsB['product_id'], $ids);
    }

    public function test_guests_are_redirected_to_login_on_every_portal_route(): void
    {
        foreach (self::allMitraRouteNames() as $name) {
            $response = $this->call($this->routeMethod($name), $this->urlFor($name, 'CAFE-LALLO-KDI', $this->keys($this->idsA)));

            $this->assertContains($response->getStatusCode(), [302, 401], "guest on {$name} got {$response->getStatusCode()}");
            if ($response->getStatusCode() === 302) {
                $this->assertStringContainsString('login', (string) $response->headers->get('Location'), "guest on {$name} was not sent to login");
            }
        }
    }

    public function test_users_without_mitra_id_are_forbidden_on_every_tenant_portal_route(): void
    {
        $portalRoutes = array_filter(self::allMitraRouteNames(), fn ($name) => ! str_contains(Route::getRoutes()->getByName($name)->uri(), 'mitra-pos/manage'));

        foreach ([$this->superAdmin, $this->internalWithoutMitra] as $user) {
            foreach ($portalRoutes as $name) {
                $response = $this->send(
                    $user,
                    $this->routeMethod($name),
                    $this->urlFor($name, null, $this->keys($this->idsA)),
                    $this->payloadFor($name, $this->idsA)
                );

                $this->assertSame(403, $response->getStatusCode(), "{$user->email} (no mitra_id) on {$name} got {$response->getStatusCode()}");
            }
        }
    }

    public function test_internal_user_without_mitra_id_cannot_use_admin_routes_even_with_owner_role(): void
    {
        $response = $this->actingAs($this->internalWithoutMitra)->get(route('mitra-pos-manage.dashboard.index', $this->mitraB->code));

        $response->assertForbidden();
    }

    public function test_mitra_users_have_no_access_to_the_activity_log(): void
    {
        foreach ([$this->ownerA, $this->kasirA, $this->ownerB] as $user) {
            foreach (['activity-log.index', 'activity-log.data', 'activity-log.statistics'] as $name) {
                $this->actingAs($user)->get(route($name))->assertForbidden();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $ids
     * @return array<string, mixed>
     */
    private function keys(array $ids): array
    {
        return [
            'material' => $ids['material_sku'],
            'product' => $ids['product_sku'],
            'transaction' => $ids['transaction_no'],
            'opname' => $ids['opname_no'],
            'user' => $ids['user_id'],
        ];
    }

    private function resolveFilterToken(string $value): string
    {
        return match ($value) {
            '{kasir_b}' => (string) $this->kasirB->id,
            '{material_b}' => (string) $this->idsB['material_id'],
            '{account_b}' => (string) $this->idsB['account_kas_id'],
            '{transaction_b}' => $this->idsB['transaction_no'],
            default => $value,
        };
    }

    private function ledgerCash(int $mitraId): float
    {
        return (float) DB::table('akuntansi_journal_lines as l')
            ->join('akuntansi_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('akuntansi_accounts as a', 'a.id', '=', 'l.akuntansi_account_id')
            ->where('e.mitra_id', $mitraId)
            ->whereIn('a.system_role', ['kas_tunai', 'kas_kasir'])
            ->sum(DB::raw('l.debit - l.credit'));
    }

    private function ledgerRevenue(int $mitraId): float
    {
        return (float) DB::table('akuntansi_journal_lines as l')
            ->join('akuntansi_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('akuntansi_accounts as a', 'a.id', '=', 'l.akuntansi_account_id')
            ->where('e.mitra_id', $mitraId)
            ->where('a.account_type', 'pendapatan')
            ->sum(DB::raw('l.credit - l.debit'));
    }
}
