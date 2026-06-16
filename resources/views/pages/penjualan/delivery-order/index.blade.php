@extends('layouts/layoutMaster')

@section('title', 'Delivery Order')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Penjualan /</span> Delivery Order</h4>

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
                        <th>Nomor DO</th>
                        <th>Referensi SO</th>
                        <th>Customer</th>
                        <th>Tipe</th>
                        <th>Kurir Ditugaskan</th>
                        <th>Status Pengiriman</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item->do_number }}</td>
                        <td>{{ $item->salesOrder->order_number ?? '-' }}</td>
                        <td>{{ $item->salesOrder->customer_name ?? '-' }}</td>
                        <td>
                            @if($item->delivery_type == 'self_pickup')
                                <span class="badge bg-label-info">Ambil di Store</span>
                            @else
                                <span class="badge bg-label-secondary">Diantar</span>
                            @endif
                        </td>
                        <td>{{ $item->delivery_type == 'self_pickup' ? 'Ambil di Store' : ($item->assignedTo->name ?? 'Belum Ditugaskan') }}</td>
                        <td>
                            @php
                                $badgeClass = 'bg-label-secondary';
                                if($item->status == 'assigned') $badgeClass = 'bg-label-primary';
                                if($item->status == 'in_delivery') $badgeClass = 'bg-label-warning';
                                if($item->status == 'delivered') $badgeClass = 'bg-label-success';
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ ucfirst($item->status) }}</span>
                        </td>
                        <td>
                            <a href="{{ route('delivery-order.show', $item->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="ri-eye-line me-1"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Belum ada data DO.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
