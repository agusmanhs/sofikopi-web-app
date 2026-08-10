@extends('layouts/layoutMaster')

@section('title', 'Penjadwalan Absen')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Absensi /</span> Penjadwalan Absen
         </h4>
      </div>

      <div class="card mb-4">
         <div class="card-body">
            <form action="{{ route('jadwal-kerja.index') }}" method="GET" class="row g-3">
               <div class="col-md-4">
                  <label class="form-label">Filter Divisi</label>
                  <select name="divisi_id" class="form-select select2" onchange="this.form.submit()">
                     <option value="">Semua Divisi</option>
                     @foreach ($divisis as $divisi)
                        <option value="{{ $divisi->id }}" {{ request('divisi_id') == $divisi->id ? 'selected' : '' }}>
                           {{ $divisi->nama }}
                        </option>
                     @endforeach
                  </select>
               </div>
               <div class="col-md-4 d-flex align-items-end">
                  @if (request('divisi_id'))
                     <a href="{{ route('jadwal-kerja.index') }}" class="btn btn-label-secondary">
                        <i class="ri-refresh-line me-1"></i>Reset Filter
                     </a>
                  @endif
               </div>
            </form>
         </div>
      </div>

      <div class="card mb-4">
         <div class="card-header">
            <h5 class="card-title mb-0">Pola Jadwal Mingguan</h5>
            <p class="text-muted small mb-0 mt-1">Atur shift tetap tiap pegawai per hari. Pilih "Libur" untuk hari
               tanpa jadwal kerja, lalu klik Simpan per baris.</p>
         </div>
         <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
               <thead>
                  <tr>
                     <th style="min-width:160px">Pegawai</th>
                     <th style="min-width:120px">Divisi</th>
                     @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $label)
                        <th class="text-center" style="min-width:130px">{{ $label }}</th>
                     @endforeach
                     <th class="text-center" style="min-width:100px">Aksi</th>
                  </tr>
               </thead>
               <tbody>
                  @php $dayOrder = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu']; @endphp
                  @forelse ($data as $pegawai)
                     @php
                        $shiftsForDivisi = $shiftsByDivisi->get($pegawai->divisi_id, collect());
                        $patternByDay = $pegawai->jadwalKerjas->keyBy('day_of_week');
                     @endphp
                     <tr data-pegawai-id="{{ $pegawai->id }}">
                        <td>{{ $pegawai->nama_lengkap }}</td>
                        <td>{{ $pegawai->divisi->nama ?? '-' }}</td>
                        @foreach ($dayOrder as $dow => $dayName)
                           @php $current = $patternByDay->get($dow); @endphp
                           <td class="jadwal-day-cell">
                              <select class="form-select form-select-sm jadwal-day-select select2" data-day="{{ $dow }}"
                                 title="{{ $dayName }}">
                                 <option value="">Libur</option>
                                 @foreach ($shiftsForDivisi as $shift)
                                    <option value="{{ $shift->id }}"
                                       {{ $current && (int) $current->shift_id === (int) $shift->id ? 'selected' : '' }}>
                                       {{ $shift->nama }}
                                    </option>
                                 @endforeach
                              </select>
                           </td>
                        @endforeach
                        <td class="text-center">
                           <button type="button" class="btn btn-sm btn-primary btn-save-pattern"
                              data-pegawai-id="{{ $pegawai->id }}">
                              <i class="ri-save-line me-1"></i>Simpan
                           </button>
                        </td>
                     </tr>
                  @empty
                     <tr>
                        <td colspan="10" class="text-center text-muted py-4">Tidak ada pegawai aktif.</td>
                     </tr>
                  @endforelse
               </tbody>
            </table>
         </div>
      </div>

      <div class="card">
         <div class="card-header">
            <h5 class="card-title mb-0">Ubah Jadwal untuk Tanggal Tertentu</h5>
            <p class="text-muted small mb-0 mt-1">Gunakan ini kalau ada perubahan jadwal pegawai hanya untuk satu
               hari saja (misalnya tukar shift atau libur mendadak). Perubahan ini akan tetap dipakai walaupun
               berbeda dari jadwal mingguan biasanya.</p>
         </div>
         <div class="card-body border-bottom">
            <form id="formOverride" class="row g-3">
               @csrf
               <div class="col-md-3">
                  <label class="form-label">Pegawai</label>
                  <select name="pegawai_id" id="override_pegawai_id" class="form-select select2" required>
                     <option value="">Pilih Pegawai</option>
                     @foreach ($pegawais as $p)
                        <option value="{{ $p->id }}" data-divisi-id="{{ $p->divisi_id }}">{{ $p->nama_lengkap }}
                        </option>
                     @endforeach
                  </select>
               </div>
               <div class="col-md-2">
                  <label class="form-label">Tanggal</label>
                  <input type="date" name="tanggal" id="override_tanggal" class="form-control" required>
               </div>
               <div class="col-md-3">
                  <label class="form-label">Shift</label>
                  <select name="shift_id" id="override_shift_id" class="form-select select2">
                     <option value="">Libur</option>
                     @foreach ($shiftsByDivisi->flatten() as $shift)
                        <option value="{{ $shift->id }}" data-divisi-id="{{ $shift->divisi_id }}">
                           {{ $shift->nama }} ({{ $shift->divisi->nama ?? '-' }})
                        </option>
                     @endforeach
                  </select>
               </div>
               <div class="col-md-3">
                  <label class="form-label">Keterangan</label>
                  <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
               </div>
               <div class="col-md-1 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary w-100">
                     <i class="ri-add-line"></i>
                  </button>
               </div>
            </form>
         </div>
         <div class="table-responsive">
            <table class="table mb-0">
               <thead>
                  <tr>
                     <th>Pegawai</th>
                     <th>Tanggal</th>
                     <th>Shift</th>
                     <th>Keterangan</th>
                     <th class="text-center">Aksi</th>
                  </tr>
               </thead>
               <tbody>
                  @forelse ($overrides as $override)
                     <tr>
                        <td>{{ $override->pegawai->nama_lengkap ?? '-' }}</td>
                        <td>{{ $override->tanggal->format('d-m-Y') }}</td>
                        <td>
                           @if ($override->shift)
                              <span class="badge bg-label-primary">{{ $override->shift->nama }}</span>
                           @else
                              <span class="badge bg-label-warning">Libur</span>
                           @endif
                        </td>
                        <td>{{ $override->keterangan ?? '-' }}</td>
                        <td class="text-center">
                           <button type="button" class="btn btn-sm btn-icon btn-label-danger btn-delete-override"
                              data-id="{{ $override->id }}">
                              <i class="ri-delete-bin-line"></i>
                           </button>
                        </td>
                     </tr>
                  @empty
                     <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada perubahan jadwal untuk
                           tanggal mendatang.</td>
                     </tr>
                  @endforelse
               </tbody>
            </table>
         </div>
      </div>
   </div>
