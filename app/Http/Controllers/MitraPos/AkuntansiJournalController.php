<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\AkuntansiJournalStoreRequest;
use App\Models\Mitra;
use App\Services\MitraPos\AkuntansiCoaService;
use App\Services\MitraPos\AkuntansiJournalService;
use App\Services\MitraPos\MitraContext;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        return $this->renderIndex($request, $this->mitraContext->id(), null);
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

    public function adminIndex(Request $request, Mitra $mitra)
    {
        return $this->renderIndex($request, $mitra->id, $mitra);
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

    private function renderIndex(Request $request, int $mitraId, ?Mitra $mitra)
    {
        $filters = $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => $request->filled('from') ? 'nullable|date_format:Y-m-d|after_or_equal:from' : 'nullable|date_format:Y-m-d',
            'q' => 'nullable|string|max:100',
            'account_id' => 'nullable|integer',
            'transaction' => 'nullable|string|max:60',
        ], [
            'from.date_format' => 'Tanggal "Dari" tidak valid.',
            'to.date_format' => 'Tanggal "Sampai" tidak valid.',
            'to.after_or_equal' => 'Tanggal "Sampai" tidak boleh lebih awal dari tanggal "Dari".',
            'q.max' => 'Kata pencarian maksimal 100 huruf.',
        ]);

        $entries = $this->service->listForMitra($mitraId, 20, $filters);
        $filterAccounts = $this->coaService->listForMitra($mitraId)->where('is_postable', true)->values();
        $routes = $this->routesFor($mitra);
        $accountSummary = ! empty($filters['account_id'])
            ? $this->service->accountSummary($mitraId, (int) $filters['account_id'], $filters)
            : null;

        return view('pages.mitra-pos.akuntansi.jurnal-index', compact('mitra', 'entries', 'routes', 'filters', 'filterAccounts', 'accountSummary'));
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
            // Intentional, no report(): this is the service's own business-rule
            // validation (e.g. unbalanced journal), not an unexpected failure.
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
