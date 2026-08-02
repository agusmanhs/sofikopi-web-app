@extends('layouts/layoutMaster')

@section('title', 'Chart of Account')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Mitra POS / Akuntansi /</span> Chart of Account</h4>

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
            <small class="text-muted">Akun mengikuti template baku PT Sofikopi Group — tidak bisa ditambah/dihapus. Aktifkan akun yang dipakai mitra ini, dan isi saldo awal bila mulai pembukuan di tengah periode berjalan.</small>
        </div>
        <form action="{{ $routes['update'] }}" method="POST">
            @csrf
            @method('PUT')
            <div class="table-responsive" style="max-height: 70vh;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="sticky-top bg-white">
                        <tr>
                            <th style="width: 90px;">Kode</th>
                            <th>Nama Akun</th>
                            <th style="width: 160px;">Saldo Awal (Rp)</th>
                            <th style="width: 70px;" class="text-center">Aktif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accounts as $account)
                        @if($account->is_postable)
                        <tr>
                            <td class="text-muted ps-{{ $account->level * 2 }}">{{ $account->code }}</td>
                            <td>{{ $account->name }}</td>
                            <td>
                                <input type="number" step="0.01" min="0" name="opening_balance[{{ $account->id }}]"
                                    class="form-control form-control-sm"
                                    value="{{ old('opening_balance.' . $account->id, $account->opening_balance) }}">
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" name="is_active[{{ $account->id }}]" value="1"
                                    @checked(old('is_active.' . $account->id, $account->is_active))>
                            </td>
                        </tr>
                        @else
                        <tr class="table-light">
                            <td class="fw-bold ps-{{ $account->level * 2 }}">{{ $account->code }}</td>
                            <td class="fw-bold" colspan="3">{{ $account->name }}</td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Simpan Chart of Account</button>
            </div>
        </form>
    </div>
</div>
@endsection
