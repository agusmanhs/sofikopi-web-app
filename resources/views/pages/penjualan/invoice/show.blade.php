@extends('layouts/layoutMaster')

@section('title', 'Detail Invoice')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Penjualan / Invoice /</span> Detail
        </h4>
        <div class="d-flex gap-2">
            <a href="{{ route('invoice.print', $data->id) }}" target="_blank" class="btn btn-outline-danger">
                <i class="ri-file-pdf-line me-1"></i> Cetak PDF
            </a>
            <a href="{{ route('invoice.index') }}" class="btn btn-outline-secondary">
                <i class="ri-arrow-left-line me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <!-- Invoice Details -->
        <div class="col-xl-8 col-lg-7">
            <div class="card mb-4">
                <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Faktur Tagihan</h5>
                    @if($data->status == 'lunas')
                        <span class="badge bg-label-success px-3 py-2 fs-7">Lunas</span>
                    @else
                        <span class="badge bg-label-danger px-3 py-2 fs-7">Belum Lunas</span>
                    @endif
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Nomor Invoice:</span>
                            <span class="fw-semibold text-heading">{{ $data->invoice_number }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Referensi SO:</span>
                            <span class="fw-semibold text-heading">{{ $data->salesOrder->order_number ?? '-' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Customer:</span>
                            <span class="fw-semibold text-heading">{{ $data->salesOrder->customer_name ?? '-' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Tanggal Terbit:</span>
                            <span class="fw-semibold text-heading">{{ $data->invoice_date ? $data->invoice_date->format('d/m/Y') : '-' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Jatuh Tempo:</span>
                            <span class="fw-semibold text-heading text-danger">{{ $data->due_date ? $data->due_date->format('d/m/Y') : '-' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Tanggal Pelunasan:</span>
                            <span class="fw-semibold text-heading text-success">{{ $data->paid_at ? $data->paid_at->format('d/m/Y H:i') : 'Belum Lunas' }}</span>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="border-top pt-3 mt-4">
                        <h6 class="fw-bold mb-3">Detail Biaya Produk</h6>
                        <div class="table-responsive border rounded mb-3">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="py-2">Nama Produk</th>
                                        <th class="py-2 text-center" width="80">Qty</th>
                                        <th class="py-2 text-end" width="120">Harga</th>
                                        <th class="py-2 text-end" width="140">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data->salesOrder->items ?? [] as $item)
                                        <tr>
                                            <td class="py-2 fw-medium">{{ $item->product->name ?? 'Produk Tidak Ditemukan' }}</td>
                                            <td class="py-2 text-center">{{ $item->quantity }}</td>
                                            <td class="py-2 text-end text-muted">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                            <td class="py-2 text-end fw-semibold text-heading">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-3 text-muted">Tidak ada produk.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Calculations summary -->
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Subtotal:</span>
                                    <span class="fw-semibold">Rp {{ number_format($data->subtotal, 0, ',', '.') }}</span>
                                </div>
                                @if($data->discount_total > 0)
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Diskon:</span>
                                    <span class="fw-semibold text-danger">- Rp {{ number_format($data->discount_total, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                @if($data->tax_total > 0)
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Pajak (PPN 11%):</span>
                                    <span class="fw-semibold">Rp {{ number_format($data->tax_total, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                <div class="d-flex justify-content-between border-top pt-2">
                                    <span class="fw-bold">Grand Total:</span>
                                    <span class="fw-bold text-primary">Rp {{ number_format($data->grand_total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar (Bank & Payment update) -->
        <div class="col-xl-4 col-lg-5">
            <!-- Bank Details -->
            <div class="card mb-4 bg-lighter border border-light">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-bold"><i class="ri-bank-card-line me-1"></i> Informasi Pembayaran</h5>
                </div>
                <div class="card-body pt-3">
                    <div class="mb-2">
                        <span class="text-muted d-block small">Nama Bank:</span>
                        <span class="fw-semibold text-heading">{{ $data->bank_name ?? 'Bank Mandiri' }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted d-block small">Nama Rekening:</span>
                        <span class="fw-semibold text-heading">{{ $data->bank_account_name ?? 'PT. SOFIKOPI GROUP INDONESIA' }}</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted d-block small">Nomor Rekening:</span>
                        <span class="fw-bold text-primary fs-5">{{ $data->bank_account_number ?? '1740010036036' }}</span>
                    </div>
                    @if($data->notes)
                    <div class="bg-white p-2 rounded border border-light">
                        <span class="text-muted d-block small mb-1 fw-bold fs-7">Catatan Finance:</span>
                        <p class="mb-0 text-heading small">{{ $data->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Finance Status Update Panel -->
            @if(in_array(auth()->user()->role->slug, ['finance', 'super-admin']))
            <div class="card mb-4 border-primary border">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-bold"><i class="ri-hand-coin-line me-1 text-primary"></i> Kelola Pembayaran (Finance)</h5>
                </div>
                <form action="{{ route('invoice.update-status', $data->id) }}" method="POST">
                    @csrf
                    <div class="card-body pt-3">
                        <div class="mb-3">
                            <label class="form-label">Ubah Status Pembayaran</label>
                            <select name="status" class="form-select" required>
                                <option value="belum_lunas" {{ $data->status == 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
                                <option value="lunas" {{ $data->status == 'lunas' ? 'selected' : '' }}>Lunas (Tandai Bayar)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Bayar</label>
                            <input type="datetime-local" name="paid_at" class="form-control" value="{{ $data->paid_at ? $data->paid_at->format('Y-m-d\TH:i') : '' }}">
                            <small class="text-muted d-block mt-1">Kosongkan jika ingin diset ke waktu sekarang.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Finance</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan transaksi atau bukti bayar...">{{ $data->notes }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer border-top pt-3 text-end">
                        <button type="submit" class="btn btn-primary w-100">Update Status Pembayaran</button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
