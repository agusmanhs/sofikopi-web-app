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
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('mitra-product.index', $mitra) }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label mb-1">Cari produk</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Ketik nama atau SKU produk">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ri-search-line me-1"></i> Cari</button>
                    @if(request()->filled('q'))
                    <a href="{{ route('mitra-product.index', $mitra) }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-bordered">
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
                    @php
                        $fmtNum = fn ($value, $decimals = 3) => rtrim(rtrim(number_format((float) $value, $decimals, ',', '.'), '0'), ',');
                    @endphp
                    @forelse($products as $index => $product)
                    @php
                        $rincianId = 'rincian-produk-'.$product->id;
                        $hpp = $product->hpp;
                        $cogs = $product->cogs;
                        $margin = $product->margin;
                        $qPercent = (float) $product->q_factor * 100;
                        $marginPercent = $product->sale_price > 0 ? $margin / $product->sale_price * 100 : null;
                    @endphp
                    <tr role="button" data-bs-toggle="collapse" data-bs-target="#{{ $rincianId }}" aria-expanded="false" aria-controls="{{ $rincianId }}">
                        <td><i class="ri-arrow-right-s-line me-1"></i>{{ $products->firstItem() + $index }}</td>
                        <td>{{ $product->sku }}</td>
                        <td>{{ $product->name }}{{ $product->variant ? ' (' . $product->variant . ')' : '' }}</td>
                        <td>{{ $product->category ?? '-' }}</td>
                        <td>{{ $fmtNum($qPercent, 2) }}%</td>
                        <td>Rp {{ number_format($product->sale_price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($hpp, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($cogs, 0, ',', '.') }}</td>
                        <td class="{{ $margin < 0 ? 'text-danger' : 'text-success' }} fw-semibold">
                            Rp {{ number_format($margin, 0, ',', '.') }}
                            @if($marginPercent !== null)
                            <small class="d-block">({{ $fmtNum($marginPercent, 1) }}%)</small>
                            @endif
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
                                <form action="{{ route('mitra-product.destroy', [$mitra, $product]) }}" method="POST" class="d-inline"
                                    data-lock-submit
                                    data-confirm-title="Hapus produk {{ $product->name }}?"
                                    data-confirm-text="Produk dan resepnya tidak akan muncul lagi di daftar dan di kasir.|Untuk menyembunyikan sementara, pilih Edit lalu ubah status menjadi Non-Aktif."
                                    data-confirm-button="Ya, hapus"
                                    data-confirm-danger>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-text-danger" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    <tr class="collapse" id="{{ $rincianId }}">
                        <td colspan="11" class="bg-lighter">
                            <div class="row g-3">
                                <div class="col-lg-7">
                                    <h6 class="fw-bold mb-2">Resep per porsi</h6>
                                    <div class="table-responsive border rounded bg-white">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Bahan</th>
                                                    <th class="text-end">Jumlah</th>
                                                    <th class="text-end">Harga satuan</th>
                                                    <th class="text-end">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($product->ingredients as $ingredient)
                                                <tr>
                                                    <td>{{ $ingredient->material->name ?? 'Material tidak ditemukan' }}</td>
                                                    <td class="text-end">{{ $fmtNum($ingredient->qty) }} {{ $ingredient->material->unit ?? '' }}</td>
                                                    <td class="text-end">Rp {{ $fmtNum($ingredient->material->harga_satuan ?? 0, 2) }}</td>
                                                    <td class="text-end">Rp {{ number_format($ingredient->qty * ($ingredient->material->harga_satuan ?? 0), 0, ',', '.') }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Belum ada bahan dalam resep ini.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <h6 class="fw-bold mb-2">Cara hitung</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-1"><b>HPP</b> = total semua bahan = Rp {{ number_format($hpp, 0, ',', '.') }}</li>
                                        <li class="mb-1"><b>COGS</b> = HPP x (1 + {{ $fmtNum($qPercent, 2) }}%) = Rp {{ number_format($hpp, 0, ',', '.') }} x {{ $fmtNum(1 + $qPercent / 100, 4) }} = Rp {{ number_format($cogs, 0, ',', '.') }}</li>
                                        <li class="mb-1"><b>Margin</b> = harga jual - COGS = Rp {{ number_format($product->sale_price, 0, ',', '.') }} - Rp {{ number_format($cogs, 0, ',', '.') }} = <span class="{{ $margin < 0 ? 'text-danger' : 'text-success' }} fw-semibold">Rp {{ number_format($margin, 0, ',', '.') }}@if($marginPercent !== null) ({{ $fmtNum($marginPercent, 1) }}% dari harga jual)@endif</span></li>
                                    </ul>
                                    <small class="text-muted d-block mt-2">Q-Factor {{ $fmtNum($qPercent, 2) }}% adalah tambahan biaya untuk bahan terbuang/overhead.</small>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center">
                            @if(request()->filled('q'))
                            Tidak ada produk yang cocok dengan pencarian.
                            @else
                            Belum ada data produk untuk mitra ini.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">Klik baris produk untuk melihat resep dan cara menghitung HPP, COGS, dan Margin.</small>
            @if($products->hasPages())
            {{ $products->links() }}
            @endif
        </div>
    </div>
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
<script>
    document.querySelectorAll('tr[data-bs-toggle="collapse"]').forEach(function(row) {
        row.addEventListener('click', function(event) {
            if (event.target.closest('a, button, form')) {
                event.stopPropagation();
            }
        }, true);
    });
</script>
@endsection
