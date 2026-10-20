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
        $allStockAlerts = $this->service->stockAlerts($mitraId);
        $stockAlertCounts = [
            'habis' => $allStockAlerts->where('alert_level', 'red')->count(),
            'menipis' => $allStockAlerts->where('alert_level', 'yellow')->count(),
            'total' => $allStockAlerts->count(),
        ];
        // Dashboard hanya menampilkan yang paling mendesak; sisanya lewat "Lihat semua".
        $stockAlerts = $allStockAlerts->take(5);
        $target = $this->service->target($mitraId);

        $reportUrl = $mitra
            ? route('mitra-pos-manage.report.index', $mitra)
            : route('mitra-report.index');

        // Admin Sofikopi punya Kelola Material (+ Stok = pembelian bertuang jurnal);
        // pemilik mitra hanya punya halaman Stok.
        $stockAllUrl = $mitra
            ? route('mitra-pos-manage.stock.index', ['mitra' => $mitra, 'stok' => 'perlu'])
            : route('mitra-stock.index', ['stok' => 'perlu']);
        $stockRowUrl = $mitra
            ? fn ($material) => route('mitra-material.index', ['mitra' => $mitra, 'beli' => $material->sku])
            : fn ($material) => route('mitra-stock.index', ['q' => $material->sku]);
        $stockRowLabel = $mitra ? '+ Stok' : 'Atur stok';

        return view('pages.mitra-pos.dashboard.index', compact(
            'stockAlertCounts',
            'stockAllUrl',
            'stockRowUrl',
            'stockRowLabel',
            'reportUrl',
            'stats',
            'paymentMix',
            'topProducts',
            'stockAlerts',
            'target',
            'mitra'
        ));
    }
}
