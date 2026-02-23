<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleAndMenuSeeder extends Seeder
{
    /**
     * This seeder now only handles the creation of core roles.
     * Menu creation and role-menu mapping is handled by AbsensiMenuSeeder and AbsensiRoleMenuSeeder.
     */
    public function run(): void
    {
        // 1. Roles
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin'],
            ['name' => 'Admin System', 'slug' => 'admin'],
            ['name' => 'User / Pegawai', 'slug' => 'user'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->command->info('✅ Core Roles created successfully!');
    }
}
