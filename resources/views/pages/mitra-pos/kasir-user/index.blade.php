@extends('layouts/layoutMaster')

@section('title', 'User Kasir')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Mitra POS /</span> User Kasir</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createKasirModal" {{ $kasirs->count() >= $maxKasir ? 'disabled' : '' }}>
            <i class="ri-user-add-line me-1"></i> Tambah Kasir
        </button>
    </div>

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
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Kasir</h5>
            <span class="badge {{ $kasirs->count() >= $maxKasir ? 'bg-label-warning' : 'bg-label-secondary' }}">{{ $kasirs->count() }} / {{ $maxKasir }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Dibuat</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kasirs as $kasir)
                    <tr>
                        <td>{{ $kasir->name }}</td>
                        <td>{{ $kasir->email }}</td>
                        <td>{{ $kasir->created_at->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#editKasirModal"
                                data-action="{{ route('mitra-kasir-user.update', $kasir) }}"
                                data-name="{{ $kasir->name }}"
                                data-email="{{ $kasir->email }}">
                                <i class="ri-edit-line"></i>
                            </button>
                            <form action="{{ route('mitra-kasir-user.destroy', $kasir) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Hapus user kasir {{ $kasir->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">Belum ada user kasir.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create modal --}}
<div class="modal fade" id="createKasirModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('mitra-kasir-user.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah User Kasir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" minlength="6" required>
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
        <form method="POST" id="editKasirForm" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit User Kasir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" id="editKasirName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="editKasirEmail" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
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
<script>
    document.getElementById('editKasirModal').addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('editKasirForm').action = btn.dataset.action;
        document.getElementById('editKasirName').value = btn.dataset.name;
        document.getElementById('editKasirEmail').value = btn.dataset.email;
    });
</script>
@endsection
