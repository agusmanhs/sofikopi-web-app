<?php

namespace App\Http\Controllers;

use App\Services\HariLiburService;
use App\Http\Requests\HariLiburRequest;
use Illuminate\Http\Request;

class HariLiburController extends Controller
{
    public function __construct(
        protected HariLiburService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);
        $data = $this->service->getPaginated($year);
        return view('pages.data-master.hari-libur.index', compact('data', 'year'));
    }

    public function sync(Request $request)
    {
        $year = $request->get('year', now()->year);
        $result = $this->service->syncFromApi($year);

        if ($result['success']) {
            return redirect()->route('hari-libur.index', ['year' => $year])->with('success', $result['message']);
        }

        return redirect()->route('hari-libur.index', ['year' => $year])->with('error', $result['message']);
    }

    // ... Keep other CRUD methods but ensure view paths are correct match
    // For now I will focus on Index and Sync as requested by user roadmap
    // I will implement Store/Update later or let them remain if they match generic


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.data-master.hari-libur.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HariLiburRequest $request)
    {
        $data = $request->validated();
        $this->service->create($data);

        return redirect()->route('hari-libur.index')
            ->with('success', 'Data berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = $this->service->find($id);
        return view('pages.data-master.hari-libur.show', compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data = $this->service->find($id);
        return view('pages.data-master.hari-libur.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(HariLiburRequest $request, $id)
    {
        $data = $request->validated();
        $this->service->update($id, $data);

        return redirect()->route('hari-libur.index')
            ->with('success', 'Data berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->service->delete($id);

        return redirect()->route('hari-libur.index')
            ->with('success', 'Data berhasil dihapus!');
    }

    /**
     * API for calendar events
     */
    public function getEvents(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $holidays = \App\Models\HariLibur::whereBetween('tanggal', [$start, $end])->get();
        $events = [];

        foreach ($holidays as $holiday) {
            $events[] = [
                'id' => $holiday->id,
                'title' => $holiday->nama,
                'start' => $holiday->tanggal->format('Y-m-d'),
                'allDay' => true,
                'extendedProps' => [
                    'calendar' => $holiday->is_nasional ? 'danger' : 'warning',
                    'description' => $holiday->deskripsi ?? '',
                ]
            ];
        }

        return response()->json($events);
    }
}