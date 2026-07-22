@extends('layouts/layoutMaster')

@section('title', 'Material - ' . $mitra->name)

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Mitra POS / {{ $mitra->name }} /</span> Material
        </h4>
        <div class="d-flex gap-2">
            @can('access', ['mitra-pos-manage.index', 'read'])
            <a href="{{ route('mitra-pos-manage.index') }}" class="btn btn-outline-secondary">
                <i class="ri-arrow-left-line me-1"></i> Kembali
            </a>
            @elsecan('access', ['mitra-stock.index', 'read'])
            <a href="{{ route('mitra-stock.index') }}" class="btn btn-outline-secondary">
                <i class="ri-arrow-left-line me-1"></i> Kembali
            </a>
            @endcan
            @can('access', ['mitra-material.index', 'create'])
            <a href="{{ route('mitra-material.create', $mitra) }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i> Tambah Material
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

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
                        <th>Netto</th>
                        <th>Harga/Pack</th>
                        <th>Harga Satuan</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $index => $material)
                    <tr>
                        <td>{{ $materials->firstItem() + $index }}</td>
                        <td>{{ $material->sku }}</td>
                        <td>{{ $material->name }}</td>
                        <td>{{ $material->category ?? '-' }}</td>
                        <td>{{ $material->unit }}</td>
                        <td>{{ rtrim(rtrim(number_format($material->netto, 3, ',', '.'), '0'), ',') }}</td>
                        <td>Rp {{ number_format($material->price_per_pack, 0, ',', '.') }}</td>
                        <td>Rp {{ rtrim(rtrim(number_format($material->harga_satuan, 2, ',', '.'), '0'), ',') }}</td>
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
                                {{ rtrim(rtrim(number_format($material->current_stock, 3, ',', '.'), '0'), ',') }} / min {{ rtrim(rtrim(number_format($material->min_stock, 3, ',', '.'), '0'), ',') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $material->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $material->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('mitra-material.show', [$mitra, $material]) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Detail"><i class="ri-eye-line"></i></a>
                                @can('access', ['mitra-material.index', 'update'])
                                <a href="{{ route('mitra-material.edit', [$mitra, $material]) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Edit"><i class="ri-edit-box-line"></i></a>
                                <button type="button" class="btn btn-sm btn-icon btn-text-info btn-adjust-stock" title="Adjust Stok"
                                    data-sku="{{ $material->sku }}"
                                    data-name="{{ $material->name }}"
                                    data-stock="{{ rtrim(rtrim(number_format($material->current_stock, 3, ',', '.'), '0'), ',') }}"
                                    data-unit="{{ $material->unit }}">
                                    <i class="ri-scales-3-line"></i>
                                </button>
                                @endcan
                                @can('access', ['mitra-material.index', 'delete'])
                                <form action="{{ route('mitra-material.destroy', [$mitra, $material]) }}" method="POST" class="form-delete-confirm d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-text-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center">Belum ada data material untuk mitra ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($materials->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $materials->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal fade" id="modalAdjustStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formAdjustStock" class="modal-content" method="POST" action="">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stok Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Material</label>
                    <input type="text" class="form-control" id="adjust_material_name" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stok Saat Ini</label>
                    <input type="text" class="form-control" id="adjust_current_stock" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Perubahan (Delta) <span class="text-danger">*</span></label>
                    <input type="number" step="0.001" name="delta" id="adjust_delta" class="form-control" placeholder="Contoh: 10 (menambah) atau -5 (mengurangi)" required>
                    <small class="text-muted">Gunakan angka negatif untuk mengurangi stok. Tidak boleh 0.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" id="adjust_notes" class="form-control" rows="2" placeholder="Alasan penyesuaian stok (opsional)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Penyesuaian</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jq = window.$ || window.jQuery;
        if (jq) {
            const adjustUrlTemplate = "{{ route('mitra-material.adjust', [$mitra, '__MATERIAL_SKU__']) }}";

            jq('.btn-adjust-stock').on('click', function() {
                const sku = jq(this).data('sku');
                const name = jq(this).data('name');
                const stock = jq(this).data('stock');
                const unit = jq(this).data('unit');

                jq('#adjust_material_name').val(name);
                jq('#adjust_current_stock').val(stock + ' ' + unit);
                jq('#adjust_delta').val('');
                jq('#adjust_notes').val('');
                jq('#formAdjustStock').attr('action', adjustUrlTemplate.replace('__MATERIAL_SKU__', encodeURIComponent(sku)));

                new bootstrap.Modal(document.getElementById('modalAdjustStock')).show();
            });

            jq('.form-delete-confirm').on('submit', function(e) {
                e.preventDefault();
                let form = this;
                Swal.fire({
                    title: 'Hapus Material?',
                    text: "Material yang sudah dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        }
    });
</script>
@endsection
