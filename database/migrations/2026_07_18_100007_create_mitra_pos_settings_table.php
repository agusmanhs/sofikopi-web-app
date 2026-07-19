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
        Schema::create('mitra_pos_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->unique()->constrained('mitras')->cascadeOnDelete();
            $table->decimal('monthly_revenue_target', 15, 2)->nullable();
            $table->text('receipt_footer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mitra_pos_settings');
    }
};
