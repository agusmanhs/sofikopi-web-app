@extends('layouts/layoutMaster')

@section('title', 'Detail Stock Opname')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS / Stock Opname /</span> {{ $opname->opname_no }}</h4>
        <a href="{{ route('mitra-opname.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-2 text-muted">Tanggal</div>
                <div class="col-md-4">{{ $opname->opname_date->format('d/m/Y') }}</div>
                <div class="col-md-2 text-muted">Oleh</div>
                <div class="col-md-4">{{ $opname->user->name ?? '-' }}</div>
            </div>
            @if ($opname->notes)
            <div class="row">
                <div class="col-md-2 text-muted">Catatan</div>
                <div class="col-md-10">{{ $opname->notes }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th class="text-end">Stok Sistem</th>
                        <th class="text-end">Stok Fisik</th>
                        <th class="text-end">Selisih</th>
                        <th class="text-end">Nilai Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalDifferenceValue = 0; @endphp
                    @foreach ($opname->items as $item)
                        @php $totalDifferenceValue += (float) $item->difference_value; @endphp
                        <tr>
                            <td>{{ $item->material->name ?? '-' }}</td>
                            <td class="text-end">{{ rtrim(rtrim(number_format($item->system_qty, 3, ',', '.'), '0'), ',') }}</td>
                            <td class="text-end">{{ rtrim(rtrim(number_format($item->physical_qty, 3, ',', '.'), '0'), ',') }}</td>
                            <td class="text-end {{ (float) $item->difference != 0 ? ((float) $item->difference > 0 ? 'text-success' : 'text-danger') : '' }}">
                                {{ (float) $item->difference > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($item->difference, 3, ',', '.'), '0'), ',') }}
                            </td>
                            <td class="text-end {{ (float) $item->difference_value != 0 ? ((float) $item->difference_value > 0 ? 'text-success' : 'text-danger') : '' }}">
                                Rp {{ number_format($item->difference_value, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">Total Nilai Selisih</td>
                        <td class="text-end {{ $totalDifferenceValue != 0 ? ($totalDifferenceValue > 0 ? 'text-success' : 'text-danger') : '' }}">
                            Rp {{ number_format($totalDifferenceValue, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
