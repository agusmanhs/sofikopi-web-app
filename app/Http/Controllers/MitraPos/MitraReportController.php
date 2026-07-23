<?php

namespace App\Http\Controllers\MitraPos;

use App\Exports\MitraDailyRecapExport;
use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Services\MitraPos\MitraContext;
use App\Services\MitraPos\MitraReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MitraReportController extends Controller
{
    public function __construct(
        protected MitraReportService $service,
        protected MitraContext $mitraContext
    ) {}

    public function index(Request $request)
    {
        [$from, $to] = $this->resolvePeriod($request);

        $rows = $this->service->dailyRecap($this->mitraContext->id(), $from, $to);
        $totals = $this->sumTotals($rows);

        return view('pages.mitra-pos.report.index', [
            'rows' => $rows,
            'totals' => $totals,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->resolvePeriod($request);
        $mitra = Mitra::findOrFail($this->mitraContext->id());

        $rows = $this->service->dailyRecap($mitra->id, $from, $to);

        $filename = "rekap-harian-{$mitra->code}-{$from->format('Ym')}.xlsx";

        return Excel::download(new MitraDailyRecapExport($rows), $filename);
    }

    /**
     * Defaults to the current calendar month when no period is given, same
     * as the sheet DATA layout (one full month per sheet).
     */
    private function resolvePeriod(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfMonth();

        return [$from, $to];
    }

    private function sumTotals($rows): array
    {
        return [
            'pendapatan_kotor' => $rows->sum('pendapatan_kotor'),
            'diskon' => $rows->sum('diskon'),
            'service_charge' => $rows->sum('service_charge'),
            'pajak' => $rows->sum('pajak'),
            'pendapatan_bersih' => $rows->sum('pendapatan_bersih'),
            'hpp' => $rows->sum('hpp'),
            'cogs' => $rows->sum('cogs'),
            'penerimaan_cash' => $rows->sum('penerimaan_cash'),
            'penerimaan_qris' => $rows->sum('penerimaan_qris'),
            'penerimaan_transfer' => $rows->sum('penerimaan_transfer'),
            'penerimaan_edc' => $rows->sum('penerimaan_edc'),
            'potongan_admin' => $rows->sum('potongan_admin'),
            'penerimaan_bersih' => $rows->sum('penerimaan_bersih'),
            'void_total' => $rows->sum('void_total'),
            'void_count' => $rows->sum('void_count'),
            'jumlah_transaksi' => $rows->sum('jumlah_transaksi'),
        ];
    }
}
