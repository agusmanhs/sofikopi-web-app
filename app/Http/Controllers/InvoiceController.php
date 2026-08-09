<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $service
    ) {}

    public function index()
    {
        $data = $this->service->allForIndex();

        return view('pages.penjualan.invoice.index', compact('data'));
    }

    public function show($id)
    {
        $data = $this->service->find($id);
        $data->load('salesOrder.mitra', 'salesOrder.items.product');

        return view('pages.penjualan.invoice.show', compact('data'));
    }

    public function updateStatus(InvoiceRequest $request, $id)
    {
        $data = $request->validated();
        try {
            $this->service->updateInvoiceStatus($id, $data);

            return redirect()->route('invoice.show', $id)
                ->with('success', 'Status invoice berhasil diperbarui!');
        } catch (\Exception $e) {
            // Real detail goes to logs/Sentry only — the raw message may include
            // internal validation/DB detail that shouldn't reach the user.
            Log::error('Gagal memperbarui status invoice: '.$e->getMessage());
            report($e);

            return redirect()->back()->with('error', 'Gagal memperbarui status invoice. Silakan coba lagi atau hubungi admin.');
        }
    }

    public function printPdf($id)
    {
        try {
            $data = $this->service->find($id);
            $data->load('salesOrder.mitra', 'salesOrder.items.product');
            $pdf = Pdf::loadView('pages.penjualan.pdf.invoice', compact('data'));

            return $pdf->stream('Invoice-'.str_replace('/', '-', $data->invoice_number).'.pdf');
        } catch (\Exception $e) {
            // DomPDF render failures can leak internal file paths in the message —
            // keep the real detail in logs/Sentry, show a generic message to the user.
            Log::error('Gagal mencetak PDF Invoice: '.$e->getMessage());
            report($e);

            return redirect()->back()->with('error', 'Gagal mencetak PDF Invoice. Silakan coba lagi atau hubungi admin.');
        }
    }
}
