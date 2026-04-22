<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\ProduksiRequest;
use App\Interfaces\Repositories\ProductsRepositoryInterface;
use App\Models\Products;
use App\Services\ProduksiService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class ProduksiController extends Controller
{
    use LogsActivity;

    protected $service;
    protected $productRepository;

    public function __construct(ProduksiService $service, ProductsRepositoryInterface $productRepository)
    {
        $this->service = $service;
        $this->productRepository = $productRepository;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getAllWithRelations();
            return ResponseHelper::success($data);
        }

        $products = $this->productRepository->all();
        return view('pages.produksi.index', compact('products'));
    }

    public function store(ProduksiRequest $request)
    {
        $data = $request->validated();
        $produksi = $this->service->storeProduksi($data);
        $product = Products::find($data['product_id']);

        $this->logActivity(
            'created',
            'produksi',
            "Menambahkan log stok [{$data['tipe']}] untuk produk {$product->name}: jumlah {$produksi->jumlah}",
            $produksi,
            ['tipe' => $data['tipe'], 'jumlah' => $produksi->jumlah, 'product' => $product->name]
        );

        return ResponseHelper::success($produksi, 'Data produksi/stok berhasil disimpan');
    }

    public function destroy($id)
    {
        $produksi = $this->service->getRepository()->find($id);
        $product = Products::find($produksi->product_id);

        $this->logActivity(
            'deleted',
            'produksi',
            "Menghapus log stok [{$produksi->tipe}] untuk produk " . ($product->name ?? '-') . ": jumlah {$produksi->jumlah} (stok dikembalikan)",
            $produksi,
            ['tipe' => $produksi->tipe, 'jumlah' => $produksi->jumlah, 'product' => $product->name ?? '-']
        );

        $this->service->deleteProduksi($id);
        return ResponseHelper::success(null, 'Data log berhasil dihapus dan stok dikembalikan');
    }
}
