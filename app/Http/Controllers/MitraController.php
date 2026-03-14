<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\MitraRequest;
use App\Models\MitraCategory;
use App\Services\MitraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MitraController extends Controller
{
    public function __construct(protected MitraService $service) {}

    public function index(Request $request)
    {
        if (!$request->wantsJson()) {
            $data = $this->service->all();
            $categories = MitraCategory::aktif()->get();
            return view('pages.mitra.index', compact('data', 'categories'));
        }

        $data = $this->service->all();
        return ResponseHelper::success($data);
    }

    public function store(MitraRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            
            if ($request->filled('titik_lokasi')) {
                $coords = explode(',', $request->titik_lokasi);
                if (count($coords) === 2) {
                    $data['latitude'] = trim($coords[0]);
                    $data['longitude'] = trim($coords[1]);
                }
            }

            $data['is_active'] = $request->boolean('is_active', true);
            $result = $this->service->create($data);
            return ResponseHelper::success($result, 'Mitra berhasil ditambahkan');
        });
    }

    public function show($id)
    {
        $data = $this->service->find($id);
        return ResponseHelper::success($data);
    }

    public function update(MitraRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $data = $request->validated();

            if ($request->filled('titik_lokasi')) {
                $coords = explode(',', $request->titik_lokasi);
                if (count($coords) === 2) {
                    $data['latitude'] = trim($coords[0]);
                    $data['longitude'] = trim($coords[1]);
                }
            }

            $data['is_active'] = $request->boolean('is_active', true);
            $result = $this->service->update($id, $data);
            return ResponseHelper::success($result, 'Mitra berhasil diperbarui');
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $this->service->delete($id);
            return ResponseHelper::success(null, 'Mitra berhasil dihapus');
        });
    }
}
