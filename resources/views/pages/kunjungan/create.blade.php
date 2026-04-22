@extends('layouts/layoutMaster')

@section('title', 'Form Kunjungan QC')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Aktivitas /</span> Form Kunjungan QC
         </h4>
      </div>

      @if (session('error'))
         <div class="alert alert-danger alert-dismissible mb-4">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
         </div>
      @endif

      <div class="row">
         <div class="col-lg-8 mx-auto">
            <div class="card">
               <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0"><i class="ri-clipboard-line me-2"></i>Format Quality Control</h5>
                  <a href="{{ route('aktivitas.riwayat.index') }}" class="btn btn-sm btn-outline-secondary">
                     <i class="ri-arrow-left-line me-1"></i>Kembali
                  </a>
               </div>
               <div class="card-body">
                  <form action="{{ route('aktivitas.kunjungan.store') }}" method="POST" enctype="multipart/form-data" id="formKunjungan">
                     @csrf

                     {{-- Jenis Kunjungan --}}
                     <div class="mb-3">
                        <label class="form-label" for="visit_type">
                           Jenis Kunjungan <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('visit_type') is-invalid @enderror" 
                           id="visit_type" name="visit_type" required>
                           <option value="">-- Pilih Jenis Kunjungan --</option>
                           <option value="routine" {{ old('visit_type') == 'routine' ? 'selected' : '' }}>Kunjungan Rutin</option>
                           <option value="by_request" {{ old('visit_type') == 'by_request' ? 'selected' : '' }}>By Request</option>
                        </select>
                        @error('visit_type')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     {{-- Tanggal Kunjungan --}}
                     <div class="mb-3">
                        <label class="form-label" for="tanggal_kunjungan">
                           Tanggal Kunjungan <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control @error('tanggal_kunjungan') is-invalid @enderror"
                           id="tanggal_kunjungan" name="tanggal_kunjungan"
                           value="{{ old('tanggal_kunjungan', date('Y-m-d')) }}" required>
                        @error('tanggal_kunjungan')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     {{-- Nama Outlet (dari Mitra) --}}
                     <div class="mb-3">
                        <label class="form-label" for="mitra_id">
                           Outlet Name <span class="text-danger">*</span>
                        </label>
                        <select class="form-select select2 @error('mitra_id') is-invalid @enderror"
                           id="mitra_id" name="mitra_id" required>
                           <option value="">-- Pilih Outlet / Mitra --</option>
                           @foreach ($mitras as $mitra)
                              <option value="{{ $mitra->id }}"
                                 data-lat="{{ $mitra->latitude }}"
                                 data-lng="{{ $mitra->longitude }}"
                                 data-address="{{ $mitra->address }}"
                                 {{ old('mitra_id') == $mitra->id ? 'selected' : '' }}>
                                 {{ $mitra->name }}
                                 @if($mitra->category)
                                    ({{ $mitra->category->name }})
                                 @endif
                              </option>
                           @endforeach
                        </select>
                        @error('mitra_id')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="outlet-info" class="mt-2 d-none">
                           <small class="text-muted">
                               <i class="ri-map-pin-line me-1"></i>
                               <span id="outlet-address"></span>
                           </small>
                        </div>
                     </div>

                     {{-- Espresso Calibration --}}
                     <div class="mb-3">
                        <label class="form-label" for="espresso_calibration">
                           Espresso Calibration <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('espresso_calibration') is-invalid @enderror"
                           id="espresso_calibration" name="espresso_calibration" rows="3"
                           placeholder="Contoh: SPRO SOFIA Roast date 30 April 2025 18gr yield 35 ml">{{ old('espresso_calibration') }}</textarea>
                        @error('espresso_calibration')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     {{-- Taste Notes --}}
                     <div class="mb-3">
                        <label class="form-label" for="taste_notes">
                           Taste Notes <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('taste_notes') is-invalid @enderror"
                           id="taste_notes" name="taste_notes" rows="3"
                           placeholder="Contoh: Bright acidity, sweet, fruity, medium body, low intensity of herb after taste">{{ old('taste_notes') }}</textarea>
                        @error('taste_notes')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     {{-- Flow of Customers --}}
                     <div class="mb-3">
                        <label class="form-label" for="flow_of_customers">
                           Flow of Customers <small class="text-muted">(Opsional)</small>
                        </label>
                        <textarea class="form-control @error('flow_of_customers') is-invalid @enderror"
                           id="flow_of_customers" name="flow_of_customers" rows="2"
                           placeholder="Contoh: start brewing 08.00 - 22.00 lumayan ramai tp tidak padat">{{ old('flow_of_customers') }}</textarea>
                        @error('flow_of_customers')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     {{-- Feedback --}}
                     <div class="mb-3">
                        <label class="form-label" for="feedback">
                           Feedback <small class="text-muted">(Opsional)</small>
                        </label>
                        <textarea class="form-control @error('feedback') is-invalid @enderror"
                           id="feedback" name="feedback" rows="2"
                           placeholder="Contoh: good responsive">{{ old('feedback') }}</textarea>
                        @error('feedback')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     {{-- Problem --}}
                     <div class="mb-3">
                        <label class="form-label" for="problem">
                           Problem <small class="text-muted">(Opsional)</small>
                        </label>
                        <textarea class="form-control @error('problem') is-invalid @enderror"
                           id="problem" name="problem" rows="3"
                           placeholder="Jelaskan masalah yang ditemukan jika ada...">{{ old('problem') }}</textarea>
                        @error('problem')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     {{-- Note --}}
                     <div class="mb-3">
                        <label class="form-label" for="note">
                           Note <small class="text-muted">(Opsional)</small>
                        </label>
                        <textarea class="form-control @error('note') is-invalid @enderror"
                           id="note" name="note" rows="3"
                           placeholder="Contoh: komplain sofia berubah jadi lebih sour, komplenan sudah diatasi...">{{ old('note') }}</textarea>
                        @error('note')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     {{-- Foto Kunjungan --}}
                     <div class="mb-4">
                        <label class="form-label" for="foto_kunjungan">
                           <i class="ri-camera-line me-1"></i> Foto Kunjungan <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control @error('foto_kunjungan') is-invalid @enderror"
                           id="foto_kunjungan" name="foto_kunjungan" accept="image/*" required>
                        @error('foto_kunjungan')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Format: JPG, PNG, WebP. Max: 5MB</small>
                        <div id="foto-preview" class="mt-2 d-none">
                           <img id="foto-preview-img" src="" alt="Preview" class="rounded border" style="max-height: 200px; object-fit: cover;">
                        </div>
                     </div>

                     {{-- Hidden Location Inputs --}}
                     <input type="hidden" name="user_lat" id="user_lat">
                     <input type="hidden" name="user_lng" id="user_lng">

                     <div id="location-warning" class="alert alert-warning d-none mb-4">
                        <i class="ri-error-warning-line me-1"></i>
                        <span id="location-msg">Mengambil lokasi...</span>
                     </div>

                     <hr>

                     <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>
                           <i class="ri-save-line me-1"></i>Simpan Kunjungan
                        </button>
                        <button type="reset" class="btn btn-label-secondary" onclick="resetPreview()">Reset</button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>
   </div>
