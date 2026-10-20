@extends('layouts/layoutMaster')

@section('title', 'Edit Produk - ' . $mitra->name)

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js'
])
@endsection

@section('content')
@php
    $qOld = old('q_factor');
    $qValue = $qOld !== null ? (is_numeric($qOld) ? round((float) $qOld * 100, 2) : $qOld) : round((float) $product->q_factor * 100, 2);
    $recipeRows = old('ingredients') !== null ? array_values(old('ingredients')) : $product->ingredients->map(fn ($i) => ['mitra_material_id' => $i->mitra_material_id, 'qty' => (float) $i->qty])->values()->all();
    if (count($recipeRows) === 0) {
        $recipeRows = [['mitra_material_id' => '', 'qty' => '']];
    }
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Mitra POS / {{ $mitra->name }} / Produk /</span> Edit
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
                <form action="{{ route('mitra-product.update', [$mitra, $product]) }}" method="POST" id="productForm" data-lock-submit>
                    @method('PUT')
                    @csrf
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control" value="{{ $product->sku }}" readonly required>
                                <small class="text-muted">SKU dipakai sebagai alamat halaman dan tidak bisa diubah. Jika salah, hapus produk ini lalu buat baru.</small>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" placeholder="Contoh: Kopi Susu Bapak Dingin" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Varian</label>
                                <input type="text" name="variant" class="form-control" value="{{ old('variant', $product->variant) }}" placeholder="Contoh: Dingin">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Kategori</label>
                                <input type="text" name="category" class="form-control" value="{{ old('category', $product->category) }}" placeholder="Contoh: Kopi Susu">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sub Kategori</label>
                                <input type="text" name="sub_category" class="form-control" value="{{ old('sub_category', $product->sub_category) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Q-Factor <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" name="q_factor" id="q_factor" class="form-control" value="{{ $qValue }}" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Tambahan biaya di atas HPP untuk bahan terbuang/overhead. Isi dalam persen, contoh: 20 artinya HPP ditambah 20%.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                                <input type="text" inputmode="numeric" name="sale_price" id="sale_price" class="form-control rupiah-input" value="{{ old('sale_price', number_format((float) $product->sale_price, 0, ',', '.')) }}" placeholder="Contoh: 22.000" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <h5>Resep (BOM)</h5>
                                <small class="text-muted d-block mb-2">Isi bahan dan jumlah yang dipakai untuk 1 porsi produk. Satu bahan cukup satu baris.</small>
                                <table class="table table-bordered" id="ingredientsTable">
                                    <thead>
                                        <tr>
                                            <th>Material</th>
                                            <th width="220">Jumlah per porsi</th>
                                            <th width="100">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recipeRows as $rowIndex => $row)
                                        <tr>
                                            <td>
                                                <select name="ingredients[{{ $rowIndex }}][mitra_material_id]" class="form-select select2 ingredient-material" required>
                                                    <option value="">-- Pilih Material --</option>
                                                    @foreach($materials as $material)
                                                    <option value="{{ $material->id }}" data-unit="{{ $material->unit }}" {{ (string) ($row['mitra_material_id'] ?? '') === (string) $material->id ? 'selected' : '' }}>{{ $material->name }} (Rp {{ rtrim(rtrim(number_format($material->harga_satuan, 2, ',', '.'), '0'), ',') }}/{{ $material->unit }})</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" step="0.001" min="0.001" name="ingredients[{{ $rowIndex }}][qty]" class="form-control ingredient-qty" value="{{ $row['qty'] ?? '' }}" placeholder="Contoh: 18" required>
                                                    <span class="input-group-text ingredient-unit">-</span>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-ingredient"><i class="ri-delete-bin-line"></i></button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="text-danger mb-2 d-none" id="ingredientMessage"></div>
                                <button type="button" class="btn btn-sm btn-info mt-2" id="addIngredient"><i class="ri-add-line"></i> Tambah Bahan</button>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="card bg-lighter border">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3">Preview perhitungan</h6>
                                        <div class="row g-3">
                                            <div class="col-sm-6 col-lg-3">
                                                <span class="text-muted d-block mb-1">HPP (total bahan):</span>
                                                <span class="fw-bold fs-5" id="preview_hpp">Rp 0</span>
                                            </div>
                                            <div class="col-sm-6 col-lg-3">
                                                <span class="text-muted d-block mb-1">Tambahan Q-Factor (<span id="preview_q_percent">0</span>%):</span>
                                                <span class="fw-bold fs-5" id="preview_q_amount">Rp 0</span>
                                            </div>
                                            <div class="col-sm-6 col-lg-3">
                                                <span class="text-muted d-block mb-1">COGS (HPP + Q-Factor):</span>
                                                <span class="fw-bold fs-5" id="preview_cogs">Rp 0</span>
                                            </div>
                                            <div class="col-sm-6 col-lg-3">
                                                <span class="text-muted d-block mb-1">Margin (Harga jual - COGS):</span>
                                                <span class="fw-bold fs-5" id="preview_margin">Rp 0</span>
                                                <small class="d-block" id="preview_margin_percent"></small>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-2">Preview ini hanya perkiraan di browser. Nilai final dihitung ulang oleh server saat disimpan.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="alert alert-warning mb-0" role="alert">
                            <i class="ri-information-line me-1"></i>
                            Perubahan resep dan harga hanya berlaku untuk transaksi berikutnya. Transaksi yang sudah terjadi tidak berubah.
                        </div>
                    </div>
                    <div class="card-footer text-end border-top pt-3">
                        <a href="{{ route('mitra-product.index', $mitra) }}" class="btn btn-outline-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="ingredientRowTemplate">
    <tr>
        <td>
            <select name="ingredients[__INDEX__][mitra_material_id]" class="form-select select2 ingredient-material" required>
                <option value="">-- Pilih Material --</option>
                @foreach($materials as $material)
                <option value="{{ $material->id }}" data-unit="{{ $material->unit }}">{{ $material->name }} (Rp {{ rtrim(rtrim(number_format($material->harga_satuan, 2, ',', '.'), '0'), ',') }}/{{ $material->unit }})</option>
                @endforeach
            </select>
        </td>
        <td>
            <div class="input-group">
                <input type="number" step="0.001" min="0.001" name="ingredients[__INDEX__][qty]" class="form-control ingredient-qty" placeholder="Contoh: 18" required>
                <span class="input-group-text ingredient-unit">-</span>
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm remove-ingredient"><i class="ri-delete-bin-line"></i></button>
        </td>
    </tr>
