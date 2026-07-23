<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\MitraSettingRequest;
use App\Models\MitraPosSetting;
use App\Services\MitraPos\MitraContext;
use App\Traits\LogsActivity;

class MitraSettingController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected MitraContext $mitraContext
    ) {}

    /**
     * The settings row always exists for an enrolled mitra — its existence
     * IS the enrollment flag (see MitraPosManageController::store()) — so
     * firstOrFail() here is a real invariant, not defensive padding.
     */
    public function index()
    {
        $setting = MitraPosSetting::forMitra($this->mitraContext->id())->firstOrFail();

        return view('pages.mitra-pos.setting.index', compact('setting'));
    }

    public function update(MitraSettingRequest $request)
    {
        $mitraId = $this->mitraContext->id();
        $setting = MitraPosSetting::forMitra($mitraId)->firstOrFail();
        $setting->update($request->validated());

        $this->logActivity('updated', 'mitra-pos', 'Memperbarui pengaturan Mitra POS', $setting);

        return redirect()->route('mitra-setting.index')
            ->with('success', 'Pengaturan berhasil disimpan');
    }
}
