@extends('layouts/layoutMaster')

@section('title', 'Stock Opname Baru')

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
    $fmtQty = fn ($qty) => rtrim(rtrim(number_format($qty, 3, ',', '.'), '0'), ',');
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS / Stock Opname /</span> Hitung Fisik @if(isset($mitra)) <span class="text-muted fw-light">— {{ $mitra->name }}</span> @endif</h4>
        <a href="{{ $routes['index'] }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    <form action="{{ $routes['store'] }}" method="POST" id="opnameForm" data-lock-submit data-confirm-button="Ya, sesuaikan stok" data-confirm-danger>
        @csrf
        <div class="card mb-4">
            <div class="card-body">
                <div class="alert alert-warning mb-3">
                    <strong>Penting:</strong> stok sistem akan langsung diubah mengikuti angka yang Anda isi dan <strong>tidak bisa dibatalkan</strong>.
                </div>
                <ol class="text-muted mb-0 ps-3">
                    <li>Hitung bahan secara langsung, lalu isi kolom <strong>Stok Fisik</strong>.</li>
                    <li>Bahan yang <strong>dikosongkan tidak dihitung</strong> dan stoknya tidak diubah.</li>
                    <li>Kolom <strong>Selisih</strong> = stok fisik dikurangi stok sistem. Hanya bahan yang selisihnya bukan 0 yang disesuaikan.</li>
                </ol>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="opnameTable">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama Material</th>
                            <th class="text-end">Stok Sistem</th>
                            <th class="text-end" style="width: 200px;">Stok Fisik (hasil hitung)</th>
                            <th class="text-end">Selisih</th>
                            <th class="text-end">Nilai Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                        <tr class="opname-row" data-name="{{ $material->name }}" data-unit="{{ $material->unit }}"
                            data-system="{{ (float) $material->current_stock }}" data-cost="{{ (float) $material->harga_satuan }}">
                            <td>{{ $material->sku }}</td>
                            <td>{{ $material->name }}</td>
                            <td class="text-end">{{ $fmtQty($material->current_stock) }} {{ $material->unit }}</td>
                            <td>
                                <div class="input-group">
                                    <input type="number" step="0.001" min="0" class="form-control text-end opname-input"
                                        name="physical_qty[{{ $material->id }}]"
                                        value="{{ old('physical_qty.'.$material->id) }}"
                                        placeholder="Belum dihitung">
                                    <span class="input-group-text">{{ $material->unit }}</span>
                                </div>
                            </td>
                            <td class="text-end opname-diff text-muted">-</td>
                            <td class="text-end opname-diff-value text-muted">-</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">Belum ada material aktif untuk dihitung.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">
                <div id="opnameSummary" class="fw-medium mb-3 text-muted">Belum ada bahan yang diisi.</div>
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="255" placeholder="Contoh: Opname akhir bulan Juli 2026">{{ old('notes') }}</textarea>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" id="opnameSubmit" class="btn btn-primary" disabled>
                    <i class="ri-save-line me-1"></i> Simpan Stock Opname
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
<script>
    (function () {
        var form = document.getElementById('opnameForm');
        var rows = Array.prototype.slice.call(document.querySelectorAll('.opname-row'));
        var summary = document.getElementById('opnameSummary');
        var submitBtn = document.getElementById('opnameSubmit');
        var MAX_LINES = 8;

        var fmt = function (n) {
            return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 3 });
        };
        var signed = function (n) {
            return (n > 0 ? '+' : '') + fmt(n);
        };
        var tone = function (cell, value) {
            cell.classList.remove('text-muted', 'text-success', 'text-danger');
            cell.classList.add(value > 0 ? 'text-success' : (value < 0 ? 'text-danger' : 'text-muted'));
        };

        var refresh = function () {
            var counted = 0;
            var changed = [];
            var totalValue = 0;

            rows.forEach(function (row) {
                var input = row.querySelector('.opname-input');
                var diffCell = row.querySelector('.opname-diff');
                var valueCell = row.querySelector('.opname-diff-value');
                var raw = input.value.trim();

                if (raw === '' || isNaN(parseFloat(raw))) {
                    diffCell.textContent = '-';
                    valueCell.textContent = '-';
                    tone(diffCell, 0);
                    tone(valueCell, 0);
                    return;
                }

                counted++;
                var system = parseFloat(row.dataset.system);
                var physical = parseFloat(raw);
                var diff = Math.round((physical - system) * 1000) / 1000;
                var value = Math.round(diff * parseFloat(row.dataset.cost));
                var unit = row.dataset.unit;

                diffCell.textContent = diff === 0 ? 'Sama (0)' : signed(diff) + ' ' + unit;
                valueCell.textContent = diff === 0 ? '-' : (value < 0 ? '-' : '') + 'Rp ' + MitraForm.formatRupiah(Math.abs(value));
                tone(diffCell, diff);
                tone(valueCell, value);

                if (diff !== 0) {
                    totalValue += value;
                    changed.push({ name: row.dataset.name, unit: unit, system: system, physical: physical, diff: diff });
                }
            });

            var skipped = rows.length - counted;
            if (counted === 0) {
                summary.className = 'fw-medium mb-3 text-muted';
                summary.textContent = 'Belum ada bahan yang diisi. Isi stok fisik minimal satu bahan.';
            } else {
                summary.className = 'fw-medium mb-3 ' + (changed.length ? 'text-warning' : 'text-success');
                summary.textContent = changed.length + ' bahan akan disesuaikan, '
                    + (counted - changed.length) + ' bahan sama dengan stok sistem, '
                    + skipped + ' bahan belum dihitung (tidak diubah)'
                    + (changed.length ? '. Total nilai selisih: ' + (totalValue < 0 ? '-' : '') + 'Rp ' + MitraForm.formatRupiah(Math.abs(totalValue)) : '.');
            }

            submitBtn.disabled = counted === 0;

            var lines = changed.slice(0, MAX_LINES).map(function (c) {
                return c.name + ': sistem ' + fmt(c.system) + ' menjadi ' + fmt(c.physical) + ' ' + c.unit + ' (selisih ' + signed(c.diff) + ')';
            });
            if (changed.length > MAX_LINES) {
                lines.push('dan ' + (changed.length - MAX_LINES) + ' bahan lainnya');
            }
            if (!changed.length) {
                lines.push('Semua bahan yang dihitung sama dengan stok sistem, tidak ada stok yang diubah.');
            }
            lines.unshift(changed.length + ' bahan akan disesuaikan, ' + skipped + ' bahan tidak dihitung.');
            lines.push('Penyesuaian stok tidak bisa dibatalkan.');

            form.setAttribute('data-confirm-title', 'Simpan stock opname?');
            form.setAttribute('data-confirm-text', lines.map(function (l) { return l.split('|').join('/'); }).join('|'));
        };

        rows.forEach(function (row) {
            row.querySelector('.opname-input').addEventListener('input', refresh);
        });
        refresh();
    })();
</script>
@endsection
