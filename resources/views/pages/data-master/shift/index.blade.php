@extends('layouts/layoutMaster')

@section('title', 'Manajemen Shift')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Data Master /</span> Shift
         </h4>
         <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalShift" onclick="resetForm()">
            <i class="ri-add-line me-1"></i>Tambah Shift
         </button>
      </div>

      <div class="card mb-4">
         <div class="card-body">
            <form action="{{ route('shift.index') }}" method="GET" class="row g-3">
               <div class="col-md-4">
                  <label class="form-label">Filter Divisi</label>
                  <select name="divisi_id" class="form-select" onchange="this.form.submit()">
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
                     <a href="{{ route('shift.index') }}" class="btn btn-label-secondary">
                        <i class="ri-refresh-line me-1"></i>Reset Filter
                     </a>
                  @endif
               </div>
            </form>
         </div>
      </div>

      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="datatables-shift table table-hover">
               <thead>
                  <tr>
                     <th>#</th>
                     <th>Divisi</th>
                     <th>Nama Shift</th>
                     <th>Jam Masuk</th>
                     <th>Jam Pulang</th>
                     <th>Ikut Libur</th>
                     <th>Status</th>
                     <th class="text-center">Aksi</th>
                  </tr>
               </thead>
               <tbody class="table-border-bottom-0">
                  @foreach ($data as $index => $item)
                     <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->divisi->nama ?? '-' }}</td>
                        <td><strong>{{ $item->nama }}</strong></td>
                        <td><span class="badge bg-label-primary">{{ $item->jam_masuk->format('H:i') }}</span></td>
                        <td><span class="badge bg-label-secondary">{{ $item->jam_pulang->format('H:i') }}</span></td>
                        <td>
                           @if ($item->ikut_libur)
                              <span class="badge bg-label-warning">Ya</span>
                           @else
                              <span class="badge bg-label-success">Tidak</span>
                           @endif
                        </td>
                        <td>
                           @if ($item->is_aktif)
                              <span class="badge bg-success">Aktif</span>
                           @else
                              <span class="badge bg-secondary">Non-Aktif</span>
                           @endif
                        </td>
                        <td class="text-center">
                           <div class="d-flex justify-content-center gap-2">
                              <button class="btn btn-sm btn-icon btn-label-primary"
                                 onclick="editShift({{ json_encode($item) }})">
                                 <i class="ri-pencil-line"></i>
                              </button>
                              <button type="button" class="btn btn-sm btn-icon btn-label-danger delete-record"
                                 data-id="{{ $item->id }}" data-name="{{ $item->nama }}">
                                 <i class="ri-delete-bin-line"></i>
                              </button>
                           </div>
                        </td>
                     </tr>
                  @endforeach
               </tbody>
            </table>
         </div>

      </div>
   </div>

   <!-- Modal Shift -->
   <div class="modal fade" id="modalShift" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="modalTitle">Tambah Shift</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formShift" onsubmit="saveShift(event)">
               @csrf
               <input type="hidden" id="shift_id" name="id">
               <div class="modal-body">
                  <div class="mb-3">
                     <label class="form-label">Divisi <span class="text-danger">*</span></label>
                     <select name="divisi_id" id="divisi_id" class="form-select" required>
                        <option value="">Pilih Divisi</option>
                        @foreach ($divisis as $divisi)
                           <option value="{{ $divisi->id }}">{{ $divisi->nama }}</option>
                        @endforeach
                     </select>
                  </div>
                  <div class="mb-3">
                     <label class="form-label">Nama Shift <span class="text-danger">*</span></label>
                     <input type="text" name="nama" id="nama" class="form-control" placeholder="Pagi" required>
                  </div>
                  <div class="row">
                     <div class="col-md-6 mb-3">
                        <label class="form-label">Jam Masuk <span class="text-danger">*</span></label>
                        <input type="time" name="jam_masuk" id="jam_masuk" class="form-control" required>
                     </div>
                     <div class="col-md-6 mb-3">
                        <label class="form-label">Jam Pulang <span class="text-danger">*</span></label>
                        <input type="time" name="jam_pulang" id="jam_pulang" class="form-control" required>
                     </div>
                  </div>
                  <div class="row">
                     <div class="col-md-6 mb-3">
                        <div class="form-check form-switch pt-2">
                           <input class="form-check-input" type="checkbox" id="ikut_libur" name="ikut_libur"
                              value="1">
                           <label class="form-check-label" for="ikut_libur">Ikuti Hari Libur</label>
                        </div>
                     </div>
                     <div class="col-md-6 mb-0">
                        <div class="form-check form-switch pt-2">
                           <input class="form-check-input" type="checkbox" id="is_aktif" name="is_aktif"
                              value="1" checked>
                           <label class="form-check-label" for="is_aktif">Aktif</label>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary">Simpan</button>
               </div>
            </form>
         </div>
      </div>
   </div>
