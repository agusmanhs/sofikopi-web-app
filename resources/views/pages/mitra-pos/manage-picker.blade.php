@extends('layouts/layoutMaster')

@section('title', 'Kelola Mitra POS')

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
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS /</span> Kelola Mitra</h4>
        <div class="d-flex align-items-center gap-2">
            @can('access', ['mitra-pos-manage.index', 'delete'])
            <div class="form-check me-2">
                <input type="checkbox" class="form-check-input" id="selectAllMitras">
                <label class="form-check-label" for="selectAllMitras">Pilih Semua</label>
            </div>
            <button type="button" class="btn btn-outline-danger" id="btnBulkDelete" disabled>
                <i class="ri-delete-bin-line me-1"></i> Hapus Terpilih (<span id="bulkSelectedCount">0</span>)
            </button>
            @endcan
            @can('access', ['mitra-pos-manage.index', 'create'])
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddMitra">
                <i class="ri-add-line me-1"></i> Tambah Mitra ke POS
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

    <div class="row g-4">
        @forelse($mitras as $mitra)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-start gap-2">
                            @can('access', ['mitra-pos-manage.index', 'delete'])
                            <input type="checkbox" class="mitra-select-checkbox form-check-input mt-1" value="{{ $mitra->id }}" data-name="{{ $mitra->name }}">
                            @endcan
                            <div>
                                <h5 class="mb-1">{{ $mitra->name }}</h5>
                                <span class="badge bg-label-primary">{{ $mitra->code }}</span>
                            </div>
                        </div>
                        <span class="badge {{ $mitra->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                            {{ $mitra->is_active ? 'Aktif' : 'Non-Aktif' }}
                        </span>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="ri-user-line me-2 text-muted"></i>
                            <span>{{ $mitra->pic ?? '-' }}</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="ri-phone-line me-2 text-muted"></i>
                            <span>{{ $mitra->phone ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('mitra-material.index', $mitra) }}" class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="ri-archive-line me-1"></i> Material
                        </a>
                        <a href="{{ route('mitra-product.index', $mitra) }}" class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="ri-cup-line me-1"></i> Produk
                        </a>
                        @can('access', ['mitra-pos-manage.index', 'delete'])
                        <form action="{{ route('mitra-pos-manage.destroy', $mitra) }}" method="POST" class="form-remove-mitra" data-code="{{ $mitra->code }}" data-name="{{ $mitra->name }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus mitra ini dari sistem POS">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">
                Belum ada mitra yang ditambahkan ke sistem POS.
                @can('access', ['mitra-pos-manage.index', 'create'])
                Klik <b>"Tambah Mitra ke POS"</b> untuk mulai.
                @endcan
            </div>
        </div>
        @endforelse
    </div>

    @can('access', ['mitra-pos-manage.index', 'delete'])
    <form id="bulkDeleteForm" action="{{ route('mitra-pos-manage.destroy-bulk') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
        <div id="bulkMitraIdsContainer"></div>
    </form>
    @endcan
</div>

<!-- Add Mitra Modal -->
<div class="modal fade" id="modalAddMitra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('mitra-pos-manage.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Mitra ke Sistem POS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Pilih Mitra</label>
                <select name="mitra_id" class="form-select select2" required>
                    <option value="">-- Pilih Mitra --</option>
                    @forelse($availableMitras as $available)
                    <option value="{{ $available->id }}">{{ $available->code }} - {{ $available->name }}</option>
                    @empty
                    <option value="" disabled>Semua mitra aktif sudah terdaftar</option>
                    @endforelse
                </select>
                <small class="text-muted d-block mt-2">
                    Hanya menambahkan mitra ke sistem POS — material dan produk diisi terpisah setelah ini.
                    Mitra yang tidak dicentang di sini tetap ada di Data Master, tidak terpengaruh.
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" @if($availableMitras->isEmpty()) disabled @endif>Tambahkan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jq = window.$ || window.jQuery;
        if (jq) {
            jq('#modalAddMitra .select2').each(function () {
                const $this = jq(this);
                $this.wrap('<div class="position-relative"></div>').select2({
                    placeholder: '-- Pilih Mitra --',
                    allowClear: true,
                    dropdownParent: $this.parent()
                });
            });

            jq('.form-remove-mitra').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const code = form.dataset.code;
                const name = form.dataset.name;

                Swal.fire({
                    title: 'Hapus mitra dari sistem POS?',
                    html: `<b>${name}</b> akan hilang dari daftar Kelola Mitra POS, dan seluruh <b>transaksi, stok, produk, dan material</b> miliknya akan dihapus PERMANEN.<br><br>` +
                          `Yang <u>tidak</u> ikut terhapus: data master mitra (tetap ada di Data Master → Mitra), user, riwayat kunjungan, dan sales order.<br><br>` +
                          `Ketik kode mitra <code>${code}</code> untuk konfirmasi:`,
                    icon: 'warning',
                    input: 'text',
                    inputPlaceholder: code,
                    showCancelButton: true,
                    confirmButtonText: 'Hapus Permanen',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                    preConfirm: (value) => {
                        if (value !== code) {
                            Swal.showValidationMessage('Kode mitra tidak cocok.');
                            return false;
                        }
                        return true;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            function updateBulkDeleteState() {
                const checked = jq('.mitra-select-checkbox:checked');
                jq('#bulkSelectedCount').text(checked.length);
                jq('#btnBulkDelete').prop('disabled', checked.length === 0);
            }

            jq('#selectAllMitras').on('change', function() {
                jq('.mitra-select-checkbox').prop('checked', this.checked);
                updateBulkDeleteState();
            });

            jq('.mitra-select-checkbox').on('change', function() {
                updateBulkDeleteState();
            });

            jq('#btnBulkDelete').on('click', function(e) {
                e.preventDefault();
                const checked = jq('.mitra-select-checkbox:checked');
                if (checked.length === 0) {
                    return;
                }

                const selected = checked.map(function() {
                    return { id: this.value, name: this.dataset.name };
                }).get();

                const namesList = selected.map(m => `<li>${m.name}</li>`).join('');

                Swal.fire({
                    title: `Hapus ${selected.length} mitra dari sistem POS?`,
                    html: `Mitra berikut akan hilang dari daftar Kelola Mitra POS, dan seluruh <b>transaksi, stok, produk, dan material</b> miliknya akan dihapus PERMANEN:<br><br>` +
                          `<ul class="text-start">${namesList}</ul>` +
                          `Yang <u>tidak</u> ikut terhapus: data master mitra (tetap ada di Data Master &rarr; Mitra), user, riwayat kunjungan, dan sales order.<br><br>` +
                          `Ketik <code>HAPUS</code> untuk konfirmasi:`,
                    icon: 'warning',
                    input: 'text',
                    inputPlaceholder: 'HAPUS',
                    showCancelButton: true,
                    confirmButtonText: 'Hapus Permanen',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                    preConfirm: (value) => {
                        if (value !== 'HAPUS') {
                            Swal.showValidationMessage('Ketik HAPUS untuk konfirmasi.');
                            return false;
                        }
                        return true;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const container = document.getElementById('bulkMitraIdsContainer');
                        container.innerHTML = '';
                        selected.forEach(m => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'mitra_ids[]';
                            input.value = m.id;
                            container.appendChild(input);
                        });
                        document.getElementById('bulkDeleteForm').submit();
                    }
                });
            });
        }
    });
</script>
@endsection
