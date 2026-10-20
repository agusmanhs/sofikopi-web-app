@extends('layouts/layoutMaster')

@section('title', 'Stok Material')

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
@php
    // Portal-only quick-adjust: owner has can_update on mitra-stock.index
    // (kasir doesn't). Admin context adjusts via the Material page instead.
    $canAdjust = !isset($mitra) && auth()->user()->can('access', ['mitra-stock.index', 'update']);
    $fmtQty = fn ($qty) => rtrim(rtrim(number_format($qty, 3, ',', '.'), '0'), ',');
    $inactiveCount = $summary['inactive_count'];
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
            @can('access', [isset($mitra) ? 'mitra-pos-manage.index' : 'mitra-opname.index', 'read'])
            <a href="{{ $routes['opname'] }}" class="btn btn-outline-secondary">
                <i class="ri-scales-3-line me-1"></i> Stock Opname
            </a>
            @endcan
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

    @if ($canAdjust)
    <div class="alert alert-info d-flex align-items-start" role="alert">
        <i class="ri-information-line me-2 mt-1"></i>
        <div>
            Tombol <strong>Tambah</strong> dan <strong>Kurangi</strong> di halaman ini hanya untuk <strong>koreksi stok</strong> (misalnya bahan rusak atau salah hitung) dan tidak tercatat di jurnal.
            Untuk <strong>membeli bahan</strong>, gunakan <strong>+ Stok</strong> di Kelola Material (otomatis tercatat di jurnal).
        </div>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ $routes['index'] }}" class="row g-3 align-items-end" data-live-filter>
                <div class="col-md-6">
                    <label class="form-label">Cari bahan</label>
                    <input type="search" name="q" class="form-control" value="{{ $search ?? '' }}" placeholder="Ketik nama atau kode (SKU) bahan">
                </div>
                <div class="col-md-4">
                    <label class="form-label ">Status stok</label>
                    <select name="stok" class="form-select">
                        <option value="">Semua bahan</option>
                        <option value="perlu" {{ ($stockStatus ?? null) === 'perlu' ? 'selected' : '' }}>Perlu dibeli (habis atau menipis)</option>
                        <option value="habis" {{ ($stockStatus ?? null) === 'habis' ? 'selected' : '' }}>Habis saja</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div id="live-results">
    <div class="card">
        <div class="card-body pb-0">
            <p class="text-muted mb-3">
                @if ($materials->total() > 0)
                Menampilkan {{ $materials->firstItem() }}-{{ $materials->lastItem() }} dari {{ $materials->total() }} material
                @else
                Menampilkan 0 material
                @endif
            </p>
        </div>
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
                    @forelse($materials as $material)
                    <tr class="{{ $material->is_active ? '' : 'text-muted' }}">
                        <td>{{ $materials->firstItem() + $loop->index }}</td>
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
                                {{ $fmtQty($material->current_stock) }} {{ $material->unit }}
                            </span>
                        </td>
                        <td>Rp {{ number_format($material->stock_value, 0, ',', '.') }}</td>
                        <td>{{ $fmtQty($material->min_stock) }} {{ $material->unit }}</td>
                        <td>
                            <span class="badge {{ $material->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $material->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </td>
                        @if($canAdjust)
                        <td>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-success btn-adjust-stock"
                                    data-bs-toggle="modal" data-bs-target="#adjustStockModal"
                                    data-direction="1"
                                    data-name="{{ $material->name }}"
                                    data-unit="{{ $material->unit }}"
                                    data-stock-raw="{{ (float) $material->current_stock }}"
                                    data-action="{{ route('mitra-stock.adjust', $material->sku) }}"
                                    title="Koreksi: tambah stok (bukan pembelian)">
                                    <i class="ri-add-line me-1"></i> Tambah
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-adjust-stock"
                                    data-bs-toggle="modal" data-bs-target="#adjustStockModal"
                                    data-direction="-1"
                                    data-name="{{ $material->name }}"
                                    data-unit="{{ $material->unit }}"
                                    data-stock-raw="{{ (float) $material->current_stock }}"
                                    data-action="{{ route('mitra-stock.adjust', $material->sku) }}"
                                    title="Koreksi: kurangi stok">
                                    <i class="ri-subtract-line me-1"></i> Kurangi
                                </button>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canAdjust ? 11 : 10 }}" class="text-center">{{ !empty($search) ? 'Tidak ada bahan yang cocok dengan pencarian "'.$search.'".' : 'Belum ada data material.' }}</td>
                    </tr>
                    @endforelse
                </tbody>
                @if ($summary['inventory_value'] > 0 || $materials->isNotEmpty())
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="7" class="text-end">
                            Total Nilai Inventory (semua bahan aktif, bukan hanya halaman ini)
                            @if ($inactiveCount > 0)
                            <div class="small fw-normal text-muted">{{ $inactiveCount }} bahan non-aktif tidak dihitung</div>
                            @endif
                        </td>
                        <td>Rp {{ number_format($summary['inventory_value'], 0, ',', '.') }}</td>
                        <td colspan="{{ $canAdjust ? 3 : 2 }}"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if ($materials->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $materials->links() }}
        </div>
        @endif
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
            <form method="POST" id="adjustStockForm" class="modal-content" data-lock-submit data-confirm-button="Ya, simpan koreksi">
                @csrf
                <input type="hidden" name="delta" id="adjustDelta">
                <input type="hidden" name="notes" id="adjustNotes">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="adjustDirectionLabel"></span> — <span id="adjustMaterialName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p id="adjustExplain" class="mb-2"></p>
                    <p class="text-muted mb-3">Stok saat ini: <strong id="adjustCurrentStock"></strong></p>
                    <div class="mb-3">
                        <label class="form-label" for="adjustQty">Jumlah <span id="adjustQtyHint" class="text-muted fw-normal"></span></label>
                        <div class="input-group">
                            <input type="number" step="0.001" min="0.001" id="adjustQty" class="form-control" placeholder="Contoh: 10" required>
                            <span class="input-group-text" id="adjustUnit"></span>
                        </div>
                        <div id="adjustPreview" class="mt-2 fw-medium"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="adjustReason">Alasan <span class="text-danger">*</span></label>
                        <select id="adjustReason" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="adjustDetail">Keterangan <span id="adjustDetailHint" class="text-muted fw-normal">(boleh dikosongkan)</span></label>
                        <input type="text" id="adjustDetail" class="form-control" maxlength="150" placeholder="Contoh: 2 bungkus bocor saat diantar">
                    </div>
                    <div class="small text-muted">
                        <i class="ri-shopping-cart-line me-1"></i>
                        Mau membeli bahan? Gunakan <strong>+ Stok</strong> di Kelola Material (otomatis tercatat di jurnal).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="adjustSubmit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
