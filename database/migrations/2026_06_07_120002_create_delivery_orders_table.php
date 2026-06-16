<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_number', 50)->unique();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->enum('delivery_type', ['delivery', 'self_pickup'])->default('delivery');
            $table->enum('status', ['pending', 'assigned', 'in_delivery', 'delivered'])->default('pending');
            $table->date('delivery_date')->nullable();
            $table->string('proof_photo')->nullable();
            $table->decimal('proof_latitude', 10, 8)->nullable();
            $table->decimal('proof_longitude', 11, 8)->nullable();
            $table->string('received_by_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
