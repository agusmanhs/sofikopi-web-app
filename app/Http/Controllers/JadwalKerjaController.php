<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\JadwalKerjaRequest;
use App\Models\Divisi;
use App\Models\Pegawai;
use App\Models\Shift;
use App\Services\JadwalKerjaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JadwalKerjaController extends Controller
{
    public function __construct(
        protected JadwalKerjaService $service
    ) {}

    /**
     * Grid mingguan pola jadwal per pegawai + panel override.
     */
    public function index(Request $request)
    {
        $divisiId = $request->integer('divisi_id') ?: null;

        $data = $this->service->getGridData($divisiId);
        $divisis = Divisi::aktif()->get();
        $pegawais = Pegawai::aktif()->orderBy('nama_lengkap')->get();
        $shiftsByDivisi = Shift::aktif()->get()->groupBy('divisi_id');
        $overrides = $this->service->getUpcomingOverrides();

        return view('pages.jadwal-kerja.index', compact(
            'data',
            'divisis',
            'pegawais',
            'shiftsByDivisi',
            'overrides'
        ));
    }

    /**
     * Simpan pola 7 hari (Minggu-Sabtu) sekaligus untuk satu pegawai.
     */
    public function savePattern(Request $request, Pegawai $pegawai)
    {
        $validated = $request->validate([
            'days' => 'required|array|size:7',
            'days.*' => 'nullable|exists:shifts,id',
        ]);

        return DB::transaction(function () use ($pegawai, $validated) {
            $this->service->savePatternForPegawai($pegawai->id, $validated['days']);

            return ResponseHelper::success(null, 'Pola jadwal berhasil disimpan');
        });
    }

    /**
     * Simpan (buat/perbarui) override jadwal per tanggal.
     */
    public function storeOverride(JadwalKerjaRequest $request)
    {
        $data = $request->validated();

        $override = $this->service->saveOverride($data);

        return ResponseHelper::success($override, 'Perubahan jadwal berhasil disimpan');
    }

    /**
     * Hapus override jadwal.
     */
    public function destroyOverride($id)
    {
        $this->service->deleteOverride($id);

        return ResponseHelper::success(null, 'Perubahan jadwal berhasil dihapus');
    }
}
