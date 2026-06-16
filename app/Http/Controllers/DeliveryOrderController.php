<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliveryOrderAssignRequest;
use App\Models\User;
use App\Services\DeliveryOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DeliveryOrderController extends Controller
{
    public function __construct(
        protected DeliveryOrderService $service
    ) {}

    public function index()
    {
        $user = auth()->user();
        if (in_array($user->role->slug, ['hrd', 'super-admin'])) {
            $data = $this->service->all();
        } else {
            $data = $this->service->getByAssignedUser($user->id);
        }

        return view('pages.penjualan.delivery-order.index', compact('data'));
    }

    public function show($id)
    {
        $data = $this->service->find($id);
        $loopers = User::whereHas('pegawai', function ($q) {
            $q->where('status_aktif', true);
        })->with(['role', 'pegawai'])
            ->get()
            ->sortBy(function ($user) {
                $priority = ($user->role && $user->role->slug === 'looper') ? 0 : 1;

                return [$priority, strtolower($user->name)];
            })->values();

        return view('pages.penjualan.delivery-order.show', compact('data', 'loopers'));
    }

    public function reassign(DeliveryOrderAssignRequest $request, $id)
    {
        $data = $request->validated();
        try {
            $this->service->reassignOrder($id, $data['assigned_to'], auth()->id() ?? 1, $data['notes'] ?? null);

            return redirect()->route('delivery-order.show', $id)
                ->with('success', 'Kurir berhasil ditugaskan ulang!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menugaskan kurir: '.$e->getMessage());
        }
    }

    public function start($id)
    {
        try {
            $this->service->startDelivery($id);

            return redirect()->route('delivery-order.show', $id)
                ->with('success', 'Pengiriman dimulai!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memulai pengiriman: '.$e->getMessage());
        }
    }

    public function uploadProof(Request $request, $id)
    {
        $request->validate([
            'received_by_name' => 'required|string|max:255',
            'proof_photo' => 'required|image|max:5000',
            'proof_latitude' => 'nullable|numeric',
            'proof_longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        try {
            $data = $request->only(['received_by_name', 'proof_latitude', 'proof_longitude', 'notes']);

            if ($request->hasFile('proof_photo')) {
                $path = $request->file('proof_photo')->store('proofs', 'public');
                $data['proof_photo'] = $path;
            }

            $this->service->completeDelivery($id, $data);

            return redirect()->route('delivery-order.show', $id)
                ->with('success', 'Bukti pengiriman berhasil diupload!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyelesaikan pengiriman: '.$e->getMessage());
        }
    }

    public function completePickup(Request $request, $id)
    {
        $request->validate([
            'received_by_name' => 'required|string|max:255',
        ]);

        try {
            $this->service->completePickup($id, $request->received_by_name);

            return redirect()->route('delivery-order.show', $id)
                ->with('success', 'Pengambilan di store berhasil diselesaikan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyelesaikan pengambilan: '.$e->getMessage());
        }
    }

    public function printPdf($id)
    {
        try {
            $data = $this->service->find($id);
            $data->load('salesOrder.items.product');
            $pdf = Pdf::loadView('pages.penjualan.pdf.delivery-order', compact('data'));

            return $pdf->stream('SuratJalan-'.str_replace('/', '-', $data->do_number).'.pdf');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mencetak PDF Surat Jalan: '.$e->getMessage());
        }
    }
}
