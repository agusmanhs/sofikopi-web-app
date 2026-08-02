<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\StockAdjustmentRequest;
use App\Models\Mitra;
use App\Services\MitraPos\MitraContext;
use App\Services\MitraPos\MitraMaterialService;
use App\Services\MitraPos\MitraStockService;
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

    // --- Tenant portal (mitra-pos/stock), mitra context from MitraContext ---

    /**
     * Portal route (`mitra-pos/stock`) has no {mitra} route param — the
     * active mitra is derived from MitraContext, set earlier by the
     * `mitra.user` middleware.
     */
    public function index()
    {
        return $this->renderIndex($this->mitraContext->id(), null);
    }

    /**
     * Portal route (`mitra-pos/stock/movements`) — filterable ledger view,
     * reusing the 'mitra-stock.index' permission (segment 'movements' isn't
     * in CheckPermission's action map -> defaults to 'read', same as index).
     */
    public function movements(Request $request)
    {
        return $this->renderMovements($request, $this->mitraContext->id(), null);
    }

    /**
     * Portal-side manual stock adjustment (owner only — the route maps
     * 'adjust' -> 'update' and only the owner role has can_update on
     * mitra-stock.index). Same service call as the admin adjust() below,
     * mitra derived from MitraContext instead of a route param.
     */
    public function portalAdjust(StockAdjustmentRequest $request, string $material)
    {
        $mitraId = $this->mitraContext->id();
        $data = $request->validated();
        $material = $this->materialService->findForMitra($mitraId, $material);

        $movement = $this->stockService->adjustStock(
            mitraId: $mitraId,
            materialId: $material->id,
            signedDelta: (float) $data['delta'],
            notes: $data['notes'] ?? 'Penyesuaian stok manual',
            userId: auth()->id(),
        );

        $this->logActivity(
            'updated',
            'mitra-pos',
            "Menyesuaikan stok material: {$material->name}, delta {$data['delta']}",
            $movement
        );

        return redirect()->route('mitra-stock.index')
            ->with('success', 'Stok berhasil disesuaikan');
    }

    // --- Sofikopi-staff admin (mitra-pos/manage/{mitra}/stock) ---

    public function adminIndex(Mitra $mitra)
    {
        return $this->renderIndex($mitra->id, $mitra);
    }

    public function adminMovements(Request $request, Mitra $mitra)
    {
        return $this->renderMovements($request, $mitra->id, $mitra);
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

    private function renderIndex(int $mitraId, ?Mitra $mitra)
    {
        $materials = $this->materialService->forMitra($mitraId);
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.stock.index', compact('materials', 'mitra', 'routes'));
    }

    private function renderMovements(Request $request, int $mitraId, ?Mitra $mitra)
    {
        $filters = $request->only(['material_id', 'type', 'from', 'to']);

        $movements = $this->stockService->movementsForMitra($mitraId, $filters);
        $materials = $this->materialService->forMitra($mitraId);
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.stock.movements', compact('movements', 'materials', 'filters', 'mitra', 'routes'));
    }

    /**
     * Builds route URLs shared by stock/index.blade.php and
     * stock/movements.blade.php so the same views render for both the
     * tenant portal (no {mitra} param) and the Sofikopi-staff admin picker
     * ({mitra} route param) — same technique as
     * PosTransactionController::routesFor(). 'material'/'product' point at
     * the material/product management screens, which are already
     * dual-context controllers taking a Mitra param directly — the portal
     * side supplies the logged-in mitra user's own mitra via auth()->user()->mitra.
     */
    private function routesFor(?Mitra $mitra): array
    {
        return [
            'index' => $mitra
                ? route('mitra-pos-manage.stock.index', $mitra)
                : route('mitra-stock.index'),
            'movements' => $mitra
                ? route('mitra-pos-manage.stock.movements', $mitra)
                : route('mitra-stock.movements'),
            'material' => route('mitra-material.index', $mitra ?? auth()->user()->mitra),
            'product' => route('mitra-product.index', $mitra ?? auth()->user()->mitra),
        ];
    }
}
