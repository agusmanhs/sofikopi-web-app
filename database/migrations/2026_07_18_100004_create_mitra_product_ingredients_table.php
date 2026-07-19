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
        Schema::create('mitra_product_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_product_id')->constrained('mitra_products')->cascadeOnDelete();
            $table->foreignId('mitra_material_id')->constrained('mitra_materials')->restrictOnDelete();
            $table->decimal('qty', 15, 3);
            $table->timestamps();

            // Explicit short name — MySQL's default auto-generated name for
            // this composite unique index exceeds the 64-char identifier limit.
            $table->unique(['mitra_product_id', 'mitra_material_id'], 'mpi_product_material_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mitra_product_ingredients');
    }
};
