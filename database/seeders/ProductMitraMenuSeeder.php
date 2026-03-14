<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\Role;

class ProductMitraMenuSeeder extends Seeder
{
    public function run(): void
    {
        $masterId = Menu::where('slug', 'data-master')->first()?->id;

        if (!$masterId) return;

        $newMenus = [
            [
                'parent_id' => $masterId,
                'name' => 'Data Produk',
                'slug' => 'products.index',
                'icon' => null,
                'order_no' => 13,
            ],
            [
                'parent_id' => $masterId,
                'name' => 'Data Mitra',
                'slug' => 'mitra.index',
                'icon' => null,
                'order_no' => 14,
            ]
        ];

        foreach ($newMenus as $menuData) {
            $menu = Menu::updateOrCreate(
                ['slug' => $menuData['slug']],
                $menuData
            );

            // Give permission to super-admin
            $superAdmin = Role::where('slug', 'super-admin')->first();
            if ($superAdmin) {
                $superAdmin->menus()->syncWithoutDetaching([
                    $menu->id => [
                        'can_read' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => true,
                    ]
                ]);
            }
        }
    }
}