</template>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
<script>
    const materialPrices = {!! json_encode($materials->mapWithKeys(fn($m) => [$m->id => (float) $m->harga_satuan])) !!};

    document.addEventListener('DOMContentLoaded', function() {
        const jq = window.$ || window.jQuery;
        if (!jq) {
            console.error('jQuery tidak ditemukan!');
            return;
        }

        const message = document.getElementById('ingredientMessage');
        const showMessage = function(text) {
            message.textContent = text;
            message.classList.toggle('d-none', text === '');
        };

        const initSelect = function(el) {
            jq(el).select2({ placeholder: '-- Pilih Material --', allowClear: true });
        };

        const rupiah = function(value) {
            return 'Rp ' + Math.round(value || 0).toLocaleString('id-ID');
        };

        const refreshUnits = function() {
            jq('#ingredientsTable tbody tr').each(function() {
                const unit = jq(this).find('.ingredient-material option:selected').data('unit');
                jq(this).find('.ingredient-unit').text(unit || '-');
            });
        };

        const recalcPreview = function() {
            let hpp = 0;
            jq('#ingredientsTable tbody tr').each(function() {
                const materialId = jq(this).find('.ingredient-material').val();
                const qty = parseFloat(jq(this).find('.ingredient-qty').val()) || 0;
                hpp += qty * (materialId ? (materialPrices[materialId] || 0) : 0);
            });

            const qPercent = parseFloat(String(jq('#q_factor').val()).replace(',', '.')) || 0;
            const salePrice = MitraForm.toRupiahNumber(jq('#sale_price').val()) || 0;
            const cogs = Math.round(hpp * (1 + qPercent / 100));
            const qAmount = cogs - Math.round(hpp);
            const margin = salePrice - cogs;

            jq('#preview_hpp').text(rupiah(hpp));
            jq('#preview_q_percent').text(qPercent.toLocaleString('id-ID', { maximumFractionDigits: 2 }));
            jq('#preview_q_amount').text(rupiah(qAmount));
            jq('#preview_cogs').text(rupiah(cogs));
            jq('#preview_margin').text(rupiah(margin)).toggleClass('text-danger', margin < 0).toggleClass('text-success', margin >= 0);
            jq('#preview_margin_percent').text(salePrice > 0 ? 'Margin = ' + (margin / salePrice * 100).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '% dari harga jual' : '');
        };

        jq('.ingredient-material').each(function() { initSelect(this); });

        let ingredientIndex = jq('#ingredientsTable tbody tr').length;
        jq('#addIngredient').on('click', function() {
            const html = document.getElementById('ingredientRowTemplate').innerHTML.replace(/__INDEX__/g, ingredientIndex);
            const tr = jq(html);
            jq('#ingredientsTable tbody').append(tr);
            initSelect(tr.find('.select2'));
            ingredientIndex++;
            showMessage('');
            refreshUnits();
            recalcPreview();
        });

        jq('#ingredientsTable').on('click', '.remove-ingredient', function() {
            if (jq('#ingredientsTable tbody tr').length <= 1) {
                showMessage('Resep minimal harus punya 1 bahan. Ganti bahannya, jangan dihapus semua.');
                return;
            }
            jq(this).closest('tr').remove();
            showMessage('');
            recalcPreview();
        });

        jq('#ingredientsTable').on('change', '.ingredient-material', function() {
            const select = this;
            const chosen = jq(select).val();
            if (chosen) {
                const duplicate = jq('.ingredient-material').not(select).filter(function() { return jq(this).val() === chosen; }).length > 0;
                if (duplicate) {
                    showMessage('Bahan ini sudah ada di resep. Satu bahan cukup satu baris, ubah jumlahnya di baris yang sudah ada.');
                    jq(select).val('').trigger('change.select2');
                    refreshUnits();
                    recalcPreview();
                    return;
                }
            }
            showMessage('');
            refreshUnits();
            recalcPreview();
        });

        jq('#ingredientsTable').on('input change', '.ingredient-qty', recalcPreview);
        jq('#q_factor, #sale_price').on('input change', recalcPreview);

        refreshUnits();
        recalcPreview();
    });
</script>
@endsection
