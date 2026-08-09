<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use Illuminate\Http\Request;

class SalesDashboardController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $totalOrder = SalesOrder::whereMonth('created_at', $bulan)
            ->whereYear('created_at', $tahun)
            ->count();
        $totalRevenue = SalesOrder::where('status', 'completed')
            ->whereMonth('created_at', $bulan)
            ->whereYear('created_at', $tahun)
            ->sum('grand_total');
        $pendingApproval = SalesOrder::where('status', 'submitted')->count();
        $activeDelivery = DeliveryOrder::whereIn('status', ['pending', 'assigned', 'in_delivery'])->count();

        return view('pages.penjualan.dashboard.index', compact(
            'totalOrder',
            'totalRevenue',
            'pendingApproval',
            'activeDelivery',
            'bulan',
            'tahun'
        ));
    }
}
