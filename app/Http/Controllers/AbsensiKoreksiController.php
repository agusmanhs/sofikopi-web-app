<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\AbsensiKoreksiRequest;
use App\Models\Absensi;
use App\Services\AbsensiKoreksiService;

class AbsensiKoreksiController extends Controller
{
    public function __construct(protected AbsensiKoreksiService $service) {}

    public function store(AbsensiKoreksiRequest $request)
    {
        return $this->handle(
            $request,
            fn () => $this->service->tambah($request->validated(), $request->user()),
            'Absensi berhasil ditambahkan.'
        );
    }

    public function update(AbsensiKoreksiRequest $request, Absensi $absensi)
    {
        return $this->handle(
            $request,
            fn () => $this->service->ubah($absensi, $request->validated(), $request->user()),
            'Absensi berhasil dikoreksi.'
        );
    }

    private function handle(AbsensiKoreksiRequest $request, \Closure $action, string $message)
    {
        try {
            $absensi = $action();

            if ($request->wantsJson()) {
                return ResponseHelper::success($absensi, $message);
            }

            return back()->with('success', $message);
        } catch (\DomainException $e) {
            if ($request->wantsJson()) {
                return ResponseHelper::error($e->getMessage(), 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
