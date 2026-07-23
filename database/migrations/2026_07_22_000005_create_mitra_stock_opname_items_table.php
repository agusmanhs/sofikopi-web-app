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
        Schema::create('mitra_stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_stock_opname_id')->constrained('mitra_stock_opnames')->cascadeOnDelete();
            $table->foreignId('mitra_material_id')->constrained('mitra_materials')->cascadeOnDelete();
            $table->decimal('system_qty', 15, 3);
            $table->decimal('physical_qty', 15, 3);
            $table->decimal('difference', 15, 3);
            // Snapshot of harga_satuan at opname time, so the rupiah value of
            // the difference stays correct even if price_per_pack changes later.
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mitra_stock_opname_items');
    }
};
