<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\Request;

class PengaturanAbsensiController extends Controller
{
    public function __construct(
        protected SettingService $service
    ) {}

    public function index()
    {
        $settings = $this->service->getByGroup(Setting::GROUP_ABSENSI);

        return view('pages.pengaturan-absensi.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $allowedKeys = $this->service->getByGroup(Setting::GROUP_ABSENSI)
            ->pluck('key')
            ->all();

        if (empty($allowedKeys)) {
            return back()->with('error', 'Data pengaturan belum tersedia. Jalankan seeder Pengaturan terlebih dahulu.');
        }

        $rules = [];
        foreach ($allowedKeys as $key) {
            $rules['settings.'.$key] = 'required|integer|min:1|max:24';
        }

        $validated = $request->validate($rules, [], $this->attributeNames($allowedKeys));

        // Hanya key yang memang terdaftar di group absensi yang disimpan,
        // sehingga input tambahan dari request tidak bisa menulis setting lain.
        $values = array_intersect_key($validated['settings'], array_flip($allowedKeys));

        $this->service->setMany($values);

        return redirect()->route('pengaturan-absensi.index')
            ->with('success', 'Pengaturan absensi berhasil disimpan.');
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, string>
     */
    protected function attributeNames(array $keys): array
    {
        $settings = $this->service->getByGroup(Setting::GROUP_ABSENSI)->keyBy('key');

        $names = [];
        foreach ($keys as $key) {
            $names['settings.'.$key] = $settings[$key]->label ?? $key;
        }

        return $names;
    }
}
