<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarded with hasColumn(): on a fresh install these columns already
     * exist (added directly to the 2026_07_18_..._create_pos_transactions
     * migration), so this is only a real ALTER on databases where that
     * migration already ran before service_charge/tax/admin_fee existed.
     */
    public function up(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_transactions', 'service_charge')) {
                $table->decimal('service_charge', 15, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('pos_transactions', 'tax')) {
                $table->decimal('tax', 15, 2)->default(0)->after('service_charge');
            }
            if (!Schema::hasColumn('pos_transactions', 'admin_fee')) {
                $table->decimal('admin_fee', 15, 2)->default(0)->after('total_cogs');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropColumn(['service_charge', 'tax', 'admin_fee']);
        });
    }
};
