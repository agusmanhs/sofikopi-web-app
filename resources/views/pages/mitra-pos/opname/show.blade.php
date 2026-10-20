@extends('layouts/layoutMaster')

@section('title', 'Detail Stock Opname')

@section('content')
@php
    $fmtQty = fn ($qty) => rtrim(rtrim(number_format($qty, 3, ',', '.'), '0'), ',');
    $changedItems = $opname->items->filter(fn ($item) => abs((float) $item->difference) > 0.0001);
    $totalDifferenceValue = $opname->items->sum(fn ($item) => (float) $item->difference_value);
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS / Stock Opname /</span> {{ $opname->opname_no }}</h4>
        <a href="{{ $routes['index'] }}" class="btn btn-outline-secondary">
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
            <div class="row mb-2">
                <div class="col-md-2 text-muted">Ringkasan</div>
                <div class="col-md-10">
                    {{ $opname->items->count() }} bahan dihitung,
                    <strong>{{ $changedItems->count() }} bahan disesuaikan</strong>,
                    {{ $opname->items->count() - $changedItems->count() }} bahan tidak berubah.
                    Total nilai selisih:
                    <strong class="{{ $totalDifferenceValue != 0 ? ($totalDifferenceValue > 0 ? 'text-success' : 'text-danger') : '' }}">{{ $totalDifferenceValue < 0 ? '-' : '' }}Rp {{ number_format(abs($totalDifferenceValue), 0, ',', '.') }}</strong>
                </div>
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
                        <th>Satuan</th>
                        <th class="text-end">Stok Sistem</th>
                        <th class="text-end">Stok Fisik</th>
                        <th class="text-end">Selisih</th>
                        <th class="text-end">Nilai Selisih</th>
                        <th>Hasil</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($opname->items as $item)
                        @php
                            $isChanged = abs((float) $item->difference) > 0.0001;
                            $diffValue = (float) $item->difference_value;
                        @endphp
                        <tr class="{{ $isChanged ? '' : 'text-muted' }}">
                            <td>{{ $item->material->name ?? '-' }}</td>
                            <td>{{ $item->material->unit ?? '-' }}</td>
                            <td class="text-end">{{ $fmtQty($item->system_qty) }}</td>
                            <td class="text-end">{{ $fmtQty($item->physical_qty) }}</td>
                            <td class="text-end {{ $isChanged ? ((float) $item->difference > 0 ? 'text-success' : 'text-danger') : '' }}">
                                {{ (float) $item->difference > 0 ? '+' : '' }}{{ $fmtQty($item->difference) }}
                            </td>
                            <td class="text-end {{ $diffValue != 0 ? ($diffValue > 0 ? 'text-success' : 'text-danger') : '' }}">
                                {{ $diffValue < 0 ? '-' : '' }}Rp {{ number_format(abs($diffValue), 0, ',', '.') }}
                            </td>
                            <td>
                                @if ($isChanged)
                                <span class="badge bg-label-warning">Disesuaikan</span>
                                @else
                                <span class="badge bg-label-secondary">Tidak berubah</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end">Total Nilai Selisih</td>
                        <td class="text-end {{ $totalDifferenceValue != 0 ? ($totalDifferenceValue > 0 ? 'text-success' : 'text-danger') : '' }}">
                            {{ $totalDifferenceValue < 0 ? '-' : '' }}Rp {{ number_format(abs($totalDifferenceValue), 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
