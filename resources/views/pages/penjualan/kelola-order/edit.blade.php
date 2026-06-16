@extends('layouts/layoutMaster')

@section('title', 'Review & Proses Sales Order')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Penjualan / Kelola Order /</span> Review & Proses
        </h4>
        <a href="{{ route('sales-order.manage.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <!-- Detail & Aksi Order -->
        <div class="col-xl-4 col-lg-5">
            <div class="card mb-4">
                <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Rangkuman Pesanan</h5>
                    @php
                        $badgeClass = 'bg-label-secondary';
                        if($data->status == 'submitted') $badgeClass = 'bg-label-warning';
                        if($data->status == 'approved') $badgeClass = 'bg-label-success';
                        if($data->status == 'rejected') $badgeClass = 'bg-label-danger';
                        if($data->status == 'completed') $badgeClass = 'bg-label-info';
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ ucfirst($data->status) }}</span>
                </div>
                <div class="card-body pt-3">
                    <div class="mb-3">
                        <span class="text-muted d-block small">Nomor SO:</span>
                        <span class="fw-semibold text-heading">{{ $data->order_number ?? '[DRAFT - Belum Disubmit]' }}</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted d-block small">Customer:</span>
                        <span class="fw-semibold text-heading">{{ $data->customer_name }}</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted d-block small">Tanggal Order:</span>
                        <span class="fw-semibold text-heading">{{ $data->order_date ? $data->order_date->format('d M Y') : '-' }}</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted d-block small">Grand Total:</span>
                        <span class="fw-bold text-primary fs-5">Rp {{ number_format($data->grand_total, 0, ',', '.') }}</span>
                    </div>

                    @if($data->status == 'submitted')
                    <div class="border-top pt-3 mt-3">
                        <h6 class="fw-bold mb-3">Tindakan Order</h6>
                        
                        <!-- Form Approve -->
                        <form action="{{ route('sales-order.approve', $data->id) }}" method="POST" id="form-approve-order" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 py-2">
                                <i class="ri-checkbox-circle-line me-1"></i> Setujui & Proses Order
                            </button>
                        </form>

                        <!-- Button Tolak Modal -->
                        <button type="button" class="btn btn-danger w-100 py-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="ri-close-circle-line me-1"></i> Tolak Order
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Penyesuaian Item & Qty -->
        <div class="col-xl-8 col-lg-7">
            <div class="card mb-4">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-bold">Penyesuaian Detail Produk & Pengiriman</h5>
                </div>
                <form action="{{ route('sales-order.manage.update', $data->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="form-label">Tipe Pengiriman</label>
                                <select name="delivery_type" class="form-select" {{ $data->status !== 'submitted' ? 'disabled' : '' }} required>
                                    <option value="delivery" {{ $data->delivery_type == 'delivery' ? 'selected' : '' }}>Diantar (Delivery)</option>
                                    <option value="self_pickup" {{ $data->delivery_type == 'self_pickup' ? 'selected' : '' }}>Ambil Sendiri (Self Pickup)</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-bordered mb-0" id="itemsTable">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Nama Produk</th>
                                        <th width="180">Jumlah Pesanan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data->items ?? [] as $index => $item)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                                <span class="fw-semibold">{{ $item->product->name ?? 'Produk Tidak Ditemukan' }}</span>
                                                <small class="d-block text-muted">Stok Saat Ini: {{ $item->product->current_stock ?? 0 }} {{ $item->product->unit ?? 'pcs' }}</small>
                                            </td>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $item->quantity }}" min="1" {{ $data->status !== 'submitted' ? 'disabled' : '' }} required>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center py-3 text-muted">Tidak ada item dalam order ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <label class="form-label">Catatan Warehouse / Keterangan</label>
                            <textarea name="notes" class="form-control" rows="3" {{ $data->status !== 'submitted' ? 'readonly' : '' }}>{{ $data->notes }}</textarea>
                        </div>
                    </div>
                    @if($data->status == 'submitted')
                    <div class="card-footer text-end border-top pt-3">
                        <button type="submit" class="btn btn-primary">Simpan Penyesuaian</button>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tolak Order -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Alasan Penolakan Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sales-order.reject', $data->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tuliskan Alasan Penolakan</label>
                        <textarea name="rejected_reason" class="form-control" rows="4" placeholder="Contoh: Stok bahan baku kopi tidak mencukupi untuk item kopi susu..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top pt-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak & Batalkan Order</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jq = window.$ || window.jQuery;
        if (jq) {
            // Confirm Approve Order
            jq('#form-approve-order').on('submit', function(e) {
                e.preventDefault();
                let form = this;
                Swal.fire({
                    title: 'Approve & Proses Order?',
                    text: "Stok produk akan dipotong secara real-time, serta Surat Jalan (DO) & Invoice akan otomatis diterbitkan!",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Approve & Generate!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-success me-2',
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
