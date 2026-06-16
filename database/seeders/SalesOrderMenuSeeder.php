<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class SalesOrderMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure necessary roles exist
        $rolesToEnsure = [
            ['name' => 'Sales', 'slug' => 'sales'],
            ['name' => 'RnD', 'slug' => 'rnd'],
            ['name' => 'Roastery', 'slug' => 'roastery'],
            ['name' => 'HRD', 'slug' => 'hrd'],
            ['name' => 'Looper', 'slug' => 'looper'],
            ['name' => 'Manager', 'slug' => 'manager'],
            ['name' => 'Finance', 'slug' => 'finance'], // The new role
        ];

        foreach ($rolesToEnsure as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                ['name' => $roleData['name']]
            );
        }

        // Get Roles
        $roles = Role::whereIn('slug', [
            'super-admin', 'sales', 'rnd', 'roastery', 'hrd', 'looper', 'finance', 'manager', 'user'
        ])->get()->keyBy('slug');

        // Parent Menu: Penjualan
        $parentMenu = Menu::updateOrCreate(
            ['slug' => 'penjualan-menu'],
            [
                'name' => 'Penjualan',
                'icon' => 'ri-shopping-cart-2-line',
                'path' => '#',
                'order_no' => 10, // Adjust accordingly
                'is_active' => true,
            ]
        );

        $subMenus = [
            [
                'name' => 'Sales Order',
                'slug' => 'sales-order.index',
                'path' => '/penjualan/sales-order',
                'order_no' => 1,
                'permissions' => [
                    'super-admin' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
                    'sales'       => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
                ]
            ],
            [
                'name' => 'Kelola Order',
                'slug' => 'sales-order.manage',
                'path' => '/penjualan/kelola-order',
                'order_no' => 2,
                'permissions' => [
                    'super-admin' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
                    'rnd'         => ['create' => false, 'read' => true, 'update' => true, 'delete' => true],
                    'roastery'    => ['create' => false, 'read' => true, 'update' => true, 'delete' => true],
                    'manager'     => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ]
            ],
            [
                'name' => 'Delivery Order',
                'slug' => 'delivery-order.index',
                'path' => '/penjualan/delivery-order',
                'order_no' => 3,
                'permissions' => [
                    'super-admin' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
                    'hrd'         => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
                    'looper'      => ['create' => false, 'read' => true, 'update' => true, 'delete' => false],
                    // We give read permission to all these roles so the menu shows, logic handles assignment viewing
                    'sales'       => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                    'rnd'         => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                    'roastery'    => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                    'manager'     => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                    'user'        => ['create' => false, 'read' => true, 'update' => true, 'delete' => false], // can update status if assigned
                ]
            ],
            [
                'name' => 'Invoice',
                'slug' => 'invoice.index',
                'path' => '/penjualan/invoice',
                'order_no' => 4,
                'permissions' => [
                    'super-admin' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
                    'finance'     => ['create' => false, 'read' => true, 'update' => true, 'delete' => false],
                    'manager'     => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ]
            ],
            [
                'name' => 'Dashboard Penjualan',
                'slug' => 'sales-dashboard.index',
                'path' => '/penjualan/dashboard',
                'order_no' => 5,
                'permissions' => [
                    'super-admin' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
                    'hrd'         => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                    'finance'     => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                    'manager'     => ['create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ]
            ],
        ];

        // Ensure super-admin can read the parent menu
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
                    // Give parent menu read access if not already granted
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
                            'can_delete' => $perms['delete']
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ Sales Order Menus and Roles seeded successfully!');
    }
}
