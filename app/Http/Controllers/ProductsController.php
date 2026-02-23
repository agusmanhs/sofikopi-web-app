<?php

namespace App\Http\Controllers;

use App\Services\ProductsService;
use App\Http\Requests\ProductsRequest;
use Illuminate\Http\Request;

use App\Services\FileUploadService;

class ProductsController extends Controller
{
    public function __construct(
        protected ProductsService $service,
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = $this->service->all();
        return view('pages.products.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductsRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        if ($request->hasFile('cover')) {
            $media = $this->fileUploadService->upload($request->file('cover'), 'products', 'public', [
                'width' => 500,
                'height' => 500,
                'crop' => true
            ]);
            $data['cover'] = $media->path;
        }

        $this->service->create($data);

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = $this->service->find($id);
        return view('pages.products.show', compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data = $this->service->find($id);
        return view('pages.products.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductsRequest $request, $id)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        if ($request->hasFile('cover')) {
            $media = $this->fileUploadService->upload($request->file('cover'), 'products', 'public', [
                'width' => 500,
                'height' => 500,
                'crop' => true
            ]);
            $data['cover'] = $media->path;
        }

        $this->service->update($id, $data);

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = $this->service->find($id);
        
        // Delete cover image if exists
        if ($product->cover) {
            // Find media by path
            $media = \App\Models\Media::where('path', $product->cover)->first();
            if ($media) {
                $this->fileUploadService->delete($media);
            }
        }

        $this->service->delete($id);

        if (request()->wantsJson()) {
            return \App\Helpers\ResponseHelper::success(null, 'Produk berhasil dihapus!');
        }

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil dihapus!');
    }
}