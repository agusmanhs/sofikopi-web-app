<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PengaturanMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Parent group menu dibuat oleh AbsensiMenuSeeder ("absensi-menu").
        $parentMenu = Menu::where('slug', 'absensi-menu')->first();

        $menu = Menu::updateOrCreate(
            ['slug' => 'pengaturan-absensi.index'],
            [
                'parent_id' => $parentMenu?->id,
                'name' => 'Pengaturan Absensi',
                'icon' => 'ri-settings-3-line',
                'path' => '/pengaturan-absensi',
                'order_no' => 6,
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

        // Role lain (admin, pegawai) sengaja TIDAK diberi akses dulu supaya
        // seeder ini tidak mengubah permission yang sudah berjalan di produksi.
        // Bila nanti Admin System perlu akses, beri lewat halaman Permission
        // atau tambahkan grant eksplisit di sini.

        $this->command->info('✅ Menu & permission Pengaturan Absensi berhasil disinkronkan!');
    }
}
