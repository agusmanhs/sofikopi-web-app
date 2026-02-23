<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\PegawaiRequest;
use App\Services\DivisiService;
use App\Services\FileUploadService;
use App\Services\KantorService;
use App\Services\PegawaiService;
use App\Services\UserService;

class PegawaiController extends Controller
{
    public function __construct(
        protected PegawaiService $service,
        protected DivisiService $divisiService,
        protected KantorService $kantorService,
        protected UserService $userService,
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $filters = $request->only(['divisi_id']);
        $data = $this->service->getAllPegawai($filters);
        $divisis = $this->divisiService->getAktif();
        return view('pages.data-master.pegawai.index', compact('data', 'divisis'));
    }

    public function create()
    {
        $divisis = $this->divisiService->getAktif();
        $kantors = $this->kantorService->getAktif();
        $users = $this->userService->getAvailableForPegawai();

        return view('pages.data-master.pegawai.create', compact('divisis', 'kantors', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PegawaiRequest $request)
    {
        $data = $request->validated();
        $data['status_aktif'] = $request->boolean('status_aktif', true);

        // Upload foto jika ada
        if ($request->hasFile('foto')) {
            $media = $this->fileUploadService->upload($request->file('foto'), 'pegawai', 'public', [
                'width' => 300,
                'height' => 300,
                'crop' => true,
            ]);
            $data['foto'] = $media->path;
        }

        $pegawai = $this->service->create($data);

        // Assign lokasi absen jika ada
        if (!empty($data['lokasi_absen'])) {
            $this->service->assignLokasiAbsen($pegawai->id, $data['lokasi_absen']);
        }

        return redirect()->route('pegawai.index')
            ->with('success', 'Pegawai berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = $this->service->getWithRelations($id);
        return view('pages.data-master.pegawai.show', compact('data'));
    }

    public function edit($id)
    {
        $data = $this->service->getWithRelations($id);
        $divisis = $this->divisiService->getAktif();
        $kantors = $this->kantorService->getAktif();
        $users = $this->userService->getAvailableForPegawai($data->user_id);

        return view('pages.data-master.pegawai.edit', compact('data', 'divisis', 'kantors', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PegawaiRequest $request, $id)
    {
        $data = $request->validated();
        $data['status_aktif'] = $request->boolean('status_aktif', true);

        // Upload foto jika ada
        if ($request->hasFile('foto')) {
            $media = $this->fileUploadService->upload($request->file('foto'), 'pegawai', 'public', [
                'width' => 300,
                'height' => 300,
                'crop' => true,
            ]);
            $data['foto'] = $media->path;
        }

        $this->service->update($id, $data);

        // Update lokasi absen
        if (isset($data['lokasi_absen'])) {
            $this->service->assignLokasiAbsen($id, $data['lokasi_absen']);
        }

        return redirect()->route('pegawai.index')
            ->with('success', 'Pegawai berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pegawai = $this->service->find($id);

        // Delete foto if exists
        if ($pegawai->foto) {
            $media = \App\Models\Media::where('path', $pegawai->foto)->first();
            if ($media) {
                $this->fileUploadService->delete($media);
            }
        }

        $this->service->delete($id);

        if (request()->wantsJson()) {
            return ResponseHelper::success(null, 'Pegawai berhasil dihapus!');
        }

        return redirect()->route('pegawai.index')
            ->with('success', 'Pegawai berhasil dihapus!');
    }
}
