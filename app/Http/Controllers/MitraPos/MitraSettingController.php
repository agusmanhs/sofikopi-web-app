<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\MitraSettingRequest;
use App\Models\Mitra;
use App\Models\MitraPosSetting;
use App\Services\MitraPos\MitraContext;
use App\Traits\LogsActivity;

class MitraSettingController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected MitraContext $mitraContext
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
        $setting->update($request->validated());

        $this->logActivity('updated', 'mitra-pos', 'Memperbarui pengaturan Mitra POS', $setting);

        return redirect()->route($redirectRoute, $redirectParams)
            ->with('success', 'Pengaturan berhasil disimpan');
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
