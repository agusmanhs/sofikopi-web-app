@extends('layouts/layoutMaster')

@section('title', 'Daftar Akun (COA)' . (isset($mitra) ? ' - ' . $mitra->name : ''))

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
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS / Akuntansi /</span> Daftar Akun (COA)</h4>

    @include('pages.mitra-pos.akuntansi._nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Akun</h5>
            <small class="text-muted d-block mb-3">Akun mengikuti template baku PT Sofikopi Group — tidak bisa ditambah/dihapus. Aktifkan akun yang dipakai mitra ini, dan isi saldo awal bila mulai pembukuan di tengah periode berjalan.</small>
            <div class="alert alert-warning py-2 mb-3">
                Mengubah saldo awal langsung mengubah Neraca. Akun yang sudah dipakai di jurnal, punya saldo awal, atau dipakai otomatis oleh kasir/stok tidak bisa dinonaktifkan.
            </div>
            <input type="search" id="coa-search" class="form-control" placeholder="Cari akun (kode atau nama)..." autocomplete="off">
        </div>
        <form action="{{ $routes['update'] }}" method="POST" id="coa-form" data-lock-submit data-confirm-button="Ya, simpan perubahan">
            @csrf
            @method('PUT')
            <div class="table-responsive" style="max-height: 70vh;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="sticky-top bg-white">
                        <tr>
                            <th style="width: 90px;">Kode</th>
                            <th>Nama Akun</th>
                            <th style="width: 120px;">Jenis</th>
                            <th style="width: 180px;">Saldo Awal (Rp)</th>
                            <th style="width: 70px;" class="text-center">Aktif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accounts as $account)
                        @if($account->is_postable)
                        <tr class="coa-row" data-search="{{ mb_strtolower($account->code.' '.$account->name) }}">
                            <td class="text-muted ps-{{ $account->level * 2 }}">{{ $account->code }}</td>
                            <td>
                                <span class="coa-name">{{ $account->name }}</span>
                                @if($account->system_role)
                                <span class="badge bg-label-secondary ms-1" title="Dipakai otomatis oleh kasir/stok, harus tetap aktif">Otomatis</span>
                                @endif
                            </td>
                            <td>{{ \App\Services\MitraPos\AkuntansiCoaService::jenisLabel($account->account_type) }}</td>
                            <td>
                                <input type="text" name="opening_balance[{{ $account->id }}]"
                                    class="form-control form-control-sm rupiah-input text-end" data-decimals="2"
                                    data-original="{{ (float) $account->opening_balance }}"
                                    data-label="{{ $account->code }} {{ $account->name }}"
                                    value="{{ old('opening_balance.' . $account->id, $account->opening_balance) }}">
                            </td>
                            <td class="text-center">
                                @if($account->system_role)
                                <input type="hidden" name="is_active[{{ $account->id }}]" value="1">
                                <input type="checkbox" class="form-check-input" checked disabled title="Dipakai otomatis oleh kasir/stok, harus tetap aktif">
                                @else
                                <input type="checkbox" class="form-check-input" name="is_active[{{ $account->id }}]" value="1"
                                    data-label="{{ $account->code }} {{ $account->name }}"
                                    data-was-active="{{ $account->is_active ? 1 : 0 }}"
                                    @checked(session()->hasOldInput() ? old('is_active.' . $account->id) !== null : $account->is_active)>
                                @endif
                            </td>
                        </tr>
                        @else
                        <tr class="table-light coa-group">
                            <td class="fw-bold ps-{{ $account->level * 2 }}">{{ $account->code }}</td>
                            <td class="fw-bold" colspan="4">{{ $account->name }}</td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-primary" id="coa-submit">Simpan Daftar Akun</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('coa-form');
        const search = document.getElementById('coa-search');
        const submitButton = document.getElementById('coa-submit');
        const activeBoxes = form.querySelectorAll('input[type="checkbox"][name^="is_active"]');
        const originalActive = {};
        const rp = function (number) { return 'Rp ' + MitraForm.formatDecimal(number, 2); };

        activeBoxes.forEach(function (box) { originalActive[box.name] = box.dataset.wasActive === '1'; });

        search.addEventListener('input', function () {
            const term = search.value.trim().toLowerCase();
            document.querySelectorAll('.coa-row').forEach(function (row) {
                row.classList.toggle('d-none', term !== '' && row.dataset.search.indexOf(term) === -1);
            });
            document.querySelectorAll('.coa-group').forEach(function (row) {
                row.classList.toggle('d-none', term !== '');
            });
        });

        function changedBalances() {
            const changes = [];
            form.querySelectorAll('input.rupiah-input').forEach(function (input) {
                const now = Math.round((MitraForm.toDecimalNumber(input.value) || 0) * 100);
                const before = Math.round(Number(input.dataset.original) * 100);
                if (now !== before) {
                    changes.push(input.dataset.label + ': ' + rp(before / 100) + ' menjadi ' + rp(now / 100));
                }
            });
            return changes;
        }

        function changedActive() {
            const changes = [];
            activeBoxes.forEach(function (box) {
                if (box.checked !== originalActive[box.name]) {
                    changes.push(box.dataset.label + ': ' + (box.checked ? 'diaktifkan' : 'dinonaktifkan'));
                }
            });
            return changes;
        }

        function refreshConfirmation() {
            const balances = changedBalances();
            const actives = changedActive();
            const lines = [];

            if (balances.length) {
                lines.push('Saldo awal yang berubah:');
                lines.push.apply(lines, balances);
                lines.push('Mengubah saldo awal langsung mengubah Neraca.');
            }
            if (actives.length) {
                lines.push('Status akun yang berubah:');
                lines.push.apply(lines, actives);
            }

            if (!lines.length) {
                form.removeAttribute('data-confirm-title');
                return false;
            }

            form.setAttribute('data-confirm-title', 'Simpan perubahan Daftar Akun?');
            form.setAttribute('data-confirm-text', lines.map(function (line) { return line.replace(/\|/g, '/'); }).join('|'));
            return true;
        }

        form.addEventListener('input', refreshConfirmation);
        form.addEventListener('change', refreshConfirmation);

        submitButton.addEventListener('click', function (event) {
            if (refreshConfirmation()) return;
            event.preventDefault();
            const message = 'Tidak ada saldo awal atau status akun yang berubah, jadi tidak ada yang perlu disimpan.';
            if (typeof Swal === 'undefined') {
                window.alert(message);
                return;
            }
            Swal.fire({
                title: 'Belum ada yang diubah',
                text: message,
                icon: 'info',
                confirmButtonText: 'Mengerti',
                customClass: { confirmButton: 'btn btn-primary' },
                buttonsStyling: false,
            });
        });

        refreshConfirmation();
    });
</script>
@endsection
