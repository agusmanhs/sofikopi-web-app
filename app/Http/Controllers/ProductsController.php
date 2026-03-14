<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\ProductsRequest;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Services\FileUploadService;
use App\Services\ProductsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    public function __construct(
        protected ProductsService $service,
        protected FileUploadService $fileUploadService
    ) {}

    public function index(Request $request)
    {
        // For standard page load
        if (!$request->wantsJson()) {
            $data = $this->service->all();
            $categories = ProductCategory::aktif()->get();
            $subCategories = ProductSubCategory::aktif()->with('category')->get();
            return view('pages.products.index', compact('data', 'categories', 'subCategories'));
        }

        // For AJAX list
        $data = $this->service->all();
        return ResponseHelper::success($data);
    }

    public function store(ProductsRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);

            if ($request->hasFile('cover')) {
                $media = $this->fileUploadService->upload($request->file('cover'), 'products', 'public', [
                    'width' => 500,
                    'height' => 500,
                    'crop' => true
                ]);
                $data['cover'] = $media->path;
            }

            $result = $this->service->create($data);
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

            if ($request->hasFile('cover')) {
                $media = $this->fileUploadService->upload($request->file('cover'), 'products', 'public', [
                    'width' => 500,
                    'height' => 500,
                    'crop' => true
                ]);
                $data['cover'] = $media->path;
            }

            $result = $this->service->update($id, $data);
            return ResponseHelper::success($result, 'Produk berhasil diperbarui');
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $product = $this->service->find($id);
            
            if ($product->cover) {
                $media = \App\Models\Media::where('path', $product->cover)->first();
                if ($media) {
                    $this->fileUploadService->delete($media);
                }
            }

            $this->service->delete($id);
            return ResponseHelper::success(null, 'Produk berhasil dihapus');
        });
    }
}