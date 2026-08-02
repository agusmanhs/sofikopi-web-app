<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Services\MitraPos\MitraContext;
use App\Services\MitraPos\MitraDashboardService;

class MitraDashboardController extends Controller
{
    public function __construct(
        protected MitraDashboardService $service,
        protected MitraContext $mitraContext
    ) {}

    // --- Tenant portal (mitra-pos/dashboard), mitra context from MitraContext ---

    public function index()
    {
        return $this->render($this->mitraContext->id(), null);
    }

    // --- Sofikopi-staff admin (mitra-pos/manage/{mitra}/dashboard) ---

    public function adminIndex(Mitra $mitra)
    {
        return $this->render($mitra->id, $mitra);
    }

    private function render(int $mitraId, ?Mitra $mitra)
    {
        $stats = $this->service->stats($mitraId);
        $paymentMix = $this->service->paymentMix($mitraId);
        $topProducts = $this->service->topProducts($mitraId);
        $stockAlerts = $this->service->stockAlerts($mitraId);
        $target = $this->service->target($mitraId);

        return view('pages.mitra-pos.dashboard.index', compact(
            'stats',
            'paymentMix',
            'topProducts',
            'stockAlerts',
            'target',
            'mitra'
        ));
    }
}
