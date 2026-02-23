@extends('layouts/layoutMaster')

@section('title', 'Edit Jenis Izin')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Data Master / Jenis Izin /</span> Edit
         </h4>
      </div>

      <div class="row">
         <div class="col-md-8 mx-auto">
            <div class="card">
               <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">Edit Jenis Izin: {{ $data->nama }}</h5>
                  <a href="{{ route('jenis-izin.index') }}" class="btn btn-sm btn-outline-secondary">
                     <i class="ri-arrow-left-line me-1"></i>Kembali
                  </a>
               </div>
               <div class="card-body">
                  <form action="{{ route('jenis-izin.update', $data->id) }}" method="POST">
                     @csrf
                     @method('PUT')
                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label" for="kode">Kode</label>
                           <input type="text" class="form-control @error('kode') is-invalid @enderror" id="kode"
                              name="kode" value="{{ old('kode', $data->kode) }}">
                           @error('kode')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label" for="nama">Nama Jenis Izin <span
                                 class="text-danger">*</span></label>
                           <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama"
                              name="nama" value="{{ old('nama', $data->nama) }}" required>
                           @error('nama')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>

                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label" for="max_hari">Maksimal Hari</label>
                           <input type="number" class="form-control @error('max_hari') is-invalid @enderror"
                              id="max_hari" name="max_hari" value="{{ old('max_hari', $data->max_hari) }}" min="1">
                           @error('max_hari')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                           <small class="text-muted">Kosongkan jika tidak ada batasan hari</small>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-center">
                           <div class="form-check form-switch mt-4">
                              <input class="form-check-input" type="checkbox" id="butuh_surat" name="butuh_surat"
                                 value="1" {{ old('butuh_surat', $data->butuh_surat) ? 'checked' : '' }}>
                              <label class="form-check-label" for="butuh_surat">Wajib Melampirkan Surat</label>
                           </div>
                        </div>
                     </div>

                     <div class="mb-3">
                        <label class="form-label" for="keterangan">Keterangan</label>
                        <textarea class="form-control @error('keterangan') is-invalid @enderror" id="keterangan" name="keterangan"
                           rows="3">{{ old('keterangan', $data->keterangan) }}</textarea>
                        @error('keterangan')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     <div class="mb-3">
                        <div class="form-check form-switch">
                           <input class="form-check-input" type="checkbox" id="is_aktif" name="is_aktif" value="1"
                              {{ old('is_aktif', $data->is_aktif) ? 'checked' : '' }}>
                           <label class="form-check-label" for="is_aktif">Aktifkan Jenis Izin</label>
                        </div>
                     </div>

                     <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">Update</button>
                        <a href="{{ route('jenis-izin.index') }}" class="btn btn-label-secondary">Batal</a>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>
   </div>
@endsection
