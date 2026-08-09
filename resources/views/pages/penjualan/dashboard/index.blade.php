@extends('layouts/layoutMaster')

@section('title', 'Dashboard Penjualan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Dashboard Penjualan</h4>

    @php
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $tahunSekarang = now()->year;
    @endphp

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('sales-dashboard.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-select" onchange="this.form.submit()">
                        @foreach ($namaBulan as $i => $nama)
                            <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        @for ($i = $tahunSekarang; $i >= $tahunSekarang - 4; $i--)
                            <option value="{{ $i }}" {{ $tahun == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>Total Order ({{ $namaBulan[$bulan] }} {{ $tahun }})</span>
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
                            <span>Total Revenue ({{ $namaBulan[$bulan] }} {{ $tahun }})</span>
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
