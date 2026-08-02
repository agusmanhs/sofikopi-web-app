<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\AkuntansiJournalStoreRequest;
use App\Models\Mitra;
use App\Services\MitraPos\AkuntansiCoaService;
use App\Services\MitraPos\AkuntansiJournalService;
use App\Services\MitraPos\MitraContext;
use App\Traits\LogsActivity;
use Illuminate\Support\Carbon;
use RuntimeException;

class AkuntansiJournalController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected AkuntansiJournalService $service,
        protected AkuntansiCoaService $coaService,
        protected MitraContext $mitraContext
    ) {}

    // --- Tenant portal (mitra-pos/akuntansi/jurnal), mitra context from MitraContext ---

    public function index()
    {
        $entries = $this->service->listForMitra($this->mitraContext->id());
        $routes = $this->routesFor(null);

        return view('pages.mitra-pos.akuntansi.jurnal-index', compact('entries', 'routes'));
    }

    public function create()
    {
        $accounts = $this->postableActiveAccounts($this->mitraContext->id());
        $routes = $this->routesFor(null);

        return view('pages.mitra-pos.akuntansi.jurnal-create', compact('accounts', 'routes'));
    }

    public function store(AkuntansiJournalStoreRequest $request)
    {
        return $this->handleStore($request, $this->mitraContext->id(), 'akuntansi-jurnal.index', []);
    }

    // --- Sofikopi-staff admin (mitra-pos/manage/{mitra}/akuntansi/jurnal) ---

    public function adminIndex(Mitra $mitra)
    {
        $entries = $this->service->listForMitra($mitra->id);
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.akuntansi.jurnal-index', compact('mitra', 'entries', 'routes'));
    }

    public function adminCreate(Mitra $mitra)
    {
        $accounts = $this->postableActiveAccounts($mitra->id);
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.akuntansi.jurnal-create', compact('mitra', 'accounts', 'routes'));
    }

    public function adminStore(AkuntansiJournalStoreRequest $request, Mitra $mitra)
    {
        return $this->handleStore($request, $mitra->id, 'mitra-pos-manage.akuntansi-jurnal.index', [$mitra]);
    }

    private function postableActiveAccounts(int $mitraId)
    {
        return $this->coaService->listForMitra($mitraId)
            ->where('is_postable', true)
            ->where('is_active', true)
            ->values();
    }

    private function handleStore(AkuntansiJournalStoreRequest $request, int $mitraId, string $redirectRoute, array $redirectParams)
    {
        $data = $request->validated();

        try {
            $entry = $this->service->createManual(
                mitraId: $mitraId,
                userId: auth()->id(),
                date: Carbon::parse($data['entry_date']),
                description: $data['description'],
                lines: $data['lines'],
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        $this->logActivity('created', 'mitra-pos', "Membuat jurnal manual: {$entry->entry_no}", $entry);

        return redirect()->route($redirectRoute, $redirectParams)
            ->with('success', "Jurnal {$entry->entry_no} berhasil disimpan.");
    }

    /**
     * Builds route callbacks shared by jurnal-index.blade.php and
     * jurnal-create.blade.php so the same view templates render for both
     * the tenant portal (no {mitra} param) and the Sofikopi-staff admin
     * picker ({mitra} route param) — same technique as
     * PosTransactionController::routesFor().
     */
    private function routesFor(?Mitra $mitra): array
    {
        return [
            'index' => $mitra
                ? route('mitra-pos-manage.akuntansi-jurnal.index', $mitra)
                : route('akuntansi-jurnal.index'),
            'create' => $mitra
                ? route('mitra-pos-manage.akuntansi-jurnal.create', $mitra)
                : route('akuntansi-jurnal.create'),
            'store' => $mitra
                ? route('mitra-pos-manage.akuntansi-jurnal.store', $mitra)
                : route('akuntansi-jurnal.store'),
            'jurnal' => $mitra
                ? route('mitra-pos-manage.akuntansi-jurnal.index', $mitra)
                : route('akuntansi-jurnal.index'),
            'coa' => $mitra
                ? route('mitra-pos-manage.akuntansi-coa.index', $mitra)
                : route('akuntansi-coa.index'),
            'neraca' => $mitra
                ? route('mitra-pos-manage.akuntansi-neraca.index', $mitra)
                : route('akuntansi-neraca.index'),
            'laba_rugi' => $mitra
                ? route('mitra-pos-manage.akuntansi-laba-rugi.index', $mitra)
                : route('akuntansi-laba-rugi.index'),
        ];
    }
}
