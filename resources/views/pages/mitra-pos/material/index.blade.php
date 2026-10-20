@extends('layouts/layoutMaster')

@section('title', 'Material - ' . $mitra->name)

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
  'resources/assets/vendor/libs/select2/select2.js'
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
            @can('access', ['mitra-material.index', 'update'])
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPurchaseStock">
                <i class="ri-shopping-cart-2-line me-1"></i> + Stok
            </button>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

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

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('mitra-material.index', $mitra) }}" class="row g-2 align-items-end" data-live-filter>
                <div class="col-md-6">
                    <label class="form-label mb-1">Cari material</label>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Ketik nama atau SKU material" autocomplete="off">
                </div>
                @if($categories->isNotEmpty())
                <div class="col-md-4">
                    <label class="form-label mb-1">Kategori</label>
                    <select name="category" class="form-select js-select2" data-placeholder="Semua kategori" data-allow-clear>
                        <option value="">Semua kategori</option>
                        @foreach($categories as $categoryOption)
                        <option value="{{ $categoryOption }}" {{ request('category') === $categoryOption ? 'selected' : '' }}>{{ $categoryOption }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </form>
        </div>
        <div id="live-results">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered">
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
                        <th>Nilai Stok <i class="ri-information-line text-muted" title="Nilai Stok = stok x harga satuan"></i></th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $fmtQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
                    @endphp
                    @forelse($materials as $index => $material)
                    <tr>
                        <td>{{ $materials->firstItem() + $index }}</td>
                        <td>{{ $material->sku }}</td>
                        <td>{{ $material->name }}</td>
                        <td>{{ $material->category ?? '-' }}</td>
                        <td>{{ $material->unit }}</td>
                        <td>{{ $fmtQty($material->netto) }} {{ $material->unit }}</td>
                        <td>Rp {{ number_format($material->price_per_pack, 0, ',', '.') }}</td>
                        <td>Rp {{ rtrim(rtrim(number_format($material->harga_satuan, 2, ',', '.'), '0'), ',') }}<small class="text-muted"> / {{ $material->unit }}</small></td>
                        <td>
                            Rp {{ number_format($material->stock_value, 0, ',', '.') }}
                            <small class="text-muted d-block">{{ $fmtQty($material->current_stock) }} {{ $material->unit }} x Rp {{ rtrim(rtrim(number_format($material->harga_satuan, 2, ',', '.'), '0'), ',') }}</small>
                        </td>
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
                                {{ $fmtQty($material->current_stock) }} {{ $material->unit }} / min {{ $fmtQty($material->min_stock) }} {{ $material->unit }}
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
                                <button type="button" class="btn btn-sm btn-icon btn-text-danger btn-adjust-stock" title="Kurangi Stok"
                                    data-direction="-1"
                                    data-sku="{{ $material->sku }}"
                                    data-name="{{ $material->name }}"
                                    data-stock="{{ (float) $material->current_stock }}"
                                    data-unit="{{ $material->unit }}">
                                    <i class="ri-subtract-line"></i>
                                </button>
                                @endcan
                                @can('access', ['mitra-material.index', 'delete'])
                                <form action="{{ route('mitra-material.destroy', [$mitra, $material]) }}" method="POST" class="d-inline"
                                    data-lock-submit
                                    data-confirm-title="Hapus material {{ $material->name }}?"
                                    data-confirm-text="Material ini tidak akan muncul lagi di daftar dan tidak bisa dipilih untuk resep.|Kalau masih dipakai di resep produk, penghapusan ditolak dan Anda diberi tahu produk mana yang memakainya.|Untuk menyembunyikan sementara, pilih Edit lalu ubah status menjadi Non-Aktif."
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
                    @empty
                    <tr>
                        <td colspan="12" class="text-center">
                            @if(request()->filled('q') || request()->filled('category'))
                            Tidak ada material yang cocok dengan pencarian.
                            @else
                            Belum ada data material untuk mitra ini.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">Nilai Stok = stok x harga satuan. Harga satuan = harga per pack / netto.</small>
            @if($materials->hasPages())
            {{ $materials->links() }}
            @endif
        </div>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal fade" id="modalAdjustStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formAdjustStock" class="modal-content" method="POST" action="" novalidate>
            @csrf
            <input type="hidden" name="delta" id="adjust_delta_hidden">
            <input type="hidden" name="notes" id="adjust_notes_hidden">
            <div class="modal-header">
                <h5 class="modal-title"><span id="adjust_direction_label">Kurangi Stok Material</span></h5>
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
                    <label class="form-label">Jumlah yang dikurangi <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.001" min="0.001" id="adjust_qty" class="form-control" placeholder="Contoh: 10">
                        <span class="input-group-text" id="adjust_unit_label">-</span>
                    </div>
                    <small class="text-muted d-block mt-1" id="adjust_preview">Stok akhir = -</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alasan <span class="text-danger">*</span></label>
                    <select id="adjust_reason" class="form-select mb-2">
                        <option value="">-- Pilih alasan --</option>
                        <option value="Rusak">Rusak</option>
                        <option value="Hilang">Hilang</option>
                        <option value="Kadaluarsa">Kadaluarsa</option>
                        <option value="Koreksi hitung">Koreksi hitung</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                    <textarea id="adjust_notes" class="form-control" rows="2" maxlength="200" placeholder="Keterangan tambahan (wajib diisi jika alasan Lainnya)"></textarea>
                    <small class="text-danger d-none mt-1" id="adjust_error"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Penyesuaian</button>
            </div>
        </form>
    </div>
</div>

<!-- Purchase Stock Modal ("+ Stok") -->
<div class="modal fade" id="modalPurchaseStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formPurchaseStock" class="modal-content" method="POST" action="{{ route('mitra-material.purchase', $mitra) }}" data-lock-submit>
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Pembelian Stok Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Material <span class="text-danger">*</span></label>
                    <select name="material_id" id="purchase_material_id" class="form-select js-select2" data-placeholder="-- Pilih material --" required>
                        <option value="">-- Pilih material --</option>
                        @foreach($allMaterials as $m)
                        <option value="{{ $m->id }}" data-unit="{{ $m->unit }}" data-price="{{ (float) $m->harga_satuan }}">
                            {{ $m->name }} ({{ $m->sku }}) - harga standar Rp {{ rtrim(rtrim(number_format($m->harga_satuan, 2, ',', '.'), '0'), ',') }}/{{ $m->unit }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Beli <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.001" min="0.001" name="qty" id="purchase_qty" class="form-control" placeholder="Contoh: 10" required>
                        <span class="input-group-text" id="purchase_qty_unit">satuan</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Harga Beli per <span id="purchase_price_unit">satuan</span> (Rp) <span class="text-danger">*</span></label>
                    <input type="text" inputmode="decimal" name="unit_price" id="purchase_unit_price" class="form-control rupiah-input" data-decimals="2" placeholder="Contoh: 18,5" required>
                    <small class="text-muted d-block mt-1" id="purchase_standard_hint">Isi harga untuk SATU satuan, bukan harga satu pack.</small>
                    <small class="text-warning d-none" id="purchase_price_warning"></small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total bayar</label>
                    <input type="text" class="form-control" id="purchase_total" disabled value="Rp 0">
                    <small class="text-muted">Total bayar = jumlah beli x harga beli per satuan.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" id="purchase_notes" class="form-control" rows="2" placeholder="Contoh: beli air mineral tambahan (opsional)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Simpan Pembelian</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const byId = function(id) { return document.getElementById(id); };
        const fmtQty = function(n) {
            return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 3 });
        };

        const adjustForm = byId('formAdjustStock');
        const adjustUrlTemplate = "{{ route('mitra-material.adjust', [$mitra, '__MATERIAL_SKU__']) }}";
        const adjustState = { stock: 0, unit: '', direction: -1 };

        const adjustQty = function() { return parseFloat(byId('adjust_qty').value) || 0; };

        const adjustProblem = function() {
            const qty = adjustQty();
            if (qty <= 0) return 'Isi jumlah yang dikurangi.';
            if (adjustState.direction < 0 && qty > adjustState.stock) {
                return 'Jumlah melebihi stok saat ini (' + fmtQty(adjustState.stock) + ' ' + adjustState.unit + ').';
            }
            if (!byId('adjust_reason').value) return 'Pilih alasan pengurangan stok.';
            if (byId('adjust_reason').value === 'Lainnya' && !byId('adjust_notes').value.trim()) {
                return 'Alasan "Lainnya" wajib diisi keterangannya.';
            }
            return '';
        };

        const refreshAdjust = function() {
            const qty = adjustQty();
            const after = adjustState.stock + adjustState.direction * qty;
            const preview = byId('adjust_preview');
            preview.textContent = 'Stok akhir = ' + fmtQty(after) + ' ' + adjustState.unit;
            const tooMuch = adjustState.direction < 0 && qty > adjustState.stock;
            preview.classList.toggle('text-danger', tooMuch);
            preview.classList.toggle('text-muted', !tooMuch);
        };

        document.addEventListener('click', function(event) {
            const button = event.target.closest ? event.target.closest('.btn-adjust-stock') : null;
            if (button) {
                adjustState.stock = parseFloat(button.dataset.stock) || 0;
                adjustState.unit = button.dataset.unit;
                adjustState.direction = parseInt(button.dataset.direction, 10) || -1;

                byId('adjust_direction_label').textContent = adjustState.direction > 0 ? 'Tambah Stok' : 'Kurangi Stok';
                byId('adjust_material_name').value = button.dataset.name;
                byId('adjust_current_stock').value = fmtQty(adjustState.stock) + ' ' + adjustState.unit;
                byId('adjust_unit_label').textContent = adjustState.unit;
                byId('adjust_qty').value = '';
                byId('adjust_reason').value = '';
                byId('adjust_notes').value = '';
                byId('adjust_error').classList.add('d-none');
                adjustForm.action = adjustUrlTemplate.replace('__MATERIAL_SKU__', encodeURIComponent(button.dataset.sku));
                MitraForm.unlock(adjustForm);
                refreshAdjust();

                new bootstrap.Modal(byId('modalAdjustStock')).show();
            }
        });

        byId('adjust_qty').addEventListener('input', refreshAdjust);

        adjustForm.addEventListener('submit', function(event) {
            event.preventDefault();
            if (adjustForm.dataset.submitting === '1') return;

            const errorBox = byId('adjust_error');
            const problem = adjustProblem();
            errorBox.textContent = problem;
            errorBox.classList.toggle('d-none', problem === '');
            if (problem) return;

            const qty = adjustQty();
            const reason = byId('adjust_reason').value;
            const detail = byId('adjust_notes').value.trim();
            const after = adjustState.stock + adjustState.direction * qty;

            MitraForm.confirm({
                title: adjustState.direction > 0 ? 'Tambah stok?' : 'Kurangi stok?',
                lines: [
                    byId('adjust_material_name').value,
                    'Stok ' + fmtQty(adjustState.stock) + ' ' + adjustState.unit + ' menjadi ' + fmtQty(after) + ' ' + adjustState.unit + '.',
                    'Alasan: ' + reason + (detail ? ' - ' + detail : ''),
                    'Perubahan ini tercatat di riwayat stok.',
                ],
                confirmText: 'Ya, simpan',
                danger: adjustState.direction < 0,
            }).then(function(ok) {
                if (!ok) return;
                byId('adjust_delta_hidden').value = (adjustState.direction * qty).toFixed(3);
                byId('adjust_notes_hidden').value = reason + (detail ? ' - ' + detail : '');
                MitraForm.lock(adjustForm);
                adjustForm.submit();
            });
        });

        const purchaseSelect = byId('purchase_material_id');

        const refreshPurchase = function() {
            const option = purchaseSelect.options[purchaseSelect.selectedIndex];
            const unit = option && option.value ? option.dataset.unit : '';
            const standard = option && option.value ? parseFloat(option.dataset.price) || 0 : 0;
            const qty = parseFloat(byId('purchase_qty').value) || 0;
            const price = MitraForm.toDecimalNumber(byId('purchase_unit_price').value) || 0;

            byId('purchase_qty_unit').textContent = unit || 'satuan';
            byId('purchase_price_unit').textContent = unit || 'satuan';
            byId('purchase_total').value = 'Rp ' + (qty * price).toLocaleString('id-ID', { maximumFractionDigits: 0 });

            byId('purchase_standard_hint').textContent = unit
                ? 'Harga standar di katalog: Rp ' + standard.toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' per ' + unit + '. Isi harga untuk SATU ' + unit + ', bukan harga satu pack.'
                : 'Isi harga untuk SATU satuan, bukan harga satu pack.';

            const warning = byId('purchase_price_warning');
            let text = '';
            if (standard > 0 && price > 0 && price > standard * 5) {
                text = 'Harga ini jauh lebih tinggi dari harga standar. Apakah yang Anda isi harga satu pack, bukan harga per ' + unit + '?';
            } else if (standard > 0 && price > 0 && price < standard * 0.2) {
                text = 'Harga ini jauh lebih rendah dari harga standar. Apakah sudah benar?';
            }
            warning.textContent = text;
            warning.classList.toggle('d-none', text === '');
        };

        purchaseSelect.addEventListener('change', refreshPurchase);
        if (window.jQuery) window.jQuery(purchaseSelect).on('change', refreshPurchase);
        byId('purchase_qty').addEventListener('input', refreshPurchase);
        byId('purchase_unit_price').addEventListener('input', refreshPurchase);

        byId('modalPurchaseStock').addEventListener('hidden.bs.modal', function() {
            byId('formPurchaseStock').reset();
            if (window.jQuery) window.jQuery(purchaseSelect).trigger('change');
            MitraForm.unlock(byId('formPurchaseStock'));
            refreshPurchase();
        });
    });
</script>
@endsection
