@extends('layouts/layoutMaster')

@section('title', 'Kelola Mitra POS')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS /</span> Kelola Mitra</h4>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row g-4">
        @forelse($mitras as $mitra)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">{{ $mitra->name }}</h5>
                            <span class="badge bg-label-primary">{{ $mitra->code }}</span>
                        </div>
                        <span class="badge {{ $mitra->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                            {{ $mitra->is_active ? 'Aktif' : 'Non-Aktif' }}
                        </span>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="ri-user-line me-2 text-muted"></i>
                            <span>{{ $mitra->pic ?? '-' }}</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="ri-phone-line me-2 text-muted"></i>
                            <span>{{ $mitra->phone ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('mitra-material.index', $mitra) }}" class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="ri-archive-line me-1"></i> Material
                        </a>
                        <a href="{{ route('mitra-product.index', $mitra) }}" class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="ri-cup-line me-1"></i> Produk
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">Belum ada mitra aktif yang dapat dikelola.</div>
        </div>
        @endforelse
    </div>
</div>
@endsection
