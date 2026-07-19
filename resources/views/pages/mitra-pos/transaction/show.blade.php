@extends('layouts/layoutMaster')

@section('title', 'Detail Transaksi POS')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS / Riwayat Transaksi /</span> {{ $transaction->transaction_no }}</h4>
        <a href="{{ route('pos-transaction.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i> Kembali ke Riwayat
        </a>
    </div>

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
                    <div class="row">
                        <div class="col-5 text-muted">Status</div>
                        <div class="col-7"><span class="badge {{ $statusBadge }}">{{ ucfirst($transaction->status) }}</span></div>
                    </div>
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
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">Grand Total</span>
                        <span class="fw-bold fs-5">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
                    </div>
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
@endsection
