@extends('layouts/layoutMaster')

@section('title', 'Stok Material')

@section('content')
@php
    // Portal-only quick-adjust: owner has can_update on mitra-stock.index
    // (kasir doesn't). Admin context adjusts via the Material page instead.
    $canAdjust = !isset($mitra) && auth()->user()->can('access', ['mitra-stock.index', 'update']);
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Mitra POS /</span> Stok Material @if(isset($mitra)) <span class="text-muted fw-light">— {{ $mitra->name }}</span> @endif
        </h4>
        <div class="d-flex gap-2">
            <a href="{{ $routes['movements'] }}" class="btn btn-outline-secondary">
                <i class="ri-history-line me-1"></i> Riwayat Mutasi
            </a>
            @can('access', [isset($mitra) ? 'mitra-pos-manage.index' : 'mitra-material.index', 'read'])
            <a href="{{ $routes['material'] }}" class="btn btn-primary">
                <i class="ri-archive-line me-1"></i> Kelola Material
            </a>
            @endcan
            @can('access', [isset($mitra) ? 'mitra-pos-manage.index' : 'mitra-product.index', 'read'])
            <a href="{{ $routes['product'] }}" class="btn btn-primary">
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
                        @if($canAdjust)
                        <th>Aksi</th>
                        @endif
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
                        @if($canAdjust)
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-adjust-stock"
                                data-bs-toggle="modal" data-bs-target="#adjustStockModal"
                                data-name="{{ $material->name }}"
                                data-stock="{{ rtrim(rtrim(number_format($material->current_stock, 3, ',', '.'), '0'), ',') }} {{ $material->unit }}"
                                data-action="{{ route('mitra-stock.adjust', $material->sku) }}">
                                <i class="ri-scales-3-line me-1"></i> Sesuaikan
                            </button>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canAdjust ? 11 : 10 }}" class="text-center">Belum ada data material.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if ($materials->isNotEmpty())
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="6" class="text-end">Total Nilai Inventory</td>
                        <td>Rp {{ number_format($materials->sum('stock_value'), 0, ',', '.') }}</td>
                        <td colspan="{{ $canAdjust ? 3 : 2 }}"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @if(!isset($mitra) && !$canAdjust)
    @cannot('access', ['mitra-material.index', 'read'])
    <p class="text-muted mt-2">
        <i class="ri-information-line me-1"></i>
        Halaman ini bersifat baca-saja. Untuk menambah, mengubah, atau menyesuaikan stok material, hubungi admin Sofikopi.
    </p>
    @endcannot
    @endif

    @if($canAdjust)
    <div class="modal fade" id="adjustStockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="adjustStockForm" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Sesuaikan Stok — <span id="adjustMaterialName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Stok saat ini: <strong id="adjustCurrentStock"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Delta (+ menambah / − mengurangi)</label>
                        <input type="number" step="0.001" name="delta" class="form-control" placeholder="Contoh: 10 atau -2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: beli 10 stok" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection

@if($canAdjust ?? false)
@push('page-script')
<script>
    document.querySelectorAll('.btn-adjust-stock').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('adjustStockForm').action = btn.dataset.action;
            document.getElementById('adjustMaterialName').textContent = btn.dataset.name;
            document.getElementById('adjustCurrentStock').textContent = btn.dataset.stock;
        });
    });
</script>
@endpush
@endif
