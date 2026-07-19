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
                <form action="{{ route('mitra-product.update', [$mitra, $product]) }}" method="POST" id="productForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Varian</label>
                                <input type="text" name="variant" class="form-control" value="{{ old('variant', $product->variant) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Kategori</label>
                                <input type="text" name="category" class="form-control" value="{{ old('category', $product->category) }}">
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
                                <input type="number" step="0.0001" min="0" name="q_factor" id="q_factor" class="form-control" value="{{ old('q_factor', $product->q_factor) }}" required>
                                <small class="text-muted">Faktor overhead/waste. Contoh: 0.2 = 20%.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="sale_price" id="sale_price" class="form-control" value="{{ old('sale_price', $product->sale_price) }}" required>
                            </div>
                        </div>

                        <!-- BOM Ingredient Repeater -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <h5>Resep (BOM)</h5>
                                <table class="table table-bordered" id="ingredientsTable">
                                    <thead>
                                        <tr>
                                            <th>Material</th>
                                            <th width="150">Qty</th>
                                            <th width="100">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($product->ingredients as $index => $ingredient)
                                        <tr>
                                            <td>
                                                <select name="ingredients[{{ $index }}][mitra_material_id]" class="form-select select2 ingredient-material" required>
                                                    <option value="">-- Pilih Material --</option>
                                                    @foreach($materials as $material)
                                                    <option value="{{ $material->id }}" {{ $ingredient->mitra_material_id == $material->id ? 'selected' : '' }}>{{ $material->name }} (Rp {{ number_format($material->harga_satuan, 2, ',', '.') }}/{{ $material->unit }})</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.001" min="0.001" name="ingredients[{{ $index }}][qty]" class="form-control ingredient-qty" value="{{ $ingredient->qty }}" required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-ingredient"><i class="ri-delete-bin-line"></i></button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td>
                                                <select name="ingredients[0][mitra_material_id]" class="form-select select2 ingredient-material" required>
                                                    <option value="">-- Pilih Material --</option>
                                                    @foreach($materials as $material)
                                                    <option value="{{ $material->id }}">{{ $material->name }} (Rp {{ number_format($material->harga_satuan, 2, ',', '.') }}/{{ $material->unit }})</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.001" min="0.001" name="ingredients[0][qty]" class="form-control ingredient-qty" value="1" required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-ingredient"><i class="ri-delete-bin-line"></i></button>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-sm btn-info mt-2" id="addIngredient"><i class="ri-add-line"></i> Tambah Bahan</button>
                            </div>
                        </div>

                        <!-- Live HPP / COGS Preview -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="card bg-lighter border">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3">Preview HPP / COGS (mengikuti perhitungan Excel)</h6>
                                        <div class="row g-3">
                                            <div class="col-sm-4">
                                                <span class="text-muted d-block mb-1">HPP:</span>
                                                <span class="fw-bold fs-5" id="preview_hpp">Rp 0</span>
                                            </div>
                                            <div class="col-sm-4">
                                                <span class="text-muted d-block mb-1">COGS:</span>
                                                <span class="fw-bold fs-5" id="preview_cogs">Rp 0</span>
                                            </div>
                                            <div class="col-sm-4">
                                                <span class="text-muted d-block mb-1">Margin:</span>
                                                <span class="fw-bold fs-5" id="preview_margin">Rp 0</span>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-2">Preview ini hanya perkiraan di sisi browser. Nilai final tetap dihitung ulang oleh server saat disimpan.</small>
                                    </div>
                                </div>
                            </div>
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

<script>
    const materialPrices = {!! json_encode($materials->mapWithKeys(fn($m) => [$m->id => (float) $m->harga_satuan])) !!};
</script>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jq = window.$ || window.jQuery;

        if (jq) {
            jq('.select2').select2({
                placeholder: '-- Pilih Material --',
                allowClear: true
            });

            function formatRupiah(value) {
                const rounded = Math.round(value || 0);
                return 'Rp ' + rounded.toLocaleString('id-ID');
            }

            function recalcPreview() {
                let hpp = 0;
                jq('#ingredientsTable tbody tr').each(function() {
                    const materialId = jq(this).find('.ingredient-material').val();
                    const qty = parseFloat(jq(this).find('.ingredient-qty').val()) || 0;
                    const price = materialId ? (materialPrices[materialId] || 0) : 0;
                    hpp += qty * price;
                });

                const qFactor = parseFloat(jq('#q_factor').val()) || 0;
                const salePrice = parseFloat(jq('#sale_price').val()) || 0;
                const cogs = Math.round(hpp * (1 + qFactor));
                const margin = salePrice - cogs;

                jq('#preview_hpp').text(formatRupiah(hpp));
                jq('#preview_cogs').text(formatRupiah(cogs));
                jq('#preview_margin').text(formatRupiah(margin));
                jq('#preview_margin').toggleClass('text-danger', margin < 0).toggleClass('text-success', margin >= 0);
            }

            let ingredientIndex = {{ max(count($product->ingredients), 1) }};
            jq('#addIngredient').on('click', function() {
                let tbody = jq('#ingredientsTable tbody');
                let tr = jq('<tr></tr>');
                tr.html(`
                    <td>
                        <select name="ingredients[${ingredientIndex}][mitra_material_id]" class="form-select select2 ingredient-material" required>
                            <option value="">-- Pilih Material --</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }} (Rp {{ number_format($material->harga_satuan, 2, ',', '.') }}/{{ $material->unit }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.001" min="0.001" name="ingredients[${ingredientIndex}][qty]" class="form-control ingredient-qty" value="1" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-ingredient"><i class="ri-delete-bin-line"></i></button>
                    </td>
                `);
                tbody.append(tr);

                tr.find('.select2').select2({
                    placeholder: '-- Pilih Material --',
                    allowClear: true
                });

                ingredientIndex++;
                recalcPreview();
            });

            jq('#ingredientsTable').on('click', '.remove-ingredient', function() {
                let tbody = jq('#ingredientsTable tbody');
                if (tbody.children().length > 1) {
                    jq(this).closest('tr').remove();
                    recalcPreview();
                } else {
                    alert('Minimal harus ada 1 bahan.');
                }
            });

            jq('#ingredientsTable').on('change', '.ingredient-material', recalcPreview);
            jq('#ingredientsTable').on('input change', '.ingredient-qty', recalcPreview);
            jq('#q_factor, #sale_price').on('input change', recalcPreview);

            recalcPreview();
        } else {
            console.error('jQuery tidak ditemukan!');
        }
    });
</script>
@endsection
