<?php

namespace App\Http\Controllers;

use App\Exports\MitraTemplateExport;
use App\Helpers\ResponseHelper;
use App\Http\Requests\MitraRequest;
use App\Imports\MitraImport;
use App\Models\Media;
use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Services\FileUploadService;
use App\Services\MitraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class MitraController extends Controller
{
    public function __construct(
        protected MitraService $service,
        protected FileUploadService $fileUploadService
    ) {}

    public function index(Request $request)
    {
        if (! $request->wantsJson()) {
            $data = $this->service->all();
            $categories = MitraCategory::aktif()->get();

            return view('pages.mitra.index', compact('data', 'categories'));
        }

        $data = $this->service->all();

        return ResponseHelper::success($data);
    }

    /**
     * Import mitra from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv|max:2048',
        ]);

        try {
            Excel::import(new MitraImport, $request->file('file'));

            return ResponseHelper::success(null, 'Data Mitra berhasil di-import');
        } catch (\Exception $e) {
            return ResponseHelper::error('Gagal import: '.$e->getMessage(), 400);
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        return Excel::download(new MitraTemplateExport, 'template_import_mitra.xlsx');
    }

    public function store(MitraRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            unset($data['logo'], $data['remove_logo']);

            if ($request->filled('titik_lokasi')) {
                $coords = explode(',', $request->titik_lokasi);
                if (count($coords) === 2) {
                    $data['latitude'] = trim($coords[0]);
                    $data['longitude'] = trim($coords[1]);
                }
            }

            $data['is_active'] = $request->boolean('is_active');

            if ($request->hasFile('logo')) {
                $media = $this->fileUploadService->upload($request->file('logo'), 'mitra/logo', 'public', [
                    'width' => 300,
                    'quality' => 85,
                ]);
                $data['logo'] = $media->path;
            }

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
            unset($data['logo'], $data['remove_logo']);

            if ($request->filled('titik_lokasi')) {
                $coords = explode(',', $request->titik_lokasi);
                if (count($coords) === 2) {
                    $data['latitude'] = trim($coords[0]);
                    $data['longitude'] = trim($coords[1]);
                }
            }

            $data['is_active'] = $request->boolean('is_active');

            $currentLogo = Mitra::findOrFail($id)->logo;

            if ($request->hasFile('logo')) {
                $this->deleteExistingMitraLogo($currentLogo);
                $media = $this->fileUploadService->upload($request->file('logo'), 'mitra/logo', 'public', [
                    'width' => 300,
                    'quality' => 85,
                ]);
                $data['logo'] = $media->path;
            } elseif ($request->boolean('remove_logo')) {
                $this->deleteExistingMitraLogo($currentLogo);
                $data['logo'] = null;
            }

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

    /**
     * Removes the currently-stored mitra logo file (and its Media row, if
     * the upload created one) before a replacement or explicit removal —
     * otherwise every re-upload orphans the previous file on disk. Mirrors
     * MitraSettingController::deleteExistingLogo().
     */
    private function deleteExistingMitraLogo(?string $logoPath): void
    {
        if (! $logoPath) {
            return;
        }

        $media = Media::where('path', $logoPath)->first();

        if ($media) {
            $this->fileUploadService->delete($media);
        } else {
            Storage::disk('public')->delete($logoPath);
        }
    }
}
