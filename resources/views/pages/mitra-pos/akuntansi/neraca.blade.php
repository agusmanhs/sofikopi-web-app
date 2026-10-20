@extends('layouts/layoutMaster')

@section('title', 'Neraca' . (isset($mitra) ? ' - ' . $mitra->name : ''))

@php
    $asOf = $neraca['as_of_date'];
    $asOfLabel = $asOf->copy()->locale('id')->translatedFormat('j M Y');
    $selisih = (int) round($neraca['aset_total'] - $neraca['total_pasiva']);
    $isBalanced = $selisih === 0;
    $accountLink = fn ($account) => $routes['jurnal'].'?'.http_build_query(['account_id' => $account->id, 'to' => $asOf->format('Y-m-d')]);
    $roleHints = [
        'hutang_bop' => 'Biaya produksi (listrik, gas, dll.) yang sudah dibebankan ke penjualan tetapi belum dibayar.',
    ];
    $lastKasIndex = collect($neraca['aset'])
        ->keys()
        ->filter(fn ($i) => in_array($neraca['aset'][$i]['account']->system_role, ['kas_tunai', 'kas_kasir'], true))
        ->last();
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS / Akuntansi /</span> Neraca per {{ $asOfLabel }}</h4>

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

    @if(! empty($neraca['inactive_with_balance']))
    <div class="alert alert-warning">
        Akun nonaktif masih punya saldo:
        {{ collect($neraca['inactive_with_balance'])->map(fn ($row) => $row['account']->code.' '.$row['account']->name.' (Rp '.number_format($row['saldo'], 0, ',', '.').')')->implode(', ') }}.
        Aktifkan kembali di <a href="{{ $routes['coa'] }}">Daftar Akun</a> agar Neraca seimbang.
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Per Tanggal</label>
                    <input type="date" name="as_of_date" class="form-control" value="{{ $asOf->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    @if($isBalanced)
                    <span class="badge bg-success fs-6">Seimbang</span>
                    @else
                    <span class="badge bg-danger fs-6">Tidak seimbang (selisih Rp {{ number_format(abs($selisih), 0, ',', '.') }})</span>
                    @endif
                    <div class="small text-muted mt-1">Total Aset dibandingkan dengan Kewajiban + Modal</div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Aset</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($neraca['aset'] as $i => $row)
                            <tr>
                                <td>{{ $row['account']->name }}</td>
                                <td class="text-end"><a href="{{ $accountLink($row['account']) }}">Rp {{ number_format($row['saldo_akhir'], 0, ',', '.') }}</a></td>
                            </tr>
                            @if($i === $lastKasIndex)
                            <tr class="table-light fw-semibold">
                                <td>Subtotal Kas <small class="text-muted fw-normal">(Kas Tunai + Kas Kasir)</small></td>
                                <td class="text-end">Rp {{ number_format($neraca['total_kas'], 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Aset</td>
                                <td class="text-end">Rp {{ number_format($neraca['aset_total'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 mb-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Kewajiban</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($neraca['kewajiban'] as $row)
                            <tr>
                                <td>
                                    {{ $row['account']->name }}
                                    @if(isset($roleHints[$row['account']->system_role]))
                                    <div class="small text-muted">{{ $roleHints[$row['account']->system_role] }}</div>
                                    @endif
                                </td>
                                <td class="text-end"><a href="{{ $accountLink($row['account']) }}">Rp {{ number_format($row['saldo_akhir'], 0, ',', '.') }}</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Kewajiban</td>
                                <td class="text-end">Rp {{ number_format($neraca['kewajiban_total'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Modal</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($neraca['modal'] as $row)
                            <tr>
                                <td>{{ $row['account']->name }}</td>
                                <td class="text-end"><a href="{{ $accountLink($row['account']) }}">Rp {{ number_format($row['saldo_akhir'], 0, ',', '.') }}</a></td>
                            </tr>
                            @endforeach
                            <tr>
                                <td>
                                    Laba/Rugi Berjalan
                                    <div class="small text-muted">Akumulasi laba/rugi sejak awal pembukuan sampai tanggal ini.</div>
                                </td>
                                <td class="text-end">Rp {{ number_format($neraca['laba_berjalan'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Modal</td>
                                <td class="text-end">Rp {{ number_format($neraca['modal_total'], 0, ',', '.') }}</td>
                            </tr>
                            <tr class="fw-bold border-top">
                                <td>Total Kewajiban + Modal</td>
                                <td class="text-end">Rp {{ number_format($neraca['total_pasiva'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
