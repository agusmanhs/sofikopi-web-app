@extends('layouts/layoutMaster')

@section('title', 'Edit Hari Libur')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Master Data / Hari Libur /</span> Edit
         </h4>
         <a href="{{ route('hari-libur.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Kembali
         </a>
      </div>

      <div class="card">
         <div class="card-body">
            <form action="{{ route('hari-libur.update', $data->id) }}" method="POST">
               @csrf
               @method('PUT')
               <div class="row">
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Tanggal</label>
                     <input type="date" name="tanggal" class="form-control" required
                        value="{{ old('tanggal', $data->tanggal->format('Y-m-d')) }}">
                  </div>
                  <div class="col-md-6 mb-3">
                     <label class="form-label">Nama Libur</label>
                     <input type="text" name="nama" class="form-control" required
                        value="{{ old('nama', $data->nama) }}" placeholder="Contoh: Gathering Kantor">
                  </div>
                  <div class="col-12 mb-3">
                     <label class="form-label">Deskripsi (Opsional)</label>
                     <textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $data->deskripsi) }}</textarea>
                  </div>
                  <div class="col-md-6 mb-3">
                     <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_nasional" value="1" id="is_nasional"
                           {{ $data->is_nasional ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_nasional">Libur Nasional (Blokir Absen)</label>
                        <div class="form-text">Jika dicentang, karyawan tidak akan bisa absen pada tanggal ini.</div>
                     </div>
                  </div>
                  <div class="col-md-6 mb-3">
                     <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_cuti_bersama" value="1"
                           id="is_cuti_bersama" {{ $data->is_cuti_bersama ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_cuti_bersama">Cuti Bersama</label>
                     </div>
                  </div>
                  <div class="col-12 text-end">
                     <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                  </div>
               </div>
            </form>
         </div>
      </div>
   </div>
@endsection
