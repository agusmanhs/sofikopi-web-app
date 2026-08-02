@extends('layouts/layoutMaster')

@section('title', 'Jurnal Akuntansi')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0"><span class="text-muted fw-light">Mitra POS / Akuntansi /</span> Jurnal</h4>
        @can('access', [isset($mitra) ? 'mitra-pos-manage.index' : 'akuntansi-jurnal.index', 'create'])
        <a href="{{ $routes['create'] }}" class="btn btn-primary">
            <i class="ri-add-line me-1"></i> Jurnal Manual
        </a>
        @endcan
    </div>

    @include('pages.mitra-pos.akuntansi._nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No. Jurnal</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Sumber</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->entry_no }}</td>
                        <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                        <td>{{ $entry->description }}</td>
                        <td>
                            @switch($entry->source_type)
                                @case('manual')
                                    <span class="badge bg-label-secondary">Manual</span>
                                    @break
                                @case('pos_sale')
                                    <span class="badge bg-label-success">Penjualan POS</span>
                                    @break
                                @case('pos_void')
                                    <span class="badge bg-label-danger">Void POS</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="text-end">Rp {{ number_format($entry->lines->sum('debit'), 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($entry->lines->sum('credit'), 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada jurnal.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $entries->links() }}
        </div>
    </div>
</div>
@endsection
