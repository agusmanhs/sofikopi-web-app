<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\MitraKasirUserRequest;
use App\Models\User;
use App\Services\MitraPos\MitraContext;
use App\Services\MitraPos\MitraKasirUserService;
use App\Traits\LogsActivity;

/**
 * Portal-only (mitra-pos/kasir-user) — owner self-service for their own
 * mitra's kasir accounts. No admin-parity routes: super-admin already has
 * full CRUD over every user via the global /user screen.
 */
class MitraKasirUserController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected MitraKasirUserService $service,
        protected MitraContext $mitraContext
    ) {}

    public function index()
    {
        $kasirs = $this->service->listForMitra($this->mitraContext->id());
        $maxKasir = MitraKasirUserService::MAX_KASIR_PER_MITRA;

        return view('pages.mitra-pos.kasir-user.index', compact('kasirs', 'maxKasir'));
    }

    public function store(MitraKasirUserRequest $request)
    {
        $kasir = $this->service->createKasir($this->mitraContext->id(), $request->validated());

        $this->logActivity('created', 'mitra-pos', "Menambahkan user kasir: {$kasir->email}", $kasir);

        return redirect()->route('mitra-kasir-user.index')
            ->with('success', 'User kasir berhasil ditambahkan');
    }

    public function update(MitraKasirUserRequest $request, User $user)
    {
        $kasir = $this->service->updateKasir($this->mitraContext->id(), $user->id, $request->validated());

        $this->logActivity('updated', 'mitra-pos', "Memperbarui user kasir: {$kasir->email}", $kasir);

        return redirect()->route('mitra-kasir-user.index')
            ->with('success', 'User kasir berhasil diperbarui');
    }

    public function destroy(User $user)
    {
        $email = $user->email;

        $this->service->deleteKasir($this->mitraContext->id(), $user->id);

        $this->logActivity('deleted', 'mitra-pos', "Menghapus user kasir: {$email}");

        return redirect()->route('mitra-kasir-user.index')
            ->with('success', 'User kasir berhasil dihapus');
    }
}
