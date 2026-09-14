<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL-only ALTER, same pattern as
     * 2026_07_22_000002_expand_payment_method_enum_on_pos_transactions_table:
     * a fresh install (SQLite tests, or a brand-new MySQL database) already
     * gets 'stock_purchase' from the 2026_07_26_..._create_akuntansi_journal_
     * entries migration, so this is a no-op there. It only does real work on
     * a MySQL database where that migration ran before the "+ Stok" purchase
     * feature (AkuntansiJournalService::postForPurchase()) added
     * 'stock_purchase' to the enum.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE akuntansi_journal_entries MODIFY source_type ENUM('manual', 'pos_sale', 'pos_void', 'stock_purchase') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Menyempitkan enum tanpa cek dulu akan diam-diam mengosongkan
        // source_type baris 'stock_purchase' yang sudah ada (MySQL non-strict
        // mode) — batalkan rollback selama masih ada baris seperti itu.
        $stillInUse = DB::table('akuntansi_journal_entries')->where('source_type', 'stock_purchase')->exists();

        if ($stillInUse) {
            throw new RuntimeException("Tidak bisa rollback: masih ada akuntansi_journal_entries dengan source_type='stock_purchase'.");
        }

        DB::statement("ALTER TABLE akuntansi_journal_entries MODIFY source_type ENUM('manual', 'pos_sale', 'pos_void') NOT NULL");
    }
};
