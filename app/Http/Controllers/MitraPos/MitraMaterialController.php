<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\MitraMaterialRequest;
use App\Models\Mitra;
use App\Services\MitraPos\MitraMaterialService;
use App\Traits\LogsActivity;

class MitraMaterialController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected MitraMaterialService $service
    ) {}

    public function index(Mitra $mitra)
    {
        $materials = $this->service->paginateForMitra($mitra->id);

        return view('pages.mitra-pos.material.index', compact('mitra', 'materials'));
    }

    public function create(Mitra $mitra)
    {
        return view('pages.mitra-pos.material.create', compact('mitra'));
    }

    public function store(MitraMaterialRequest $request, Mitra $mitra)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $material = $this->service->createForMitra($mitra->id, $data);

        $this->logActivity('created', 'mitra-pos', "Menambahkan material: {$material->name} ({$mitra->name})", $material);

        return redirect()->route('mitra-material.index', $mitra)
            ->with('success', 'Material berhasil ditambahkan');
    }

    /**
     * {material} is the SKU, not the numeric id — resolved explicitly and
     * tenant-scoped via the service (see MitraMaterialService::findForMitra),
     * never via implicit route-model binding: sku is only unique per-mitra.
     */
    public function show(Mitra $mitra, string $material)
    {
        $material = $this->service->findForMitra($mitra->id, $material);

        return view('pages.mitra-pos.material.show', compact('mitra', 'material'));
    }

    public function edit(Mitra $mitra, string $material)
    {
        $material = $this->service->findForMitra($mitra->id, $material);

        return view('pages.mitra-pos.material.edit', compact('mitra', 'material'));
    }

    public function update(MitraMaterialRequest $request, Mitra $mitra, string $material)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $material = $this->service->updateForMitra($mitra->id, $material, $data);

        $this->logActivity('updated', 'mitra-pos', "Memperbarui material: {$material->name} ({$mitra->name})", $material);

        return redirect()->route('mitra-material.index', $mitra)
            ->with('success', 'Material berhasil diperbarui');
    }

    public function destroy(Mitra $mitra, string $material)
    {
        $model = $this->service->findForMitra($mitra->id, $material);
        $this->service->deleteForMitra($mitra->id, $material);

        $this->logActivity('deleted', 'mitra-pos', "Menghapus material: {$model->name} ({$mitra->name})", $model);

        return redirect()->route('mitra-material.index', $mitra)
            ->with('success', 'Material berhasil dihapus');
    }
}
