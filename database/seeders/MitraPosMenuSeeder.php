<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MitraPosMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Legacy migration: the module first shipped with a single 'mitra'
        // role. Rename it in place (keeps role_id, so existing mitra users
        // keep their role without a data migration) — then it becomes the
        // owner role, and the kasir role is created alongside it.
        $legacy = Role::where('slug', 'mitra')->first();
        if ($legacy && ! Role::where('slug', 'mitra-owner')->exists()) {
            $legacy->update(['slug' => 'mitra-owner', 'name' => 'Mitra Owner']);
        }

        Role::updateOrCreate(
            ['slug' => 'mitra-owner'],
            ['name' => 'Mitra Owner']
        );
        Role::updateOrCreate(
            ['slug' => 'mitra-kasir'],
            ['name' => 'Mitra Kasir']
        );

        // Get Roles
        $roles = Role::whereIn('slug', ['super-admin', 'mitra-owner', 'mitra-kasir'])->get()->keyBy('slug');

        // Parent Menu: Mitra POS
        $parentMenu = Menu::updateOrCreate(
            ['slug' => 'mitra-pos-menu'],
            [
                'name' => 'Mitra POS',
                'icon' => 'ri-store-2-line',
                'path' => '#',
                'order_no' => 11,
                'is_active' => true,
            ]
        );

        // Role split: owner sees everything in the tenant portal including
        // the dashboard (revenue/profit figures); kasir gets the operational
        // screens only (POS, history, stock) — no profit numbers.
        $subMenus = [
            [
                'name' => 'Dashboard',
                'slug' => 'mitra-dashboard.index',
                'path' => '/mitra-pos/dashboard',
                'order_no' => 1,
                'permissions' => [
                    'mitra-owner' => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ],
            ],
            [
                'name' => 'Kasir (POS)',
                'slug' => 'pos.index',
                'path' => '/mitra-pos/pos',
                'order_no' => 2,
                'permissions' => [
                    // store -> 'create' flag per CheckPermission's action map.
                    'mitra-owner' => ['create' => true, 'read' => true, 'update' => false, 'delete' => false],
                    'mitra-kasir' => ['create' => true, 'read' => true, 'update' => false, 'delete' => false],
                ],
            ],
            [
                'name' => 'Riwayat Transaksi',
                'slug' => 'pos-transaction.index',
                'path' => '/mitra-pos/transaction',
                'order_no' => 3,
                'permissions' => [
                    'mitra-owner' => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                    'mitra-kasir' => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ],
            ],
            [
                'name' => 'Stok Bahan',
                'slug' => 'mitra-stock.index',
                'path' => '/mitra-pos/stock',
                'order_no' => 4,
                'permissions' => [
                    'mitra-owner' => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                    'mitra-kasir' => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ],
            ],
            [
                'name' => 'Kelola Mitra POS',
                'slug' => 'mitra-pos-manage.index',
                'path' => '/mitra-pos/manage',
                'order_no' => 5,
                'permissions' => [
                    'super-admin' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
                ],
            ],
            [
                'name' => 'Material',
                'slug' => 'mitra-material.index',
                'path' => '#',
                'order_no' => 6,
                'permissions' => [
                    'super-admin' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
                ],
            ],
            [
                'name' => 'Produk',
                'slug' => 'mitra-product.index',
                'path' => '#',
                'order_no' => 7,
                'permissions' => [
                    'super-admin' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
                ],
            ],
        ];

        // Ensure super-admin can read the parent menu.
        if (isset($roles['super-admin'])) {
            DB::table('role_menu')->updateOrInsert(
                ['role_id' => $roles['super-admin']->id, 'menu_id' => $parentMenu->id],
                ['can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true]
            );
        }

        foreach ($subMenus as $menuData) {
            $menu = Menu::updateOrCreate(
                ['slug' => $menuData['slug']],
                [
                    'parent_id' => $parentMenu->id,
                    'name' => $menuData['name'],
                    'path' => $menuData['path'],
                    'order_no' => $menuData['order_no'],
                    'is_active' => true,
                ]
            );

            // Assign permissions
            foreach ($menuData['permissions'] as $roleSlug => $perms) {
                if (isset($roles[$roleSlug])) {
                    // Give parent menu read access if not already granted.
                    DB::table('role_menu')->updateOrInsert(
                        ['role_id' => $roles[$roleSlug]->id, 'menu_id' => $parentMenu->id],
                        ['can_create' => false, 'can_read' => true, 'can_update' => false, 'can_delete' => false]
                    );

                    // Give submenu access
                    DB::table('role_menu')->updateOrInsert(
                        ['role_id' => $roles[$roleSlug]->id, 'menu_id' => $menu->id],
                        [
                            'can_create' => $perms['create'],
                            'can_read' => $perms['read'],
                            'can_update' => $perms['update'],
                            'can_delete' => $perms['delete'],
                        ]
                    );
                }
            }
        }

        // Note: kasir intentionally gets no mitra-dashboard.index pivot row
        // here — but if an admin later hand-grants it via the Permission
        // screen, re-running this seeder leaves that choice alone
        // (updateOrInsert only touches rows it explicitly seeds).
        $this->command->info('✅ Mitra POS Menus and Roles (owner + kasir split) seeded successfully!');
    }
}
