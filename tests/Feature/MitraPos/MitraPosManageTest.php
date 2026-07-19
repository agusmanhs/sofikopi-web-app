<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\MitraMaterial;
use App\Models\MitraPosSetting;
use App\Models\MitraProduct;
use App\Models\MitraStockMovement;
use App\Models\PosTransaction;
use App\Models\Role;
use App\Models\User;
use App\Services\MitraPos\PosTransactionService;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Kelola Mitra POS" screen is a CRUD over which mitras are enrolled in
 * the POS system (enrollment = having a mitra_pos_settings row), NOT a
 * listing of every active mitra in the shared master table:
 * - index()   lists only enrolled mitras
 * - store()   enrolls an existing mitra
 * - destroy() wipes ALL of a mitra's POS data (including rows created
 *             through real checkouts) AND de-enrolls it, so it disappears
 *             from index() — while the mitras master row, its users,
 *             kunjungan history, and sales orders are never touched.
 */
class MitraPosManageTest extends TestCase
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

    public function test_destroy_wipes_pos_data_including_checkout_generated_rows_and_de_enrolls_but_keeps_mitra_and_users(): void
    {
        // Real checkout so every table (transactions, items, movements) has rows.
        $kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
        $product = MitraProduct::forMitra($this->mitra->id)->where('sku', 'SLK011')->firstOrFail();

        app(PosTransactionService::class)->checkout(
            $this->mitra->id,
            $kasir->id,
            [['mitra_product_id' => $product->id, 'qty' => 1]],
            0,
            'dine_in',
            'cash'
        );

        $this->assertGreaterThan(0, PosTransaction::forMitra($this->mitra->id)->count());
        $this->assertGreaterThan(0, MitraStockMovement::forMitra($this->mitra->id)->count());

        $admin = $this->makeSuperAdmin('admin-destroy@internal.test');

        $this->actingAs($admin)
            ->delete(route('mitra-pos-manage.destroy', $this->mitra))
            ->assertRedirect(route('mitra-pos-manage.index'));

        $this->assertSame(0, PosTransaction::forMitra($this->mitra->id)->count());
        $this->assertSame(0, MitraStockMovement::forMitra($this->mitra->id)->count());
        $this->assertSame(0, MitraProduct::forMitra($this->mitra->id)->withTrashed()->count());
        $this->assertSame(0, MitraMaterial::forMitra($this->mitra->id)->withTrashed()->count());

        // Master data survives — only POS enrollment + data is gone.
        $this->assertDatabaseHas('mitras', ['code' => 'CAFE-LALLO-KDI']);
        $this->assertDatabaseHas('users', ['email' => 'kasir@cafelallo.test']);
        $this->assertFalse($this->mitra->fresh()->posSetting()->exists());
    }

    public function test_mitra_user_cannot_destroy_via_manage_route(): void
    {
        $kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();

        // Passes mitra.scope (own mitra) but lacks the mitra-pos-manage
        // delete permission — check.permission must 403.
        $this->actingAs($kasir)
            ->delete(route('mitra-pos-manage.destroy', $this->mitra))
            ->assertForbidden();

        $this->assertGreaterThan(0, MitraMaterial::forMitra($this->mitra->id)->count());
    }

    public function test_destroy_unknown_mitra_code_returns_404(): void
    {
        $admin = $this->makeSuperAdmin('admin-404@internal.test');

        $this->actingAs($admin)
            ->delete('/mitra-pos/manage/TIDAK-ADA')
            ->assertNotFound();
    }

    public function test_index_only_lists_enrolled_mitras(): void
    {
        $admin = $this->makeSuperAdmin('admin-index@internal.test');
        $category = MitraCategory::firstOrCreate(['name' => 'Test'], ['is_active' => true]);
        $unenrolled = Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => 'MT-UNENROLLED',
            'name' => 'Mitra Belum Terdaftar',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('mitra-pos-manage.index'));

        // $mitras = enrolled-only listing (the cards); $availableMitras = the
        // "add" dropdown's candidates — the unenrolled mitra legitimately
        // appears there, just not as a card.
        $response->assertViewHas('mitras', fn ($mitras) => $mitras->pluck('id')->contains($this->mitra->id)
            && ! $mitras->pluck('id')->contains($unenrolled->id));
        $response->assertViewHas('availableMitras', fn ($available) => $available->pluck('id')->contains($unenrolled->id));
    }

    public function test_admin_can_enroll_an_existing_mitra_into_pos(): void
    {
        $admin = $this->makeSuperAdmin('admin-enroll@internal.test');
        $category = MitraCategory::firstOrCreate(['name' => 'Test'], ['is_active' => true]);
        $newMitra = Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => 'MT-NEW',
            'name' => 'Mitra Baru',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('mitra-pos-manage.store'), ['mitra_id' => $newMitra->id])
            ->assertRedirect(route('mitra-pos-manage.index'));

        $this->assertTrue($newMitra->fresh()->posSetting()->exists());

        $response = $this->actingAs($admin)->get(route('mitra-pos-manage.index'));
        $response->assertSee('Mitra Baru');
    }

    public function test_enrolling_an_already_enrolled_mitra_is_rejected(): void
    {
        $admin = $this->makeSuperAdmin('admin-dup@internal.test');

        $this->actingAs($admin)
            ->post(route('mitra-pos-manage.store'), ['mitra_id' => $this->mitra->id])
            ->assertSessionHasErrors('mitra_id');
    }

    public function test_admin_can_bulk_destroy_multiple_mitras(): void
    {
        $admin = $this->makeSuperAdmin('admin-bulk-destroy@internal.test');

        $category = MitraCategory::firstOrCreate(['name' => 'Test'], ['is_active' => true]);
        $second = Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => 'MT-SECOND',
            'name' => 'Mitra Kedua',
            'is_active' => true,
        ]);
        MitraPosSetting::firstOrCreate(['mitra_id' => $second->id]);

        $this->assertGreaterThan(0, MitraMaterial::forMitra($this->mitra->id)->count());

        $this->actingAs($admin)
            ->delete(route('mitra-pos-manage.destroy-bulk'), [
                'mitra_ids' => [$this->mitra->id, $second->id],
            ])
            ->assertRedirect(route('mitra-pos-manage.index'));

        $this->assertSame(0, MitraMaterial::forMitra($this->mitra->id)->withTrashed()->count());
        $this->assertSame(0, MitraMaterial::forMitra($second->id)->withTrashed()->count());
        $this->assertFalse($this->mitra->fresh()->posSetting()->exists());
        $this->assertFalse($second->fresh()->posSetting()->exists());

        // Master data survives.
        $this->assertDatabaseHas('mitras', ['code' => 'CAFE-LALLO-KDI']);
        $this->assertDatabaseHas('mitras', ['code' => 'MT-SECOND']);
        $this->assertDatabaseHas('users', ['email' => 'kasir@cafelallo.test']);
    }

    public function test_mitra_user_cannot_bulk_destroy(): void
    {
        $kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();

        $this->actingAs($kasir)
            ->delete(route('mitra-pos-manage.destroy-bulk'), [
                'mitra_ids' => [$this->mitra->id],
            ])
            ->assertForbidden();

        $this->assertGreaterThan(0, MitraMaterial::forMitra($this->mitra->id)->count());
    }

    public function test_bulk_destroy_rejects_empty_mitra_ids(): void
    {
        $admin = $this->makeSuperAdmin('admin-bulk-empty@internal.test');

        $this->actingAs($admin)
            ->delete(route('mitra-pos-manage.destroy-bulk'), [
                'mitra_ids' => [],
            ])
            ->assertSessionHasErrors('mitra_ids');
    }

    public function test_bulk_destroy_rejects_nonexistent_mitra_id(): void
    {
        $admin = $this->makeSuperAdmin('admin-bulk-invalid@internal.test');

        $this->actingAs($admin)
            ->delete(route('mitra-pos-manage.destroy-bulk'), [
                'mitra_ids' => [99999],
            ])
            ->assertSessionHasErrors();
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
}
