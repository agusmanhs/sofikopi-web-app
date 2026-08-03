<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\MitraSettingRequest;
use App\Models\Media;
use App\Models\Mitra;
use App\Models\MitraPosSetting;
use App\Services\FileUploadService;
use App\Services\MitraPos\MitraContext;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Storage;

class MitraSettingController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected MitraContext $mitraContext,
        protected FileUploadService $fileUploadService
    ) {}

    // --- Tenant portal (mitra-pos/settings), mitra context from MitraContext ---

    /**
     * The settings row always exists for an enrolled mitra — its existence
     * IS the enrollment flag (see MitraPosManageController::store()) — so
     * firstOrFail() here is a real invariant, not defensive padding. Holds
     * identically in admin context: a route-bound Mitra reached via
     * mitra.scope is by definition enrolled.
     */
    public function index()
    {
        return $this->renderIndex($this->mitraContext->id(), null);
    }

    public function update(MitraSettingRequest $request)
    {
        return $this->handleUpdate($request, $this->mitraContext->id(), 'mitra-setting.index', []);
    }

    // --- Sofikopi-staff admin (mitra-pos/manage/{mitra}/settings) ---

    public function adminIndex(Mitra $mitra)
    {
        return $this->renderIndex($mitra->id, $mitra);
    }

    public function adminUpdate(MitraSettingRequest $request, Mitra $mitra)
    {
        return $this->handleUpdate($request, $mitra->id, 'mitra-pos-manage.setting.index', [$mitra]);
    }

    private function renderIndex(int $mitraId, ?Mitra $mitra)
    {
        $setting = MitraPosSetting::forMitra($mitraId)->firstOrFail();
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.setting.index', compact('setting', 'mitra', 'routes'));
    }

    private function handleUpdate(MitraSettingRequest $request, int $mitraId, string $redirectRoute, array $redirectParams)
    {
        $setting = MitraPosSetting::forMitra($mitraId)->firstOrFail();

        // receipt_logo/remove_logo aren't columns — handled separately below,
        // never mass-assigned.
        $data = $request->validated();
        unset($data['receipt_logo'], $data['remove_logo']);

        if ($request->hasFile('receipt_logo')) {
            $this->deleteExistingLogo($setting);
            $media = $this->fileUploadService->upload($request->file('receipt_logo'), 'mitra-pos/logo', 'public', [
                'width' => 300,
                'quality' => 85,
            ]);
            $data['receipt_logo_path'] = $media->path;
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteExistingLogo($setting);
            $data['receipt_logo_path'] = null;
        }

        $setting->update($data);

        $this->logActivity('updated', 'mitra-pos', 'Memperbarui pengaturan Mitra POS', $setting);

        return redirect()->route($redirectRoute, $redirectParams)
            ->with('success', 'Pengaturan berhasil disimpan');
    }

    /**
     * Removes the currently-stored logo file (and its Media row, if the
     * upload created one) before a replacement or explicit removal —
     * otherwise every re-upload orphans the previous file on disk.
     */
    private function deleteExistingLogo(MitraPosSetting $setting): void
    {
        if (! $setting->receipt_logo_path) {
            return;
        }

        $media = Media::where('path', $setting->receipt_logo_path)->first();

        if ($media) {
            $this->fileUploadService->delete($media);
        } else {
            Storage::disk('public')->delete($setting->receipt_logo_path);
        }
    }

    /**
     * Same routesFor() technique as AkuntansiCoaController — lets
     * setting/index.blade.php render identically for the tenant portal and
     * the Sofikopi-staff admin picker.
     */
    private function routesFor(?Mitra $mitra): array
    {
        return [
            'update' => $mitra
                ? route('mitra-pos-manage.setting.update', $mitra)
                : route('mitra-setting.update'),
        ];
    }
}
