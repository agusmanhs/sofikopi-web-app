@extends('layouts/layoutMaster')

@section('title', 'Jurnal Manual')

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

    <div class="card">
        <form action="{{ $routes['store'] }}" method="POST" id="jurnal-form">
            @csrf
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="Contoh: Pembelian bahan baku tunai" required>
                    </div>
                </div>

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
                        @for($i = 0; $i < 2; $i++)
                        <tr class="jurnal-line">
                            <td>
                                <select name="lines[{{ $i }}][account_id]" class="form-select select2 account-select" required>
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" class="form-control"></td>
                            <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" class="form-control"></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-line" title="Hapus baris">
                                    <i class="ri-close-line"></i>
                                </button>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>

                <button type="button" class="btn btn-outline-secondary btn-sm" id="add-line">
                    <i class="ri-add-line me-1"></i> Tambah Baris
                </button>

                <div class="mt-3">
                    <small class="text-muted">Total debit harus sama dengan total kredit sebelum disimpan.</small>
                    <div class="fw-bold" id="jurnal-balance-hint"></div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <a href="{{ $routes['index'] }}" class="btn btn-outline-secondary me-2">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Jurnal</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const jq = window.$ || window.jQuery;
        const body = document.getElementById('jurnal-lines-body');
        let index = body.querySelectorAll('.jurnal-line').length;

        // Options string baked once at render time — new rows reuse it
        // instead of re-querying $accounts, same idea as product/create's
        // ingredient-material select2 rows.
        const accountOptionsHtml = document.querySelector('.account-select').innerHTML;

        function updateBalanceHint() {
            let debit = 0, credit = 0;
            body.querySelectorAll('.jurnal-line').forEach(function (row) {
                debit += parseFloat(row.querySelector('[name$="[debit]"]').value || 0);
                credit += parseFloat(row.querySelector('[name$="[credit]"]').value || 0);
            });
            const hint = document.getElementById('jurnal-balance-hint');
            const balanced = Math.abs(debit - credit) < 0.01;
            hint.textContent = 'Debit: ' + debit.toLocaleString('id-ID') + ' | Kredit: ' + credit.toLocaleString('id-ID') + (balanced ? ' — Balance ✓' : ' — Belum balance');
            hint.className = 'fw-bold ' + (balanced ? 'text-success' : 'text-danger');
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
                '<td><select name="lines[' + i + '][account_id]" class="form-select select2 account-select" required>' + accountOptionsHtml + '</select></td>' +
                '<td><input type="number" step="0.01" min="0" name="lines[' + i + '][debit]" class="form-control"></td>' +
                '<td><input type="number" step="0.01" min="0" name="lines[' + i + '][credit]" class="form-control"></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger remove-line" title="Hapus baris"><i class="ri-close-line"></i></button></td>';
            body.appendChild(row);
            initSelect2(row);
        });

        body.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-line');
            if (!btn) return;
            if (body.querySelectorAll('.jurnal-line').length <= 2) return;
            if (jq) jq(btn.closest('.jurnal-line')).find('.select2').select2('destroy');
            btn.closest('.jurnal-line').remove();
            updateBalanceHint();
        });

        body.addEventListener('input', updateBalanceHint);
        initSelect2(body);
        updateBalanceHint();
    });
</script>
@endsection
