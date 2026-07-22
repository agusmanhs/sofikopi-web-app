@extends('layouts/layoutMaster')

@section('title', 'Produk - ' . $mitra->name)

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
            <span class="text-muted fw-light">Mitra POS / {{ $mitra->name }} /</span> Produk
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
            @can('access', ['mitra-product.index', 'create'])
            <a href="{{ route('mitra-product.create', $mitra) }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i> Tambah Produk
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
                        <th>Q-Factor</th>
                        <th>Harga Jual</th>
                        <th>HPP</th>
                        <th>COGS</th>
                        <th>Margin</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                    <tr>
                        <td>{{ $products->firstItem() + $index }}</td>
                        <td>{{ $product->sku }}</td>
                        <td>{{ $product->name }}{{ $product->variant ? ' (' . $product->variant . ')' : '' }}</td>
                        <td>{{ $product->category ?? '-' }}</td>
                        <td>{{ rtrim(rtrim(number_format($product->q_factor, 4, ',', '.'), '0'), ',') }}</td>
                        <td>Rp {{ number_format($product->sale_price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($product->hpp, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($product->cogs, 0, ',', '.') }}</td>
                        <td class="{{ $product->margin < 0 ? 'text-danger' : 'text-success' }} fw-semibold">
                            Rp {{ number_format($product->margin, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge {{ $product->status == 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $product->status == 'active' ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('mitra-product.show', [$mitra, $product]) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Detail"><i class="ri-eye-line"></i></a>
                                @can('access', ['mitra-product.index', 'update'])
                                <a href="{{ route('mitra-product.edit', [$mitra, $product]) }}" class="btn btn-sm btn-icon btn-text-secondary" title="Edit"><i class="ri-edit-box-line"></i></a>
                                @endcan
                                @can('access', ['mitra-product.index', 'delete'])
                                <form action="{{ route('mitra-product.destroy', [$mitra, $product]) }}" method="POST" class="form-delete-confirm d-inline">
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
                        <td colspan="11" class="text-center">Belum ada data produk untuk mitra ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jq = window.$ || window.jQuery;
        if (jq) {
            jq('.form-delete-confirm').on('submit', function(e) {
                e.preventDefault();
                let form = this;
                Swal.fire({
                    title: 'Hapus Produk?',
                    text: "Produk beserta resep BOM-nya akan dihapus permanen!",
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
