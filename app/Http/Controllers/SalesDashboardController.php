<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\DeliveryOrder;
use App\Models\Invoice;

class SalesDashboardController extends Controller
{
    public function index()
    {
        // Simple mock for now
        $totalOrder = SalesOrder::whereMonth('created_at', date('m'))->count();
        $totalRevenue = SalesOrder::where('status', 'completed')
                        ->whereMonth('created_at', date('m'))
                        ->sum('grand_total');
        $pendingApproval = SalesOrder::where('status', 'submitted')->count();
        $activeDelivery = DeliveryOrder::whereIn('status', ['pending', 'assigned', 'in_delivery'])->count();
        
        return view('pages.penjualan.dashboard.index', compact(
            'totalOrder',
            'totalRevenue',
            'pendingApproval',
            'activeDelivery'
        ));
    }
}
