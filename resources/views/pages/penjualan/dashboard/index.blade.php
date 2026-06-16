@extends('layouts/layoutMaster')

@section('title', 'Dashboard Penjualan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Dashboard Penjualan</h4>
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>Total Order (Bulan Ini)</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2">{{ $totalOrder }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ri-shopping-cart-line ri-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>Total Revenue</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ri-money-dollar-circle-line ri-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>Menunggu Approval</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2">{{ $pendingApproval }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded p-2"><i class="ri-time-line ri-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>Pengiriman Aktif</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2">{{ $activeDelivery }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ri-truck-line ri-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
