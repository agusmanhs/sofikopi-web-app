<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndMenuSeeder::class,
            UserSeeder::class,
            AbsensiMasterSeeder::class,
            ShiftSeeder::class,
            AbsensiMenuSeeder::class,
            AbsensiRoleMenuSeeder::class,
        ]);
    }
}
