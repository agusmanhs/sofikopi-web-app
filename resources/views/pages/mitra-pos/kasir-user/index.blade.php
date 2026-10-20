@extends('layouts/layoutMaster')

@section('title', 'User Kasir')

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
    $isFull = $kasirs->count() >= $maxKasir;
    $reopen = old('_form');
    $reopenKasirId = (int) old('_kasir_id');
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS /</span> User Kasir</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createKasirModal" {{ $isFull ? 'disabled' : '' }}>
            <i class="ri-user-add-line me-1"></i> Tambah Kasir
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($isFull)
    <div class="alert alert-warning">
        <strong>Batas kasir tercapai ({{ $kasirs->count() }} dari {{ $maxKasir }}).</strong>
        Kasir yang sudah punya riwayat transaksi tidak bisa dihapus agar laporan tetap akurat. Untuk mengosongkan satu slot kasir, hubungi admin Sofikopi.
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

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Kasir</h5>
            <span class="badge {{ $isFull ? 'bg-label-warning' : 'bg-label-secondary' }}">{{ $kasirs->count() }} dari {{ $maxKasir }} kasir</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Dibuat</th>
                        <th class="text-end">Jumlah Transaksi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kasirs as $kasir)
                    @php $trxCount = $transactionCounts[$kasir->id] ?? 0; @endphp
                    <tr>
                        <td>{{ $kasir->name }}</td>
                        <td>{{ $kasir->email }}</td>
                        <td>{{ $kasir->created_at->format('d/m/Y') }}</td>
                        <td class="text-end">{{ number_format($trxCount, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#editKasirModal"
                                data-id="{{ $kasir->id }}"
                                data-action="{{ route('mitra-kasir-user.update', $kasir) }}"
                                data-name="{{ $kasir->name }}"
                                data-email="{{ $kasir->email }}"
                                title="Ubah nama, email, atau kata sandi">
                                <i class="ri-edit-line"></i>
                            </button>
                            @if ($trxCount > 0)
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                title="Sudah punya {{ $trxCount }} transaksi, tidak bisa dihapus. Untuk mencabut akses, ganti kata sandinya.">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                            @else
                            <form action="{{ route('mitra-kasir-user.destroy', $kasir) }}" method="POST" class="d-inline"
                                data-lock-submit
                                data-confirm-title="Hapus kasir {{ $kasir->name }}?"
                                data-confirm-text="Akun {{ $kasir->email }} tidak bisa masuk lagi.|Kasir ini belum pernah membuat transaksi, jadi riwayat transaksi tidak terpengaruh.|Tindakan ini tidak bisa dibatalkan."
                                data-confirm-button="Ya, hapus kasir"
                                data-confirm-danger>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus kasir">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">Belum ada user kasir.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-muted">
            Kasir yang sudah pernah membuat transaksi tidak bisa dihapus supaya riwayat transaksi tetap utuh. Untuk mencabut akses kasir seperti itu, ganti kata sandinya.
        </div>
    </div>
</div>

{{-- Create modal --}}
<div class="modal fade" id="createKasirModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('mitra-kasir-user.store') }}" class="modal-content" data-lock-submit>
            @csrf
            <input type="hidden" name="_form" value="create">
            <div class="modal-header">
                <h5 class="modal-title">Tambah User Kasir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ $reopen === 'create' ? old('name') : '' }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ $reopen === 'create' ? old('email') : '' }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kata Sandi</label>
                    <input type="password" name="password" class="form-control" minlength="6" placeholder="Minimal 6 karakter" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit modal --}}
<div class="modal fade" id="editKasirModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="editKasirForm" class="modal-content" data-lock-submit
            @if ($reopen === 'edit' && $reopenKasirId) action="{{ route('mitra-kasir-user.update', $reopenKasirId) }}" @endif>
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit">
            <input type="hidden" name="_kasir_id" id="editKasirId" value="{{ $reopen === 'edit' ? $reopenKasirId : '' }}">
            <div class="modal-header">
                <h5 class="modal-title">Edit User Kasir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" id="editKasirName" class="form-control" value="{{ $reopen === 'edit' ? old('name') : '' }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="editKasirEmail" class="form-control" value="{{ $reopen === 'edit' ? old('email') : '' }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kata Sandi Baru</label>
                    <input type="password" name="password" class="form-control" minlength="6" placeholder="Kosongkan jika tidak diubah">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-script')
@include('pages.mitra-pos._form-safety')
<script>
    document.getElementById('editKasirModal').addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;
        document.getElementById('editKasirForm').action = btn.dataset.action;
        document.getElementById('editKasirId').value = btn.dataset.id;
        document.getElementById('editKasirName').value = btn.dataset.name;
        document.getElementById('editKasirEmail').value = btn.dataset.email;
    });

    @if ($reopen === 'create' || ($reopen === 'edit' && $reopenKasirId))
    document.addEventListener('DOMContentLoaded', function () {
        const target = @json($reopen === 'edit' ? 'editKasirModal' : 'createKasirModal');
        new bootstrap.Modal(document.getElementById(target)).show();
    });
    @endif
</script>
@endsection
