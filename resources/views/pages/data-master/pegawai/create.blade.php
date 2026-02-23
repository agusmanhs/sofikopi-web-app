@extends('layouts/layoutMaster')

@section('title', 'Tambah Pegawai')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Data Master / Pegawai /</span> Tambah
         </h4>
      </div>

      <div class="row">
         <div class="col-md-10 mx-auto">
            <div class="card">
               <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">Form Tambah Pegawai</h5>
                  <a href="{{ route('pegawai.index') }}" class="btn btn-sm btn-outline-secondary">
                     <i class="ri-arrow-left-line me-1"></i>Kembali
                  </a>
               </div>
               <div class="card-body">
                  <form action="{{ route('pegawai.store') }}" method="POST" enctype="multipart/form-data">
                     @csrf

                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label" for="user_id">Akun User <span class="text-danger">*</span></label>
                           <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id"
                              required>
                              <option value="">-- Pilih User --</option>
                              @foreach ($users as $user)
                                 <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                 </option>
                              @endforeach
                           </select>
                           @error('user_id')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label" for="nip">NIP</label>
                           <input type="text" class="form-control @error('nip') is-invalid @enderror" id="nip"
                              name="nip" value="{{ old('nip') }}" placeholder="1990123456">
                           @error('nip')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>

                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label" for="nama_lengkap">Nama Lengkap <span
                                 class="text-danger">*</span></label>
                           <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror"
                              id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required>
                           @error('nama_lengkap')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                           <label class="form-label" for="gender">Jenis Kelamin <span
                                 class="text-danger">*</span></label>
                           <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender"
                              required>
                              <option value="">-- Pilih --</option>
                              <option value="L" {{ old('gender') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                              <option value="P" {{ old('gender') == 'P' ? 'selected' : '' }}>Perempuan</option>
                           </select>
                           @error('gender')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                           <label class="form-label" for="no_telp">No. Telepon</label>
                           <input type="text" class="form-control @error('no_telp') is-invalid @enderror" id="no_telp"
                              name="no_telp" value="{{ old('no_telp') }}" placeholder="08123456789">
                           @error('no_telp')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>

                     <div class="row">
                        <div class="col-md-3 mb-3">
                           <label class="form-label" for="divisi_id">Divisi <span class="text-danger">*</span></label>
                           <select class="form-select @error('divisi_id') is-invalid @enderror" id="divisi_id"
                              name="divisi_id" required onchange="loadShifts(this.value)">
                              <option value="">-- Pilih Divisi --</option>
                              @foreach ($divisis as $divisi)
                                 <option value="{{ $divisi->id }}"
                                    {{ old('divisi_id') == $divisi->id ? 'selected' : '' }}>
                                    {{ $divisi->nama }}
                                 </option>
                              @endforeach
                           </select>
                           @error('divisi_id')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-3 mb-3 d-none">
                           <label class="form-label" for="shift_id">Shift</label>
                           <select class="form-select @error('shift_id') is-invalid @enderror" id="shift_id"
                              name="shift_id">
                              <option value="">-- Pilih Shift --</option>
                           </select>
                           @error('shift_id')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                           <label class="form-label" for="kantor_id">Kantor Utama</label>
                           <select class="form-select @error('kantor_id') is-invalid @enderror" id="kantor_id"
                              name="kantor_id">
                              <option value="">-- Pilih Kantor --</option>
                              @foreach ($kantors as $kantor)
                                 <option value="{{ $kantor->id }}"
                                    {{ old('kantor_id') == $kantor->id ? 'selected' : '' }}>
                                    {{ $kantor->nama }}
                                 </option>
                              @endforeach
                           </select>
                           @error('kantor_id')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                           <label class="form-label" for="jabatan">Jabatan</label>
                           <input type="text" class="form-control @error('jabatan') is-invalid @enderror"
                              id="jabatan" name="jabatan" value="{{ old('jabatan') }}" placeholder="Staff IT">
                           @error('jabatan')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>

                     <div class="row">
                        <div class="col-md-4 mb-3">
                           <label class="form-label" for="tgl_masuk">Tanggal Masuk Kerja</label>
                           <input type="date" class="form-control @error('tgl_masuk') is-invalid @enderror"
                              id="tgl_masuk" name="tgl_masuk" value="{{ old('tgl_masuk') }}">
                           @error('tgl_masuk')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-8 mb-3">
                           <label class="form-label" for="foto">Foto</label>
                           <input type="file" class="form-control @error('foto') is-invalid @enderror"
                              id="foto" name="foto" accept="image/*">
                           @error('foto')
                              <div class="invalid-feedback">{{ $message }}</div>
                           @enderror
                           <small class="text-muted">Format: JPG, PNG. Max: 2MB</small>
                        </div>
                     </div>

                     <div class="mb-3">
                        <label class="form-label" for="alamat">Alamat</label>
                        <textarea class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" rows="2">{{ old('alamat') }}</textarea>
                        @error('alamat')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     <div class="mb-3">
                        <label class="form-label">Lokasi Absensi yang Diizinkan</label>
                        <div class="row">
                           @foreach ($kantors as $kantor)
                              <div class="col-md-4 mb-2">
                                 <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="lokasi_{{ $kantor->id }}"
                                       name="lokasi_absen[]" value="{{ $kantor->id }}"
                                       {{ in_array($kantor->id, old('lokasi_absen', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="lokasi_{{ $kantor->id }}">
                                       {{ $kantor->nama }} <small
                                          class="text-muted">({{ $kantor->radius_meter }}m)</small>
                                    </label>
                                 </div>
                              </div>
                           @endforeach
                        </div>
                        <small class="text-muted">Pilih lokasi kantor di mana pegawai diizinkan untuk absen</small>
                     </div>

                     <div class="mb-3">
                        <div class="form-check form-switch">
                           <input class="form-check-input" type="checkbox" id="status_aktif" name="status_aktif"
                              value="1" {{ old('status_aktif', 1) ? 'checked' : '' }}>
                           <label class="form-check-label" for="status_aktif">Pegawai Aktif</label>
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
@section('page-script')
   <script>
      function loadShifts(divisiId, selectedShiftId = null) {
         const shiftSelect = document.getElementById('shift_id');
         shiftSelect.innerHTML = '<option value="">-- Loading --</option>';

         if (!divisiId) {
            shiftSelect.innerHTML = '<option value="">-- Pilih Divisi Terlebih Dahulu --</option>';
            return;
         }

         fetch(`{{ url('api/shifts/by-divisi') }}/${divisiId}`)
            .then(response => response.json())
            .then(data => {
               shiftSelect.innerHTML = '<option value="">-- Pilih Shift --</option>';
               data.forEach(shift => {
                  const option = document.createElement('option');
                  option.value = shift.id;
                  option.textContent = `${shift.nama} (${shift.jam_masuk} - ${shift.jam_pulang})`;
                  if (selectedShiftId == shift.id) {
                     option.selected = true;
                  }
                  shiftSelect.appendChild(option);
               });

               if (data.length === 0) {
                  shiftSelect.innerHTML = '<option value="">-- Tidak ada shift di divisi ini --</option>';
               }
            })
            .catch(error => {
               console.error('Error loading shifts:', error);
               shiftSelect.innerHTML = '<option value="">-- Gagal memuat shift --</option>';
            });
      }

      // Initial load if old input exists
      const oldDivisi = "{{ old('divisi_id') }}";
      const oldShift = "{{ old('shift_id') }}";
      if (oldDivisi) {
         loadShifts(oldDivisi, oldShift);
      }
   </script>
@endsection
