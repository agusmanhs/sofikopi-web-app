<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // firstOrCreate (not just where(...)->first()) so this seeder is
        // self-contained: it works even when RoleAndMenuSeeder hasn't run
        // (e.g. DatabaseSeeder currently only calls MitraPosMenuSeeder,
        // which only creates 'super-admin', 'mitra-owner', 'mitra-kasir').
        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin System']);
        $userRole = Role::firstOrCreate(['slug' => 'user'], ['name' => 'User / Pegawai']);

        // 1. Super Admin
        User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole->id,
            ]
        );

        // 2. Admin
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
            ]
        );

        // 3. Regular User
        User::updateOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password'),
                'role_id' => $userRole->id,
            ]
        );

        $this->command->info('Users created with password: password');
    }
}
