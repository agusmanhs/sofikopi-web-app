@extends('layouts/layoutMaster')

@section('title', 'Pengaturan Absensi')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Absensi /</span> Pengaturan Absensi
         </h4>
      </div>

      <div class="row">
         <div class="col-md-9 mx-auto">
            <div class="alert alert-info d-flex align-items-start" role="alert">
               <i class="ri-information-line me-2 mt-1"></i>
               <div>
                  Pengaturan di halaman ini berlaku <strong>global untuk semua divisi dan semua shift</strong>.
                  Semua nilai dihitung relatif terhadap jam shift masing-masing pegawai, bukan jam tetap.
               </div>
            </div>

            <div class="card">
               <div class="card-header">
                  <h5 class="mb-0">Batas Waktu Absensi &amp; Pengajuan Izin</h5>
               </div>
               <div class="card-body">
                  @if ($settings->isEmpty())
                     <div class="alert alert-warning mb-0">
                        Data pengaturan belum tersedia. Jalankan
                        <code>php artisan db:seed --class=SettingSeeder</code> terlebih dahulu.
                     </div>
                  @else
                     <form action="{{ route('pengaturan-absensi.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        @foreach ($settings as $setting)
                           @php
                              $inputName = 'settings[' . $setting->key . ']';
                              $errorKey = 'settings.' . $setting->key;
                           @endphp
                           <div class="mb-4">
                              <label class="form-label" for="{{ $setting->key }}">
                                 {{ $setting->label }} <span class="text-danger">*</span>
                              </label>
                              <div class="input-group">
                                 <input type="number" class="form-control @error($errorKey) is-invalid @enderror"
                                    id="{{ $setting->key }}" name="{{ $inputName }}"
                                    value="{{ old($errorKey, $setting->value) }}" min="1" max="24" required>
                                 <span class="input-group-text">jam</span>
                                 @error($errorKey)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                              </div>
                              @if ($setting->keterangan)
                                 <div class="form-text">{{ $setting->keterangan }}</div>
                              @endif
                           </div>
                        @endforeach

                        <div class="mt-4">
                           <button type="submit" class="btn btn-primary me-2">
                              <i class="ri-save-line me-1"></i>Simpan Pengaturan
                           </button>
                           <a href="{{ route('absensi.dashboard') }}" class="btn btn-label-secondary">Batal</a>
                        </div>
                     </form>
                  @endif
               </div>
            </div>
         </div>
      </div>
   </div>
@endsection
