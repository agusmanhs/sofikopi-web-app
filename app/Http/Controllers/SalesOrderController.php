<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesOrderRequest;
use App\Models\Mitra;
use App\Models\Products;
use App\Services\SalesOrderService;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesOrderController extends Controller
{
    public function __construct(
        protected SalesOrderService $service
    ) {}

    public function index()
    {
        $data = $this->service->all();

        return view('pages.penjualan.sales-order.index', compact('data'));
    }

    public function create()
    {
        $products = Products::all();
        $mitras = Mitra::all();

        return view('pages.penjualan.sales-order.create', compact('products', 'mitras'));
    }

    public function store(SalesOrderRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id() ?? 1; // Fallback for testing

        $this->service->createWithItems($data);

        return redirect()->route('sales-order.index')
            ->with('success', 'Order draft berhasil dibuat!');
    }

    public function show($id)
    {
        $data = $this->service->find($id);

        return view('pages.penjualan.sales-order.show', compact('data'));
    }

    public function edit($id)
    {
        $data = $this->service->find($id);
        $products = Products::all();
        $mitras = Mitra::all();

        return view('pages.penjualan.sales-order.edit', compact('data', 'products', 'mitras'));
    }

    public function update(SalesOrderRequest $request, $id)
    {
        $data = $request->validated();
        try {
            $this->service->updateWithItems($id, $data);

            return redirect()->route('sales-order.index')
                ->with('success', 'Order draft berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui order: '.$e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);

            return redirect()->route('sales-order.index')
                ->with('success', 'Order draft berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus order: '.$e->getMessage());
        }
    }

    public function submit($id)
    {
        try {
            $this->service->submitOrder($id);

            return redirect()->route('sales-order.index')
                ->with('success', 'Order berhasil disubmit!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mensubmit order: '.$e->getMessage());
        }
    }

    public function printPdf($id)
    {
        try {
            $data = $this->service->find($id);
            $data->load('items.product', 'mitra');
            $pdf = Pdf::loadView('pages.penjualan.pdf.sales-order', compact('data'));

            return $pdf->stream('SalesOrder-'.str_replace('/', '-', $data->order_number ?? $data->id).'.pdf');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mencetak PDF Sales Order: '.$e->getMessage());
        }
    }
}
