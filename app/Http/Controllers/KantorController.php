<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\KantorRequest;
use App\Services\KantorService;

class KantorController extends Controller
{
    public function __construct(
        protected KantorService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = $this->service->all();
        return view('pages.data-master.kantor.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.data-master.kantor.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(KantorRequest $request)
    {
        $data = $request->validated();
        $data['is_aktif'] = $request->boolean('is_aktif', true);
        $data['radius_meter'] = $data['radius_meter'] ?? 100;

        $this->service->create($data);

        return redirect()->route('kantor.index')
            ->with('success', 'Kantor berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = $this->service->find($id);
        return view('pages.data-master.kantor.show', compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data = $this->service->find($id);
        return view('pages.data-master.kantor.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(KantorRequest $request, $id)
    {
        $data = $request->validated();
        $data['is_aktif'] = $request->boolean('is_aktif', true);

        $this->service->update($id, $data);

        return redirect()->route('kantor.index')
            ->with('success', 'Kantor berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->service->delete($id);

        if (request()->wantsJson()) {
            return ResponseHelper::success(null, 'Kantor berhasil dihapus!');
        }

        return redirect()->route('kantor.index')
            ->with('success', 'Kantor berhasil dihapus!');
    }
}
