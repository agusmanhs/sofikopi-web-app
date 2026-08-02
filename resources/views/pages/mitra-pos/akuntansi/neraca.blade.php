@extends('layouts/layoutMaster')

@section('title', 'Neraca')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS / Akuntansi /</span> Neraca</h4>

    @include('pages.mitra-pos.akuntansi._nav')

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Per Tanggal</label>
                    <input type="date" name="as_of_date" class="form-control" value="{{ $neraca['as_of_date']->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Aset</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($neraca['aset'] as $row)
                            <tr>
                                <td>{{ $row['account']->name }}</td>
                                <td class="text-end">Rp {{ number_format($row['saldo_akhir'], 0, ',', '.') }}</td>
                            </tr>
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
                                <td>{{ $row['account']->name }}</td>
                                <td class="text-end">Rp {{ number_format($row['saldo_akhir'], 0, ',', '.') }}</td>
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
                                <td class="text-end">Rp {{ number_format($row['saldo_akhir'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td>Laba/Rugi Berjalan</td>
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
