@extends('layouts/layoutMaster')

@section('title', 'Ajukan Izin')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Izin /</span> Ajukan Izin Baru
         </h4>
      </div>

      @if (session('error'))
         <div class="alert alert-danger alert-dismissible mb-4">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
         </div>
      @endif

      <div class="row">
         <div class="col-md-8 mx-auto">
            <div class="card">
               <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">Form Pengajuan Izin</h5>
                  <a href="{{ route('izin.index') }}" class="btn btn-sm btn-outline-secondary">
                     <i class="ri-arrow-left-line me-1"></i>Kembali
                  </a>
               </div>
               <div class="card-body">
                  <form action="{{ route('izin.store') }}" method="POST" enctype="multipart/form-data">
                     @csrf

                     <div class="mb-3">
                        <label class="form-label" for="jenis_izin_id">Jenis Izin <span class="text-danger">*</span></label>
                        <select class="form-select @error('jenis_izin_id') is-invalid @enderror" id="jenis_izin_id"
                           name="jenis_izin_id" required>
                           <option value="">-- Pilih Jenis Izin --</option>
                           @foreach ($jenisIzins as $jenis)
                              <option value="{{ $jenis->id }}" data-butuh-surat="{{ $jenis->butuh_surat ? '1' : '0' }}"
                                 data-max-hari="{{ $jenis->max_hari ?? '' }}"
                                 {{ old('jenis_izin_id') == $jenis->id ? 'selected' : '' }}>
                                 {{ $jenis->nama }}
                                 @if ($jenis->max_hari)
                                    (max {{ $jenis->max_hari }} hari)
                                 @endif
                              </option>
                           @endforeach
                        </select>
                        @error('jenis_izin_id')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     <div class="row mb-3">
                        <div class="col-md-6">
                           <label class="form-label" for="tgl_mulai">Tanggal Mulai <span
                                 class="text-danger">*</span></label>
                           <input type="date" class="form-control @error('tgl_mulai') is-invalid @enderror"
                              id="tgl_mulai" name="tgl_mulai" value="{{ old('tgl_mulai', date('Y-m-d')) }}"
                              min="{{ date('Y-m-d') }}" required>
                           @error('tgl_mulai')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-6">
                           <label class="form-label" for="tgl_selesai">Tanggal Selesai <span
                                 class="text-danger">*</span></label>
                           <input type="date" class="form-control @error('tgl_selesai') is-invalid @enderror"
                              id="tgl_selesai" name="tgl_selesai" value="{{ old('tgl_selesai', date('Y-m-d')) }}"
                              min="{{ date('Y-m-d') }}" required>
                           @error('tgl_selesai')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>

                     <div class="mb-3">
                        <label class="form-label" for="alasan">Alasan <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('alasan') is-invalid @enderror" id="alasan" name="alasan" rows="4"
                           placeholder="Jelaskan alasan pengajuan izin Anda..." required>{{ old('alasan') }}</textarea>
                        @error('alasan')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     <div class="mb-4" id="file-surat-container">
                        <label class="form-label" for="file_surat">
                           Surat Pendukung <small class="text-muted">(Opsional)</small>
                        </label>
                        <input type="file" class="form-control @error('file_surat') is-invalid @enderror"
                           id="file_surat" name="file_surat" accept=".pdf,.jpg,.jpeg,.png">
                        @error('file_surat')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Format: PDF, JPG, PNG. Max: 5MB</small>
                     </div>

                     <div class="alert alert-info d-none" id="info-alert">
                        <i class="ri-information-line me-2"></i>
                        <span id="info-text"></span>
                     </div>

                     <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                           <i class="ri-send-plane-line me-1"></i>Ajukan Izin
                        </button>
                        <button type="reset" class="btn btn-label-secondary">Reset</button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>
   </div>
@endsection

