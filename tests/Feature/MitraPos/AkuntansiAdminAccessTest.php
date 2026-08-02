<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiAccount;
use App\Models\Mitra;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers full read+write Akuntansi access for Sofikopi staff via the
 * mitra-pos/manage/{mitra}/akuntansi/... routes — the tenant-portal routes
 * (mitra.user middleware) 403 anyone with mitra_id === null, which is every
 * super-admin, regardless of role_menu permission pivots. These admin
 * routes are the actual fix for that.
 */
class AkuntansiAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
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

    public function test_super_admin_gets_403_on_the_tenant_portal_route(): void
    {
        $admin = $this->makeSuperAdmin('admin-portal-403@internal.test');

        $this->actingAs($admin)->get(route('akuntansi-coa.index'))->assertForbidden();
    }

    public function test_super_admin_can_view_and_update_coa_via_admin_route(): void
    {
        $admin = $this->makeSuperAdmin('admin-coa@internal.test');
        $account = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'kas_kasir')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('mitra-pos-manage.akuntansi-coa.index', $this->mitra))
            ->assertOk();

        $response = $this->actingAs($admin)->put(route('mitra-pos-manage.akuntansi-coa.update', $this->mitra), [
            'opening_balance' => [$account->id => '750000'],
            'is_active' => [$account->id => '1'],
        ]);

        $response->assertRedirect(route('mitra-pos-manage.akuntansi-coa.index', $this->mitra));
        $account->refresh();
        $this->assertEqualsWithDelta(750000.0, (float) $account->opening_balance, 0.01);
    }

    public function test_super_admin_can_view_and_submit_manual_journal_via_admin_route(): void
    {
        $admin = $this->makeSuperAdmin('admin-jurnal@internal.test');
        $kas = AkuntansiAccount::forMitra($this->mitra->id)->where('system_role', 'kas_kasir')->firstOrFail();
        $beban = AkuntansiAccount::forMitra($this->mitra->id)->where('code', '40167')->firstOrFail();

        $this->actingAs($admin)->get(route('mitra-pos-manage.akuntansi-jurnal.index', $this->mitra))->assertOk();
        $this->actingAs($admin)->get(route('mitra-pos-manage.akuntansi-jurnal.create', $this->mitra))->assertOk();

        $response = $this->actingAs($admin)->post(route('mitra-pos-manage.akuntansi-jurnal.store', $this->mitra), [
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Jurnal manual oleh admin',
            'lines' => [
                ['account_id' => $beban->id, 'debit' => 30000, 'credit' => 0],
                ['account_id' => $kas->id, 'debit' => 0, 'credit' => 30000],
            ],
        ]);

        $response->assertRedirect(route('mitra-pos-manage.akuntansi-jurnal.index', $this->mitra));
        $this->assertDatabaseHas('akuntansi_journal_entries', [
            'mitra_id' => $this->mitra->id,
            'description' => 'Jurnal manual oleh admin',
        ]);
    }

    public function test_super_admin_can_view_neraca_and_laba_rugi_via_admin_route(): void
    {
        $admin = $this->makeSuperAdmin('admin-report@internal.test');

        $this->actingAs($admin)->get(route('mitra-pos-manage.akuntansi-neraca.index', $this->mitra))->assertOk();
        $this->actingAs($admin)->get(route('mitra-pos-manage.akuntansi-laba-rugi.index', $this->mitra))->assertOk();
    }
}