@endsection

@section('page-script')
   <script type="module">
      $(function() {
         const btnSubmit = $('#btnSubmit');
         const locWarning = $('#location-warning');
         const locMsg = $('#location-msg');
         const latInput = $('#user_lat');
         const lngInput = $('#user_lng');

         // 1. Ambil lokasi saat halaman load
         function getLocation() {
            if (navigator.geolocation) {
               locWarning.removeClass('d-none alert-danger').addClass('alert-warning');
               locMsg.text('Sedang memverifikasi lokasi Anda...');
               btnSubmit.prop('disabled', true);

               navigator.geolocation.getCurrentPosition(
                  (position) => {
                     latInput.val(position.coords.latitude);
                     lngInput.val(position.coords.longitude);
                     
                     locWarning.addClass('d-none');
                     btnSubmit.prop('disabled', false);
                     console.log('Location captured:', position.coords.latitude, position.coords.longitude);
                  },
                  (error) => {
                     locWarning.removeClass('d-none alert-warning').addClass('alert-danger');
                     btnSubmit.prop('disabled', true);
                     
                     switch(error.code) {
                        case error.PERMISSION_DENIED:
                           locMsg.text('Izin lokasi ditolak. Aktifkan GPS dan izinkan browser mengakses lokasi.');
                           break;
                        case error.POSITION_UNAVAILABLE:
                           locMsg.text('Informasi lokasi tidak tersedia.');
                           break;
                        case error.TIMEOUT:
                           locMsg.text('Waktu pengambilan lokasi habis. Coba refresh halaman.');
                           break;
                        default:
                           locMsg.text('Terjadi kesalahan saat mengambil lokasi.');
                     }
                  },
                  { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
               );
            } else {
               locWarning.removeClass('d-none alert-warning').addClass('alert-danger');
               locMsg.text('Browser Anda tidak mendukung fitur lokasi.');
            }
         }

         // Trigger awal
         getLocation();

         // Init Select2
         $('#mitra_id').wrap('<div class="position-relative"></div>').select2({
            placeholder: 'Cari outlet / mitra...',
            allowClear: true,
            dropdownParent: $('#mitra_id').parent()
         });

         // Show outlet info on select
         $('#mitra_id').on('change', function() {
            const selected = $(this).find(':selected');
            const address = selected.data('address');
            const lat = selected.data('lat');
            const lng = selected.data('lng');

            if (address) {
               let info = `<i class="ri-map-pin-line me-1"></i>${address}`;
               if (!lat || !lng) {
                  info += '<br><span class="text-danger small"><i class="ri-error-warning-line me-1"></i>Outlet ini belum memiliki koordinat lokasi.</span>';
               }
               $('#outlet-address').html(info);
               $('#outlet-info').removeClass('d-none');
            } else {
               $('#outlet-info').addClass('d-none');
            }
         });

         // Foto preview
         $('#foto_kunjungan').on('change', function() {
            const file = this.files[0];
            if (file) {
               const reader = new FileReader();
               reader.onload = function(e) {
                  $('#foto-preview-img').attr('src', e.target.result);
                  $('#foto-preview').removeClass('d-none');
               }
               reader.readAsDataURL(file);
            } else {
               $('#foto-preview').addClass('d-none');
            }
         });

         // Prevent double submit
         $('#formKunjungan').on('submit', function() {
            const btn = $('#btnSubmit');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');
         });

         // Trigger initial outlet info
         if ($('#mitra_id').val()) {
            $('#mitra_id').trigger('change');
         }
      });

      window.resetPreview = function() {
         $('#foto-preview').addClass('d-none');
         $('#outlet-info').addClass('d-none');
      }
   </script>
@endsection