@section('page-script')
   <script>
      document.addEventListener('DOMContentLoaded', function() {
         const jenisSelect = document.getElementById('jenis_izin_id');
         const infoAlert = document.getElementById('info-alert');
         const infoText = document.getElementById('info-text');
         const tglMulai = document.getElementById('tgl_mulai');
         const tglSelesai = document.getElementById('tgl_selesai');

         const today = new Date();
         today.setHours(0, 0, 0, 0);

         const sevenDaysLater = new Date(today);
         sevenDaysLater.setDate(today.getDate() + 7);

         const formatDate = (date) => {
            let d = new Date(date),
               month = '' + (d.getMonth() + 1),
               day = '' + d.getDate(),
               year = d.getFullYear();

            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;

            return [year, month, day].join('-');
         };

         const dateTodayStr = formatDate(today);
         const dateSevenDaysStr = formatDate(sevenDaysLater);

         function updateDateConstraints() {
            const selected = jenisSelect.options[jenisSelect.selectedIndex];
            if (!selected || !selected.value) return;

            const nama = selected.text.toLowerCase();
            const isCuti = nama.includes('cuti');
            const maxHari = selected.dataset.maxHari;

            let minDate = dateTodayStr;
            let info = '';

            if (isCuti) {
               minDate = dateSevenDaysStr;
               info =
                  `<strong>Info Cuti:</strong> Pengajuan cuti harus dilakukan minimal 1 minggu sebelumnya (Minimal tanggal ${dateSevenDaysStr}).`;
            }

            if (maxHari) {
               info += (info ? '<br>' : '') + `<strong>Maksimal:</strong> Jenis izin ini maksimal ${maxHari} hari.`;
            }

            if (info) {
               infoAlert.classList.remove('d-none', 'alert-info', 'alert-warning');
               infoAlert.classList.add(isCuti ? 'alert-warning' : 'alert-info');
               infoText.innerHTML = info;
            } else {
               infoAlert.classList.add('d-none');
            }

            tglMulai.setAttribute('min', minDate);
            if (tglMulai.value < minDate) {
               tglMulai.value = minDate;
               tglSelesai.value = minDate;
            }
            tglSelesai.setAttribute('min', tglMulai.value);
         }

         jenisSelect.addEventListener('change', updateDateConstraints);

         tglMulai.addEventListener('change', function() {
            tglSelesai.setAttribute('min', this.value);
            if (tglSelesai.value < this.value) {
               tglSelesai.value = this.value;
            }

            // Check max hari
            const selected = jenisSelect.options[jenisSelect.selectedIndex];
            const maxHari = selected.dataset.maxHari;
            if (maxHari && tglSelesai.value) {
               const start = new Date(this.value);
               const end = new Date(tglSelesai.value);
               const diffTime = Math.abs(end - start);
               const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

               if (diffDays > maxHari) {
                  // Adjust tglSelesai to max allowed
                  const newEnd = new Date(start);
                  newEnd.setDate(start.getDate() + parseInt(maxHari) - 1);
                  tglSelesai.value = formatDate(newEnd);
                  window.AlertHandler.showError(`Maksimal izin ini adalah ${maxHari} hari.`);
               }
            }
         });

         tglSelesai.addEventListener('change', function() {
            const selected = jenisSelect.options[jenisSelect.selectedIndex];
            const maxHari = selected.dataset.maxHari;
            if (maxHari && tglMulai.value) {
               const start = new Date(tglMulai.value);
               const end = new Date(this.value);
               const diffTime = Math.abs(end - start);
               const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

               if (diffDays > maxHari) {
                  const newEnd = new Date(start);
                  newEnd.setDate(start.getDate() + parseInt(maxHari) - 1);
                  this.value = formatDate(newEnd);
                  window.AlertHandler.showError(`Maksimal izin ini adalah ${maxHari} hari.`);
               }
            }
         });

         // Trigger initial
         updateDateConstraints();
      });
   </script>
@endsection
