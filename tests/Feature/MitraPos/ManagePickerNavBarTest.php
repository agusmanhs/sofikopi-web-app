<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression for the "Mode admin" bar leaking onto the Kelola Mitra POS
 * picker page (before any mitra is chosen). Root cause: manage-picker.blade
 * uses @forelse($mitras as $mitra), and Blade's @extends passes the calling
 * view's own get_defined_vars() to the layout — so the loop's last $mitra
 * survived into contentNavbarLayout and satisfied the old isset($mitra)
 * guard. Fixed by reading request()->route('mitra') instead, which is only
 * ever populated on the manage/{mitra}/... routes.
 */
class ManagePickerNavBarTest extends TestCase
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
            'email' => 'admin-navbar@internal.test',
            'password' => bcrypt('password'),
            'role_id' => $superAdmin->id,
            'mitra_id' => null,
        ]);
    }

    public function test_admin_bar_does_not_appear_on_the_picker_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mitra-pos-manage.index'));

        $response->assertOk();
        $response->assertDontSee('Mode admin');
    }

    public function test_admin_bar_appears_once_a_mitra_is_selected(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mitra-pos-manage.dashboard.index', $this->mitra));

        $response->assertOk();
        $response->assertSee('Mode admin');
        $response->assertSee($this->mitra->name);
    }
}
