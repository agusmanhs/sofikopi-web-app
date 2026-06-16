@extends('layouts/layoutMaster')

@section('title', 'Kelola Order')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Penjualan /</span> Kelola Order (Warehouse)</h4>

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
            <table class="datatables-basic table table-bordered">
                <thead>
                    <tr>
                        <th>Nomor Order</th>
                        <th>Customer</th>
                        <th>Tipe Pengiriman</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item->order_number ?? '[DRAFT - Belum Disubmit]' }}</td>
                        <td>{{ $item->customer_name }}</td>
                        <td>{{ $item->delivery_type == 'delivery' ? 'Diantar' : 'Ambil Sendiri' }}</td>
                        <td>
                            @php
                                $badgeClass = 'bg-label-secondary';
                                if($item->status == 'submitted') $badgeClass = 'bg-label-warning';
                                if($item->status == 'approved') $badgeClass = 'bg-label-success';
                                if($item->status == 'rejected') $badgeClass = 'bg-label-danger';
                                if($item->status == 'completed') $badgeClass = 'bg-label-info';
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ ucfirst($item->status) }}</span>
                        </td>
                        <td>
                            @if($item->status == 'submitted')
                                <a href="{{ route('sales-order.manage.edit', $item->id) }}" class="btn btn-sm btn-primary">
                                    <i class="ri-edit-box-line me-1"></i> Review & Proses
                                </a>
                            @else
                                <a href="{{ route('sales-order.manage.edit', $item->id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="ri-eye-line me-1"></i> Detail
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada order yang perlu dikelola.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
