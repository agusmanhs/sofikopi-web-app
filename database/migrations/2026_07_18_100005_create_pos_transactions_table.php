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
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitras')->cascadeOnDelete();
            $table->string('transaction_no')->unique();
            $table->enum('sales_mode', ['dine_in', 'take_away', 'online']);
            // 'transfer'/'edc' added after this migration first shipped — see
            // the companion 2026_07_22_..._expand_payment_method_enum
            // migration, which ALTERs this column on databases where this
            // migration already ran (MySQL only; fresh SQLite installs/tests
            // get the 4 values directly from this line).
            $table->enum('payment_method', ['cash', 'qris', 'transfer', 'edc']);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('service_charge', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('total_hpp', 15, 2)->default(0);
            $table->decimal('total_cogs', 15, 2)->default(0);
            // Deducted by the payment provider, never added to the customer's
            // bill — recorded only so daily recap reporting can show
            // "penerimaan bersih" (net of admin fees) per the DATA sheet.
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->enum('status', ['completed', 'voided'])->default('completed');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transacted_at');
            $table->timestamps();

            $table->index(['mitra_id', 'transacted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
    }
};
