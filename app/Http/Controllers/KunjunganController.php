<?php

namespace App\Http\Controllers;

use App\Exports\KunjunganExport;
use App\Helpers\ResponseHelper;
use App\Http\Requests\KunjunganRequest;
use App\Services\KunjunganService;
use App\Services\MitraService;
use App\Services\PegawaiService;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class KunjunganController extends Controller
{
    use LogsActivity;
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

            $kunjungan = $this->service->createKunjungan(auth()->id(), $data, $foto);

            $this->logActivity(
                'created',
                'kunjungan',
                'Membuat laporan kunjungan QC ke ' . ($kunjungan->mitra->name ?? '-'),
                $kunjungan,
                ['visit_type' => $data['visit_type'] ?? null, 'outlet' => $kunjungan->mitra->name ?? '-']
            );

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
     * Export kunjungan to Excel (Admin)
     */
    public function adminExport(Request $request)
    {
        $filters = $request->only(['user_id', 'mitra_id', 'date_from', 'date_to']);
        
        $exportFilters = [
            'user_id' => $filters['user_id'] ?? null,
            'mitra_id' => $filters['mitra_id'] ?? null,
            'start_date' => $filters['date_from'] ?? null,
            'end_date' => $filters['date_to'] ?? null,
        ];

        $this->logActivity(
            'exported',
            'kunjungan',
            'Mengexport data kunjungan QC ke Excel',
            null,
            $exportFilters
        );

        return Excel::download(new KunjunganExport($exportFilters), 'laporan_kunjungan_qc.xlsx');
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
            $kunjungan = $this->service->findWithRelations($id);

            $this->logActivity(
                'deleted',
                'kunjungan',
                'Menghapus laporan kunjungan ke ' . ($kunjungan->mitra->name ?? '-') . ' tanggal ' . $kunjungan->tanggal_kunjungan->format('d M Y'),
                $kunjungan,
                ['outlet' => $kunjungan->mitra->name ?? '-', 'date' => $kunjungan->tanggal_kunjungan->format('Y-m-d')]
            );

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
