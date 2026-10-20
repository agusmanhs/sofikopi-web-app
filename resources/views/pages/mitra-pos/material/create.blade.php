@extends('layouts/layoutMaster')

@section('title', 'Tambah Material - ' . $mitra->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Mitra POS / {{ $mitra->name }} / Material /</span> Tambah Baru
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
                <form action="{{ route('mitra-material.store', $mitra) }}" method="POST" data-lock-submit>
                    @csrf
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control" value="{{ old('sku') }}" placeholder="Contoh: SHB021" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Material <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Lallo Blend 1000 GR" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kategori</label>
                                <input type="text" name="category" class="form-control" value="{{ old('category') }}" placeholder="Contoh: Bahan Baku">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" value="{{ old('brand') }}" placeholder="Contoh: Sofikopi">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Satuan <span class="text-danger">*</span></label>
                                <select name="unit" id="unit" class="form-select" required>
                                    <option value="">-- Pilih Satuan --</option>
                                    @foreach(['GR', 'KG', 'ML', 'LTR', 'PCS', 'BTL', 'PACK'] as $unitOption)
                                    <option value="{{ $unitOption }}" {{ old('unit') == $unitOption ? 'selected' : '' }}>{{ $unitOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Netto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.001" min="0.001" name="netto" id="netto" class="form-control" value="{{ old('netto') }}" placeholder="Contoh: 1000" required>
                                    <span class="input-group-text unit-suffix">satuan</span>
                                </div>
                                <small class="text-muted">Isi satu pack berapa. Contoh: 1000 untuk kemasan 1000 GR.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga per Pack (Rp) <span class="text-danger">*</span></label>
                                <input type="text" inputmode="decimal" name="price_per_pack" id="price_per_pack" class="form-control rupiah-input" data-decimals="2" value="{{ old('price_per_pack') }}" placeholder="Contoh: 180.000" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="alert alert-secondary mb-0 py-2" id="unitPriceLine">
                                    Harga satuan = Rp - per satuan
                                    <small class="d-block text-muted">Harga satuan = harga per pack / netto. Angka inilah yang dipakai untuk menghitung HPP produk.</small>
                                </div>
                                <small class="text-warning d-none" id="nettoWarning"></small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Stok Awal</label>
                                <div class="input-group">
                                    <input type="number" step="0.001" min="0" name="current_stock" class="form-control" value="{{ old('current_stock', 0) }}">
                                    <span class="input-group-text unit-suffix">satuan</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stok Minimum</label>
                                <div class="input-group">
                                    <input type="number" step="0.001" min="0" name="min_stock" class="form-control" value="{{ old('min_stock', 0) }}">
                                    <span class="input-group-text unit-suffix">satuan</span>
                                </div>
                                <small class="text-muted">Peringatan muncul saat stok di bawah angka ini.</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end border-top pt-3">
                        <a href="{{ route('mitra-material.index', $mitra) }}" class="btn btn-outline-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const unitSelect = document.getElementById('unit');
        const nettoInput = document.getElementById('netto');
        const priceInput = document.getElementById('price_per_pack');
        const line = document.getElementById('unitPriceLine');
        const warning = document.getElementById('nettoWarning');

        const refresh = function() {
            const unit = unitSelect.value || 'satuan';
            const netto = parseFloat(nettoInput.value) || 0;
            const price = MitraForm.toDecimalNumber(priceInput.value) || 0;

            document.querySelectorAll('.unit-suffix').forEach(function(el) { el.textContent = unit; });

            const perUnit = netto > 0 ? price / netto : 0;
            line.firstChild.textContent = 'Harga satuan = Rp ' + perUnit.toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' per ' + unit + ' ';

            const suspicious = netto === 1 && (unit === 'KG' || unit === 'LTR');
            warning.textContent = suspicious
                ? 'Netto 1 ' + unit + '. Apakah maksud Anda ' + (unit === 'KG' ? '1000 GR' : '1000 ML') + '? Pastikan satuan dan netto sudah sesuai kemasan.'
                : '';
            warning.classList.toggle('d-none', !suspicious);
        };

        [unitSelect, nettoInput, priceInput].forEach(function(el) {
            el.addEventListener('input', refresh);
            el.addEventListener('change', refresh);
        });
        refresh();
    });
</script>
@endsection
