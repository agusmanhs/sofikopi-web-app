<?php

namespace App\Services\MitraPos;

use Illuminate\Support\Facades\DB;

/**
 * Single owner of the "wipe one mitra's POS data" operation, used by
 * MitraPosManageController::destroy() (the "Hapus dari Sistem POS" button)
 * so the delete order is defined in exactly one place.
 *
 * Deliberately never touches: the mitras master row, its users, kunjungan
 * history, or sales orders. Hard deletes (not soft) so the per-mitra
 * unique SKU indexes are truly freed for re-seeding/re-input.
 */
class MitraPosResetService
{
    /**
     * @return array<string, int> row counts per table label
     */
    public function counts(int $mitraId): array
    {
        return [
            'Transaksi POS' => DB::table('pos_transactions')->where('mitra_id', $mitraId)->count(),
            'Pergerakan stok' => DB::table('mitra_stock_movements')->where('mitra_id', $mitraId)->count(),
            'Produk (menu)' => DB::table('mitra_products')->where('mitra_id', $mitraId)->count(),
            'Material' => DB::table('mitra_materials')->where('mitra_id', $mitraId)->count(),
        ];
    }

    /**
     * @return array<string, int> the counts that were deleted
     */
    public function wipe(int $mitraId): array
    {
        $counts = $this->counts($mitraId);

        DB::transaction(function () use ($mitraId) {
            // Order matters for the FK graph:
            // pos_transactions → items (cascade); mitra_products →
            // ingredients (cascade, and ingredients restrictOnDelete their
            // material, so products must go before materials);
            // mitra_materials → stock movements (cascade).
            DB::table('pos_transactions')->where('mitra_id', $mitraId)->delete();
            DB::table('mitra_products')->where('mitra_id', $mitraId)->delete();
            DB::table('mitra_materials')->where('mitra_id', $mitraId)->delete();
            DB::table('mitra_pos_settings')->where('mitra_id', $mitraId)->delete();
        });

        return $counts;
    }
}
