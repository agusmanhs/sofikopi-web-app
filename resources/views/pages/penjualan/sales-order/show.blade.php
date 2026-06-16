@extends('layouts/layoutMaster')

@section('title', 'Detail Sales Order')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-8 col-lg-7 mx-auto">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom mb-3 py-3">
                    <h5 class="mb-0 fw-bold">Detail Sales Order</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('sales-order.print', $data->id) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i class="ri-file-pdf-line me-1"></i> Cetak PDF
                        </a>
                        <a href="{{ route('sales-order.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Nomor Order:</span>
                            <span class="fw-semibold text-heading">{{ $data->order_number ?? '[DRAFT - Belum Disubmit]' }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Status:</span>
                            @php
                                $badgeClass = 'bg-label-secondary';
                                if($data->status == 'submitted') $badgeClass = 'bg-label-warning';
                                if($data->status == 'approved') $badgeClass = 'bg-label-success';
                                if($data->status == 'rejected') $badgeClass = 'bg-label-danger';
                                if($data->status == 'completed') $badgeClass = 'bg-label-info';
                            @endphp
                            <span class="badge {{ $badgeClass }} px-3 py-2 fs-7">{{ ucfirst($data->status) }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Customer:</span>
                            <span class="fw-semibold text-heading">{{ $data->customer_name }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Tanggal Order:</span>
                            <span class="fw-semibold text-heading">{{ $data->order_date ? $data->order_date->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Tipe Pengiriman:</span>
                            <span class="fw-semibold text-heading">{{ $data->delivery_type == 'delivery' ? 'Diantar' : 'Ambil Sendiri' }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Total Tagihan:</span>
                            <span class="fw-bold text-primary fs-5">Rp {{ number_format($data->grand_total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if($data->notes)
                    <div class="mb-4 bg-lighter p-3 rounded">
                        <span class="text-muted d-block mb-1 fw-bold fs-7">Catatan:</span>
                        <p class="mb-0 text-heading small">{{ $data->notes }}</p>
                    </div>
                    @endif

                    <div class="mt-4 border-top pt-3">
                        <h6 class="fw-bold mb-3">Daftar Produk Pesanan</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="py-2">Produk</th>
                                        <th class="py-2 text-center" width="80">Qty</th>
                                        <th class="py-2 text-end" width="120">Harga</th>
                                        <th class="py-2 text-end" width="140">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data->items ?? [] as $item)
                                        <tr>
                                            <td class="py-2 fw-medium">{{ $item->product->name ?? 'Produk Tidak Ditemukan' }}</td>
                                            <td class="py-2 text-center">{{ $item->quantity }}</td>
                                            <td class="py-2 text-end text-muted">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                            <td class="py-2 text-end fw-semibold text-heading">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">Tidak ada item dalam order ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
