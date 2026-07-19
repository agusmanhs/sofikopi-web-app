<?php

namespace App\Http\Controllers\MitraPos;

use App\Http\Controllers\Controller;
use App\Services\MitraPos\MitraContext;
use App\Services\MitraPos\PosTransactionService;

class PosTransactionController extends Controller
{
    public function __construct(
        protected PosTransactionService $service,
        protected MitraContext $mitraContext
    ) {}

    public function index()
    {
        $transactions = $this->service->forMitra($this->mitraContext->id());

        return view('pages.mitra-pos.transaction.index', compact('transactions'));
    }

    /**
     * $transaction is the transaction_no (e.g. POS/LALLO/20260718/0001),
     * not the numeric id — see PosTransactionService::findForMitra.
     */
    public function show(string $transaction)
    {
        $transaction = $this->service->findForMitra($this->mitraContext->id(), $transaction);

        return view('pages.mitra-pos.transaction.show', compact('transaction'));
    }
}
