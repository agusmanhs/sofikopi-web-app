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
        Schema::table('mitra_pos_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('mitra_pos_settings', 'service_charge_percent')) {
                $table->decimal('service_charge_percent', 5, 2)->default(0)->after('monthly_revenue_target');
            }
            if (!Schema::hasColumn('mitra_pos_settings', 'tax_percent')) {
                $table->decimal('tax_percent', 5, 2)->default(0)->after('service_charge_percent');
            }
            if (!Schema::hasColumn('mitra_pos_settings', 'qris_fee_percent')) {
                $table->decimal('qris_fee_percent', 5, 2)->default(0)->after('tax_percent');
            }
            if (!Schema::hasColumn('mitra_pos_settings', 'transfer_fee_percent')) {
                $table->decimal('transfer_fee_percent', 5, 2)->default(0)->after('qris_fee_percent');
            }
            if (!Schema::hasColumn('mitra_pos_settings', 'edc_fee_percent')) {
                $table->decimal('edc_fee_percent', 5, 2)->default(0)->after('transfer_fee_percent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mitra_pos_settings', function (Blueprint $table) {
            $table->dropColumn([
                'service_charge_percent',
                'tax_percent',
                'qris_fee_percent',
                'transfer_fee_percent',
                'edc_fee_percent',
            ]);
        });
    }
};
