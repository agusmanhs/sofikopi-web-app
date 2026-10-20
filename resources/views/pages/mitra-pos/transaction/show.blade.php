@extends('layouts/layoutMaster')

@section('title', 'Detail Transaksi POS')

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

@section('content')
@php
    $canSeeCost = $canSeeCost ?? false;
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS / Riwayat Transaksi /</span> {{ $transaction->transaction_no }}</h4>
        <div class="d-flex gap-2">
            <a href="{{ $routes['receipt']($transaction->transaction_no) }}" target="_blank" class="btn btn-outline-secondary">
                <i class="ri-printer-line me-1"></i> Cetak Struk
            </a>
            @if ($transaction->status === 'completed' && ($canVoid ?? false))
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#voidModal">
                <i class="ri-close-circle-line me-1"></i> Batalkan Transaksi (Void)
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
        $fmtQty = fn ($qty) => rtrim(rtrim(number_format($qty, 3, ',', '.'), '0'), ',');
        $canOpenJournal = auth()->user()->can('access', [isset($mitra) ? 'mitra-pos-manage.index' : 'akuntansi-jurnal.index', 'read']);
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
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Status</div>
                        <div class="col-7"><span class="badge {{ $statusBadge }}">{{ $transaction->status === 'voided' ? 'Dibatalkan' : 'Selesai' }}</span></div>
                    </div>
                    @if ($transaction->status === 'voided')
                    <div class="row mb-2">
                        <div class="col-5 text-muted">Dibatalkan oleh</div>
                        <div class="col-7">{{ $transaction->voidedBy->name ?? '-' }}, {{ optional($transaction->voided_at)->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="row">
                        <div class="col-5 text-muted">Alasan Pembatalan</div>
                        <div class="col-7">{{ $transaction->void_reason }}</div>
                    </div>
                    @endif
                    @if ($canSeeCost && $journalEntries->isNotEmpty())
                    <div class="row mt-2">
                        <div class="col-5 text-muted">Jurnal Akuntansi</div>
                        <div class="col-7">
                            @foreach ($journalEntries as $entry)
                                <div>
                                    {{ $entry->entry_no }}
                                    <span class="text-muted small">({{ $entry->source_type === 'pos_void' ? 'pembalik karena dibatalkan' : 'penjualan' }})</span>
                                </div>
                            @endforeach
                            @if ($canOpenJournal)
                            <a href="{{ $routes['journal'] }}" class="small">Buka halaman Jurnal</a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Item Transaksi</h5>
                    @if ($canSeeCost)
                    <small class="text-muted">Klik baris produk untuk melihat bahan yang dipakai.</small>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Subtotal</th>
                                @if ($canSeeCost)
                                <th class="text-end">Untung (setelah Faktor Q)</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transaction->items as $item)
                                @php
                                    $lineMargin = ((float) $item->unit_price - (float) $item->cogs_snapshot) * (float) $item->qty;
                                    $detailId = 'bahan-item-'.$item->id;
                                    $ingredients = $item->product?->ingredients ?? collect();
                                @endphp
                                <tr @if ($canSeeCost) role="button" data-bs-toggle="collapse" data-bs-target="#{{ $detailId }}" aria-expanded="false" aria-controls="{{ $detailId }}" @endif>
                                    <td>@if ($canSeeCost)<i class="ri-arrow-right-s-line me-1"></i>@endif{{ $item->product_name }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($item->qty, 3, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                    @if ($canSeeCost)
                                    <td class="text-end {{ $lineMargin < 0 ? 'text-danger' : 'text-success' }}">
                                        Rp {{ number_format($lineMargin, 0, ',', '.') }}
                                    </td>
                                    @endif
                                </tr>
                                @if ($canSeeCost)
                                <tr class="collapse" id="{{ $detailId }}">
                                    <td colspan="5" class="bg-lighter">
                                        <div class="small text-muted mb-2">
                                            Bahan untuk {{ $fmtQty($item->qty) }} porsi, menurut <strong>resep saat ini</strong>
                                            (jika resep pernah diubah setelah transaksi ini, lihat tabel "Bahan yang keluar dari stok" di bawah untuk angka yang sebenarnya).
                                        </div>
                                        @if ($ingredients->isEmpty())
                                            <div class="small">Resep produk ini tidak tersedia (produk dihapus atau belum punya bahan).</div>
                                        @else
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Bahan</th>
                                                    <th class="text-end">Dipakai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($ingredients as $ingredient)
                                                <tr>
                                                    <td>{{ $ingredient->material->name ?? 'Bahan sudah dihapus' }}</td>
                                                    <td class="text-end">{{ $fmtQty((float) $ingredient->qty * (float) $item->qty) }} {{ $ingredient->material->unit ?? '' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="{{ $canSeeCost ? 5 : 4 }}" class="text-center">Tidak ada item.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            @if ($canSeeCost)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Bahan yang keluar dari stok</h5>
                    <small class="text-muted">Dicatat saat penjualan. Ini angka sebenarnya, tidak berubah walau resep diedit.</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Bahan</th>
                                <th class="text-end">Dipakai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stockOut as $movement)
                            <tr>
                                <td>{{ $movement->material->name ?? 'Bahan sudah dihapus' }}</td>
                                <td class="text-end">{{ $fmtQty($movement->qty) }} {{ $movement->material->unit ?? '' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">Tidak ada catatan pemakaian bahan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

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
                    @if ($canSeeCost)
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Biaya bahan (resep)</span>
                        <span>Rp {{ number_format($transaction->total_hpp, 0, ',', '.') }}</span>
                    </div>
                    <div class="small text-muted mb-2">Harga bahan sesuai resep saat transaksi (istilah teknis: HPP).</div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Biaya bahan + cadangan (Faktor Q)</span>
                        <span>Rp {{ number_format($transaction->total_cogs, 0, ',', '.') }}</span>
                    </div>
                    <div class="small text-muted mb-2">Biaya bahan ditambah cadangan untuk bahan terbuang dan biaya kecil lainnya (istilah teknis: COGS).</div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Laba Kotor</span>
                        <span class="fw-bold {{ $grossProfit < 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($grossProfit, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="small text-muted">
                        Laba Kotor = Grand Total dikurangi <strong>Biaya bahan + cadangan (Faktor Q)</strong>, bukan biaya bahan saja.
                        Pajak dan service charge ikut dihitung di Grand Total, dan potongan admin tidak dikurangkan.
                    </div>
                    @endif
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
                <h5 class="modal-title fw-bold">Batalkan Transaksi {{ $transaction->transaction_no }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ $routes['void']($transaction->transaction_no) }}" method="POST"
                data-lock-submit
                data-confirm-title="Batalkan transaksi {{ $transaction->transaction_no }}?"
                data-confirm-text="Stok bahan dikembalikan.|Jurnal penjualan dibalik otomatis.|Penjualan ini tidak dihitung lagi di laporan harian.|Tidak bisa dibatalkan setelah ini."
                data-confirm-button="Ya, batalkan transaksi"
                data-confirm-danger>
                @csrf
                <div class="modal-body">
                    <p class="mb-2">Jika transaksi ini dibatalkan:</p>
                    <ul class="mb-3">
                        <li>Stok bahan dikembalikan.</li>
                        <li>Jurnal penjualan dibalik otomatis.</li>
                        <li>Penjualan tidak dihitung di laporan harian.</li>
                        <li class="fw-bold text-danger">Tidak bisa dibatalkan.</li>
                    </ul>
                    <div class="mb-3">
                        <label class="form-label">Alasan pembatalan <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" minlength="5" maxlength="255" placeholder="Contoh: Salah input produk oleh kasir (minimal 5 huruf)" required>{{ old('reason') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top pt-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kembali</button>
                    <button type="submit" class="btn btn-danger">Batalkan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
@if ($errors->has('reason'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('voidModal');
        if (modal) new bootstrap.Modal(modal).show();
    });
</script>
@endif
@endsection
