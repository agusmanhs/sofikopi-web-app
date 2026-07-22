<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Mitra;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for two related sidebar-menu bugs in the Mitra POS
 * module:
 *
 * 1) Active-state collision: MitraPosMenuSeeder originally seeded "Material"
 *    and "Produk" with the exact same `path` as "Kelola Mitra POS"
 *    (/mitra-pos/manage). verticalMenu.blade.php's isMenuActive() Priority-3
 *    fallback does an exact string comparison against each menu's `path`, so
 *    visiting /mitra-pos/manage lit up all three items as active at once.
 *    Fix: Material/Produk paths became '#'.
 *
 * 2) Dead links: with `path => '#'` those two items rendered as
 *    non-navigable entries in the sidebar. Fix: they are now seeded with
 *    `is_active => false` and the AppServiceProvider sidebar view composer
 *    filters `where('is_active', true)` on the parent query and both child
 *    closures — so Material/Produk no longer render in the sidebar AT ALL.
 *    Crucially, their `role_menu` pivots are still seeded, because
 *    hasPermission() (and the Kelola Mitra POS per-mitra picker buttons)
 *    depend on those pivot rows: hidden-from-sidebar must NOT mean
 *    no-permission.
 */
class MitraPosMenuActiveStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);
    }

    /**
     * Data-integrity level: guards the whole bug CLASS, not just this one
     * instance. Any future seeder that copy-pastes a path across two leaf
     * rows under the same parent would reproduce the original active-state
     * bug and would be caught by this assertion.
     */
    public function test_no_two_sibling_menus_share_a_real_navigable_path(): void
    {
        $parent = Menu::where('slug', 'mitra-pos-menu')->firstOrFail();

        $children = Menu::where('parent_id', $parent->id)->get();

        // Sanity: the three menus this bug involved actually exist as rows
        // (Material/Produk are inactive but must still exist — see the
        // permission-pivot test below).
        $manage = $children->firstWhere('slug', 'mitra-pos-manage.index');
        $material = $children->firstWhere('slug', 'mitra-material.index');
        $product = $children->firstWhere('slug', 'mitra-product.index');

        $this->assertNotNull($manage);
        $this->assertNotNull($material);
        $this->assertNotNull($product);

        // Specific assertions for this bug instance.
        $this->assertSame('#', $material->path);
        $this->assertSame('#', $product->path);
        $this->assertSame('/mitra-pos/manage', $manage->path);

        // General assertion guarding the whole bug class: among all
        // siblings, no two DISTINCT rows share an identical `path` unless
        // that value is a non-navigable placeholder ('#' or null).
        foreach ($children as $a) {
            if ($a->path === '#' || $a->path === null || $a->path === '') {
                continue;
            }

            foreach ($children as $b) {
                if ($a->id === $b->id) {
                    continue;
                }

                $this->assertNotSame(
                    $a->path,
                    $b->path,
                    "Sibling menus '{$a->slug}' and '{$b->slug}' share the real navigable path '{$a->path}', ".
                    'which causes both to render as active whenever either is visited.'
                );
            }
        }

        // Concretely: Kelola Mitra POS's real path must not be reused by
        // any other sibling row.
        foreach ($children as $child) {
            if ($child->id === $manage->id) {
                continue;
            }

            $this->assertNotSame(
                $manage->path,
                $child->path,
                "Menu '{$child->slug}' collides with Kelola Mitra POS's path '{$manage->path}'."
            );
        }
    }

    /**
     * HTTP/rendered-output level: on /mitra-pos/manage, "Kelola Mitra POS"
     * is the only Mitra POS sidebar item rendered as active — and the
     * formerly-dead-link "Material"/"Produk" items are not rendered in the
     * sidebar at all (they are seeded with is_active=false and the sidebar
     * composer filters them out).
     */
    public function test_should_render_kelola_mitra_pos_as_only_active_item_when_visiting_manage_page(): void
    {
        $admin = $this->makeSuperAdmin('admin-menu-active@internal.test');

        $response = $this->actingAs($admin)->get(route('mitra-pos-manage.index'));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertMenuItemActive($html, 'Kelola Mitra POS', true);
        $this->assertMenuItemNotRendered($html, 'Material');
        $this->assertMenuItemNotRendered($html, 'Produk');
    }

    /**
     * Regression guard for the dead-link fix itself: any menu row flagged
     * is_active=false must never appear in the rendered sidebar, even when
     * the role holds a can_read pivot for it. A control sibling (identical
     * except is_active=true) proves the row would otherwise render — so a
     * future removal of the `where('is_active', true)` filter in the
     * AppServiceProvider view composer fails this test.
     */
    public function test_should_not_render_inactive_menus_in_sidebar_when_menu_row_has_is_active_false(): void
    {
        $admin = $this->makeSuperAdmin('admin-inactive-menu@internal.test');

        $visible = Menu::create([
            'parent_id' => null,
            'name' => 'Visible Control Menu Xq7',
            'icon' => 'ri-eye-line',
            'path' => '#',
            'slug' => 'visible-control-menu',
            'order_no' => 90,
            'is_active' => true,
        ]);

        $hidden = Menu::create([
            'parent_id' => null,
            'name' => 'Hidden Inactive Menu Xq7',
            'icon' => 'ri-eye-off-line',
            'path' => '#',
            'slug' => 'hidden-inactive-menu',
            'order_no' => 91,
            'is_active' => false,
        ]);

        foreach ([$visible, $hidden] as $menu) {
            DB::table('role_menu')->insert([
                'role_id' => $admin->role_id,
                'menu_id' => $menu->id,
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('mitra-pos-manage.index'));

        $response->assertOk();
        $response->assertSee('Visible Control Menu Xq7');
        $response->assertDontSee('Hidden Inactive Menu Xq7');
    }

    /**
     * The seeded Material/Produk rows themselves (this exact bug instance)
     * are inactive and therefore filtered out of the sidebar — asserted at
     * the DB level so a seeder regression is caught even without rendering.
     */
    public function test_should_seed_material_and_produk_as_inactive_when_running_mitra_pos_menu_seeder(): void
    {
        $this->assertDatabaseHas('menus', ['slug' => 'mitra-material.index', 'is_active' => false]);
        $this->assertDatabaseHas('menus', ['slug' => 'mitra-product.index', 'is_active' => false]);

        // The visible siblings stay active.
        $this->assertDatabaseHas('menus', ['slug' => 'mitra-pos-manage.index', 'is_active' => true]);
        $this->assertDatabaseHas('menus', ['slug' => 'mitra-pos-menu', 'is_active' => true]);
    }

    /**
     * Hidden-from-sidebar must NOT mean no-permission: the mitra-scoped
     * material index route stays reachable (HTTP 200) even though the
     * 'mitra-material.index' menu row is is_active=false, because its
     * role_menu pivot is still seeded. This is load-bearing for
     * hasPermission() and the Kelola Mitra POS picker buttons.
     */
    public function test_should_keep_permission_pivots_working_when_menu_is_hidden_from_sidebar(): void
    {
        $materialMenu = Menu::where('slug', 'mitra-material.index')->firstOrFail();
        $superAdmin = Role::where('slug', 'super-admin')->firstOrFail();

        // The menu row is hidden from the sidebar...
        $this->assertFalse((bool) $materialMenu->is_active);

        // ...but its permission pivot is still seeded for super-admin.
        $this->assertDatabaseHas('role_menu', [
            'role_id' => $superAdmin->id,
            'menu_id' => $materialMenu->id,
            'can_read' => true,
        ]);

        // And the mitra-scoped page it guards is still reachable over HTTP.
        $admin = $this->makeSuperAdmin('admin-hidden-menu-access@internal.test');
        $mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('mitra-material.index', ['mitra' => $mitra->code]));

        $response->assertOk();

        // While that page's own sidebar still hides the Material item.
        $this->assertMenuItemNotRendered($response->getContent(), 'Material');
    }

    /**
     * Locates the <li class="menu-item ..."> that wraps the given menu name
     * (rendered inside <div>{name}</div> by verticalMenu.blade.php) and
     * asserts whether its class attribute contains "active".
     *
     * The <li> for a leaf menu item has the shape:
     *   <li class="menu-item [active]">
     *      <a ...><div>Name</div></a>
     *   </li>
     * with no nested <li> in between (leaf menus render no submenu), so a
     * non-greedy match from the opening <li> up to the menu's own <div>Name</div>
     * safely captures only that item's own class attribute without bleeding
     * into unrelated sidebar sections.
     */
    private function assertMenuItemActive(string $html, string $menuName, bool $expectActive): void
    {
        $pattern = $this->menuItemPattern($menuName);

        $this->assertMatchesRegularExpression(
            $pattern,
            $html,
            "Could not locate the sidebar <li> for menu item '{$menuName}' in the rendered page."
        );

        preg_match($pattern, $html, $matches);
        $classAttribute = trim($matches[1]);
        $classes = $classAttribute === '' ? [] : explode(' ', $classAttribute);

        if ($expectActive) {
            $this->assertContains(
                'active',
                $classes,
                "Expected menu item '{$menuName}' to render with the 'active' class, but its class attribute was '{$classAttribute}'."
            );
        } else {
            $this->assertNotContains(
                'active',
                $classes,
                "Expected menu item '{$menuName}' to NOT render with the 'active' class, but its class attribute was '{$classAttribute}'."
            );
        }
    }

    /**
     * Asserts the sidebar contains NO <li class="menu-item ..."> wrapping
     * the given menu name at all. Deliberately scoped to the sidebar's
     * menu-item markup (not a bare assertDontSee) because the page body may
     * legitimately contain the same word — e.g. the Kelola Mitra POS cards
     * have "Material"/"Produk" picker buttons.
     */
    private function assertMenuItemNotRendered(string $html, string $menuName): void
    {
        $this->assertDoesNotMatchRegularExpression(
            $this->menuItemPattern($menuName),
            $html,
            "Expected menu item '{$menuName}' to be absent from the rendered sidebar, but its <li class=\"menu-item\"> was found."
        );
    }

    private function menuItemPattern(string $menuName): string
    {
        return '/<li class="menu-item([^"]*)">(?:(?!<li).)*?<div>'.preg_quote($menuName, '/').'<\/div>/s';
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
