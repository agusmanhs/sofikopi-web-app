<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\MitraCategoryRequest;
use App\Services\MitraCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MitraCategoryController extends Controller
{
    public function __construct(protected MitraCategoryService $service) {}

    public function index()
    {
        $data = $this->service->all();
        return ResponseHelper::success($data);
    }

    public function store(MitraCategoryRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);
            $result = $this->service->create($data);
            return ResponseHelper::success($result, 'Kategori mitra berhasil ditambahkan');
        });
    }

    public function update(MitraCategoryRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);
            $result = $this->service->update($id, $data);
            return ResponseHelper::success($result, 'Kategori mitra berhasil diperbarui');
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
            return ResponseHelper::success(null, 'Kategori mitra berhasil dihapus');
        });
    }
}
