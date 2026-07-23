@extends('layouts/layoutMaster')

@section('title', 'Stok Material')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Mitra POS /</span> Stok Material
        </h4>
        {{-- auth()->user()->mitra is guaranteed non-null here: mitra.user middleware 403s any user with mitra_id === null before this page renders. --}}
        <div class="d-flex gap-2">
            <a href="{{ route('mitra-stock.movements') }}" class="btn btn-outline-secondary">
                <i class="ri-history-line me-1"></i> Riwayat Mutasi
            </a>
            @can('access', ['mitra-material.index', 'read'])
            <a href="{{ route('mitra-material.index', auth()->user()->mitra) }}" class="btn btn-primary">
                <i class="ri-archive-line me-1"></i> Kelola Material
            </a>
            @endcan
            @can('access', ['mitra-product.index', 'read'])
            <a href="{{ route('mitra-product.index', auth()->user()->mitra) }}" class="btn btn-primary">
                <i class="ri-cup-line me-1"></i> Kelola Produk
            </a>
            @endcan
        </div>
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
                        <th>Nilai Stok</th>
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
                        <td>Rp {{ number_format($material->stock_value, 0, ',', '.') }}</td>
                        <td>{{ rtrim(rtrim(number_format($material->min_stock, 3, ',', '.'), '0'), ',') }}</td>
                        <td>
                            <span class="badge {{ $material->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $material->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">Belum ada data material.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if ($materials->isNotEmpty())
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="6" class="text-end">Total Nilai Inventory</td>
                        <td>Rp {{ number_format($materials->sum('stock_value'), 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @cannot('access', ['mitra-material.index', 'read'])
    <p class="text-muted mt-2">
        <i class="ri-information-line me-1"></i>
        Halaman ini bersifat baca-saja. Untuk menambah, mengubah, atau menyesuaikan stok material, hubungi admin Sofikopi.
    </p>
    @endcannot
</div>
@endsection
