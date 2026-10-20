@extends('layouts/layoutMaster')

@section('title', 'Laba Rugi' . (isset($mitra) ? ' - ' . $mitra->name : ''))

@php
    $from = $labaRugi['from'];
    $to = $labaRugi['to'];
    $fromLabel = $from->copy()->locale('id')->translatedFormat($from->year === $to->year ? 'j M' : 'j M Y');
    $toLabel = $to->copy()->locale('id')->translatedFormat('j M Y');
    $accountLink = fn ($account) => $routes['jurnal'].'?'.http_build_query(['account_id' => $account->id, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]);
    $roleHints = [
        'beban_overhead_produksi' => 'Biaya produksi tidak langsung (listrik, gas, dll.) yang dibebankan ke setiap penjualan.',
    ];
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS / Akuntansi /</span> Laba Rugi periode {{ $fromLabel }} - {{ $toLabel }}</h4>

    @include('pages.mitra-pos.akuntansi._nav')

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control" value="{{ $from->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control" value="{{ $to->format('Y-m-d') }}">
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
                        <td class="text-end"><a href="{{ $accountLink($row['account']) }}">Rp {{ number_format($row['amount'], 0, ',', '.') }}</a></td>
                    </tr>
                    @endforeach
                    <tr class="fw-bold">
                        <td>Total Pendapatan</td>
                        <td class="text-end">Rp {{ number_format($labaRugi['pendapatan_total'], 0, ',', '.') }}</td>
                    </tr>

                    <tr class="table-light">
                        <td colspan="2">
                            <span class="fw-bold">Harga Pokok Penjualan</span>
                            <div class="small text-muted">Modal bahan dan biaya produksi dari barang yang terjual.</div>
                        </td>
                    </tr>
                    @foreach($labaRugi['hpp'] as $row)
                    <tr>
                        <td class="ps-4">
                            {{ $row['account']->name }}
                            @if(isset($roleHints[$row['account']->system_role]))
                            <div class="small text-muted">{{ $roleHints[$row['account']->system_role] }}</div>
                            @endif
                        </td>
                        <td class="text-end"><a href="{{ $accountLink($row['account']) }}">Rp {{ number_format($row['amount'], 0, ',', '.') }}</a></td>
                    </tr>
                    @endforeach
                    <tr class="fw-bold">
                        <td>Total HPP</td>
                        <td class="text-end">Rp {{ number_format($labaRugi['hpp_total'], 0, ',', '.') }}</td>
                    </tr>

                    <tr class="table-light">
                        <td colspan="2">
                            <span class="fw-bold">Biaya Administrasi &amp; Umum</span>
                            <div class="small text-muted">Biaya operasional harian di luar bahan baku, misalnya biaya bank, listrik kantor, dan gaji.</div>
                        </td>
                    </tr>
                    @foreach($labaRugi['biaya_adm_umum'] as $row)
                    <tr>
                        <td class="ps-4">
                            {{ $row['account']->name }}
                            @if(isset($roleHints[$row['account']->system_role]))
                            <div class="small text-muted">{{ $roleHints[$row['account']->system_role] }}</div>
                            @endif
                        </td>
                        <td class="text-end"><a href="{{ $accountLink($row['account']) }}">Rp {{ number_format($row['amount'], 0, ',', '.') }}</a></td>
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
