@extends('layouts/layoutMaster')

@section('title', 'Laporan Harian Mitra POS')

@section('content')
<style>
    .report-table th:first-child,
    .report-table td:first-child {
        position: sticky;
        left: 0;
        z-index: 1;
        background: var(--bs-card-bg, #fff);
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS /</span> Laporan Harian @if(isset($mitra)) <span class="text-muted fw-light">— {{ $mitra->name }}</span> @endif</h4>
        <a href="{{ $routes['export'] }}" class="btn btn-outline-success">
            <i class="ri-file-excel-2-line me-1"></i> Export Excel
        </a>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

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

    <div class="card mb-4">
        <div class="card-header">
            <a class="fw-medium" data-bs-toggle="collapse" href="#caraMembacaLaporan" role="button" aria-expanded="false" aria-controls="caraMembacaLaporan">
                <i class="ri-question-line me-1"></i> Cara membaca laporan
            </a>
        </div>
        <div class="collapse" id="caraMembacaLaporan">
            <div class="card-body small">
                <p class="mb-2">Yang dihitung hanya transaksi berstatus <strong>Selesai</strong>, per tanggal transaksi. Transaksi yang dibatalkan (void) tidak ikut di kolom pendapatan.</p>
                <ul class="mb-2">
                    <li><strong>Pendapatan Kotor</strong>: jumlah harga produk yang terjual, sebelum diskon.</li>
                    <li><strong>Diskon</strong>: potongan harga yang diberikan kasir pada transaksi.</li>
                    <li><strong>Service Charge dan Pajak</strong>: ditambahkan ke total bayar sesuai pengaturan.</li>
                    <li><strong>Pendapatan Bersih</strong>: total yang dibayar pelanggan = kotor - diskon + service charge + pajak.</li>
                    <li><strong>HPP</strong>: biaya bahan sesuai resep. <strong>COGS</strong>: HPP ditambah cadangan (Faktor Q) untuk bahan terbuang dan biaya kecil.</li>
                    <li><strong>Cash, QRIS, Transfer, EDC</strong>: pendapatan bersih dipisah menurut cara bayar.</li>
                    <li><strong>Potongan Admin</strong>: biaya yang dipotong penyedia pembayaran (QRIS/transfer/EDC). Tidak ditambahkan ke tagihan pelanggan.</li>
                    <li><strong>Penerimaan Bersih</strong>: Pendapatan Bersih dikurangi Potongan Admin, yaitu uang yang benar-benar diterima.</li>
                    <li><strong>Void</strong>: nilai transaksi yang dibatalkan.</li>
                </ul>
                <p class="mb-0 text-danger">Catatan: transaksi yang dibatalkan dicatat pada <strong>tanggal pembatalan</strong>, bukan tanggal penjualan. Penjualan itu hilang dari kolom pendapatan di hari penjualannya, dan nilainya muncul di kolom Void pada hari dibatalkan.</p>
            </div>
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
            <table class="table table-bordered table-sm mb-0 report-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="text-end">Pendapatan Kotor</th>
                        <th class="text-end">Diskon</th>
                        <th class="text-end">Service Charge</th>
                        <th class="text-end">Pajak</th>
                        <th class="text-end">Pendapatan Bersih</th>
                        <th class="text-end">HPP</th>
                        <th class="text-end">COGS (+ Faktor Q)</th>
                        <th class="text-end">Cash</th>
                        <th class="text-end">QRIS</th>
                        <th class="text-end">Transfer</th>
                        <th class="text-end">EDC</th>
                        <th class="text-end">Potongan Admin</th>
                        <th class="text-end">Penerimaan Bersih</th>
                        <th class="text-end">Void (tgl. dibatalkan)</th>
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
