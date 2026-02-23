<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AbsensiMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Menu::where('slug', 'divisi.parent')->delete();

        $menus = [
            // Dashboard
            [
                'slug' => 'dashboard',
                'name' => 'Dashboard',
                'icon' => 'ri-home-smile-line',
                'path' => '/',
                'parent_slug' => null,
                'order_no' => 1
            ],
            
            // Data Master (Group)
            [
                'slug' => 'data-master',
                'name' => 'Data Master',
                'icon' => 'ri-database-2-line',
                'path' => null,
                'parent_slug' => null,
                'order_no' => 2
            ],
            [
                'slug' => 'divisi.index',
                'name' => 'Data Divisi',
                'icon' => 'ri-community-line',
                'path' => '/divisi',
                'parent_slug' => 'data-master',
                'order_no' => 1
            ],
            [
                'slug' => 'shift.index',
                'name' => 'Shift Kerja',
                'icon' => 'ri-time-line',
                'path' => '/shift',
                'parent_slug' => 'data-master',
                'order_no' => 2
            ],
            [
                'slug' => 'kantor.index',
                'name' => 'Kantor',
                'icon' => 'ri-building-line',
                'path' => '/kantor',
                'parent_slug' => 'data-master',
                'order_no' => 3
            ],
            [
                'slug' => 'pegawai.index',
                'name' => 'Pegawai',
                'icon' => 'ri-user-star-line',
                'path' => '/pegawai',
                'parent_slug' => 'data-master',
                'order_no' => 4
            ],
            [
                'slug' => 'jenis-izin.index',
                'name' => 'Jenis Izin',
                'icon' => 'ri-file-settings-line',
                'path' => '/jenis-izin',
                'parent_slug' => 'data-master',
                'order_no' => 5
            ],
            [
                'slug' => 'hari-libur.index',
                'name' => 'Hari Libur',
                'icon' => 'ri-calendar-todo-line',
                'path' => '/hari-libur',
                'parent_slug' => 'data-master',
                'order_no' => 6
            ],
            
            // Absensi (Group)
            [
                'slug' => 'absensi-menu',
                'name' => 'Absensi',
                'icon' => 'ri-fingerprint-line',
                'path' => null,
                'parent_slug' => null,
                'order_no' => 3
            ],
            [
                'slug' => 'absensi.index',
                'name' => 'Absensi Saya',
                'icon' => 'ri-camera-line',
                'path' => '/absensi',
                'parent_slug' => 'absensi-menu',
                'order_no' => 1
            ],
            [
                'slug' => 'absensi.history',
                'name' => 'Riwayat Saya',
                'icon' => 'ri-history-line',
                'path' => '/absensi/history',
                'parent_slug' => 'absensi-menu',
                'order_no' => 2
            ],
            [
                'slug' => 'absensi.dashboard',
                'name' => 'Dashboard Admin',
                'icon' => 'ri-dashboard-line',
                'path' => '/absensi/dashboard',
                'parent_slug' => 'absensi-menu',
                'order_no' => 3
            ],
            [
                'slug' => 'absensi.rekap',
                'name' => 'Rekap Bulanan',
                'icon' => 'ri-file-chart-line',
                'path' => '/absensi/rekap',
                'parent_slug' => 'absensi-menu',
                'order_no' => 4
            ],
            
            // Izin & Cuti (Group)
            [
                'slug' => 'izin-menu',
                'name' => 'Izin & Cuti',
                'icon' => 'ri-calendar-check-line',
                'path' => null,
                'parent_slug' => null,
                'order_no' => 4
            ],
            [
                'slug' => 'izin.create',
                'name' => 'Ajukan Izin',
                'icon' => 'ri-add-circle-line',
                'path' => '/izin/create',
                'parent_slug' => 'izin-menu',
                'order_no' => 1
            ],
            [
                'slug' => 'izin.index',
                'name' => 'Izin Saya',
                'icon' => 'ri-file-list-3-line',
                'path' => '/izin',
                'parent_slug' => 'izin-menu',
                'order_no' => 2
            ],
            [
                'slug' => 'izin.admin',
                'name' => 'Kelola Izin (Admin)',
                'icon' => 'ri-checkbox-circle-line',
                'path' => '/izin/admin',
                'parent_slug' => 'izin-menu',
                'order_no' => 3
            ],

            // User Management (Group)
            [
                'slug' => 'user-management',
                'name' => 'User Management',
                'icon' => 'ri-user-settings-line',
                'path' => null,
                'parent_slug' => null,
                'order_no' => 5
            ],
            [
                'slug' => 'user.index',
                'name' => 'Users',
                'icon' => 'ri-user-line',
                'path' => '/user',
                'parent_slug' => 'user-management',
                'order_no' => 1
            ],
            [
                'slug' => 'role.index',
                'name' => 'Roles',
                'icon' => 'ri-shield-user-line',
                'path' => '/role',
                'parent_slug' => 'user-management',
                'order_no' => 2
            ],
            [
                'slug' => 'menu.index',
                'name' => 'Menus',
                'icon' => 'ri-menu-search-line',
                'path' => '/menu',
                'parent_slug' => 'user-management',
                'order_no' => 3
            ],
            [
                'slug' => 'permission.index',
                'name' => 'Permissions',
                'icon' => 'ri-lock-password-line',
                'path' => '/permission',
                'parent_slug' => 'user-management',
                'order_no' => 4
            ],

            // System
            [
                'slug' => 'activity-log.index',
                'name' => 'Activity Log',
                'icon' => 'ri-history-line',
                'path' => '/activity-log',
                'parent_slug' => null,
                'order_no' => 6
            ],
        ];

        // First pass: Create or update all menus
        foreach ($menus as $m) {
            Menu::updateOrCreate(
                ['slug' => $m['slug']],
                [
                    'name' => $m['name'],
                    'icon' => $m['icon'],
                    'path' => $m['path'],
                    'order_no' => $m['order_no'],
                    'is_active' => true,
                ]
            );
        }

        // Second pass: Link parents
        foreach ($menus as $m) {
            if ($m['parent_slug']) {
                $parent = Menu::where('slug', $m['parent_slug'])->first();
                if ($parent) {
                    Menu::where('slug', $m['slug'])->update(['parent_id' => $parent->id]);
                }
            } else {
                Menu::where('slug', $m['slug'])->update(['parent_id' => null]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('✅ Struktur Menu Absensi & Core berhasil disinkronkan!');
    }
}
