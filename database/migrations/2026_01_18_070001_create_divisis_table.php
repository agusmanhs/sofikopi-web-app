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
        // 1. Divisis
        Schema::create('divisis', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('kode', 20)->unique()->nullable();
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->integer('toleransi_terlambat')->default(0)->comment('dalam menit');
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        // 2. Kantors
        Schema::create('kantors', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('kode', 20)->unique()->nullable();
            $table->text('alamat')->nullable();
            $table->string('titik_lokasi')->nullable();
            $table->integer('radius_meter')->default(100)->comment('radius valid absensi');
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        // 3. Jenis Izins
        Schema::create('jenis_izins', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50);
            $table->string('kode', 20)->unique()->nullable();
            $table->boolean('butuh_surat')->default(false);
            $table->integer('max_hari')->nullable()->comment('maksimal hari izin, null = unlimited');
            $table->text('keterangan')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        // 4. Pegawais
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('divisi_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('kantor_id')->nullable();
            $table->string('nama_lengkap');
            $table->string('jabatan', 100)->nullable();
            $table->date('tgl_masuk')->nullable();
            $table->string('nip', 50)->nullable();
            $table->string('foto')->nullable();
            $table->enum('gender', ['L', 'P']);
            $table->string('no_telp')->nullable();
            $table->text('alamat')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();

            $table->foreign('kantor_id')->references('id')->on('kantors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
        Schema::dropIfExists('jenis_izins');
        Schema::dropIfExists('kantors');
        Schema::dropIfExists('divisis');
    }
};
