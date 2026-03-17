<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\ProductsRequest;
use App\Interfaces\Repositories\ProductsRepositoryInterface;
use App\Interfaces\Repositories\ProductSubCategoryRepositoryInterface;
use App\Interfaces\Repositories\ProductCategoryRepositoryInterface;
use App\Services\ProductsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
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

    public function store(ProductsRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);

            $result = $this->service->storeProduct($data, $request->file('cover'));
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
            return ResponseHelper::success($result, 'Produk berhasil diperbarui');
        });
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return ResponseHelper::success(null, 'Produk berhasil dihapus');
    }
}