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
            // RoleAndMenuSeeder::class,
            // Dinonaktifkan lagi — men-seed kredensial default
            // (superadmin@gmail.com / password) yang di-reset ulang setiap
            // seeder ini jalan. Aktifkan manual hanya di lokal/testing bila
            // perlu akun awal.
            // UserSeeder::class,
            // AbsensiMasterSeeder::class,
            // ShiftSeeder::class,
            // AbsensiMenuSeeder::class,
            // Needs 'absensi-menu' parent from AbsensiMenuSeeder above.
            // JadwalKerjaMenuSeeder::class,
            // Grants super-admin full CRUD on every menu that exists at this
            // point (Menu::all()) plus fixed slug lists for admin/user. MUST
            // run BEFORE MitraPosMenuSeeder: the Mitra POS tenant-portal
            // submenus (Dashboard, Kasir POS, Stok Bahan, dll.) are
            // intentionally scoped to mitra-owner/mitra-kasir only — a
            // super-admin browsing the main admin sidebar should only see
            // "Kelola Mitra POS", not the tenant's operational screens. If
            // this ran after MitraPosMenuSeeder, its blanket Menu::all()
            // grant would wrongly override that restriction.
            // AbsensiRoleMenuSeeder::class,
            // Self-grants super-admin on its own submenus explicitly, so
            // order relative to AbsensiRoleMenuSeeder doesn't matter for it.
            // SalesOrderMenuSeeder::class,
            MitraPosMenuSeeder::class,
            AkuntansiCoaBackfillSeeder::class,
        ]);
    }
}
