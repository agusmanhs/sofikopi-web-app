<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\IzinRequest;
use App\Services\IzinService;
use App\Services\JenisIzinService;
use App\Services\PegawaiService;
use Illuminate\Http\Request;

class IzinController extends Controller
{
    public function __construct(
        protected IzinService $service,
        protected JenisIzinService $jenisIzinService,
        protected PegawaiService $pegawaiService
    ) {}

    /**
     * Display a listing of the resource (untuk pegawai).
     */
    public function index()
    {
        $pegawai = $this->pegawaiService->getByUserId(auth()->id());

        if (!$pegawai) {
            return redirect()->route('dashboard')
                ->with('error', 'Anda belum terdaftar sebagai pegawai.');
        }

        $data = $this->service->getByPegawai($pegawai->id);
        
        return view('pages.izin.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pegawai = $this->pegawaiService->getByUserId(auth()->id());

        if (!$pegawai) {
            return redirect()->route('dashboard')
                ->with('error', 'Anda belum terdaftar sebagai pegawai.');
        }

        $jenisIzins = $this->jenisIzinService->getAktif();
        
        return view('pages.izin.create', compact('jenisIzins'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(IzinRequest $request)
    {
        try {
            $pegawai = $this->pegawaiService->getByUserId(auth()->id());

            if (!$pegawai) {
                return redirect()->route('dashboard')
                    ->with('error', 'Anda belum terdaftar sebagai pegawai.');
            }

            $data = $request->validated();
            $data['file_surat'] = $request->file('file_surat');

            $data['alasan'] = $request->alasan ?? '-';

            $this->service->ajukanIzin($pegawai->id, $data);

            return redirect()->route('izin.index')
                ->with('success', 'Pengajuan izin berhasil dikirim!');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = $this->service->find($id);
        return view('pages.izin.show', compact('data'));
    }

    /**
     * Cancel izin yang masih pending
     */
    public function cancel($id)
    {
        try {
            $pegawai = $this->pegawaiService->getByUserId(auth()->id());

            if (!$pegawai) {
                return ResponseHelper::error('Anda belum terdaftar sebagai pegawai.', 403);
            }

            $this->service->cancelIzin($id, $pegawai->id);

            if (request()->wantsJson()) {
                return ResponseHelper::success(null, 'Pengajuan izin berhasil dibatalkan!');
            }

            return redirect()->route('izin.index')
                ->with('success', 'Pengajuan izin berhasil dibatalkan!');

        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return ResponseHelper::error($e->getMessage(), 400);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    // ============== ADMIN METHODS ==============

    /**
     * List semua izin untuk admin
     */
    public function adminIndex()
    {
        $data = $this->service->all();
        $statistik = $this->service->getStatistik();

        return view('pages.izin.admin.index', compact('data', 'statistik'));
    }

    /**
     * List izin pending untuk approval
     */
    public function pending()
    {
        $data = $this->service->getPending();
        return view('pages.izin.admin.pending', compact('data'));
    }

    /**
     * Approve izin
     */
    public function approve(Request $request, $id)
    {
        try {
            $this->service->approveIzin($id, $request->catatan);

            if ($request->wantsJson()) {
                return ResponseHelper::success(null, 'Izin berhasil disetujui!');
            }

            return redirect()->route('izin.admin.index')
                ->with('success', 'Izin berhasil disetujui!');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return ResponseHelper::error($e->getMessage(), 400);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject izin
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'required|string|min:10',
        ], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
            'catatan.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);

        try {
            $this->service->rejectIzin($id, $request->catatan);

            if ($request->wantsJson()) {
                return ResponseHelper::success(null, 'Izin berhasil ditolak!');
            }

            return redirect()->route('izin.admin.index')
                ->with('success', 'Izin berhasil ditolak!');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return ResponseHelper::error($e->getMessage(), 400);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
