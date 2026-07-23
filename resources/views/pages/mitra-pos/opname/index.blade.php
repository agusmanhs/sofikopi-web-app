@extends('layouts/layoutMaster')

@section('title', 'Riwayat Stock Opname')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS /</span> Stock Opname</h4>
        @can('access', ['mitra-opname.index', 'create'])
        <a href="{{ route('mitra-opname.create') }}" class="btn btn-primary">
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
                        <th>Catatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($opnames as $opname)
                    <tr>
                        <td>{{ $opname->opname_no }}</td>
                        <td>{{ $opname->opname_date->format('d/m/Y') }}</td>
                        <td>{{ $opname->user->name ?? '-' }}</td>
                        <td>{{ $opname->notes ?? '-' }}</td>
                        <td>
                            <a href="{{ route('mitra-opname.show', $opname) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Detail">
                                <i class="ri-eye-line"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">Belum ada stock opname.</td>
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
