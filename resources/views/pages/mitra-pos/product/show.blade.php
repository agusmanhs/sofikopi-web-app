@extends('layouts/layoutMaster')

@section('title', 'Detail Produk - ' . $mitra->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-9 col-lg-8 mx-auto">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom mb-3 py-3">
                    <h5 class="mb-0 fw-bold">Detail Produk</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('mitra-product.edit', [$mitra, $product]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="ri-edit-box-line me-1"></i> Edit
                        </a>
                        <a href="{{ route('mitra-product.index', $mitra) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ri-arrow-left-line me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">SKU:</span>
                            <span class="fw-semibold text-heading">{{ $product->sku }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Status:</span>
                            <span class="badge {{ $product->status == 'active' ? 'bg-label-success' : 'bg-label-secondary' }} px-3 py-2 fs-7">
                                {{ $product->status == 'active' ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Nama Produk:</span>
                            <span class="fw-semibold text-heading">{{ $product->name }}{{ $product->variant ? ' (' . $product->variant . ')' : '' }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Mitra:</span>
                            <span class="fw-semibold text-heading">{{ $mitra->name }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Kategori / Sub Kategori:</span>
                            <span class="fw-semibold text-heading">{{ $product->category ?? '-' }} / {{ $product->sub_category ?? '-' }}</span>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted d-block mb-1">Q-Factor:</span>
                            <span class="fw-semibold text-heading">{{ rtrim(rtrim(number_format($product->q_factor, 4, ',', '.'), '0'), ',') }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block mb-1">Harga Jual:</span>
                            <span class="fw-bold text-primary fs-5">Rp {{ number_format($product->sale_price, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <h6 class="fw-bold mb-3">Resep (BOM)</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="py-2">Material</th>
                                        <th class="py-2 text-center" width="100">Qty</th>
                                        <th class="py-2 text-end" width="140">Harga Satuan</th>
                                        <th class="py-2 text-end" width="140">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($product->ingredients as $ingredient)
                                    <tr>
                                        <td class="py-2 fw-medium">{{ $ingredient->material->name ?? 'Material Tidak Ditemukan' }}</td>
                                        <td class="py-2 text-center">{{ rtrim(rtrim(number_format($ingredient->qty, 3, ',', '.'), '0'), ',') }} {{ $ingredient->material->unit ?? '' }}</td>
                                        <td class="py-2 text-end text-muted">Rp {{ number_format($ingredient->material->harga_satuan ?? 0, 2, ',', '.') }}</td>
                                        <td class="py-2 text-end fw-semibold text-heading">Rp {{ number_format($ingredient->qty * ($ingredient->material->harga_satuan ?? 0), 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Belum ada bahan dalam resep ini.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <h6 class="fw-bold mb-3">Ringkasan HPP / COGS</h6>
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="card bg-lighter border h-100">
                                    <div class="card-body text-center">
                                        <span class="text-muted d-block mb-1">HPP</span>
                                        <span class="fw-bold fs-5">Rp {{ number_format($product->hpp, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="card bg-lighter border h-100">
                                    <div class="card-body text-center">
                                        <span class="text-muted d-block mb-1">COGS</span>
                                        <span class="fw-bold fs-5">Rp {{ number_format($product->cogs, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="card bg-lighter border h-100">
                                    <div class="card-body text-center">
                                        <span class="text-muted d-block mb-1">Margin</span>
                                        <span class="fw-bold fs-5 {{ $product->margin < 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($product->margin, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
