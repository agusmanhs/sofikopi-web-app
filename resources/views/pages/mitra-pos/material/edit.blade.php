@extends('layouts/layoutMaster')

@section('title', 'Edit Material - ' . $mitra->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Mitra POS / {{ $mitra->name }} / Material /</span> Edit
    </h4>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <form action="{{ route('mitra-material.update', [$mitra, $material]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control" value="{{ old('sku', $material->sku) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Material <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $material->name) }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kategori</label>
                                <input type="text" name="category" class="form-control" value="{{ old('category', $material->category) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" value="{{ old('brand', $material->brand) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Satuan <span class="text-danger">*</span></label>
                                <select name="unit" class="form-select" required>
                                    <option value="">-- Pilih Satuan --</option>
                                    @foreach(['GR', 'KG', 'ML', 'LTR', 'PCS', 'BTL', 'PACK'] as $unitOption)
                                    <option value="{{ $unitOption }}" {{ old('unit', $material->unit) == $unitOption ? 'selected' : '' }}>{{ $unitOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Netto <span class="text-danger">*</span></label>
                                <input type="number" step="0.001" min="0.001" name="netto" class="form-control" value="{{ old('netto', (float) $material->netto) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga per Pack (Rp) <span class="text-danger">*</span></label>
                                <input type="text" inputmode="numeric" name="price_per_pack" class="form-control rupiah-input" value="{{ old('price_per_pack', number_format((float) $material->price_per_pack, 0, ',', '.')) }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Stok Saat Ini</label>
                                <input type="text" class="form-control" value="{{ rtrim(rtrim(number_format($material->current_stock, 3, ',', '.'), '0'), ',') }} {{ $material->unit }}" disabled>
                                <small class="text-muted">Untuk mengubah stok, gunakan tombol <b>Adjust Stok</b> di halaman daftar material.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stok Minimum</label>
                                <input type="number" step="0.001" min="0" name="min_stock" class="form-control" value="{{ old('min_stock', (float) $material->min_stock) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $material->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end border-top pt-3">
                        <a href="{{ route('mitra-material.index', $mitra) }}" class="btn btn-outline-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Live thousand-separator formatting (id-ID: 180000 -> 180.000).
        // Server strips the dots back off in the FormRequest, so this is
        // purely presentational.
        document.querySelectorAll('.rupiah-input').forEach(function(el) {
            const format = function() {
                const digits = el.value.replace(/[^\d]/g, '');
                el.value = digits ? Number(digits).toLocaleString('id-ID') : '';
            };
            el.addEventListener('input', format);
            format();
        });
    });
</script>
@endsection
