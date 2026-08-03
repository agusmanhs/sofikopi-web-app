<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\MitraPosSetting;
use App\Models\PosTransaction;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Owner self-service for kasir accounts (mitra-pos/kasir-user): hard cap of
 * 2 kasir per mitra enforced server-side (not just UI), tenant isolation
 * (owner of mitra A can't touch mitra B's kasir), kasir role is locked out
 * entirely (no role_menu pivot), and a kasir with transaction history can't
 * be deleted (password reset is the intended way to revoke their access).
 */
class MitraKasirUserTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $owner;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@cafelallo.test')->firstOrFail();
    }

    public function test_owner_can_create_a_second_kasir(): void
    {
        $response = $this->actingAs($this->owner)->post(route('mitra-kasir-user.store'), [
            'name' => 'Kasir Kedua',
            'email' => 'kasir2@cafelallo.test',
            'password' => 'password123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('mitra-kasir-user.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'kasir2@cafelallo.test',
            'mitra_id' => $this->mitra->id,
        ]);

        $newKasir = User::where('email', 'kasir2@cafelallo.test')->firstOrFail();
        $this->assertSame('mitra-kasir', $newKasir->role->slug);
    }

    public function test_third_kasir_is_rejected(): void
    {
        $this->actingAs($this->owner)->post(route('mitra-kasir-user.store'), [
            'name' => 'Kasir Kedua',
            'email' => 'kasir2@cafelallo.test',
            'password' => 'password123',
        ])->assertSessionHasNoErrors();

        $response = $this->actingAs($this->owner)->post(route('mitra-kasir-user.store'), [
            'name' => 'Kasir Ketiga',
            'email' => 'kasir3@cafelallo.test',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'kasir3@cafelallo.test']);
    }

    public function test_kasir_is_forbidden_from_kasir_user_page(): void
    {
        $this->actingAs($this->kasir)
            ->get(route('mitra-kasir-user.index'))
            ->assertForbidden();
    }

    public function test_owner_cannot_touch_another_mitras_kasir(): void
    {
        $otherMitra = $this->makeOtherMitra('MITRA-B');
        $otherOwnerRole = Role::where('slug', 'mitra-owner')->firstOrFail();
        $otherOwner = User::create([
            'name' => 'Owner B',
            'email' => 'owner-b@test.test',
            'password' => bcrypt('password'),
            'role_id' => $otherOwnerRole->id,
            'mitra_id' => $otherMitra->id,
        ]);

        $this->actingAs($otherOwner)->put(route('mitra-kasir-user.update', $this->kasir), [
            'name' => 'Diretas',
            'email' => 'kasir@cafelallo.test',
        ])->assertNotFound();

        $this->actingAs($otherOwner)->delete(route('mitra-kasir-user.destroy', $this->kasir))
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $this->kasir->id,
            'name' => 'Kasir Cafe Lallo',
        ]);
    }

    public function test_owner_can_update_kasir_name_and_password(): void
    {
        $response = $this->actingAs($this->owner)->put(route('mitra-kasir-user.update', $this->kasir), [
            'name' => 'Kasir Baru',
            'email' => 'kasir@cafelallo.test',
            'password' => 'newpassword123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('mitra-kasir-user.index'));

        $this->assertDatabaseHas('users', [
            'id' => $this->kasir->id,
            'name' => 'Kasir Baru',
        ]);

        $this->assertTrue(Hash::check('newpassword123', $this->kasir->fresh()->password));
    }

    public function test_owner_can_delete_kasir_without_transactions(): void
    {
        $this->actingAs($this->owner)->delete(route('mitra-kasir-user.destroy', $this->kasir))
            ->assertRedirect(route('mitra-kasir-user.index'));

        $this->assertDatabaseMissing('users', ['id' => $this->kasir->id]);
    }

    public function test_owner_cannot_delete_kasir_with_transaction_history(): void
    {
        PosTransaction::create([
            'mitra_id' => $this->mitra->id,
            'transaction_no' => 'POS/LALLO/20260803/0001',
            'sales_mode' => 'dine_in',
            'payment_method' => 'cash',
            'subtotal' => 10000,
            'discount' => 0,
            'service_charge' => 0,
            'tax' => 0,
            'grand_total' => 10000,
            'total_hpp' => 0,
            'total_cogs' => 0,
            'admin_fee' => 0,
            'status' => 'completed',
            'user_id' => $this->kasir->id,
            'transacted_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)->delete(route('mitra-kasir-user.destroy', $this->kasir));

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', ['id' => $this->kasir->id]);
    }

    private function makeOtherMitra(string $code): Mitra
    {
        $category = MitraCategory::firstOrCreate(['name' => 'Test Category'], ['is_active' => true]);

        $mitra = Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => $code,
            'name' => "Mitra {$code}",
            'is_active' => true,
        ]);

        MitraPosSetting::create(['mitra_id' => $mitra->id]);

        return $mitra;
    }
}
