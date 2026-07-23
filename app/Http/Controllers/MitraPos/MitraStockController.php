<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\StockAdjustmentRequest;
use App\Models\Mitra;
use App\Services\MitraPos\MitraMaterialService;
use App\Services\MitraPos\MitraStockService;
use App\Services\MitraPos\MitraContext;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class MitraStockController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected MitraMaterialService $materialService,
        protected MitraStockService $stockService,
        protected MitraContext $mitraContext
    ) {}

    /**
     * Portal route (`mitra-pos/stock`) has no {mitra} route param — the
     * active mitra is derived from MitraContext, set earlier by the
     * `mitra.user` middleware.
     */
    public function index()
    {
        $mitraId = $this->mitraContext->id();
        $materials = $this->materialService->forMitra($mitraId);

        return view('pages.mitra-pos.stock.index', compact('materials'));
    }

    /**
     * Portal route (`mitra-pos/stock/movements`) — filterable ledger view,
     * reusing the 'mitra-stock.index' permission (segment 'movements' isn't
     * in CheckPermission's action map -> defaults to 'read', same as index).
     */
    public function movements(Request $request)
    {
        $mitraId = $this->mitraContext->id();
        $filters = $request->only(['material_id', 'type', 'from', 'to']);

        $movements = $this->stockService->movementsForMitra($mitraId, $filters);
        $materials = $this->materialService->forMitra($mitraId);

        return view('pages.mitra-pos.stock.movements', compact('movements', 'materials', 'filters'));
    }

    /**
     * Admin-side manual stock adjustment: POST material/{material}/adjust
     * under `mitra-pos/manage/{mitra}`. {material} is the SKU, resolved
     * tenant-scoped via the service — see MitraMaterialController for why
     * this never uses implicit route-model binding on sku.
     */
    public function adjust(StockAdjustmentRequest $request, Mitra $mitra, string $material)
    {
        $data = $request->validated();
        $material = $this->materialService->findForMitra($mitra->id, $material);

        $movement = $this->stockService->adjustStock(
            mitraId: $mitra->id,
            materialId: $material->id,
            signedDelta: (float) $data['delta'],
            notes: $data['notes'] ?? 'Penyesuaian stok manual',
            userId: auth()->id(),
        );

        $this->logActivity(
            'updated',
            'mitra-pos',
            "Menyesuaikan stok material: {$material->name} ({$mitra->name}), delta {$data['delta']}",
            $movement
        );

        return redirect()->route('mitra-material.index', $mitra)
            ->with('success', 'Stok berhasil disesuaikan');
    }
}