@endsection

@section('page-script')
   <script>
      window.addEventListener('load', function() {
         // ============== INIT SELECT2 ==============
         $('.select2').each(function() {
            var $this = $(this);
            var $cell = $this.closest('td.jadwal-day-cell');

            if ($cell.length) {
               // Selects inside the weekly pattern table: anchor the dropdown to the
               // specific <td> so it isn't clipped by the .table-responsive overflow.
               $this.select2({
                  placeholder: 'Pilih Opsian',
                  dropdownParent: $cell,
                  width: '100%'
               });
            } else {
               $this.wrap('<div class="position-relative"></div>').select2({
                  placeholder: 'Pilih Opsian',
                  dropdownParent: $this.parent()
               });
            }
         });

         // ============== SAVE PATTERN PER ROW ==============
         document.querySelectorAll('.btn-save-pattern').forEach(function(btn) {
            btn.addEventListener('click', function() {
               const pegawaiId = this.dataset.pegawaiId;
               const row = this.closest('tr');
               const days = {};
               row.querySelectorAll('.jadwal-day-select').forEach(function(sel) {
                  days[sel.dataset.day] = sel.value === '' ? null : sel.value;
               });

               fetch(`{{ url('jadwal-kerja/pattern') }}/${pegawaiId}`, {
                     method: 'POST',
                     headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                     },
                     body: JSON.stringify({
                        days
                     })
                  })
                  .then(async response => {
                     const data = await response.json();
                     if (!response.ok) {
                        if (window.AlertHandler) window.AlertHandler.handle(data);
                        return {
                           success: false
                        };
                     }
                     return data;
                  })
                  .then(data => {
                     if (data.success && window.AlertHandler) {
                        window.AlertHandler.handle(data);
                     }
                  })
                  .catch(function(error) {
                     console.error('Error:', error);
                     if (window.AlertHandler) {
                        window.AlertHandler.showError('Terjadi kesalahan sistem saat menyimpan pola jadwal.');
                     }
                  });
            });
         });

         // ============== FILTER SHIFT OPTIONS BY PEGAWAI DIVISI (Override form) ==============
         const overridePegawaiSelect = document.getElementById('override_pegawai_id');
         const overrideShiftSelect = document.getElementById('override_shift_id');

         // Select2 builds its own dropdown DOM from the native <select> options and does
         // NOT notice `option.hidden` toggles on next open. So instead of hiding options,
         // we keep the full master list here and physically add/remove <option> elements
         // from the real <select> — Select2 (select-backed) re-reads that DOM correctly.
         const allShiftOptions = Array.from(overrideShiftSelect.options)
            .filter(function(opt) {
               return opt.value !== '';
            })
            .map(function(opt) {
               return {
                  value: opt.value,
                  text: opt.textContent.trim(),
                  divisiId: opt.dataset.divisiId
               };
            });

         function filterOverrideShiftOptions() {
            const selectedOption = overridePegawaiSelect.options[overridePegawaiSelect.selectedIndex];
            const divisiId = selectedOption ? selectedOption.dataset.divisiId : null;

            Array.from(overrideShiftSelect.options).forEach(function(opt) {
               if (opt.value !== '') opt.remove(); // keep "Libur" option always
            });

            allShiftOptions.forEach(function(shift) {
               if (!divisiId || shift.divisiId === divisiId) {
                  const opt = new Option(shift.text, shift.value, false, false);
                  opt.dataset.divisiId = shift.divisiId;
                  overrideShiftSelect.add(opt);
               }
            });

            overrideShiftSelect.value = '';
            $(overrideShiftSelect).trigger('change.select2');
         }

         if (overridePegawaiSelect && overrideShiftSelect) {
            overridePegawaiSelect.addEventListener('change', filterOverrideShiftOptions);
         }

         // ============== SUBMIT OVERRIDE FORM ==============
         const formOverride = document.getElementById('formOverride');
         if (formOverride) {
            formOverride.addEventListener('submit', function(e) {
               e.preventDefault();
               const formData = new FormData(formOverride);

               fetch(`{{ route('jadwal-kerja.override.store') }}`, {
                     method: 'POST',
                     body: formData,
                     headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                     }
                  })
                  .then(async response => {
                     const data = await response.json();
                     if (!response.ok) {
                        if (window.AlertHandler) window.AlertHandler.handle(data);
                        return {
                           success: false
                        };
                     }
                     return data;
                  })
                  .then(data => {
                     if (data.success) {
                        if (window.AlertHandler) window.AlertHandler.handle(data);
                        setTimeout(() => window.location.reload(), 1000);
                     }
                  })
                  .catch(function(error) {
                     console.error('Error:', error);
                     if (window.AlertHandler) {
                        window.AlertHandler.showError('Terjadi kesalahan sistem saat menyimpan perubahan jadwal.');
                     }
                  });
            });
         }

         // ============== DELETE OVERRIDE ==============
         document.querySelectorAll('.btn-delete-override').forEach(function(btn) {
            btn.addEventListener('click', function() {
               const id = this.dataset.id;

               if (window.AlertHandler) {
                  window.AlertHandler.confirm(
                     'Hapus Perubahan Jadwal?',
                     'Apakah Anda yakin ingin menghapus perubahan jadwal ini? Pegawai akan kembali mengikuti jadwal mingguan biasanya.',
                     'Ya, Hapus!',
                     function() {
                        fetch(`{{ url('jadwal-kerja/override') }}/${id}`, {
                              method: 'DELETE',
                              headers: {
                                 'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                 'Accept': 'application/json'
                              }
                           })
                           .then(response => response.json())
                           .then(data => {
                              window.AlertHandler.handle(data);
                              if (data.success) {
                                 setTimeout(() => window.location.reload(), 1500);
                              }
                           });
                     }
                  );
               }
            });
         });
      });
   </script>
@endsection
