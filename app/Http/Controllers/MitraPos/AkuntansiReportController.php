<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Services\MitraPos\AkuntansiJournalService;
use App\Services\MitraPos\MitraContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class AkuntansiReportController extends Controller
{
    private const MAX_RANGE_DAYS = 366;

    public function __construct(
        protected AkuntansiJournalService $service,
        protected MitraContext $mitraContext
    ) {}

    // --- Tenant portal, mitra context from MitraContext ---

    public function neraca(Request $request)
    {
        return $this->renderNeraca($request, $this->mitraContext->id(), null);
    }

    public function labaRugi(Request $request)
    {
        return $this->renderLabaRugi($request, $this->mitraContext->id(), null);
    }

    // --- Sofikopi-staff admin (mitra-pos/manage/{mitra}/akuntansi/...) ---

    public function adminNeraca(Request $request, Mitra $mitra)
    {
        return $this->renderNeraca($request, $mitra->id, $mitra);
    }

    public function adminLabaRugi(Request $request, Mitra $mitra)
    {
        return $this->renderLabaRugi($request, $mitra->id, $mitra);
    }

    private function renderNeraca(Request $request, int $mitraId, ?Mitra $mitra)
    {
        $routes = $this->routesFor($mitra);

        $validator = Validator::make($request->only('as_of_date'), [
            'as_of_date' => 'nullable|date_format:Y-m-d',
        ], [
            'as_of_date.date_format' => 'Tanggal Neraca tidak valid. Pilih tanggal dari kalender.',
        ]);

        if ($validator->fails()) {
            return redirect($routes['neraca'])->withErrors($validator);
        }

        $asOfDate = $request->filled('as_of_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('as_of_date'))->startOfDay()
            : Carbon::now();

        $neraca = $this->service->neraca($mitraId, $asOfDate);

        return view('pages.mitra-pos.akuntansi.neraca', compact('neraca', 'mitra', 'routes'));
    }

    private function renderLabaRugi(Request $request, int $mitraId, ?Mitra $mitra)
    {
        $routes = $this->routesFor($mitra);

        $validator = Validator::make($request->only('from', 'to'), [
            'from' => 'nullable|date_format:Y-m-d',
            'to' => $request->filled('from') ? 'nullable|date_format:Y-m-d|after_or_equal:from' : 'nullable|date_format:Y-m-d',
        ], [
            'from.date_format' => 'Tanggal "Dari" tidak valid. Pilih tanggal dari kalender.',
            'to.date_format' => 'Tanggal "Sampai" tidak valid. Pilih tanggal dari kalender.',
            'to.after_or_equal' => 'Tanggal "Sampai" tidak boleh lebih awal dari tanggal "Dari".',
        ]);

        if ($validator->fails()) {
            return redirect($routes['laba_rugi'])->withErrors($validator);
        }

        $from = $request->filled('from')
            ? Carbon::createFromFormat('Y-m-d', $request->input('from'))->startOfDay()
            : Carbon::now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::createFromFormat('Y-m-d', $request->input('to'))->startOfDay()
            : Carbon::now()->endOfMonth()->startOfDay();

        if ($from->gt($to)) {
            return redirect($routes['laba_rugi'])->withErrors([
                'to' => 'Tanggal "Sampai" tidak boleh lebih awal dari tanggal "Dari".',
            ]);
        }

        if ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
            return redirect($routes['laba_rugi'])->withErrors([
                'to' => 'Rentang tanggal terlalu panjang. Pilih paling lama 1 tahun (366 hari).',
            ]);
        }

        $labaRugi = $this->service->labaRugi($mitraId, $from, $to);

        return view('pages.mitra-pos.akuntansi.laba-rugi', compact('labaRugi', 'mitra', 'routes'));
    }

    /**
     * Same routesFor() technique as AkuntansiCoaController/
     * AkuntansiJournalController — lets neraca.blade.php/laba-rugi.blade.php
     * render identically for the tenant portal and the Sofikopi-staff admin
     * picker.
     */
    private function routesFor(?Mitra $mitra): array
    {
        return [
            'coa' => $mitra
                ? route('mitra-pos-manage.akuntansi-coa.index', $mitra)
                : route('akuntansi-coa.index'),
            'jurnal' => $mitra
                ? route('mitra-pos-manage.akuntansi-jurnal.index', $mitra)
                : route('akuntansi-jurnal.index'),
            'neraca' => $mitra
                ? route('mitra-pos-manage.akuntansi-neraca.index', $mitra)
                : route('akuntansi-neraca.index'),
            'laba_rugi' => $mitra
                ? route('mitra-pos-manage.akuntansi-laba-rugi.index', $mitra)
                : route('akuntansi-laba-rugi.index'),
        ];
    }
}
