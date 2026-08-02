<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Services\MitraPos\AkuntansiJournalService;
use App\Services\MitraPos\MitraContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AkuntansiReportController extends Controller
{
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
        $asOfDate = $request->filled('as_of_date')
            ? Carbon::parse($request->input('as_of_date'))
            : Carbon::now();

        $neraca = $this->service->neraca($mitraId, $asOfDate);
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.akuntansi.neraca', compact('neraca', 'mitra', 'routes'));
    }

    private function renderLabaRugi(Request $request, int $mitraId, ?Mitra $mitra)
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))
            : Carbon::now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))
            : Carbon::now()->endOfMonth();

        $labaRugi = $this->service->labaRugi($mitraId, $from, $to);
        $routes = $this->routesFor($mitra);

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
