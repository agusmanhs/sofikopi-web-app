<?php

namespace App\Services\MitraPos;

use App\Models\PosTransaction;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Read-only aggregation for the daily sales recap (sheet DATA in the Cafe
 * Lallo Kendari workbook) — one row per calendar date, so a mitra can close
 * their books daily instead of re-deriving these numbers in Excel.
 */
class MitraReportService
{
    /**
     * Returns one row per date in [$from, $to] inclusive — including days
     * with zero transactions, matching the sheet DATA layout (every date of
     * the month is listed, not just days with activity).
     */
    public function dailyRecap(int $mitraId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $completed = PosTransaction::forMitra($mitraId)
            ->where('status', 'completed')
            ->whereDate('transacted_at', '>=', $from->toDateString())
            ->whereDate('transacted_at', '<=', $to->toDateString())
            ->selectRaw(
                'DATE(transacted_at) as tanggal,'
                .' COALESCE(SUM(subtotal), 0) as pendapatan_kotor,'
                .' COALESCE(SUM(discount), 0) as diskon,'
                .' COALESCE(SUM(service_charge), 0) as service_charge,'
                .' COALESCE(SUM(tax), 0) as pajak,'
                .' COALESCE(SUM(grand_total), 0) as pendapatan_bersih,'
                .' COALESCE(SUM(total_hpp), 0) as hpp,'
                .' COALESCE(SUM(total_cogs), 0) as cogs,'
                .' COALESCE(SUM(admin_fee), 0) as potongan_admin,'
                ." COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN grand_total ELSE 0 END), 0) as penerimaan_cash,"
                ." COALESCE(SUM(CASE WHEN payment_method = 'qris' THEN grand_total ELSE 0 END), 0) as penerimaan_qris,"
                ." COALESCE(SUM(CASE WHEN payment_method = 'transfer' THEN grand_total ELSE 0 END), 0) as penerimaan_transfer,"
                ." COALESCE(SUM(CASE WHEN payment_method = 'edc' THEN grand_total ELSE 0 END), 0) as penerimaan_edc,"
                .' COUNT(*) as jumlah_transaksi'
            )
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        // Voided transactions are grouped by voided_at, not transacted_at —
        // a sale voided today counts in today's void column even if it was
        // originally transacted on an earlier date (matches sheet DATA's
        // DISKON/VOID column intent: what happened to the books today).
        $voided = PosTransaction::forMitra($mitraId)
            ->where('status', 'voided')
            ->whereNotNull('voided_at')
            ->whereDate('voided_at', '>=', $from->toDateString())
            ->whereDate('voided_at', '<=', $to->toDateString())
            ->selectRaw('DATE(voided_at) as tanggal, COALESCE(SUM(grand_total), 0) as void_total, COUNT(*) as void_count')
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $rows = collect();

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $key = $date->toDateString();
            $c = $completed->get($key);
            $v = $voided->get($key);

            $pendapatanBersih = (float) ($c->pendapatan_bersih ?? 0);
            $potonganAdmin = (float) ($c->potongan_admin ?? 0);

            $rows->push([
                'tanggal' => $date->copy(),
                'pendapatan_kotor' => (float) ($c->pendapatan_kotor ?? 0),
                'diskon' => (float) ($c->diskon ?? 0),
                'service_charge' => (float) ($c->service_charge ?? 0),
                'pajak' => (float) ($c->pajak ?? 0),
                'pendapatan_bersih' => $pendapatanBersih,
                'hpp' => (float) ($c->hpp ?? 0),
                'cogs' => (float) ($c->cogs ?? 0),
                'penerimaan_cash' => (float) ($c->penerimaan_cash ?? 0),
                'penerimaan_qris' => (float) ($c->penerimaan_qris ?? 0),
                'penerimaan_transfer' => (float) ($c->penerimaan_transfer ?? 0),
                'penerimaan_edc' => (float) ($c->penerimaan_edc ?? 0),
                'potongan_admin' => $potonganAdmin,
                'penerimaan_bersih' => $pendapatanBersih - $potonganAdmin,
                'void_total' => (float) ($v->void_total ?? 0),
                'void_count' => (int) ($v->void_count ?? 0),
                'jumlah_transaksi' => (int) ($c->jumlah_transaksi ?? 0),
            ]);
        }

        return $rows;
    }
}
