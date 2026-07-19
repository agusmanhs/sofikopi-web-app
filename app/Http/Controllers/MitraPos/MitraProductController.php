<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\MitraProductRequest;
use App\Models\Mitra;
use App\Services\MitraPos\MitraMaterialService;
use App\Services\MitraPos\MitraProductService;
use App\Traits\LogsActivity;

class MitraProductController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected MitraProductService $service,
        protected MitraMaterialService $materialService
    ) {}

    public function index(Mitra $mitra)
    {
        $products = $this->service->paginateForMitra($mitra->id);

        return view('pages.mitra-pos.product.index', compact('mitra', 'products'));
    }

    public function create(Mitra $mitra)
    {
        $materials = $this->materialService->forMitra($mitra->id);

        return view('pages.mitra-pos.product.create', compact('mitra', 'materials'));
    }

    public function store(MitraProductRequest $request, Mitra $mitra)
    {
        $data = $request->validated();

        $product = $this->service->createForMitra($mitra->id, $data);

        $this->logActivity('created', 'mitra-pos', "Menambahkan produk: {$product->name} ({$mitra->name})", $product);

        return redirect()->route('mitra-product.index', $mitra)
            ->with('success', 'Produk berhasil ditambahkan');
    }

    /**
     * {product} is the SKU, not the numeric id — resolved explicitly and
     * tenant-scoped via the service (see MitraProductService::findForMitra),
     * never via implicit route-model binding: sku is only unique per-mitra.
     */
    public function show(Mitra $mitra, string $product)
    {
        $product = $this->service->findForMitra($mitra->id, $product);

        return view('pages.mitra-pos.product.show', compact('mitra', 'product'));
    }

    public function edit(Mitra $mitra, string $product)
    {
        $product = $this->service->findForMitra($mitra->id, $product);
        $materials = $this->materialService->forMitra($mitra->id);

        return view('pages.mitra-pos.product.edit', compact('mitra', 'product', 'materials'));
    }

    public function update(MitraProductRequest $request, Mitra $mitra, string $product)
    {
        $data = $request->validated();

        $product = $this->service->updateForMitra($mitra->id, $product, $data);

        $this->logActivity('updated', 'mitra-pos', "Memperbarui produk: {$product->name} ({$mitra->name})", $product);

        return redirect()->route('mitra-product.index', $mitra)
            ->with('success', 'Produk berhasil diperbarui');
    }

    public function destroy(Mitra $mitra, string $product)
    {
        $model = $this->service->findForMitra($mitra->id, $product);
        $this->service->deleteForMitra($mitra->id, $product);

        $this->logActivity('deleted', 'mitra-pos', "Menghapus produk: {$model->name} ({$mitra->name})", $model);

        return redirect()->route('mitra-product.index', $mitra)
            ->with('success', 'Produk berhasil dihapus');
    }
}
