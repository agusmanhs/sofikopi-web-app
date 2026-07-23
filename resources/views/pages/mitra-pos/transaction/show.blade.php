@extends('layouts/layoutMaster')

@section('title', 'Detail Transaksi POS')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS / Riwayat Transaksi /</span> {{ $transaction->transaction_no }}</h4>
        <div class="d-flex gap-2">
            <a href="{{ $routes['receipt']($transaction->transaction_no) }}" target="_blank" class="btn btn-outline-secondary">
                <i class="ri-printer-line me-1"></i> Cetak Struk
            </a>
            @if ($transaction->status === 'completed' && ($canVoid ?? false))
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#voidModal">
                <i class="ri-close-circle-line me-1"></i> Void Transaksi
            </button>
            @endif
            <a href="{{ $routes['index'] }}" class="btn btn-outline-secondary">
                <i class="ri-arrow-left-line me-1"></i> Kembali ke Riwayat
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php
        $salesModeLabel = match ($transaction->sales_mode) {
            'dine_in' => 'Dine In',
            'take_away' => 'Take Away',
            'online' => 'Online',
            default => ucfirst($transaction->sales_mode),
        };
        $statusBadge = $transaction->status === 'voided' ? 'bg-label-danger' : 'bg-label-success';
        $grossProfit = (float) $transaction->grand_total - (float) $transaction->total_cogs;
    @endphp

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Transaksi</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 text-muted">No. Transaksi</div>
                        <div class="col-7 fw-medium">{{ $transaction->transaction_no }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Tanggal</div>
                        <div class="col-7">{{ optional($transaction->transacted_at)->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Kasir</div>
                        <div class="col-7">{{ $transaction->user->name ?? '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Mode Penjualan</div>
                        <div class="col-7"><span class="badge bg-label-primary">{{ $salesModeLabel }}</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Metode Pembayaran</div>
                        <div class="col-7"><span class="badge bg-label-success">{{ strtoupper($transaction->payment_method) }}</span></div>
                    </div>
                    <div class="row {{ $transaction->status === 'voided' ? 'mb-2' : '' }}">
                        <div class="col-5 text-muted">Status</div>
                        <div class="col-7"><span class="badge {{ $statusBadge }}">{{ ucfirst($transaction->status) }}</span></div>
                    </div>
                    @if ($transaction->status === 'voided')
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Di-void oleh</div>
                        <div class="col-7">{{ $transaction->voidedBy->name ?? '-' }}, {{ optional($transaction->voided_at)->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="row">
                        <div class="col-5 text-muted">Alasan Void</div>
                        <div class="col-7">{{ $transaction->void_reason }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Item Transaksi</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transaction->items as $item)
                                @php
                                    $lineMargin = ((float) $item->unit_price - (float) $item->cogs_snapshot) * (float) $item->qty;
                                @endphp
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($item->qty, 3, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                    <td class="text-end {{ $lineMargin < 0 ? 'text-danger' : 'text-success' }}">
                                        Rp {{ number_format($lineMargin, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada item.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Ringkasan</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Diskon</span>
                        <span>Rp {{ number_format($transaction->discount, 0, ',', '.') }}</span>
                    </div>
                    @if ((float) $transaction->service_charge > 0)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Service Charge</span>
                        <span>Rp {{ number_format($transaction->service_charge, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if ((float) $transaction->tax > 0)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Pajak</span>
                        <span>Rp {{ number_format($transaction->tax, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">Grand Total</span>
                        <span class="fw-bold fs-5">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
                    </div>
                    @if ((float) $transaction->admin_fee > 0)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Potongan Admin ({{ strtoupper($transaction->payment_method) }})</span>
                        <span>Rp {{ number_format($transaction->admin_fee, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total HPP</span>
                        <span>Rp {{ number_format($transaction->total_hpp, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total COGS</span>
                        <span>Rp {{ number_format($transaction->total_cogs, 0, ',', '.') }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Gross Profit</span>
                        <span class="fw-bold {{ $grossProfit < 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($grossProfit, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($transaction->status === 'completed' && ($canVoid ?? false))
<div class="modal fade" id="voidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Void Transaksi {{ $transaction->transaction_no }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ $routes['void']($transaction->transaction_no) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted">Stok bahan yang terpakai pada transaksi ini akan dikembalikan secara otomatis. Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="mb-3">
                        <label class="form-label">Alasan Void</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Contoh: Salah input produk oleh kasir" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top pt-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Void Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
