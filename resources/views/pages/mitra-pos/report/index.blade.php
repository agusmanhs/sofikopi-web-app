@extends('layouts/layoutMaster')

@section('title', 'Laporan Harian Mitra POS')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS /</span> Laporan Harian</h4>
        <a href="{{ route('mitra-report.export', request()->only('from', 'to')) }}" class="btn btn-outline-success">
            <i class="ri-file-excel-2-line me-1"></i> Export Excel
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control" value="{{ $from->toDateString() }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control" value="{{ $to->toDateString() }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-filter-3-line me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Pendapatan Bersih</div>
                <div class="fw-bold fs-5">Rp {{ number_format($totals['pendapatan_bersih'], 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Potongan Admin</div>
                <div class="fw-bold fs-5">Rp {{ number_format($totals['potongan_admin'], 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Penerimaan Bersih</div>
                <div class="fw-bold fs-5">Rp {{ number_format($totals['penerimaan_bersih'], 0, ',', '.') }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Total Void ({{ $totals['void_count'] }}x)</div>
                <div class="fw-bold fs-5 text-danger">Rp {{ number_format($totals['void_total'], 0, ',', '.') }}</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="text-end">Pendapatan Kotor</th>
                        <th class="text-end">Diskon</th>
                        <th class="text-end">Service Charge</th>
                        <th class="text-end">Pajak</th>
                        <th class="text-end">Pendapatan Bersih</th>
                        <th class="text-end">HPP</th>
                        <th class="text-end">COGS</th>
                        <th class="text-end">Cash</th>
                        <th class="text-end">QRIS</th>
                        <th class="text-end">Transfer</th>
                        <th class="text-end">EDC</th>
                        <th class="text-end">Potongan Admin</th>
                        <th class="text-end">Penerimaan Bersih</th>
                        <th class="text-end">Void</th>
                        <th class="text-end">Jml. Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                    <tr class="{{ $row['jumlah_transaksi'] === 0 && $row['void_count'] === 0 ? 'text-muted' : '' }}">
                        <td>{{ $row['tanggal']->format('d/m/Y') }}</td>
                        <td class="text-end">{{ number_format($row['pendapatan_kotor'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['diskon'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['service_charge'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['pajak'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['pendapatan_bersih'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['hpp'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['cogs'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['penerimaan_cash'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['penerimaan_qris'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['penerimaan_transfer'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['penerimaan_edc'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($row['potongan_admin'], 0, ',', '.') }}</td>
                        <td class="text-end fw-medium">{{ number_format($row['penerimaan_bersih'], 0, ',', '.') }}</td>
                        <td class="text-end {{ $row['void_total'] > 0 ? 'text-danger' : '' }}">{{ number_format($row['void_total'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ $row['jumlah_transaksi'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>TOTAL</td>
                        <td class="text-end">{{ number_format($totals['pendapatan_kotor'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['diskon'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['service_charge'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['pajak'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['pendapatan_bersih'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['hpp'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['cogs'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['penerimaan_cash'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['penerimaan_qris'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['penerimaan_transfer'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['penerimaan_edc'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['potongan_admin'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($totals['penerimaan_bersih'], 0, ',', '.') }}</td>
                        <td class="text-end text-danger">{{ number_format($totals['void_total'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ $totals['jumlah_transaksi'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
