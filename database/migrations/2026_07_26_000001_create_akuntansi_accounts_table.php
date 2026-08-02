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
        Schema::create('akuntansi_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitras')->cascadeOnDelete();
            $table->string('code');
            $table->string('parent_code')->nullable();
            $table->string('name');
            $table->unsignedTinyInteger('level');
            $table->enum('account_type', ['aset', 'kewajiban', 'modal', 'pendapatan', 'hpp', 'biaya_adm_umum'])->nullable();
            $table->enum('position', ['neraca', 'laba_rugi'])->nullable();
            $table->boolean('is_postable')->default(false);
            $table->string('system_role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['mitra_id', 'code']);
            $table->index(['mitra_id', 'system_role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akuntansi_accounts');
    }
};
