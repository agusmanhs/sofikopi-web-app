<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\AkuntansiCoaUpdateRequest;
use App\Models\Mitra;
use App\Services\MitraPos\AkuntansiCoaService;
use App\Services\MitraPos\MitraContext;
use App\Traits\LogsActivity;

class AkuntansiCoaController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected AkuntansiCoaService $service,
        protected MitraContext $mitraContext
    ) {}

    // --- Tenant portal (mitra-pos/akuntansi/coa), mitra context from MitraContext ---

    public function index()
    {
        $accounts = $this->service->listForMitra($this->mitraContext->id());
        $routes = $this->routesFor(null);

        return view('pages.mitra-pos.akuntansi.coa', compact('accounts', 'routes'));
    }

    public function update(AkuntansiCoaUpdateRequest $request)
    {
        return $this->handleUpdate($request, $this->mitraContext->id(), 'akuntansi-coa.index', []);
    }

    // --- Sofikopi-staff admin (mitra-pos/manage/{mitra}/akuntansi/coa) ---

    public function adminIndex(Mitra $mitra)
    {
        $accounts = $this->service->listForMitra($mitra->id);
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.akuntansi.coa', compact('mitra', 'accounts', 'routes'));
    }

    public function adminUpdate(AkuntansiCoaUpdateRequest $request, Mitra $mitra)
    {
        return $this->handleUpdate($request, $mitra->id, 'mitra-pos-manage.akuntansi-coa.index', [$mitra]);
    }

    /**
     * One form submit for the whole COA table: checkboxes only appear in
     * the request body for postable rows that were ticked, and every
     * postable row always has an opening_balance input — so we iterate the
     * full postable list rather than trusting request keys as the source
     * of truth (an unticked checkbox must still turn is_active off).
     */
    private function handleUpdate(AkuntansiCoaUpdateRequest $request, int $mitraId, string $redirectRoute, array $redirectParams)
    {
        $data = $request->validated();

        $accounts = $this->service->listForMitra($mitraId)->where('is_postable', true);

        foreach ($accounts as $account) {
            $isActive = isset($data['is_active'][$account->id]);
            $openingBalance = (float) ($data['opening_balance'][$account->id] ?? 0);

            $this->service->toggleActive($mitraId, $account->id, $isActive);
            $this->service->updateOpeningBalance($mitraId, $account->id, $openingBalance);
        }

        $this->logActivity('updated', 'mitra-pos', 'Memperbarui Chart of Account (aktif/saldo awal)');

        return redirect()->route($redirectRoute, $redirectParams)
            ->with('success', 'Chart of Account berhasil disimpan.');
    }

    /**
     * Builds route callbacks shared by coa.blade.php so the same view
     * template renders for both the tenant portal (no {mitra} param) and
     * the Sofikopi-staff admin picker ({mitra} route param) — same
     * technique as PosTransactionController::routesFor().
     */
    private function routesFor(?Mitra $mitra): array
    {
        return [
            'update' => $mitra
                ? route('mitra-pos-manage.akuntansi-coa.update', $mitra)
                : route('akuntansi-coa.update'),
            'coa' => $mitra
                ? route('mitra-pos-manage.akuntansi-coa.index', $mitra)
                : route('akuntansi-coa.index'),
            'jurnal' => $mitra
                ? route('mitra-pos-manage.akuntansi-jurnal.index', $mitra)
                : route('akuntansi-jurnal.index'),
            'neraca' => $mitra
                ? route('mitra-pos-manage.akuntansi-neraca.index', $mitra)
                : route('akuntansi-neraca.index'),
            'laba_rugi' => $mitra
                ? route('mitra-pos-manage.akuntansi-laba-rugi.index', $mitra)
                : route('akuntansi-laba-rugi.index'),
        ];
    }
}
