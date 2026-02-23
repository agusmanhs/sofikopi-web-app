<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\DivisiRequest;
use App\Services\DivisiService;

class DivisiController extends Controller
{
    public function __construct(
        protected DivisiService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = $this->service->all();
        return view('pages.data-master.divisi.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.data-master.divisi.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DivisiRequest $request)
    {
        $data = $request->validated();
        $data['is_aktif'] = $request->boolean('is_aktif', true);

        $this->service->create($data);

        return redirect()->route('divisi.index')
            ->with('success', 'Divisi berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = $this->service->find($id);
        return view('pages.data-master.divisi.show', compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data = $this->service->find($id);
        return view('pages.data-master.divisi.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DivisiRequest $request, $id)
    {
        $data = $request->validated();
        $data['is_aktif'] = $request->boolean('is_aktif', true);

        $this->service->update($id, $data);

        return redirect()->route('divisi.index')
            ->with('success', 'Divisi berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->service->delete($id);

        if (request()->wantsJson()) {
            return ResponseHelper::success(null, 'Divisi berhasil dihapus!');
        }

        return redirect()->route('divisi.index')
            ->with('success', 'Divisi berhasil dihapus!');
    }
}
