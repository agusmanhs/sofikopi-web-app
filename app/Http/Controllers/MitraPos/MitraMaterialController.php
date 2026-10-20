<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\MitraMaterialRequest;
use App\Models\Mitra;
use App\Services\MitraPos\MitraMaterialService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class MitraMaterialController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected MitraMaterialService $service
    ) {}

    public function index(Request $request, Mitra $mitra)
    {
        $search = $request->query('q');
        $category = $request->query('category');
        $materials = $this->service->paginateForMitra(
            $mitra->id,
            10,
            is_string($search) ? $search : null,
            is_string($category) ? $category : null
        );
        $categories = $this->service->categoriesForMitra($mitra->id);
        // Unpaginated, full list for the "+ Stok" purchase modal's material
        // picker — the table above only shows the current page.
        $allMaterials = $this->service->forMitra($mitra->id);

        return view('pages.mitra-pos.material.index', compact('mitra', 'materials', 'allMaterials', 'categories'));
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

        $usedBy = $this->service->productNamesUsing($mitra->id, $model);
        if ($usedBy->isNotEmpty()) {
            $names = $usedBy->take(5)->implode(', ');
            $more = $usedBy->count() > 5 ? ' dan '.($usedBy->count() - 5).' produk lain' : '';

            return redirect()->route('mitra-material.index', $mitra)
                ->with('error', "Material dipakai di resep: {$names}{$more}. Ubah ke Non-Aktif atau hapus dari resepnya dulu.");
        }

        $this->service->deleteForMitra($mitra->id, $material);

        $this->logActivity('deleted', 'mitra-pos', "Menghapus material: {$model->name} ({$mitra->name})", $model);

        return redirect()->route('mitra-material.index', $mitra)
            ->with('success', 'Material berhasil dihapus');
    }
}
