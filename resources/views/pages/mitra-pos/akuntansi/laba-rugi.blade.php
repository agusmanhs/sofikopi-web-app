@extends('layouts/layoutMaster')

@section('title', 'Laba Rugi')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS / Akuntansi /</span> Laba Rugi</h4>

    @include('pages.mitra-pos.akuntansi._nav')

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control" value="{{ $labaRugi['from']->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control" value="{{ $labaRugi['to']->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0">
                <tbody>
                    <tr class="table-light">
                        <td colspan="2" class="fw-bold">Pendapatan</td>
                    </tr>
                    @foreach($labaRugi['pendapatan'] as $row)
                    <tr>
                        <td class="ps-4">{{ $row['account']->name }}</td>
                        <td class="text-end">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="fw-bold">
                        <td>Total Pendapatan</td>
                        <td class="text-end">Rp {{ number_format($labaRugi['pendapatan_total'], 0, ',', '.') }}</td>
                    </tr>

                    <tr class="table-light">
                        <td colspan="2" class="fw-bold">Harga Pokok Penjualan</td>
                    </tr>
                    @foreach($labaRugi['hpp'] as $row)
                    <tr>
                        <td class="ps-4">{{ $row['account']->name }}</td>
                        <td class="text-end">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="fw-bold">
                        <td>Total HPP</td>
                        <td class="text-end">Rp {{ number_format($labaRugi['hpp_total'], 0, ',', '.') }}</td>
                    </tr>

                    <tr class="table-light">
                        <td colspan="2" class="fw-bold">Biaya Administrasi &amp; Umum</td>
                    </tr>
                    @foreach($labaRugi['biaya_adm_umum'] as $row)
                    <tr>
                        <td class="ps-4">{{ $row['account']->name }}</td>
                        <td class="text-end">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="fw-bold">
                        <td>Total Biaya</td>
                        <td class="text-end">Rp {{ number_format($labaRugi['biaya_total'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="fw-bold border-top {{ $labaRugi['laba_bersih'] >= 0 ? 'text-success' : 'text-danger' }}">
                        <td>{{ $labaRugi['laba_bersih'] >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</td>
                        <td class="text-end">Rp {{ number_format(abs($labaRugi['laba_bersih']), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
