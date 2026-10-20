@extends('layouts/layoutMaster')

@section('title', 'Jurnal Manual' . (isset($mitra) ? ' - ' . $mitra->name : ''))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@php
    $coa = \App\Services\MitraPos\AkuntansiCoaService::class;
    $accountLabel = fn ($account) => $account->code.' - '.$account->name
        .' ('.$coa::jenisLabel($account->account_type).') · '.$coa::normalSideLabel($account->account_type);
    $oldLines = array_values(old('lines', [[], []]));
    if (count($oldLines) < 2) {
        $oldLines = array_pad($oldLines, 2, []);
    }
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS / Akuntansi / Jurnal /</span> Manual</h4>

    @include('pages.mitra-pos.akuntansi._nav')

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

    <div class="card mb-4">
        <div class="card-header py-2" role="button" data-bs-toggle="collapse" data-bs-target="#contoh-jurnal" aria-expanded="false" aria-controls="contoh-jurnal">
            <i class="ri-lightbulb-line me-1"></i> <strong>Contoh jurnal umum</strong>
            <small class="text-muted ms-2">klik untuk melihat cara mengisi</small>
        </div>
        <div class="collapse" id="contoh-jurnal">
            <div class="card-body">
                <p class="text-muted small mb-3">Isi satu baris untuk uang yang bertambah/dipakai, satu baris lagi untuk asal atau tujuannya. Jumlah Debit harus sama dengan jumlah Kredit.</p>
                <div class="row g-3 small">
                    <div class="col-md-6">
                        <strong>Bayar listrik / gas</strong>
                        <ul class="mb-0 ps-3">
                            <li>Debit: Biaya Listrik (atau Biaya Gas)</li>
                            <li>Kredit: Kas</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Beli bahan tunai</strong>
                        <ul class="mb-0 ps-3">
                            <li>Debit: Persediaan Bahan Baku</li>
                            <li>Kredit: Kas</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Setoran modal dari pemilik</strong>
                        <ul class="mb-0 ps-3">
                            <li>Debit: Kas</li>
                            <li>Kredit: Modal (akun modal pemilik)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Bayar hutang BOP</strong>
                        <ul class="mb-0 ps-3">
                            <li>Debit: Hutang BOP</li>
                            <li>Kredit: Kas</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <form action="{{ $routes['store'] }}" method="POST" id="jurnal-form" data-lock-submit data-confirm-title="Simpan jurnal ini?" data-confirm-button="Ya, simpan jurnal">
            @csrf
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="entry-date">Tanggal</label>
                        <input type="date" id="entry-date" name="entry_date" class="form-control" max="{{ now()->format('Y-m-d') }}" value="{{ old('entry_date', now()->format('Y-m-d')) }}" required>
                        <small class="text-muted">Tidak boleh tanggal yang belum terjadi.</small>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="entry-description">Keterangan</label>
                        <input type="text" id="entry-description" name="description" class="form-control" maxlength="255" value="{{ old('description') }}" placeholder="Contoh: Pembelian bahan baku tunai" required>
                    </div>
                </div>

                <template id="account-options">
                    <option value="">-- Pilih Akun --</option>
                    @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $accountLabel($account) }}</option>
                    @endforeach
                </template>

                <table class="table" id="jurnal-lines-table">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Akun</th>
                            <th>Debit (Rp)</th>
                            <th>Kredit (Rp)</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="jurnal-lines-body">
                        @foreach($oldLines as $i => $line)
                        <tr class="jurnal-line">
                            <td>
                                <select name="lines[{{ $i }}][account_id]" class="form-select select2 account-select" required>
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" @selected((string) ($line['account_id'] ?? '') === (string) $account->id)>{{ $accountLabel($account) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="text" name="lines[{{ $i }}][debit]" class="form-control rupiah-input line-debit" data-decimals="2" placeholder="0" value="{{ $line['debit'] ?? '' }}"></td>
                            <td><input type="text" name="lines[{{ $i }}][credit]" class="form-control rupiah-input line-credit" data-decimals="2" placeholder="0" value="{{ $line['credit'] ?? '' }}"></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-line" title="Hapus baris">
                                    <i class="ri-close-line"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <button type="button" class="btn btn-outline-secondary btn-sm" id="add-line">
                    <i class="ri-add-line me-1"></i> Tambah Baris
                </button>
                <small class="text-muted ms-2">Setiap baris isi salah satu saja: Debit atau Kredit.</small>

                <div class="row mt-4 g-3" id="jurnal-footer">
                    <div class="col-md-4">
                        <div class="text-muted small">Total Debit</div>
                        <div class="fs-5 fw-bold" id="total-debit">Rp 0</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Total Kredit</div>
                        <div class="fs-5 fw-bold" id="total-credit">Rp 0</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Selisih</div>
                        <div class="fs-5 fw-bold" id="jurnal-balance-hint"></div>
                    </div>
                </div>
                <div class="mt-2 small text-muted" id="jurnal-blocker"></div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <a href="{{ $routes['index'] }}" class="btn btn-outline-secondary me-2">Batal</a>
                <button type="submit" class="btn btn-primary" id="submit-journal" disabled>Simpan Jurnal</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const jq = window.$ || window.jQuery;
        const form = document.getElementById('jurnal-form');
        const body = document.getElementById('jurnal-lines-body');
        const submitButton = document.getElementById('submit-journal');
        const dateInput = document.getElementById('entry-date');
        const descriptionInput = document.getElementById('entry-description');
        const optionsHtml = document.getElementById('account-options').innerHTML;
        const MIN_ROWS = 2;
        let index = body.querySelectorAll('.jurnal-line').length;

        const rp = function (cents) { return 'Rp ' + MitraForm.formatDecimal(cents / 100, 2); };
        const amountOf = function (input) { return Math.round((MitraForm.toDecimalNumber(input.value) || 0) * 100); };

        function syncRowSides(row) {
            const debit = row.querySelector('.line-debit');
            const credit = row.querySelector('.line-credit');
            const hasDebit = amountOf(debit) > 0;
            const hasCredit = amountOf(credit) > 0;

            debit.disabled = hasCredit && !hasDebit;
            credit.disabled = hasDebit && !hasCredit;
            if (hasDebit && hasCredit) {
                credit.value = '';
                credit.disabled = true;
            }
        }

        function onSideInput(event) {
            const input = event.target;
            if (!input.classList.contains('line-debit') && !input.classList.contains('line-credit')) return;
            const row = input.closest('.jurnal-line');
            const other = row.querySelector(input.classList.contains('line-debit') ? '.line-credit' : '.line-debit');
            if (amountOf(input) > 0) other.value = '';
            syncRowSides(row);
        }

        function setBalanceHint(totalDebit, totalCredit) {
            const hint = document.getElementById('jurnal-balance-hint');
            const diff = totalDebit - totalCredit;

            if (diff === 0 && totalDebit > 0) {
                hint.className = 'fs-5 fw-bold';
                hint.innerHTML = '<span class="badge bg-success fs-6">Seimbang</span>';
                return;
            }
            if (diff === 0) {
                hint.className = 'fs-5 fw-bold text-muted';
                hint.textContent = 'Rp 0';
                return;
            }
            hint.className = 'fs-5 fw-bold text-danger';
            hint.textContent = 'Selisih ' + rp(Math.abs(diff)) + ' - kurang di ' + (diff > 0 ? 'Kredit' : 'Debit');
        }

        function describeBlocker(rows, totalDebit, totalCredit) {
            if (rows.length < MIN_ROWS) return 'Jurnal minimal punya 2 baris.';
            const incomplete = rows.findIndex(function (r) { return !r.hasAccount || r.amount <= 0; });
            if (incomplete >= 0) return 'Baris ke-' + (incomplete + 1) + ' belum lengkap: pilih akun dan isi jumlah Debit atau Kredit.';
            if (totalDebit !== totalCredit) return 'Total Debit dan Total Kredit harus sama sebelum bisa disimpan.';
            return '';
        }

        function refresh() {
            const rowEls = body.querySelectorAll('.jurnal-line');
            let totalDebit = 0, totalCredit = 0;
            const rows = [];

            rowEls.forEach(function (row) {
                const debit = amountOf(row.querySelector('.line-debit'));
                const credit = amountOf(row.querySelector('.line-credit'));
                totalDebit += debit;
                totalCredit += credit;
                rows.push({ hasAccount: !!row.querySelector('.account-select').value, amount: debit + credit });

                const removeButton = row.querySelector('.remove-line');
                const atMinimum = rowEls.length <= MIN_ROWS;
                removeButton.disabled = atMinimum;
                removeButton.title = atMinimum ? 'Jurnal minimal punya 2 baris, baris ini tidak bisa dihapus' : 'Hapus baris';
            });

            document.getElementById('total-debit').textContent = rp(totalDebit);
            document.getElementById('total-credit').textContent = rp(totalCredit);
            setBalanceHint(totalDebit, totalCredit);

            const blocker = describeBlocker(rows, totalDebit, totalCredit);
            document.getElementById('jurnal-blocker').textContent = blocker;
            if (!form.dataset.submitting) submitButton.disabled = blocker !== '';

            updateConfirmation(totalDebit);
        }

        function formatDateId(value) {
            const parts = value.split('-');
            return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : value;
        }

        function isOtherMonth(value) {
            const now = new Date();
            const current = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
            return value.length >= 7 && value.slice(0, 7) !== current;
        }

        function updateConfirmation(totalDebit) {
            const clean = function (text) { return String(text).replace(/\|/g, '/'); };
            const lines = [
                'Tanggal: ' + formatDateId(dateInput.value),
                'Keterangan: ' + clean(descriptionInput.value),
                'Total: ' + rp(totalDebit),
            ];
            if (dateInput.value && isOtherMonth(dateInput.value)) {
                lines.push('PERHATIAN: tanggal ini bukan di bulan ini. Pastikan tanggalnya sudah benar.');
            }
            lines.push('Jurnal tidak bisa diubah setelah disimpan. Jika salah, buat jurnal pembalik.');
            form.setAttribute('data-confirm-text', lines.join('|'));
        }

        function initSelect2(scope) {
            if (!jq) return;
            jq(scope).find('.select2').select2({
                placeholder: '-- Pilih Akun --',
                allowClear: true,
                width: '100%',
            });
        }

        document.getElementById('add-line').addEventListener('click', function () {
            // Built from scratch (not cloneNode) — select2 injects sibling
            // markup next to the original <select> that cloning would
            // duplicate and break on re-init.
            const i = index++;
            const row = document.createElement('tr');
            row.className = 'jurnal-line';
            row.innerHTML =
                '<td><select name="lines[' + i + '][account_id]" class="form-select select2 account-select" required>' + optionsHtml + '</select></td>' +
                '<td><input type="text" name="lines[' + i + '][debit]" class="form-control rupiah-input line-debit" data-decimals="2" placeholder="0"></td>' +
                '<td><input type="text" name="lines[' + i + '][credit]" class="form-control rupiah-input line-credit" data-decimals="2" placeholder="0"></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger remove-line" title="Hapus baris"><i class="ri-close-line"></i></button></td>';
            body.appendChild(row);
            MitraForm.initRupiahInputs(row);
            initSelect2(row);
            refresh();
        });

        body.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-line');
            if (!btn || btn.disabled) return;
            if (body.querySelectorAll('.jurnal-line').length <= MIN_ROWS) return;
            if (jq) jq(btn.closest('.jurnal-line')).find('.select2').select2('destroy');
            btn.closest('.jurnal-line').remove();
            refresh();
        });

        body.addEventListener('input', function (e) {
            onSideInput(e);
            refresh();
        });
        body.addEventListener('change', refresh);
        if (jq) jq(body).on('change select2:select select2:clear', '.account-select', refresh);
        dateInput.addEventListener('input', refresh);
        descriptionInput.addEventListener('input', refresh);

        body.querySelectorAll('.jurnal-line').forEach(syncRowSides);
        initSelect2(body);
        refresh();
    });
</script>
@endsection
