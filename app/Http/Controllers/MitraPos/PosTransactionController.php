<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraPos\VoidTransactionRequest;
use App\Models\Mitra;
use App\Models\MitraPosSetting;
use App\Services\MitraPos\MitraContext;
use App\Services\MitraPos\PosTransactionService;
use RuntimeException;

class PosTransactionController extends Controller
{
    public function __construct(
        protected PosTransactionService $service,
        protected MitraContext $mitraContext
    ) {}

    // --- Tenant portal (mitra-pos/transaction/...), mitra context from MitraContext ---

    public function index()
    {
        $transactions = $this->service->forMitra($this->mitraContext->id());
        $routes = $this->routesFor(null);

        return view('pages.mitra-pos.transaction.index', compact('transactions', 'routes'));
    }

    /**
     * $transaction is the transaction_no (e.g. POS/LALLO/20260718/0001),
     * not the numeric id — see PosTransactionService::findForMitra.
     */
    public function show(string $transaction)
    {
        $transaction = $this->service->findForMitra($this->mitraContext->id(), $transaction);
        $routes = $this->routesFor(null);
        $canVoid = (bool) auth()->user()?->hasPermission('pos-transaction.index', 'delete');

        return view('pages.mitra-pos.transaction.show', compact('transaction', 'routes', 'canVoid'));
    }

    /**
     * $transaction is the transaction_no — see show() above.
     */
    public function void(VoidTransactionRequest $request, string $transaction)
    {
        return $this->handleVoid($request, $this->mitraContext->id(), $transaction, 'pos-transaction.show', [$transaction]);
    }

    public function receipt(string $transaction)
    {
        $mitraId = $this->mitraContext->id();
        $transaction = $this->service->findForMitra($mitraId, $transaction);

        return $this->renderReceipt($mitraId, $transaction);
    }

    // --- Sofikopi-staff admin (mitra-pos/manage/{mitra}/transaction/...) ---

    public function adminIndex(Mitra $mitra)
    {
        $transactions = $this->service->forMitra($mitra->id);
        $routes = $this->routesFor($mitra);

        return view('pages.mitra-pos.transaction.index', compact('mitra', 'transactions', 'routes'));
    }

    public function adminShow(Mitra $mitra, string $transaction)
    {
        $transaction = $this->service->findForMitra($mitra->id, $transaction);
        $routes = $this->routesFor($mitra);
        $canVoid = (bool) auth()->user()?->hasPermission('mitra-pos-manage.index', 'delete');

        return view('pages.mitra-pos.transaction.show', compact('mitra', 'transaction', 'routes', 'canVoid'));
    }

    public function adminVoid(VoidTransactionRequest $request, Mitra $mitra, string $transaction)
    {
        return $this->handleVoid($request, $mitra->id, $transaction, 'mitra-pos-manage.transaction.show', [$mitra, $transaction]);
    }

    public function adminReceipt(Mitra $mitra, string $transaction)
    {
        $transaction = $this->service->findForMitra($mitra->id, $transaction);

        return $this->renderReceipt($mitra->id, $transaction, $mitra);
    }

    private function renderReceipt(int $mitraId, $transaction, ?Mitra $mitra = null)
    {
        $mitra ??= Mitra::findOrFail($mitraId);
        $footer = MitraPosSetting::forMitra($mitraId)->first()?->receipt_footer;

        return view('pages.mitra-pos.transaction.receipt', compact('transaction', 'mitra', 'footer'));
    }

    private function handleVoid(VoidTransactionRequest $request, int $mitraId, string $transaction, string $redirectRoute, array $redirectParams)
    {
        $data = $request->validated();

        try {
            $this->service->void(
                mitraId: $mitraId,
                transactionNo: $transaction,
                userId: auth()->id(),
                reason: $data['reason'],
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route($redirectRoute, $redirectParams)
            ->with('success', 'Transaksi berhasil dibatalkan (void).');
    }

    /**
     * Builds route callbacks shared by transaction/index.blade.php and
     * transaction/show.blade.php so the same view templates render for both
     * the tenant portal (no {mitra} param, context-derived) and the
     * Sofikopi-staff admin picker ({mitra} route param).
     */
    private function routesFor(?Mitra $mitra): array
    {
        return [
            'index' => $mitra
                ? route('mitra-pos-manage.transaction.index', $mitra)
                : route('pos-transaction.index'),
            'show' => fn (string $transactionNo) => $mitra
                ? route('mitra-pos-manage.transaction.show', [$mitra, $transactionNo])
                : route('pos-transaction.show', $transactionNo),
            'void' => fn (string $transactionNo) => $mitra
                ? route('mitra-pos-manage.transaction.void', [$mitra, $transactionNo])
                : route('pos-transaction.void', $transactionNo),
            'receipt' => fn (string $transactionNo) => $mitra
                ? route('mitra-pos-manage.transaction.receipt', [$mitra, $transactionNo])
                : route('pos-transaction.receipt', $transactionNo),
        ];
    }
}
