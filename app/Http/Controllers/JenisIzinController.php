<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\JenisIzinRequest;
use App\Services\JenisIzinService;

class JenisIzinController extends Controller
{
    public function __construct(
        protected JenisIzinService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = $this->service->all();
        return view('pages.data-master.jenis-izin.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.data-master.jenis-izin.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(JenisIzinRequest $request)
    {
        $data = $request->validated();
        $data['is_aktif'] = $request->boolean('is_aktif', true);
        $data['butuh_surat'] = $request->boolean('butuh_surat', false);

        $this->service->create($data);

        return redirect()->route('jenis-izin.index')
            ->with('success', 'Jenis Izin berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = $this->service->find($id);
        return view('pages.data-master.jenis-izin.show', compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data = $this->service->find($id);
        return view('pages.data-master.jenis-izin.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(JenisIzinRequest $request, $id)
    {
        $data = $request->validated();
        $data['is_aktif'] = $request->boolean('is_aktif', true);
        $data['butuh_surat'] = $request->boolean('butuh_surat', false);

        $this->service->update($id, $data);

        return redirect()->route('jenis-izin.index')
            ->with('success', 'Jenis Izin berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->service->delete($id);

        if (request()->wantsJson()) {
            return ResponseHelper::success(null, 'Jenis Izin berhasil dihapus!');
        }

        return redirect()->route('jenis-izin.index')
            ->with('success', 'Jenis Izin berhasil dihapus!');
    }
}
