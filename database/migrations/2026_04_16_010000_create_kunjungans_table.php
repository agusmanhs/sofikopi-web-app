<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kunjungans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('mitra_id')->constrained('mitras')->cascadeOnDelete();
            $table->date('tanggal_kunjungan');
            $table->text('espresso_calibration');
            $table->text('taste_notes');
            $table->text('flow_of_customers')->nullable();
            $table->text('feedback')->nullable();
            $table->text('note')->nullable();
            $table->string('foto_kunjungan')->nullable(); // single photo path
            $table->timestamps();

            $table->index(['user_id', 'tanggal_kunjungan']);
            $table->index('mitra_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungans');
    }
};
