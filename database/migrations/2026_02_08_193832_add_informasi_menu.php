<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $menu = \App\Models\Menu::create([
            'parent_id' => 7,
            'name' => 'Informasi',
            'icon' => 'ri-information-line',
            'path' => 'informasi',
            'slug' => 'informasi',
            'order_no' => 90,
            'is_active' => true,
        ]);

        // Role 1 & 2 (Super Admin & Admin) have all access
        foreach ([1, 2] as $roleId) {
            \Illuminate\Support\Facades\DB::table('role_menu')->insert([
                'role_id' => $roleId,
                'menu_id' => $menu->id,
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Role 3 (User) only read access
        \Illuminate\Support\Facades\DB::table('role_menu')->insert([
            'role_id' => 3,
            'menu_id' => $menu->id,
            'can_create' => false,
            'can_read' => true,
            'can_update' => false,
            'can_delete' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $menu = \App\Models\Menu::where('slug', 'informasi')->first();
        if ($menu) {
            \Illuminate\Support\Facades\DB::table('role_menu')->where('menu_id', $menu->id)->delete();
            $menu->delete();
        }
    }
};
