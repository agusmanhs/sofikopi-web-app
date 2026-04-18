<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\KunjunganRequest;
use App\Services\KunjunganService;
use App\Services\MitraService;
use App\Services\PegawaiService;
use App\Models\User;
use Illuminate\Http\Request;

class KunjunganController extends Controller
{
    public function __construct(
        protected KunjunganService $service,
        protected MitraService $mitraService,
        protected PegawaiService $pegawaiService
    ) {}

    // ============== USER METHODS ==============

    /**
     * Form buat kunjungan baru
     */
    public function create()
    {
        $mitras = $this->mitraService->all()->where('is_active', true);
        return view('pages.kunjungan.create', compact('mitras'));
    }

    /**
     * Simpan kunjungan baru
     */
    public function store(KunjunganRequest $request)
    {
        try {
            $data = $request->validated();
            $foto = $request->file('foto_kunjungan');

            $this->service->createKunjungan(auth()->id(), $data, $foto);

            return redirect()->route('aktivitas.riwayat.index')
                ->with('success', 'Laporan kunjungan berhasil disimpan!');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * History kunjungan user sendiri
     */
    public function index()
    {
        $data = $this->service->getByUser(auth()->id());
        return view('pages.kunjungan.index', compact('data'));
    }

    /**
     * Detail kunjungan
     */
    public function show($id)
    {
        $data = $this->service->findWithRelations($id);
        return view('pages.kunjungan.show', compact('data'));
    }

    // ============== ADMIN METHODS ==============

    /**
     * Admin melihat semua kunjungan
     */
    public function adminIndex(Request $request)
    {
        $filters = $request->only(['user_id', 'mitra_id', 'date_from', 'date_to']);
        $data = $this->service->getAllFiltered($filters);

        // Data untuk filter dropdowns
        $users = User::whereHas('pegawai')->with('pegawai')->get();
        $mitras = $this->mitraService->all()->where('is_active', true);

        return view('pages.kunjungan.admin.index', compact('data', 'users', 'mitras', 'filters'));
    }

    /**
     * Admin detail kunjungan
     */
    public function adminShow($id)
    {
        $data = $this->service->findWithRelations($id);
        return view('pages.kunjungan.show', compact('data'));
    }

    /**
     * Admin hapus kunjungan
     */
    public function adminDestroy($id)
    {
        try {
            $this->service->adminDelete($id);

            if (request()->wantsJson()) {
                return ResponseHelper::success(null, 'Kunjungan berhasil dihapus!');
            }

            return redirect()->route('aktivitas.kunjungan.admin.index')
                ->with('success', 'Kunjungan berhasil dihapus!');

        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return ResponseHelper::error($e->getMessage(), 400);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
