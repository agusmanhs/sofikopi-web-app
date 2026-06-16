@extends('layouts/layoutMaster')

@section('title', 'Daftar Invoice')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Penjualan /</span> Invoice (Finance)</h4>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-basic table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Nomor Invoice</th>
                        <th>Customer</th>
                        <th>Tanggal Tempo</th>
                        <th>Total Tagihan</th>
                        <th>Status Pembayaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item->invoice_number }}</td>
                        <td>{{ $item->salesOrder->customer_name ?? '-' }}</td>
                        <td>{{ $item->due_date ? $item->due_date->format('d/m/Y') : '-' }}</td>
                        <td>Rp {{ number_format($item->grand_total, 0, ',', '.') }}</td>
                        <td>
                            @if($item->status == 'lunas')
                                <span class="badge bg-label-success">Lunas</span>
                            @else
                                <span class="badge bg-label-danger">Belum Lunas</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('invoice.show', $item->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="ri-eye-line me-1"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Belum ada invoice diterbitkan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
