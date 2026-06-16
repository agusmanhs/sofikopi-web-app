@extends('layouts/layoutMaster')

@section('title', 'Sales Order')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Penjualan /</span> Sales Order</h4>
        <a href="{{ route('sales-order.create') }}" class="btn btn-primary">
            <i class="ri-add-line me-1"></i> Buat Order Baru
        </a>
    </div>

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
                        <th>No</th>
                        <th>Nomor Order</th>
                        <th>Customer</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->order_number ?? '[DRAFT - Belum Disubmit]' }}</td>
                        <td>{{ $item->customer_name }}</td>
                        <td>{{ $item->order_date ? $item->order_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            @php
                                $badgeClass = 'bg-label-primary';
                                if($item->status == 'submitted') $badgeClass = 'bg-label-warning';
                                if($item->status == 'approved') $badgeClass = 'bg-label-success';
                                if($item->status == 'rejected') $badgeClass = 'bg-label-danger';
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ ucfirst($item->status) }}</span>
                        </td>
                        <td>Rp {{ number_format($item->grand_total, 0, ',', '.') }}</td>
                        <td>
                            <a href="{{ route('sales-order.show', $item->id) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Detail"><i class="ri-eye-line"></i></a>
                            @if($item->status == 'draft')
                            <a href="{{ route('sales-order.edit', $item->id) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Edit"><i class="ri-edit-box-line"></i></a>
                            <form action="{{ route('sales-order.submit', $item->id) }}" method="POST" class="form-submit-confirm d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-icon btn-text-success" title="Submit"><i class="ri-send-plane-line"></i></button>
                            </form>
                            <form action="{{ route('sales-order.destroy', $item->id) }}" method="POST" class="form-delete-confirm d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-text-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Belum ada data Sales Order.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jq = window.$ || window.jQuery;
        if (jq) {
            jq('.form-submit-confirm').on('submit', function(e) {
                e.preventDefault();
                let form = this;
                Swal.fire({
                    title: 'Submit Order?',
                    text: "Setelah disubmit, order tidak dapat diedit atau dihapus oleh Sales!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Submit!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            jq('.form-delete-confirm').on('submit', function(e) {
                e.preventDefault();
                let form = this;
                Swal.fire({
                    title: 'Hapus Draft?',
                    text: "Draft order akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        }
    });
</script>
@endsection
