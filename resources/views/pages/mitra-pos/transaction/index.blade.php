@extends('layouts/layoutMaster')

@section('title', 'Riwayat Transaksi POS')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS /</span> Riwayat Transaksi</h4>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ $routes['index'] }}" class="row g-3 align-items-end" data-live-filter>
                <div class="col-md-3">
                    <label class="form-label">No. Transaksi</label>
                    <input type="search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Contoh: 0012" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="voided" {{ ($filters['status'] ?? '') === 'voided' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kasir</label>
                    <select name="user_id" class="form-select js-select2" data-placeholder="Semua kasir" data-allow-clear>
                        <option value="">Semua kasir</option>
                        @foreach ($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" {{ (string) ($filters['user_id'] ?? '') === (string) $cashier->id ? 'selected' : '' }}>{{ $cashier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                </div>
            </form>
        </div>
    </div>

    <div id="live-results">
    @if ($errors->any())
    <div class="text-danger small mb-2">{{ $errors->first() }}</div>
    @endif
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-basic table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Transaksi</th>
                        <th>Tanggal</th>
                        <th>Mode</th>
                        <th>Pembayaran</th>
                        <th>Kasir</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $index => $transaction)
                        @php
                            $salesModeBadge = match ($transaction->sales_mode) {
                                'dine_in' => 'bg-label-primary',
                                'take_away' => 'bg-label-info',
                                'online' => 'bg-label-warning',
                                default => 'bg-label-secondary',
                            };
                            $salesModeLabel = match ($transaction->sales_mode) {
                                'dine_in' => 'Dine In',
                                'take_away' => 'Take Away',
                                'online' => 'Online',
                                default => ucfirst($transaction->sales_mode),
                            };
                            $paymentBadge = $transaction->payment_method === 'qris' ? 'bg-label-info' : 'bg-label-success';
                            $statusBadge = $transaction->status === 'voided' ? 'bg-label-danger' : 'bg-label-success';
                        @endphp
                        <tr>
                            <td>{{ $transactions->firstItem() + $index }}</td>
                            <td>{{ $transaction->transaction_no }}</td>
                            <td>{{ optional($transaction->transacted_at)->format('d/m/Y H:i') }}</td>
                            <td><span class="badge {{ $salesModeBadge }}">{{ $salesModeLabel }}</span></td>
                            <td><span class="badge {{ $paymentBadge }}">{{ strtoupper($transaction->payment_method) }}</span></td>
                            <td>{{ $transaction->user->name ?? '-' }}</td>
                            <td>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</td>
                            <td><span class="badge {{ $statusBadge }}">{{ $transaction->status === 'voided' ? 'Dibatalkan' : 'Selesai' }}</span></td>
                            <td>
                                <a href="{{ $routes['show']($transaction->transaction_no) }}"
                                    class="btn btn-sm btn-icon btn-text-secondary" title="Lihat detail">
                                    <i class="ri-eye-line"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">{{ !empty($filters) ? 'Tidak ada transaksi yang cocok dengan filter ini.' : 'Belum ada transaksi.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->hasPages())
            <div class="card-footer d-flex justify-content-end">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
    </div>
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
@endsection
