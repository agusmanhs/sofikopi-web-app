<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\MitraStockOpnameRequest;
use App\Models\Mitra;
use App\Models\MitraStockOpname;
use App\Services\MitraPos\MitraContext;
use App\Services\MitraPos\MitraMaterialService;
use App\Services\MitraPos\MitraStockService;

class MitraOpnameController extends Controller
{
    public function __construct(
        protected MitraStockService $stockService,
        protected MitraMaterialService $materialService,
        protected MitraContext $mitraContext
    ) {}

    // --- Tenant portal (mitra-pos/opname), mitra context from MitraContext ---

    public function index()
    {
        return $this->renderIndex($this->mitraContext->id(), null);
    }

    public function create()
    {
        return $this->renderCreate($this->mitraContext->id(), null);
    }

    public function store(MitraStockOpnameRequest $request)
    {
        return $this->handleStore($request, $this->mitraContext->id(), null);
    }

    /**
     * $opname is the opname_no (e.g. OPN/LALLO/20260722/0001), not the
     * numeric id — same slash-in-param handling as PosTransactionController.
     */
    public function show(string $opname)
    {
        return $this->renderShow($this->mitraContext->id(), $opname, null);
    }

    // --- Sofikopi-staff admin (mitra-pos/manage/{mitra}/opname) ---

    public function adminIndex(Mitra $mitra)
    {
        return $this->renderIndex($mitra->id, $mitra);
    }

    public function adminCreate(Mitra $mitra)
    {
        return $this->renderCreate($mitra->id, $mitra);
    }

    public function adminStore(MitraStockOpnameRequest $request, Mitra $mitra)
    {
        return $this->handleStore($request, $mitra->id, $mitra);
    }

    public function adminShow(Mitra $mitra, string $opname)
    {
        return $this->renderShow($mitra->id, $opname, $mitra);
    }

    private function renderIndex(int $mitraId, ?Mitra $mitra)
    {
        $opnames = MitraStockOpname::forMitra($mitraId)
            ->orderByDesc('opname_date')
            ->paginate(15);
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.opname.index', compact('opnames', 'mitra', 'routes'));
    }

    private function renderCreate(int $mitraId, ?Mitra $mitra)
    {
        $materials = $this->materialService->forMitra($mitraId)
            ->where('is_active', true)
            ->values();
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.opname.create', compact('materials', 'mitra', 'routes'));
    }

    private function handleStore(MitraStockOpnameRequest $request, int $mitraId, ?Mitra $mitra)
    {
        $data = $request->validated();

        $counts = collect($data['physical_qty'])
            ->map(fn ($qty, $materialId) => ['mitra_material_id' => (int) $materialId, 'physical_qty' => (float) $qty])
            ->values()
            ->all();

        $opname = $this->stockService->performOpname(
            mitraId: $mitraId,
            userId: auth()->id(),
            counts: $counts,
            notes: $data['notes'] ?? null,
        );

        $redirectRoute = $mitra ? 'mitra-pos-manage.opname.show' : 'mitra-opname.show';
        $redirectParams = $mitra ? [$mitra, $opname] : [$opname];

        return redirect()->route($redirectRoute, $redirectParams)
            ->with('success', 'Stock opname berhasil disimpan.');
    }

    private function renderShow(int $mitraId, string $opnameNo, ?Mitra $mitra)
    {
        $opname = MitraStockOpname::forMitra($mitraId)
            ->where('opname_no', $opnameNo)
            ->with('items.material', 'user')
            ->firstOrFail();
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.opname.show', compact('opname', 'mitra', 'routes'));
    }

    /**
     * Builds route URLs shared by opname/{index,create,show}.blade.php so
     * the same views render for both the tenant portal (no {mitra} param)
     * and the Sofikopi-staff admin picker ({mitra} route param) — same
     * technique as PosTransactionController::routesFor().
     */
    private function routesFor(?Mitra $mitra): array
    {
        return [
            'index' => $mitra
                ? route('mitra-pos-manage.opname.index', $mitra)
                : route('mitra-opname.index'),
            'create' => $mitra
                ? route('mitra-pos-manage.opname.create', $mitra)
                : route('mitra-opname.create'),
            'store' => $mitra
                ? route('mitra-pos-manage.opname.store', $mitra)
                : route('mitra-opname.store'),
            'show' => fn (string $opnameNo) => $mitra
                ? route('mitra-pos-manage.opname.show', [$mitra, $opnameNo])
                : route('mitra-opname.show', $opnameNo),
        ];
    }
}
