<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JadwalKerjaMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Parent group menu is created by AbsensiMenuSeeder ("absensi-menu").
        $parentMenu = Menu::where('slug', 'absensi-menu')->first();

        $menu = Menu::updateOrCreate(
            ['slug' => 'jadwal-kerja.index'],
            [
                'parent_id' => $parentMenu?->id,
                'name' => 'Penjadwalan Absen',
                'icon' => 'ri-calendar-schedule-line',
                'path' => '/jadwal-kerja',
                'order_no' => 5,
                'is_active' => true,
            ]
        );

        // Super Admin: full CRUD
        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin) {
            DB::table('role_menu')->updateOrInsert(
                ['role_id' => $superAdmin->id, 'menu_id' => $menu->id],
                ['can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true]
            );
        }

        // Admin role (same level of access as absensi.dashboard in AbsensiRoleMenuSeeder): full CRUD
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            DB::table('role_menu')->updateOrInsert(
                ['role_id' => $adminRole->id, 'menu_id' => $menu->id],
                ['can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true]
            );
        }

        // Ensure parent group is at least readable for admin (mirrors AbsensiRoleMenuSeeder grants)
        if ($parentMenu && $adminRole) {
            DB::table('role_menu')->updateOrInsert(
                ['role_id' => $adminRole->id, 'menu_id' => $parentMenu->id],
                ['can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true]
            );
        }

        // Regular users (pegawai) do NOT get access to this menu.

        $this->command->info('✅ Menu & permission Penjadwalan Absen (Jadwal Kerja) berhasil disinkronkan!');
    }
}
