<?php

namespace Tests\Feature\MitraPos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Feature\MitraPos\Concerns\BuildsCrossTenantFixture;
use Tests\TestCase;

class CrossTenantAdminRoleTamperTest extends TestCase
{
    use BuildsCrossTenantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildCrossTenantFixture();
    }

    private function keys(array $ids): array
    {
        return [
            'material' => $ids['material_sku'], 'product' => $ids['product_sku'],
            'transaction' => $ids['transaction_no'], 'opname' => $ids['opname_no'], 'user' => $ids['user_id'],
        ];
    }

    private function adminRoutes(): array
    {
        return array_values(array_filter(self::allMitraRouteNames(), fn ($n) => in_array('mitra', Route::getRoutes()->getByName($n)->parameterNames(), true)));
    }

    private function portalRoutes(): array
    {
        return array_values(array_filter(self::allMitraRouteNames(), fn ($n) => ! str_contains(Route::getRoutes()->getByName($n)->uri(), 'mitra-pos/manage')));
    }

    public function test_mitra_users_get_403_on_every_admin_route_for_b_and_for_their_own_slug(): void
    {
        $before = $this->tenantFingerprint($this->mitraB->id);
        $failures = [];
        foreach ([$this->ownerA, $this->kasirA] as $user) {
            foreach ([[$this->mitraB->code, $this->idsB], [$this->mitraA->code, $this->idsA]] as [$code, $ids]) {
                foreach ($this->adminRoutes() as $name) {
                    $r = $this->send($user, $this->routeMethod($name), $this->urlFor($name, $code, $this->keys($ids)), $this->payloadFor($name, $ids));
                    if ($r->getStatusCode() !== 403) {
                        $failures[] = "{$user->email} {$name} {$code} => {$r->getStatusCode()}";
                    }
                }
            }
        }
        $this->assertSame([], $failures);
        $this->assertSame($before, $this->tenantFingerprint($this->mitraB->id));
    }

    public function test_mitra_users_cannot_use_picker_enroll_or_bulk_wipe(): void
    {
        $before = $this->tenantFingerprint($this->mitraB->id);
        foreach ([$this->ownerA, $this->kasirA] as $user) {
            $this->actingAs($user)->get(route('mitra-pos-manage.index'))->assertForbidden();
            $this->actingAs($user)->post(route('mitra-pos-manage.store'), ['mitra_id' => $this->mitraB->id])->assertForbidden();
            $this->actingAs($user)->delete(route('mitra-pos-manage.destroy-bulk'), ['mitra_ids' => [$this->mitraB->id]])->assertForbidden();
        }
        $this->assertSame($before, $this->tenantFingerprint($this->mitraB->id));
    }

    public function test_super_admin_scoped_bindings_never_resolve_b_rows_under_a_url(): void
    {
        $beforeA = $this->tenantFingerprint($this->mitraA->id);
        $beforeB = $this->tenantFingerprint($this->mitraB->id);
        $failures = [];
        foreach ($this->adminRoutes() as $name) {
            $route = Route::getRoutes()->getByName($name);
            $method = $this->routeMethod($name);
            $hasChild = count($route->parameterNames()) > 1;
            if (($method === 'GET' && ! $hasChild) || in_array($name, ['mitra-pos-manage.destroy', 'mitra-material.store', 'mitra-pos-manage.setting.update'], true)) {
                continue;
            }
            $r = $this->send($this->superAdmin, $method, $this->urlFor($name, $this->mitraA->code, $this->keys($this->idsB)), $this->payloadFor($name, $this->idsB));
            $s = $r->getStatusCode();
            $ok = in_array($s, [403, 404, 422], true) || ($s === 302 && (session()->has('errors') || session()->has('error')));
            if (! $ok || ($s === 200 && str_contains((string) $r->getContent(), self::B_MARK))) {
                $failures[] = "{$method} {$name} => {$s}";
            }
        }
        $this->assertSame([], $failures);
        $this->assertSame($beforeB, $this->tenantFingerprint($this->mitraB->id), 'mitra B modified via mitra A admin URL');
        $this->assertSame($beforeA, $this->tenantFingerprint($this->mitraA->id), 'mitra A modified by B-referencing requests');
    }

    public function test_super_admin_list_pages_under_a_url_show_only_a_data(): void
    {
        foreach ($this->adminRoutes() as $name) {
            if ($this->routeMethod($name) !== 'GET' || count(Route::getRoutes()->getByName($name)->parameterNames()) > 1 || str_contains($name, 'export')) {
                continue;
            }
            $r = $this->actingAs($this->superAdmin)->get($this->urlFor($name, $this->mitraA->code, []));
            $r->assertOk();
            $this->assertNoTenantMarkerLeak($r, $name);
            $this->assertListScopedToMitraA($name, $r);
        }
    }

    public function test_wiping_mitra_a_leaves_mitra_b_untouched(): void
    {
        $before = $this->tenantFingerprint($this->mitraB->id);
        $this->actingAs($this->superAdmin)->delete(route('mitra-pos-manage.destroy', $this->mitraA->code));
        $this->assertSame($before, $this->tenantFingerprint($this->mitraB->id));
    }

    public function test_role_matrix_for_owner_and_kasir_in_portal(): void
    {
        $allow = ['pos.products', 'pos.index', 'pos.store', 'pos-transaction.index', 'pos-transaction.show', 'pos-transaction.receipt', 'mitra-stock.movements', 'mitra-stock.index'];
        $failures = [];
        foreach ($this->portalRoutes() as $name) {
            $method = $this->routeMethod($name);
            $before = $this->tenantFingerprint($this->mitraA->id);
            $r = $this->send($this->kasirA, $method, $this->urlFor($name, null, $this->keys($this->idsA)), $this->payloadFor($name, $this->idsA));
            $expectedDenied = ! in_array($name, $allow, true);
            if ($expectedDenied && $r->getStatusCode() !== 403) {
                $failures[] = "kasir {$method} {$name} => {$r->getStatusCode()} (intended 403)";
            }
            if ($expectedDenied && $method !== 'GET') {
                $this->assertSame($before, $this->tenantFingerprint($this->mitraA->id), "kasir {$name} modified data");
            }
            if (! $expectedDenied && $r->getStatusCode() === 403) {
                $failures[] = "kasir {$name} => 403 (intended allowed)";
            }
        }
        foreach ($this->portalRoutes() as $name) {
            $r = $this->send($this->ownerA, $this->routeMethod($name), $this->urlFor($name, null, $this->keys($this->idsA)), $this->payloadFor($name, $this->idsA));
            if (in_array($r->getStatusCode(), [403, 404, 401], true)) {
                $failures[] = "owner {$name} => {$r->getStatusCode()} (intended allowed)";
            }
        }
        $this->assertSame([], $failures);
    }

    public function test_kasir_transaction_detail_hides_profit_figures(): void
    {
        $r = $this->actingAs($this->kasirA)->get(route('pos-transaction.show', $this->idsA['transaction_no']));
        $r->assertOk();
        $this->assertStringNotContainsString('Laba Kotor', $r->getContent(), 'kasir sees profit on transaction detail');
    }

    public function test_tampering_with_mitra_id_role_id_and_foreign_ids_creates_nothing_under_b(): void
    {
        $before = $this->tenantFingerprint($this->mitraB->id);
        $roleId = $this->superAdmin->role_id;

        $this->actingAs($this->ownerA)->post(route('mitra-kasir-user.store'), [
            'name' => 'Tamper', 'email' => 'tamper@evil.test', 'password' => 'secret123',
            'mitra_id' => $this->mitraB->id, 'role_id' => $roleId,
        ]);
        $user = DB::table('users')->where('email', 'tamper@evil.test')->first();
        $this->assertNotNull($user);
        $this->assertSame($this->mitraA->id, (int) $user->mitra_id);
        $this->assertNotSame($roleId, (int) $user->role_id);

        $this->actingAs($this->ownerA)->put(route('mitra-setting.update'), $this->settingPayload() + ['mitra_id' => $this->mitraB->id]);
        $this->actingAs($this->ownerA)->post(route('mitra-opname.store'), ['physical_qty' => [$this->idsB['material_id'] => 1], 'mitra_id' => $this->mitraB->id]);
        $this->actingAs($this->ownerA)->post(route('akuntansi-jurnal.store'), $this->payloadFor('akuntansi-jurnal.store', $this->idsB) + ['mitra_id' => $this->mitraB->id]);
        $this->actingAs($this->ownerA)->put(route('akuntansi-coa.update'), $this->payloadFor('akuntansi-coa.update', $this->idsB));
        $this->actingAs($this->ownerA)->postJson(route('pos.store'), $this->payloadFor('pos.store', $this->idsB) + ['mitra_id' => $this->mitraB->id]);

        $this->assertSame($before, $this->tenantFingerprint($this->mitraB->id));
        $this->assertSame(0, $this->orphanTenantRowCount());
    }

    public function test_super_admin_material_store_ignores_mitra_id_in_body(): void
    {
        $this->actingAs($this->superAdmin)->post(route('mitra-material.store', $this->mitraA->code), $this->materialPayload('HACK-MAT') + ['mitra_id' => $this->mitraB->id]);
        $this->assertSame(0, DB::table('mitra_materials')->where('mitra_id', $this->mitraB->id)->where('sku', 'HACK-MAT')->count());
    }
}
