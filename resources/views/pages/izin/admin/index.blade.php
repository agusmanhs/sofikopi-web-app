@extends('layouts/layoutMaster')

@section('title', 'Manajemen Izin')

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
            <span class="text-muted fw-light">Admin /</span> Manajemen Izin
         </h4>
         <div class="d-flex gap-2">
            <a href="{{ route('izin.admin.pending') }}" class="btn btn-warning">
               <i class="ri-time-line me-1"></i>Pending ({{ $statistik['pending'] }})
            </a>
         </div>
      </div>

      <!-- Stats Cards -->
      <div class="row g-4 mb-4">
         <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
               <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                     <div class="content-left">
                        <h4 class="mb-0 fw-bold">{{ $statistik['pending'] }}</h4>
                        <small class="text-warning fw-medium">Menunggu</small>
                     </div>
                     <div class="avatar avatar-lg">
                        <div class="avatar-initial bg-label-warning rounded">
                           <i class="ri-time-line ri-24px"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
               <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                     <div class="content-left">
                        <h4 class="mb-0 fw-bold">{{ $statistik['approved'] }}</h4>
                        <small class="text-success fw-medium">Disetujui</small>
                     </div>
                     <div class="avatar avatar-lg">
                        <div class="avatar-initial bg-label-success rounded">
                           <i class="ri-check-double-line ri-24px"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
               <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                     <div class="content-left">
                        <h4 class="mb-0 fw-bold">{{ $statistik['rejected'] }}</h4>
                        <small class="text-danger fw-medium">Ditolak</small>
                     </div>
                     <div class="avatar avatar-lg">
                        <div class="avatar-initial bg-label-danger rounded">
                           <i class="ri-close-circle-line ri-24px"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
               <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                     <div class="content-left">
                        <h4 class="mb-0 fw-bold">{{ $statistik['total'] }}</h4>
                        <small class="text-primary fw-medium">Total Pengajuan</small>
                     </div>
                     <div class="avatar avatar-lg">
                        <div class="avatar-initial bg-label-primary rounded">
                           <i class="ri-file-list-3-line ri-24px"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <!-- Table -->
      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="datatables-izin table table-hover">
               <thead>
                  <tr>
                     <th>#</th>
                     <th>Pegawai</th>
                     <th>Jenis Izin</th>
                     <th>Tanggal</th>
                     <th>Jumlah</th>
                     <th>Status</th>
                     <th>Aksi</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($data as $index => $izin)
                     <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                           <div class="d-flex align-items-center">
                              <div class="avatar avatar-sm me-2">
                                 <img src="{{ $izin->pegawai->foto_url }}" alt="" class="rounded-circle">
                              </div>
                              <div>
                                 <strong>{{ $izin->pegawai->nama_lengkap }}</strong>
                                 <br><small class="text-muted">{{ $izin->pegawai->divisi->nama ?? '-' }}</small>
                              </div>
                           </div>
                        </td>
                        <td>
                           {{ $izin->jenisIzin->nama }}
                           @if ($izin->file_surat)
                              <a href="{{ $izin->file_surat_url }}" target="_blank" class="ms-1">
                                 <i class="ri-attachment-line text-primary" title="Lihat Lampiran"></i>
                              </a>
                           @endif
                        </td>
                        <td>
                           {{ $izin->tgl_mulai->format('d/m/Y') }}
                           @if ($izin->tgl_mulai != $izin->tgl_selesai)
                              - {{ $izin->tgl_selesai->format('d/m/Y') }}
                           @endif
                        </td>
                        <td>{{ $izin->jumlah_hari }} hari</td>
                        <td>
                           @if ($izin->status_approval === 'Pending')
                              <span class="badge bg-warning">Menunggu</span>
                           @elseif($izin->status_approval === 'Approved')
                              <span class="badge bg-success">Disetujui</span>
                           @else
                              <span class="badge bg-danger">Ditolak</span>
                           @endif
                        </td>
                        <td>
                           <div class="d-flex gap-1">
                              <a href="{{ route('izin.show', $izin->id) }}" class="btn btn-sm btn-outline-info">
                                 <i class="ri-eye-line"></i>
                              </a>
                              @if ($izin->status_approval === 'Pending')
                                 <button type="button" class="btn btn-sm btn-success btn-approve"
                                    data-id="{{ $izin->id }}" data-name="{{ $izin->pegawai->nama_lengkap }}">
                                    <i class="ri-check-line"></i>
                                 </button>
                                 <button type="button" class="btn btn-sm btn-danger btn-reject"
                                    data-id="{{ $izin->id }}" data-name="{{ $izin->pegawai->nama_lengkap }}">
                                    <i class="ri-close-line"></i>
                                 </button>
                              @endif
                           </div>
                        </td>
                     </tr>
                  @endforeach
               </tbody>
            </table>
         </div>

      </div>
   </div>

   <!-- Modal Reject -->
   <div class="modal fade" id="rejectModal" tabindex="-1">
      <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title">Tolak Izin</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reject-form">
               <div class="modal-body">
                  <p>Izin dari: <strong id="reject-name"></strong></p>
                  <div class="mb-3">
                     <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                     <textarea class="form-control" id="reject-catatan" name="catatan" rows="3" required
                        placeholder="Jelaskan alasan penolakan..."></textarea>
                     <small class="text-muted">Minimal 10 karakter</small>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-danger">Tolak Izin</button>
               </div>
            </form>
         </div>
      </div>
   </div>
@endsection

@section('page-script')
   <script>
      window.addEventListener('load', function() {
         const dt_izin = $('.datatables-izin');

         if (dt_izin.length) {
            dt_izin.DataTable({
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
            $('div.head-label').html('<h5 class="card-title mb-0">Semua Pengajuan Izin</h5>');
         }

         let currentRejectId = null;
         const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));

         // Approve
         document.querySelectorAll('.btn-approve').forEach(btn => {
            btn.addEventListener('click', function() {
               const id = this.dataset.id;
               const name = this.dataset.name;

               window.AlertHandler.confirm(
                  'Setujui Izin?',
                  `Setujui pengajuan izin dari "${name}"?`,
                  'Ya, Setujui',
                  function() {
                     fetch(`{{ url('izin/admin') }}/${id}/approve`, {
                           method: 'POST',
                           headers: {
                              'X-CSRF-TOKEN': '{{ csrf_token() }}',
                              'Accept': 'application/json',
                              'Content-Type': 'application/json'
                           },
                           body: JSON.stringify({})
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
            });
         });

         // Reject - Open Modal
         document.querySelectorAll('.btn-reject').forEach(btn => {
            btn.addEventListener('click', function() {
               currentRejectId = this.dataset.id;
               document.getElementById('reject-name').textContent = this.dataset.name;
               document.getElementById('reject-catatan').value = '';
               rejectModal.show();
            });
         });

         // Reject - Submit
         document.getElementById('reject-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const catatan = document.getElementById('reject-catatan').value;

            if (catatan.length < 10) {
               window.AlertHandler.showError('Alasan penolakan minimal 10 karakter');
               return;
            }

            fetch(`{{ url('izin/admin') }}/${currentRejectId}/reject`, {
                  method: 'POST',
                  headers: {
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json',
                     'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({
                     catatan: catatan
                  })
               })
               .then(response => response.json())
               .then(data => {
                  rejectModal.hide();
                  window.AlertHandler.handle(data);
                  if (data.success) {
                     setTimeout(() => window.location.reload(), 1500);
                  }
               });
         });
      });
   </script>
@endsection
