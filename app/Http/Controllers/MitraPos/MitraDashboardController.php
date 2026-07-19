<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Services\MitraPos\MitraContext;
use App\Services\MitraPos\MitraDashboardService;

class MitraDashboardController extends Controller
{
    public function __construct(
        protected MitraDashboardService $service,
        protected MitraContext $mitraContext
    ) {}

    public function index()
    {
        $mitraId = $this->mitraContext->id();

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
            'target'
        ));
    }
}
