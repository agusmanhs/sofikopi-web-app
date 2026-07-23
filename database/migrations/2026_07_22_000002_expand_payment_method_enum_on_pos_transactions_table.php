<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL-only ALTER: on a fresh install (SQLite tests, or a brand-new
     * MySQL database) the 2026_07_18_..._create_pos_transactions migration
     * already creates the column with all 4 values, so this is a no-op
     * there. It only does real work on a MySQL database where that
     * migration ran before 'transfer'/'edc' were added to its enum.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE pos_transactions MODIFY payment_method ENUM('cash', 'qris', 'transfer', 'edc') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE pos_transactions MODIFY payment_method ENUM('cash', 'qris') NOT NULL");
    }
};
