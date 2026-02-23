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
        // 1. Lokasi Absen Pegawai
        Schema::create('lokasi_absen_pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawais')->cascadeOnDelete();
            $table->foreignId('kantor_id')->constrained('kantors')->cascadeOnDelete();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->unique(['pegawai_id', 'kantor_id'], 'unique_pegawai_kantor');
        });

        // 2. Absensis
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('foto_masuk')->nullable();
            $table->string('foto_pulang')->nullable();
            $table->string('latitude_masuk')->nullable();
            $table->string('longitude_masuk')->nullable();
            $table->string('latitude_pulang')->nullable();
            $table->string('longitude_pulang')->nullable();
            $table->string('lokasi_masuk')->nullable();
            $table->string('lokasi_pulang')->nullable();
            $table->string('device_masuk')->nullable();
            $table->string('device_pulang')->nullable();
            $table->string('status')->default('Hadir')->comment('Hadir, Terlambat, Izin, Alpa, Cuti, Sakit');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['pegawai_id', 'tanggal']);
        });

        // 3. Izins
        Schema::create('izins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jenis_izin_id')->constrained('jenis_izins');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->text('alasan')->nullable();
            $table->string('file_surat')->nullable();
            $table->enum('status_approval', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('izins');
        Schema::dropIfExists('absensis');
        Schema::dropIfExists('lokasi_absen_pegawais');
    }
};
