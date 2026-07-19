@extends('layouts/layoutMaster')

@section('title', 'Riwayat Transaksi POS')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS /</span> Riwayat Transaksi</h4>

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
                            <td><span class="badge {{ $statusBadge }}">{{ ucfirst($transaction->status) }}</span></td>
                            <td>
                                <a href="{{ route('pos-transaction.show', $transaction) }}"
                                    class="btn btn-sm btn-icon btn-text-secondary" title="Detail">
                                    <i class="ri-eye-line"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">Belum ada transaksi.</td>
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
@endsection