@if($canAdjust ?? false)
<script>
    (function () {
        var REASONS = {
            '1': ['Koreksi hasil hitung (stok sebenarnya lebih banyak)', 'Bahan ditemukan / belum tercatat', 'Lainnya'],
            '-1': ['Bahan rusak atau kedaluwarsa', 'Tumpah atau terbuang', 'Dipakai di luar penjualan', 'Koreksi hasil hitung (stok sebenarnya lebih sedikit)', 'Lainnya']
        };
        var form = document.getElementById('adjustStockForm');
        var qtyInput = document.getElementById('adjustQty');
        var reasonSelect = document.getElementById('adjustReason');
        var detailInput = document.getElementById('adjustDetail');
        var submitBtn = document.getElementById('adjustSubmit');
        var preview = document.getElementById('adjustPreview');
        var state = { direction: 1, stock: 0, unit: '', name: '' };

        var fmt = function (n) {
            return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 3 });
        };

        var buildNotes = function () {
            var reason = reasonSelect.value;
            var detail = detailInput.value.trim().split('|').join('/');
            if (!reason) return '';
            if (reason === 'Lainnya') return detail ? 'Lainnya: ' + detail : '';
            return detail ? reason + ' - ' + detail : reason;
        };

        var refresh = function () {
            var qty = parseFloat(qtyInput.value);
            var hasQty = !isNaN(qty) && qty > 0;
            var delta = hasQty ? state.direction * qty : 0;
            var result = Math.round((state.stock + delta) * 1000) / 1000;
            var tooMuch = hasQty && state.direction < 0 && qty > state.stock + 0.0001;
            var notes = buildNotes();
            var action = state.direction > 0 ? 'Tambah' : 'Kurangi';

            document.getElementById('adjustDetailHint').textContent =
                reasonSelect.value === 'Lainnya' ? '(wajib diisi untuk alasan "Lainnya")' : '(boleh dikosongkan)';

            if (!hasQty) {
                preview.className = 'mt-2 fw-medium text-muted';
                preview.textContent = 'Stok akhir = ' + fmt(state.stock) + ' ' + state.unit + ' (isi jumlah dulu)';
            } else if (tooMuch) {
                preview.className = 'mt-2 fw-medium text-danger';
                preview.textContent = 'Stok hanya ' + fmt(state.stock) + ' ' + state.unit + ', tidak bisa dikurangi ' + fmt(qty) + ' ' + state.unit + '.';
            } else {
                preview.className = 'mt-2 fw-medium ' + (state.direction > 0 ? 'text-success' : 'text-danger');
                preview.textContent = 'Stok akhir = ' + fmt(result) + ' ' + state.unit;
            }

            submitBtn.disabled = !hasQty || tooMuch || notes === '';

            document.getElementById('adjustDelta').value = delta.toFixed(3);
            document.getElementById('adjustNotes').value = notes;

            form.setAttribute('data-confirm-title', action + ' stok ' + state.name + '?');
            form.setAttribute('data-confirm-text', [
                'Bahan: ' + state.name,
                'Tindakan: ' + action.toLowerCase() + ' stok ' + fmt(hasQty ? qty : 0) + ' ' + state.unit,
                'Stok sekarang ' + fmt(state.stock) + ' ' + state.unit + ', stok akhir ' + fmt(result) + ' ' + state.unit,
                'Alasan: ' + notes,
                'Ini koreksi, bukan pembelian, dan tidak masuk jurnal.'
            ].join('|'));
            if (state.direction < 0) {
                form.setAttribute('data-confirm-danger', '');
            } else {
                form.removeAttribute('data-confirm-danger');
            }
        };

        document.addEventListener('click', function (event) {
            var btn = event.target.closest('.btn-adjust-stock');
            if (!btn) return;
            {
                state.direction = parseInt(btn.dataset.direction, 10);
                state.stock = parseFloat(btn.dataset.stockRaw) || 0;
                state.unit = btn.dataset.unit;
                state.name = btn.dataset.name;
                var adding = state.direction > 0;

                form.action = btn.dataset.action;
                document.getElementById('adjustMaterialName').textContent = state.name;
                document.getElementById('adjustDirectionLabel').textContent = adding ? 'Tambah Stok (Koreksi)' : 'Kurangi Stok';
                document.getElementById('adjustExplain').textContent = adding
                    ? 'Dipakai kalau stok di sistem lebih sedikit dari kenyataan. Stok akan bertambah sebanyak jumlah di bawah.'
                    : 'Dipakai kalau bahan rusak, terbuang, atau stok di sistem lebih banyak dari kenyataan. Stok akan berkurang sebanyak jumlah di bawah.';
                document.getElementById('adjustQtyHint').textContent = adding ? '(yang ditambahkan)' : '(yang dikurangi)';
                document.getElementById('adjustCurrentStock').textContent = fmt(state.stock) + ' ' + state.unit;
                document.getElementById('adjustUnit').textContent = state.unit;
                submitBtn.className = 'btn ' + (adding ? 'btn-primary' : 'btn-danger');
                submitBtn.textContent = adding ? 'Simpan Penambahan' : 'Simpan Pengurangan';

                reasonSelect.innerHTML = '<option value="">-- Pilih alasan --</option>' + REASONS[String(state.direction)].map(function (r) {
                    return '<option value="' + MitraForm.escapeHtml(r) + '">' + MitraForm.escapeHtml(r) + '</option>';
                }).join('');
                qtyInput.value = '';
                detailInput.value = '';
                MitraForm.unlock(form);
                refresh();
            }
        });

        [qtyInput, reasonSelect, detailInput].forEach(function (el) {
            el.addEventListener('input', refresh);
            el.addEventListener('change', refresh);
        });
    })();
</script>
@endif
@endsection
