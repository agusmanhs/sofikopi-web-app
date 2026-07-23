<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\MitraStockOpnameRequest;
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

    public function index()
    {
        $opnames = MitraStockOpname::forMitra($this->mitraContext->id())
            ->orderByDesc('opname_date')
            ->paginate(15);

        return view('pages.mitra-pos.opname.index', compact('opnames'));
    }

    public function create()
    {
        $materials = $this->materialService->forMitra($this->mitraContext->id())
            ->where('is_active', true)
            ->values();

        return view('pages.mitra-pos.opname.create', compact('materials'));
    }

    public function store(MitraStockOpnameRequest $request)
    {
        $data = $request->validated();

        $counts = collect($data['physical_qty'])
            ->map(fn ($qty, $materialId) => ['mitra_material_id' => (int) $materialId, 'physical_qty' => (float) $qty])
            ->values()
            ->all();

        $opname = $this->stockService->performOpname(
            mitraId: $this->mitraContext->id(),
            userId: auth()->id(),
            counts: $counts,
            notes: $data['notes'] ?? null,
        );

        return redirect()->route('mitra-opname.show', $opname)
            ->with('success', 'Stock opname berhasil disimpan.');
    }

    /**
     * $opname is the opname_no (e.g. OPN/LALLO/20260722/0001), not the
     * numeric id — same slash-in-param handling as PosTransactionController.
     */
    public function show(string $opname)
    {
        $opname = MitraStockOpname::forMitra($this->mitraContext->id())
            ->where('opname_no', $opname)
            ->with('items.material', 'user')
            ->firstOrFail();

        return view('pages.mitra-pos.opname.show', compact('opname'));
    }
}
