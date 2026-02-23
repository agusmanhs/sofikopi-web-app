<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AbsensiRoleMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Get primary roles
        $superAdmin = Role::where('slug', 'super-admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();
        $userRole = Role::where('slug', 'user')->first();

        // 1. Super Admin gets EVERYTHING
        $allMenus = Menu::all();
        foreach ($allMenus as $menu) {
            DB::table('role_menu')->updateOrInsert(
                ['role_id' => $superAdmin->id, 'menu_id' => $menu->id],
                ['can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true]
            );
        }

        // 2. Admin access (Almost everything except core user management)
        $adminSlugs = [
            'dashboard',
            'data-master', 'divisi.index', 'shift.index', 'kantor.index', 'pegawai.index', 'jenis-izin.index',
            'absensi-menu', 'absensi.index', 'absensi.history', 'absensi.dashboard', 'absensi.rekap',
            'izin-menu', 'izin.create', 'izin.index', 'izin.admin',
            'activity-log.index'
        ];

        foreach ($adminSlugs as $slug) {
            $menu = Menu::where('slug', $slug)->first();
            if ($menu && $adminRole) {
                DB::table('role_menu')->updateOrInsert(
                    ['role_id' => $adminRole->id, 'menu_id' => $menu->id],
                    ['can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true]
                );
            }
        }

        // 3. Regular User (Pegawai) access
        $userSlugs = [
            'dashboard',
            'absensi-menu', 'absensi.index', 'absensi.history',
            'izin-menu', 'izin.create', 'izin.index'
        ];

        foreach ($userSlugs as $slug) {
            $menu = Menu::where('slug', $slug)->first();
            if ($menu && $userRole) {
                DB::table('role_menu')->updateOrInsert(
                    ['role_id' => $userRole->id, 'menu_id' => $menu->id],
                    ['can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => false]
                );
            }
        }

        $this->command->info('✅ Role permissions for Absensi successfully updated and synchronized!');
    }
}
