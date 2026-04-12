<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;

class BackupMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create the menu item
        $menu = Menu::updateOrCreate(
            ['slug' => 'backup'],
            [
                'name' => 'Database Backup',
                'icon' => 'ri-database-2-line',
                'path' => 'backup',
                'order_no' => 99, // Place it at the end
                'is_active' => true,
            ]
        );

        // 2. Assign to Super Admin and Admin
        $roles = Role::whereIn('slug', ['super-admin', 'admin'])->get();
        foreach ($roles as $role) {
            $role->menus()->syncWithoutDetaching([
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
