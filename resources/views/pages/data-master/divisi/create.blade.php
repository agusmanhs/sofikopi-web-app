@extends('layouts/layoutMaster')

@section('title', 'Tambah Divisi')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Data Master / Divisi /</span> Tambah
         </h4>
      </div>

      <div class="row">
         <div class="col-md-8 mx-auto">
            <div class="card">
               <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">Form Tambah Divisi</h5>
                  <a href="{{ route('divisi.index') }}" class="btn btn-sm btn-outline-secondary">
                     <i class="ri-arrow-left-line me-1"></i>Kembali
                  </a>
               </div>
               <div class="card-body">
                  <form action="{{ route('divisi.store') }}" method="POST">
                     @csrf
                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label" for="kode">Kode Divisi</label>
                           <input type="text" class="form-control @error('kode') is-invalid @enderror" id="kode"
                              name="kode" value="{{ old('kode') }}" placeholder="IT">
                           @error('kode')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label" for="nama">Nama Divisi <span class="text-danger">*</span></label>
                           <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama"
                              name="nama" value="{{ old('nama') }}" placeholder="Information Technology" required>
                           @error('nama')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>

                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label" for="toleransi_terlambat">Toleransi Terlambat (menit)</label>
                           <input type="number" class="form-control @error('toleransi_terlambat') is-invalid @enderror"
                              id="toleransi_terlambat" name="toleransi_terlambat"
                              value="{{ old('toleransi_terlambat', 15) }}" min="0" max="120">
                           @error('toleransi_terlambat')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>

                     <div class="mb-3">
                        <div class="form-check form-switch">
                           <input class="form-check-input" type="checkbox" id="is_aktif" name="is_aktif" value="1"
                              {{ old('is_aktif', 1) ? 'checked' : '' }}>
                           <label class="form-check-label" for="is_aktif">Aktifkan Divisi</label>
                        </div>
                     </div>

                     <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">Simpan</button>
                        <button type="reset" class="btn btn-label-secondary">Reset</button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>
   </div>
@endsection
