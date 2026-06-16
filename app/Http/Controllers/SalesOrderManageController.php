<?php

namespace App\Http\Controllers;

use App\Services\SalesOrderService;
use Illuminate\Http\Request;

class SalesOrderManageController extends Controller
{
    public function __construct(
        protected SalesOrderService $service
    ) {}

    public function index()
    {
        // Show only submitted, approved, completed, rejected
        $data = \App\Models\SalesOrder::where('status', '!=', 'draft')
            ->orderBy('id', 'desc')
            ->get();
        return view('pages.penjualan.kelola-order.index', compact('data'));
    }

    public function edit($id)
    {
        $data = $this->service->find($id);
        $products = \App\Models\Products::all();
        $mitras = \App\Models\Mitra::all();
        return view('pages.penjualan.kelola-order.edit', compact('data', 'products', 'mitras'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'delivery_type' => 'required|in:delivery,self_pickup',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        
        try {
            $this->service->updateWithItems($id, $validated);
            return redirect()->route('sales-order.manage.index')
                ->with('success', 'Order berhasil disesuaikan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyesuaikan order: ' . $e->getMessage())->withInput();
        }
    }

    public function approve($id, Request $request)
    {
        try {
            $this->service->approveOrder($id, auth()->id() ?? 1);
            return redirect()->route('sales-order.manage.index')
                ->with('success', 'Order berhasil disetujui, DO dan Invoice telah digenerate!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyetujui order: ' . $e->getMessage());
        }
    }
    
    public function reject($id, Request $request)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:500'
        ]);
        
        try {
            $this->service->rejectOrder($id, $request->rejected_reason, auth()->id() ?? 1);
            return redirect()->route('sales-order.manage.index')
                ->with('success', 'Order berhasil ditolak!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menolak order: ' . $e->getMessage());
        }
    }
}