@endsection

@section('page-script')
   <script>
      window.addEventListener('load', function() {
         const dt_shift = $('.datatables-shift');

         if (dt_shift.length) {
            dt_shift.DataTable({
               responsive: true,
               displayLength: 10,
               lengthMenu: [10, 25, 50, 75, 100],
               language: {
                  paginate: {
                     next: '<i class="ri-arrow-right-s-line"></i>',
                     previous: '<i class="ri-arrow-left-s-line"></i>'
                  },
                  search: "",
                  searchPlaceholder: "Cari...",
                  lengthMenu: "_MENU_",
                  info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
               },
               dom: '<"card-header flex-column flex-md-row border-bottom"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"fB>><"row"<"col-sm-12 col-md-6"l>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
               buttons: []
            });
            $('div.head-label').html('<h5 class="card-title mb-0">Daftar Shift</h5>');
         }

         const modalElement = document.getElementById('modalShift');
         const formShift = document.getElementById('formShift');
         let modalShiftInstance = null;

         // Initialize modal only when needed
         function getModalInstance() {
            if (!modalShiftInstance && window.bootstrap) {
               modalShiftInstance = new bootstrap.Modal(modalElement);
            }
            return modalShiftInstance;
         }

         window.resetForm = function() {
            formShift.reset();
            document.getElementById('shift_id').value = '';
            document.getElementById('modalTitle').textContent = 'Tambah Shift';
         };

         window.editShift = function(data) {
            window.resetForm();
            document.getElementById('modalTitle').textContent = 'Edit Shift';
            document.getElementById('shift_id').value = data.id;
            document.getElementById('divisi_id').value = data.divisi_id;
            document.getElementById('nama').value = data.nama;

            // Handle time format H:i:s -> H:i
            if (data.jam_masuk) document.getElementById('jam_masuk').value = data.jam_masuk.substring(0, 5);
            if (data.jam_pulang) document.getElementById('jam_pulang').value = data.jam_pulang.substring(0, 5);

            document.getElementById('ikut_libur').checked = !!data.ikut_libur;
            document.getElementById('is_aktif').checked = !!data.is_aktif;
            const modal = getModalInstance();
            if (modal) modal.show();
         };

         window.saveShift = function(e) {
            e.preventDefault();
            const id = document.getElementById('shift_id').value;
            const url = id ? `{{ url('shift') }}/${id}` : `{{ url('shift') }}`;

            const formData = new FormData(formShift);
            if (id) formData.append('_method', id ? 'PUT' : 'POST');

            fetch(url, {
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
                     window.AlertHandler.handle(data);
                     return {
                        success: false
                     };
                  }
                  return data;
               })
               .then(data => {
                  if (data.success) {
                     window.AlertHandler.handle(data);
                     const modal = getModalInstance();
                     if (modal) modal.hide();
                     setTimeout(() => window.location.reload(), 1000);
                  }
               })
               .catch(error => {
                  console.error('Error:', error);
                  window.AlertHandler.showError('Terjadi kesalahan sistem atau format data tidak valid');
               });
         };

         document.querySelectorAll('.delete-record').forEach(btn => {
            btn.addEventListener('click', function() {
               const id = this.dataset.id;
               const name = this.dataset.name;

               if (window.AlertHandler) {
                  window.AlertHandler.confirm(
                     'Hapus Shift?',
                     `Apakah Anda yakin ingin menghapus shift "${name}"?`,
                     'Ya, Hapus!',
                     function() {
                        fetch(`{{ url('shift') }}/${id}`, {
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
