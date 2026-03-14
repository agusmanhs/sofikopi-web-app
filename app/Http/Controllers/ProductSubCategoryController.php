<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\ProductSubCategoryRequest;
use App\Services\ProductSubCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSubCategoryController extends Controller
{
    public function __construct(protected ProductSubCategoryService $service) {}

    public function index()
    {
        $data = $this->service->all();
        return ResponseHelper::success($data);
    }

    public function store(ProductSubCategoryRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);
            $result = $this->service->create($data);
            return ResponseHelper::success($result, 'Sub-kategori produk berhasil ditambahkan');
        });
    }

    public function update(ProductSubCategoryRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);
            $result = $this->service->update($id, $data);
            return ResponseHelper::success($result, 'Sub-kategori produk berhasil diperbarui');
        });
    }

    public function show($id)
    {
        $data = $this->service->find($id);
        return ResponseHelper::success($data);
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $this->service->delete($id);
            return ResponseHelper::success(null, 'Sub-kategori produk berhasil dihapus');
        });
    }

    public function getByCategory($categoryId)
    {
        $data = $this->service->getByCategory($categoryId);
        return response()->json($data);
    }
}
