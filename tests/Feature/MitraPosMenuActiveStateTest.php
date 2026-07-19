<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for a sidebar-menu "active state" bug:
 *
 * MitraPosMenuSeeder seeded "Material" and "Produk" with the exact same
 * `path` as "Kelola Mitra POS" (/mitra-pos/manage). verticalMenu.blade.php's
 * isMenuActive() has a Priority-3 fallback that does an exact string
 * comparison between request()->path() and each menu's `path`, so visiting
 * /mitra-pos/manage lit up all three sidebar items as active simultaneously
 * instead of just "Kelola Mitra POS".
 *
 * The fix changed Material's and Produk's `path` to '#' (the existing
 * convention for menu rows without a single fixed destination), leaving
 * Kelola Mitra POS's `path` untouched. Priority 1 of isMenuActive() (exact
 * route-name === menu-slug match) still activates Material/Produk correctly
 * when the user is genuinely on those pages.
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
     * rows under the same parent would reproduce the original bug and would
     * be caught by this assertion.
     */
    public function test_no_two_sibling_menus_share_a_real_navigable_path(): void
    {
        $parent = Menu::where('slug', 'mitra-pos-menu')->firstOrFail();

        $children = Menu::where('parent_id', $parent->id)->get();

        // Sanity: the three menus this bug involved actually exist.
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
        $pathsByMenu = $children->mapWithKeys(fn (Menu $menu) => [$menu->slug => $menu->path]);

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
     * HTTP/rendered-output level: proves the actual user-visible symptom is
     * gone. Only "Kelola Mitra POS" should render with the `active` class
     * when visiting /mitra-pos/manage; "Material" and "Produk" must not.
     */
    public function test_only_kelola_mitra_pos_menu_item_is_active_on_manage_page(): void
    {
        $admin = $this->makeSuperAdmin('admin-menu-active@internal.test');

        $response = $this->actingAs($admin)->get(route('mitra-pos-manage.index'));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertMenuItemActive($html, 'Kelola Mitra POS', true);
        $this->assertMenuItemActive($html, 'Material', false);
        $this->assertMenuItemActive($html, 'Produk', false);
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
        $pattern = '/<li class="menu-item([^"]*)">(?:(?!<li).)*?<div>'.preg_quote($menuName, '/').'<\/div>/s';

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
