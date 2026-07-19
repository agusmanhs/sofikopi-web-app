@extends('layouts/layoutMaster')

@section('title', 'Detail Material - ' . $mitra->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-8 col-lg-7 mx-auto">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom mb-3 py-3">
                    <h5 class="mb-0 fw-bold">Detail Material</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('mitra-material.edit', [$mitra, $material]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="ri-edit-box-line me-1"></i> Edit
                        </a>
                        <a href="{{ route('mitra-material.index', $mitra) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">SKU:</span>
                            <span class="fw-semibold text-heading">{{ $material->sku }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Status:</span>
                            <span class="badge {{ $material->is_active ? 'bg-label-success' : 'bg-label-secondary' }} px-3 py-2 fs-7">
                                {{ $material->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Nama Material:</span>
                            <span class="fw-semibold text-heading">{{ $material->name }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Mitra:</span>
                            <span class="fw-semibold text-heading">{{ $mitra->name }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Kategori:</span>
                            <span class="fw-semibold text-heading">{{ $material->category ?? '-' }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Brand:</span>
                            <span class="fw-semibold text-heading">{{ $material->brand ?? '-' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Satuan / Netto:</span>
                            <span class="fw-semibold text-heading">{{ $material->unit }} / {{ rtrim(rtrim(number_format($material->netto, 3, ',', '.'), '0'), ',') }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Harga per Pack:</span>
                            <span class="fw-semibold text-heading">Rp {{ number_format($material->price_per_pack, 0, ',', '.') }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Harga Satuan:</span>
                            <span class="fw-bold text-primary">Rp {{ number_format($material->harga_satuan, 2, ',', '.') }} / {{ $material->unit }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Produk Sofikopi Terkait:</span>
                            <span class="fw-semibold text-heading">{{ $material->product->name ?? '-' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Stok Saat Ini:</span>
                            @php
                                $stockBadge = 'bg-label-success';
                                if ($material->current_stock <= 0) {
                                    $stockBadge = 'bg-label-danger';
                                } elseif ($material->current_stock < $material->min_stock) {
                                    $stockBadge = 'bg-label-warning';
                                }
                            @endphp
                            <span class="badge {{ $stockBadge }} fs-7">
                                {{ rtrim(rtrim(number_format($material->current_stock, 3, ',', '.'), '0'), ',') }} {{ $material->unit }}
                            </span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Stok Minimum:</span>
                            <span class="fw-semibold text-heading">{{ rtrim(rtrim(number_format($material->min_stock, 3, ',', '.'), '0'), ',') }} {{ $material->unit }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
