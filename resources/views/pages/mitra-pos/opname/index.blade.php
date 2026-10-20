@extends('layouts/layoutMaster')

@section('title', 'Riwayat Stock Opname')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS /</span> Stock Opname @if(isset($mitra)) <span class="text-muted fw-light">— {{ $mitra->name }}</span> @endif</h4>
        @can('access', [isset($mitra) ? 'mitra-pos-manage.index' : 'mitra-opname.index', 'create'])
        <a href="{{ $routes['create'] }}" class="btn btn-primary">
            <i class="ri-add-line me-1"></i> Opname Baru
        </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>No. Opname</th>
                        <th>Tanggal</th>
                        <th>Oleh</th>
                        <th class="text-end">Bahan Dihitung</th>
                        <th class="text-end">Bahan Disesuaikan</th>
                        <th class="text-end">Total Nilai Selisih</th>
                        <th>Catatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($opnames as $opname)
                    @php
                        $changedCount = $opname->items->filter(fn ($item) => abs((float) $item->difference) > 0.0001)->count();
                        $totalValue = $opname->items->sum(fn ($item) => (float) $item->difference_value);
                    @endphp
                    <tr>
                        <td>{{ $opname->opname_no }}</td>
                        <td>{{ $opname->opname_date->format('d/m/Y') }}</td>
                        <td>{{ $opname->user->name ?? '-' }}</td>
                        <td class="text-end">{{ $opname->items->count() }}</td>
                        <td class="text-end">{{ $changedCount }}</td>
                        <td class="text-end {{ $totalValue != 0 ? ($totalValue > 0 ? 'text-success' : 'text-danger') : '' }}">
                            {{ $totalValue < 0 ? '-' : '' }}Rp {{ number_format(abs($totalValue), 0, ',', '.') }}
                        </td>
                        <td>{{ $opname->notes ?? '-' }}</td>
                        <td>
                            <a href="{{ $routes['show']($opname->opname_no) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Lihat detail">
                                <i class="ri-eye-line"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Belum ada stock opname.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($opnames->hasPages())
            <div class="card-footer d-flex justify-content-end">
                {{ $opnames->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
