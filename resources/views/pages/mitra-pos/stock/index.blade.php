@extends('layouts/layoutMaster')

@section('title', 'Stok Material')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Mitra POS /</span> Stok Material
        </h4>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-basic table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>SKU</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th>Harga Satuan</th>
                        <th>Stok Saat Ini</th>
                        <th>Stok Minimum</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $index => $material)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $material->sku }}</td>
                        <td>{{ $material->name }}</td>
                        <td>{{ $material->category ?? '-' }}</td>
                        <td>{{ $material->unit }}</td>
                        <td>Rp {{ number_format($material->harga_satuan, 2, ',', '.') }}</td>
                        <td>
                            @php
                                $stockBadge = 'bg-label-success';
                                if ($material->current_stock <= 0) {
                                    $stockBadge = 'bg-label-danger';
                                } elseif ($material->current_stock < $material->min_stock) {
                                    $stockBadge = 'bg-label-warning';
                                }
                            @endphp
                            <span class="badge {{ $stockBadge }}">
                                {{ rtrim(rtrim(number_format($material->current_stock, 3, ',', '.'), '0'), ',') }}
                            </span>
                        </td>
                        <td>{{ rtrim(rtrim(number_format($material->min_stock, 3, ',', '.'), '0'), ',') }}</td>
                        <td>
                            <span class="badge {{ $material->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $material->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">Belum ada data material.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-muted mt-2">
        <i class="ri-information-line me-1"></i>
        Halaman ini bersifat baca-saja. Untuk menambah, mengubah, atau menyesuaikan stok material, hubungi admin Sofikopi.
    </p>
</div>
@endsection
