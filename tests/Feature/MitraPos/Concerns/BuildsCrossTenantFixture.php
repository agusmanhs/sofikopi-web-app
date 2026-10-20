<?php

namespace Tests\Feature\MitraPos\Concerns;

use App\Models\AkuntansiAccount;
use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\MitraMaterial;
use App\Models\MitraPosSetting;
use App\Models\MitraProduct;
use App\Models\MitraStockOpname;
use App\Models\PosTransaction;
use App\Models\Role;
use App\Models\User;
use App\Services\MitraPos\AkuntansiCoaService;
use App\Services\MitraPos\AkuntansiJournalService;
use App\Services\MitraPos\MitraMaterialService;
use App\Services\MitraPos\MitraProductService;
use App\Services\MitraPos\MitraStockService;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

trait BuildsCrossTenantFixture
{
    protected const B_MARK = 'ZZB-';

    protected Mitra $mitraA;

    protected Mitra $mitraB;

    protected User $ownerA;

    protected User $kasirA;

    protected User $ownerB;

    protected User $kasirB;

    protected User $superAdmin;

    protected User $internalWithoutMitra;

    /** @var array<string, mixed> */
    protected array $idsA = [];

    /** @var array<string, mixed> */
    protected array $idsB = [];

    protected function buildCrossTenantFixture(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 21, 10, 0, 0));

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $ownerRole = Role::where('slug', 'mitra-owner')->firstOrFail();
        $kasirRole = Role::where('slug', 'mitra-kasir')->firstOrFail();
        $superRole = Role::where('slug', 'super-admin')->firstOrFail();

        $this->mitraA = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->ownerA = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->kasirA = User::where('email', 'kasir@cafelallo.test')->firstOrFail();

        $this->buildMitraB($ownerRole, $kasirRole);

        $this->superAdmin = $this->makeUser('Super Admin', 'superadmin@internal.test', $superRole, null);
        $this->internalWithoutMitra = $this->makeUser('Staff Tanpa Mitra', 'staff-owner-role@internal.test', $ownerRole, null);

        $this->idsA = $this->populateMitraA();
        $this->idsB = $this->populateMitraB();
    }

    private function makeUser(string $name, string $email, Role $role, ?int $mitraId): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'mitra_id' => $mitraId,
        ]);
    }

    private function buildMitraB(Role $ownerRole, Role $kasirRole): void
    {
        $category = MitraCategory::firstOrCreate(['name' => 'Cafe'], ['is_active' => true]);

        $this->mitraB = Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => 'ZZB-MITRA',
            'pic' => 'PIC ZZB-RAHASIA',
            'name' => 'Mitra ZZB-RAHASIA',
            'address' => 'Jl. ZZB-RAHASIA No. 1',
            'is_active' => true,
        ]);

        MitraPosSetting::create([
            'mitra_id' => $this->mitraB->id,
            'receipt_footer' => 'Footer ZZB-RAHASIA',
            'service_charge_percent' => 0,
            'tax_percent' => 0,
            'qris_fee_percent' => 0,
            'transfer_fee_percent' => 0,
            'edc_fee_percent' => 0,
        ]);

        app(AkuntansiCoaService::class)->seedForMitra($this->mitraB->id);

        $this->ownerB = $this->makeUser('Owner ZZB-RAHASIA', 'owner@zzb-rahasia.test', $ownerRole, $this->mitraB->id);
        $this->kasirB = $this->makeUser('Kasir ZZB-RAHASIA', 'kasir@zzb-rahasia.test', $kasirRole, $this->mitraB->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function populateMitraB(): array
    {
        $mitraId = $this->mitraB->id;

        $material = app(MitraMaterialService::class)->createForMitra($mitraId, [
            'sku' => 'ZZB-MAT-001',
            'name' => 'ZZB-RAHASIA Biji Kopi',
            'category' => 'ZZB-RAHASIA-KAT',
            'brand' => 'ZZB-RAHASIA-BRAND',
            'unit' => 'GR',
            'netto' => 1000,
            'price_per_pack' => 300000,
            'min_stock' => 100,
            'is_active' => true,
        ]);

        app(MitraStockService::class)->applyMovement(
            mitraId: $mitraId,
            materialId: $material->id,
            type: 'in',
            qty: 5000,
            unitCost: 300,
            notes: 'Stok awal ZZB-RAHASIA',
            userId: $this->ownerB->id,
        );

        $product = app(MitraProductService::class)->createForMitra($mitraId, [
            'sku' => 'ZZB-PRD-001',
            'name' => 'ZZB-RAHASIA Latte',
            'category' => 'ZZB-RAHASIA-KAT',
            'q_factor' => 0.2,
            'sale_price' => 777777,
            'status' => 'active',
            'ingredients' => [['mitra_material_id' => $material->id, 'qty' => 20]],
        ]);

        $checkout = app(PosTransactionService::class)->checkout(
            mitraId: $mitraId,
            userId: $this->kasirB->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 1]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );

        $journal = app(AkuntansiJournalService::class)->createManual(
            mitraId: $mitraId,
            userId: $this->ownerB->id,
            date: Carbon::now(),
            description: 'Jurnal ZZB-RAHASIA beban',
            lines: $this->journalLines($mitraId, 424242),
        );

        $opname = app(MitraStockService::class)->performOpname(
            mitraId: $mitraId,
            userId: $this->ownerB->id,
            counts: [['mitra_material_id' => $material->id, 'physical_qty' => 4000]],
            notes: 'Opname ZZB-RAHASIA',
        );

        return $this->collectIds($this->mitraB, $material, $product, $checkout['transaction'], $journal, $opname, $this->kasirB);
    }

    /**
     * @return array<string, mixed>
     */
    private function populateMitraA(): array
    {
        $mitraId = $this->mitraA->id;

        MitraPosSetting::forMitra($mitraId)->update(['receipt_footer' => 'A-PENANDA footer']);

        $material = MitraMaterial::forMitra($mitraId)->where('sku', 'ABM001')->firstOrFail();
        $product = MitraProduct::forMitra($mitraId)->where('sku', 'SLK048')->firstOrFail();

        $checkout = app(PosTransactionService::class)->checkout(
            mitraId: $mitraId,
            userId: $this->kasirA->id,
            items: [['mitra_product_id' => $product->id, 'qty' => 2]],
            discount: 0,
            salesMode: 'dine_in',
            paymentMethod: 'cash',
        );

        $journal = app(AkuntansiJournalService::class)->createManual(
            mitraId: $mitraId,
            userId: $this->ownerA->id,
            date: Carbon::now(),
            description: 'A-PENANDA jurnal manual',
            lines: $this->journalLines($mitraId, 5000),
        );

        $opname = app(MitraStockService::class)->performOpname(
            mitraId: $mitraId,
            userId: $this->ownerA->id,
            counts: [['mitra_material_id' => $material->id, 'physical_qty' => 90]],
            notes: 'A-PENANDA opname',
        );

        return $this->collectIds($this->mitraA, $material, $product, $checkout['transaction'], $journal, $opname, $this->kasirA);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function journalLines(int $mitraId, int $amount): array
    {
        $beban = AkuntansiAccount::forMitra($mitraId)->where('code', '40167')->firstOrFail();
        $kas = AkuntansiAccount::forMitra($mitraId)->where('system_role', 'kas_kasir')->firstOrFail();

        return [
            ['account_id' => $beban->id, 'debit' => $amount, 'credit' => 0],
            ['account_id' => $kas->id, 'debit' => 0, 'credit' => $amount],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectIds(Mitra $mitra, MitraMaterial $material, MitraProduct $product, PosTransaction $transaction, $journal, MitraStockOpname $opname, User $kasir): array
    {
        $mitraId = $mitra->id;

        return [
            'mitra_id' => $mitraId,
            'mitra_code' => $mitra->code,
            'material_id' => $material->id,
            'material_sku' => $material->sku,
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'transaction_no' => $transaction->transaction_no,
            'transaction_id' => $transaction->id,
            'entry_no' => $journal->entry_no,
            'opname_no' => $opname->opname_no,
            'user' => $kasir->id,
            'user_id' => $kasir->id,
            'account_beban_id' => AkuntansiAccount::forMitra($mitraId)->where('code', '40167')->value('id'),
            'account_kas_id' => AkuntansiAccount::forMitra($mitraId)->where('system_role', 'kas_kasir')->value('id'),
            'movement_id' => DB::table('mitra_stock_movements')->where('mitra_id', $mitraId)->orderBy('id')->value('id'),
        ];
    }

    /**
     * Order-stable hash of every tenant-owned row (including child tables
     * and soft-deleted rows) so any create/update/delete under a mitra is
     * detected. Raw DB::table() on purpose: Eloquent global scopes depend on
     * who is logged in and would hide exactly the rows we want to compare.
     *
     * @return array<string, string>
     */
    protected function tenantFingerprint(int $mitraId): array
    {
        $direct = [
            'mitra_materials', 'mitra_products', 'mitra_stock_movements', 'pos_transactions',
            'mitra_pos_settings', 'mitra_stock_opnames', 'akuntansi_accounts',
            'akuntansi_journal_entries', 'users',
        ];

        $fingerprint = [];
        foreach ($direct as $table) {
            $fingerprint[$table] = md5(DB::table($table)->where('mitra_id', $mitraId)->orderBy('id')->get()->toJson());
        }

        $children = [
            'pos_transaction_items' => ['pos_transaction_id', 'pos_transactions'],
            'mitra_product_ingredients' => ['mitra_product_id', 'mitra_products'],
            'mitra_stock_opname_items' => ['mitra_stock_opname_id', 'mitra_stock_opnames'],
            'akuntansi_journal_lines' => ['journal_entry_id', 'akuntansi_journal_entries'],
        ];

        foreach ($children as $table => [$fk, $parent]) {
            $parentIds = DB::table($parent)->where('mitra_id', $mitraId)->select('id');
            $fingerprint[$table] = md5(DB::table($table)->whereIn($fk, $parentIds)->orderBy('id')->get()->toJson());
        }

        $fingerprint['mitras'] = md5(DB::table('mitras')->where('id', $mitraId)->get()->toJson());

        return $fingerprint;
    }

    protected function orphanTenantRowCount(): int
    {
        $count = 0;
        foreach (['mitra_materials', 'mitra_products', 'mitra_stock_movements', 'pos_transactions', 'mitra_stock_opnames', 'akuntansi_accounts', 'akuntansi_journal_entries'] as $table) {
            $count += DB::table($table)->whereNotIn('mitra_id', [$this->mitraA->id, $this->mitraB->id])->count();
        }

        return $count;
    }

    /**
     * @return list<string>
     */
    protected static function allMitraRouteNames(): array
    {
        $names = [];
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'mitra-pos') && $route->getName()) {
                $names[] = $route->getName();
            }
        }
        sort($names);

        return $names;
    }

    protected function routeMethod(string $name): string
    {
        return Route::getRoutes()->getByName($name)->methods()[0];
    }

    /**
     * @param  array<string, mixed>  $keys  child identifiers (material, product, transaction, opname, user)
     */
    protected function urlFor(string $name, ?string $mitraCode, array $keys): string
    {
        $params = [];
        foreach (Route::getRoutes()->getByName($name)->parameterNames() as $parameter) {
            $params[$parameter] = $parameter === 'mitra' ? $mitraCode : $keys[$parameter];
        }

        return route($name, $params);
    }

    /**
     * Valid request body for a route, expressed in the identifiers of $ids.
     *
     * @param  array<string, mixed>  $ids
     * @return array<string, mixed>
     */
    protected function payloadFor(string $name, array $ids): array
    {
        $segments = explode('.', $name);
        $last = end($segments);

        return match (true) {
            str_ends_with($name, 'pos.store') => [
                'items' => [['mitra_product_id' => $ids['product_id'], 'qty' => 1]],
                'sales_mode' => 'dine_in',
                'payment_method' => 'cash',
            ],
            str_ends_with($name, '.void') => ['reason' => 'uji isolasi antar tenant'],
            $last === 'adjust' => ['delta' => 5, 'notes' => 'uji isolasi antar tenant'],
            $last === 'update' && str_contains($name, 'setting') => $this->settingPayload(),
            str_ends_with($name, 'opname.store') || $name === 'mitra-opname.store' => ['physical_qty' => [$ids['material_id'] => 1], 'notes' => 'HACK-OPNAME'],
            $name === 'mitra-kasir-user.update' => ['name' => 'HACK-NAMA', 'email' => 'hack-update@evil.test', 'password' => 'hackedpass123'],
            $name === 'mitra-kasir-user.store' => ['name' => 'Kasir Baru', 'email' => 'kasir-baru@evil.test', 'password' => 'newpass123'],
            str_ends_with($name, 'akuntansi-coa.update') => [
                'is_active' => [$ids['account_beban_id'] => '1'],
                'opening_balance' => [$ids['account_beban_id'] => 999999],
            ],
            str_ends_with($name, 'akuntansi-jurnal.store') => [
                'entry_date' => Carbon::now()->format('Y-m-d'),
                'description' => 'HACK-JURNAL',
                'lines' => [
                    ['account_id' => $ids['account_beban_id'], 'debit' => 1000, 'credit' => 0],
                    ['account_id' => $ids['account_kas_id'], 'debit' => 0, 'credit' => 1000],
                ],
            ],
            $name === 'mitra-material.store' => $this->materialPayload('HACK-MAT'),
            $name === 'mitra-material.update' => $this->materialPayload($ids['material_sku']),
            $name === 'mitra-material.purchase' => ['material_id' => $ids['material_id'], 'qty' => 1, 'unit_price' => 1000, 'notes' => 'HACK-BELI'],
            $name === 'mitra-product.store' => $this->productPayload('HACK-PRD', $ids['material_id']),
            $name === 'mitra-product.update' => $this->productPayload($ids['product_sku'], $ids['material_id']),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingPayload(): array
    {
        return [
            'receipt_footer' => 'HACK-FOOTER',
            'monthly_revenue_target' => 1,
            'service_charge_percent' => 0,
            'tax_percent' => 0,
            'qris_fee_percent' => 0,
            'transfer_fee_percent' => 0,
            'edc_fee_percent' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function materialPayload(string $sku): array
    {
        return [
            'sku' => $sku,
            'name' => 'HACK-NAMA-MATERIAL',
            'unit' => 'GR',
            'netto' => 1000,
            'price_per_pack' => 1000,
            'current_stock' => 1,
            'min_stock' => 1,
            'is_active' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productPayload(string $sku, int $materialId): array
    {
        return [
            'sku' => $sku,
            'name' => 'HACK-NAMA-PRODUK',
            'q_factor' => 20,
            'sale_price' => 1000,
            'status' => 'active',
            'ingredients' => [['mitra_material_id' => $materialId, 'qty' => 1]],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function send(User $actor, string $method, string $uri, array $payload = [])
    {
        return $this->actingAs($actor)->call($method, $uri, $payload);
    }

    protected function assertBlockedOrRejected($response, string $context, bool $strict): void
    {
        $status = $response->getStatusCode();

        if ($strict) {
            $this->assertContains($status, [403, 404], "{$context}: expected 403/404 but got {$status}");

            return;
        }

        $rejectedWithErrors = $status === 302 && (session()->has('errors') || session()->has('error'));
        $this->assertTrue(
            in_array($status, [403, 404, 422], true) || $rejectedWithErrors,
            "{$context}: expected 403/404/422 or redirect-with-validation-errors but got {$status}"
        );
    }

    /**
     * @param  list<string>  $echoedInput  request values the page legitimately prints back (search boxes, pagination links)
     */
    protected function assertNoTenantMarkerLeak($response, string $context, array $echoedInput = []): void
    {
        if ($response->getStatusCode() !== 200) {
            return;
        }

        $body = html_entity_decode((string) $response->getContent());
        foreach ($echoedInput as $value) {
            $body = str_replace([$value, rawurlencode($value), urlencode($value)], '', $body);
        }

        $this->assertStringNotContainsString(self::B_MARK, $body, "{$context}: body contains a mitra B marker");
        $this->assertStringNotContainsString('777.777', $body, "{$context}: body contains mitra B revenue figure");
        $this->assertStringNotContainsString('777777', $body, "{$context}: body contains mitra B revenue figure");
        $this->assertStringNotContainsString('424.242', $body, "{$context}: body contains mitra B journal amount");
    }

    protected function assertListScopedToMitraA(string $name, $response): void
    {
        $mitraId = $this->mitraA->id;
        $countOf = fn (string $table) => DB::table($table)->where('mitra_id', $mitraId)->count();
        $liveCountOf = fn (string $table) => DB::table($table)->where('mitra_id', $mitraId)->whereNull('deleted_at')->count();

        switch ($name) {
            case 'pos-transaction.index':
            case 'mitra-pos-manage.transaction.index':
                $this->assertSame($countOf('pos_transactions'), $response->viewData('transactions')->total(), $name);
                break;
            case 'mitra-stock.index':
            case 'mitra-pos-manage.stock.index':
            case 'mitra-material.index':
                $this->assertSame($liveCountOf('mitra_materials'), $response->viewData('materials')->total(), $name);
                break;
            case 'mitra-stock.movements':
            case 'mitra-pos-manage.stock.movements':
                $this->assertSame($countOf('mitra_stock_movements'), $response->viewData('movements')->total(), $name);
                $this->assertSame($liveCountOf('mitra_materials'), $response->viewData('materials')->count(), $name);
                break;
            case 'mitra-opname.index':
            case 'mitra-pos-manage.opname.index':
                $this->assertSame($countOf('mitra_stock_opnames'), $response->viewData('opnames')->total(), $name);
                break;
            case 'mitra-kasir-user.index':
                $this->assertSame([$this->kasirA->id], $response->viewData('kasirs')->pluck('id')->all(), $name);
                break;
            case 'akuntansi-coa.index':
            case 'mitra-pos-manage.akuntansi-coa.index':
                $this->assertSame($countOf('akuntansi_accounts'), $response->viewData('accounts')->count(), $name);
                break;
            case 'akuntansi-jurnal.index':
            case 'mitra-pos-manage.akuntansi-jurnal.index':
                $this->assertSame($countOf('akuntansi_journal_entries'), $response->viewData('entries')->total(), $name);
                break;
            case 'pos.index':
            case 'mitra-pos-manage.pos.index':
                $this->assertSame(
                    DB::table('mitra_products')->where('mitra_id', $mitraId)->where('status', 'active')->whereNull('deleted_at')->count(),
                    $response->viewData('products')->count(),
                    $name
                );
                break;
            case 'mitra-product.index':
                $this->assertSame($liveCountOf('mitra_products'), $response->viewData('products')->total(), $name);
                break;
            case 'mitra-dashboard.index':
            case 'mitra-pos-manage.dashboard.index':
                $this->assertEqualsWithDelta(
                    (float) DB::table('pos_transactions')->where('mitra_id', $mitraId)->where('status', 'completed')->sum('grand_total'),
                    $response->viewData('stats')['revenue_month'],
                    0.01,
                    $name
                );
                break;
        }
    }
}
