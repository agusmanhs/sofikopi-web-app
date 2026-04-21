<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\ProductsRequest;
use App\Interfaces\Repositories\ProductsRepositoryInterface;
use App\Interfaces\Repositories\ProductSubCategoryRepositoryInterface;
use App\Interfaces\Repositories\ProductCategoryRepositoryInterface;
use App\Services\ProductsService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Exports\ProductsTemplateExport;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductsController extends Controller
{
    use LogsActivity;

    protected $service;
    protected $repository;
    protected $subCategoryRepository;
    protected $categoryRepository;

    public function __construct(
        ProductsService $service,
        ProductsRepositoryInterface $repository,
        ProductSubCategoryRepositoryInterface $subCategoryRepository,
        ProductCategoryRepositoryInterface $categoryRepository
    ) {
        $this->service = $service;
        $this->repository = $repository;
        $this->subCategoryRepository = $subCategoryRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->repository->all();
            return ResponseHelper::success($data);
        }

        $subCategories = $this->subCategoryRepository->all();
        $categories = $this->categoryRepository->all();
        
        return view('pages.products.index', compact('subCategories', 'categories'));
    }

    /**
     * Import products from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv|max:2048',
        ]);

        try {
            Excel::import(new ProductsImport, $request->file('file'));

            $this->logActivity('imported', 'products', 'Mengimport data produk dari file Excel');

            return ResponseHelper::success(null, 'Data Produk berhasil di-import');
        } catch (\Exception $e) {
            return ResponseHelper::error('Gagal import: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        return Excel::download(new ProductsTemplateExport, 'template_import_produk.xlsx');
    }

    public function store(ProductsRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);

            $result = $this->service->storeProduct($data, $request->file('cover'));

            $this->logActivity('created', 'products', "Menambahkan produk: {$result->name}", $result);

            return ResponseHelper::success($result, 'Produk berhasil ditambahkan');
        });
    }

    public function show($id)
    {
        $data = $this->service->find($id);
        return ResponseHelper::success($data);
    }

    public function update(ProductsRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);

            $result = $this->service->updateProduct($id, $data, $request->file('cover'));

            $this->logActivity('updated', 'products', "Memperbarui produk: {$result->name}", $result);

            return ResponseHelper::success($result, 'Produk berhasil diperbarui');
        });
    }

    public function destroy($id)
    {
        $product = $this->service->find($id);
        $this->logActivity('deleted', 'products', "Menghapus produk: {$product->name}", $product);

        $this->service->delete($id);
        return ResponseHelper::success(null, 'Produk berhasil dihapus');
    }
}