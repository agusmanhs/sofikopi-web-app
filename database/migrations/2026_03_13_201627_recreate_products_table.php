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
        Schema::dropIfExists('products');
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_sub_category_id')->constrained('product_sub_categories')->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->bigInteger('buying_price')->default(0);
            $table->bigInteger('selling_price')->default(0);
            $table->integer('current_stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->string('unit')->default('Pcs'); // Gram, Kilogram, Mililiter, Liter, Pcs, Bag etc
            $table->decimal('netto', 10, 2)->nullable();
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->json('attributes')->nullable(); // For dynamic fields (Origin, Roast Level, Flavor etc)
            $table->string('cover')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
