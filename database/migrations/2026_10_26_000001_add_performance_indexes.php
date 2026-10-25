<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dashboard/rekap memfilter absensis per tanggal saja; unique (pegawai_id, tanggal, shift_id)
        // tidak membantu karena pegawai_id ada di depan.
        $this->addIndex('absensis', ['tanggal'], 'absensis_tanggal_index');

        // Dicek di jalur absen masuk (sedang cuti/izin) dan dashboard.
        $this->addIndex('izins', ['pegawai_id', 'status_approval', 'tgl_mulai'], 'izins_pegawai_status_mulai_index');

        // Filter modul di halaman Activity Log, diurutkan menurut waktu.
        $this->addIndex('activity_logs', ['module', 'created_at'], 'activity_logs_module_created_at_index');
    }

    public function down(): void
    {
        foreach ([
            'absensis' => 'absensis_tanggal_index',
            'izins' => 'izins_pegawai_status_mulai_index',
            'activity_logs' => 'activity_logs_module_created_at_index',
        ] as $table => $index) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $index)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index));
            }
        }
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
    }
};
