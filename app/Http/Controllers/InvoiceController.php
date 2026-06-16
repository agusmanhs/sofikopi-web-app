<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $service
    ) {}

    public function index()
    {
        $data = $this->service->all();

        return view('pages.penjualan.invoice.index', compact('data'));
    }

    public function show($id)
    {
        $data = $this->service->find($id);

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
            return redirect()->back()->with('error', 'Gagal memperbarui status invoice: '.$e->getMessage());
        }
    }

    public function printPdf($id)
    {
        try {
            $data = $this->service->find($id);
            $data->load('salesOrder.items.product');
            $pdf = Pdf::loadView('pages.penjualan.pdf.invoice', compact('data'));

            return $pdf->stream('Invoice-'.str_replace('/', '-', $data->invoice_number).'.pdf');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mencetak PDF Invoice: '.$e->getMessage());
        }
    }
}
