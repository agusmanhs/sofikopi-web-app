@extends('layouts/layoutMaster')

@section('title', 'Detail Izin')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Izin /</span> Detail Pengajuan
         </h4>
      </div>

      <div class="row">
         <div class="col-md-8 mx-auto">
            <div class="card">
               <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">Detail Izin #{{ $data->id }}</h5>
                  <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                     <i class="ri-arrow-left-line me-1"></i>Kembali
                  </a>
               </div>
               <div class="card-body">
                  <!-- Status Badge -->
                  <div class="text-center mb-4">
                     @if ($data->status_approval === 'Pending')
                        <span class="badge bg-warning fs-5 px-4 py-2">
                           <i class="ri-time-line me-1"></i>Menunggu Persetujuan
                        </span>
                     @elseif($data->status_approval === 'Approved')
                        <span class="badge bg-success fs-5 px-4 py-2">
                           <i class="ri-check-line me-1"></i>Disetujui
                        </span>
                     @else
                        <span class="badge bg-danger fs-5 px-4 py-2">
                           <i class="ri-close-line me-1"></i>Ditolak
                        </span>
                     @endif
                  </div>

                  <table class="table table-borderless">
                     <tr>
                        <td class="text-muted" style="width: 35%;">Pegawai</td>
                        <td><strong>{{ $data->pegawai->nama_lengkap }}</strong></td>
                     </tr>
                     <tr>
                        <td class="text-muted">NIP</td>
                        <td>{{ $data->pegawai->nip ?? '-' }}</td>
                     </tr>
                     <tr>
                        <td class="text-muted">Jenis Izin</td>
                        <td><strong>{{ $data->jenisIzin->nama }}</strong></td>
                     </tr>
                     <tr>
                        <td class="text-muted">Tanggal</td>
                        <td>
                           {{ $data->tgl_mulai->format('d F Y') }}
                           @if ($data->tgl_mulai != $data->tgl_selesai)
                              <strong>s/d</strong> {{ $data->tgl_selesai->format('d F Y') }}
                           @endif
                        </td>
                     </tr>
                     <tr>
                        <td class="text-muted">Jumlah Hari</td>
                        <td>{{ $data->jumlah_hari }} hari</td>
                     </tr>
                     <tr>
                        <td class="text-muted">Alasan</td>
                        <td>{{ $data->alasan }}</td>
                     </tr>
                     @if ($data->file_surat)
                        <tr>
                           <td class="text-muted">Surat Pendukung</td>
                           <td>
                              <a href="{{ $data->file_surat_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                 <i class="ri-file-download-line me-1"></i>Lihat File
                              </a>
                           </td>
                        </tr>
                     @endif
                     <tr>
                        <td class="text-muted">Tanggal Pengajuan</td>
                        <td>{{ $data->created_at->format('d F Y H:i') }}</td>
                     </tr>
                  </table>

                  @if ($data->status_approval !== 'Pending')
                     <hr>
                     <h6 class="mb-3"><i class="ri-user-settings-line me-1"></i>Info Approval</h6>
                     <table class="table table-borderless">
                        <tr>
                           <td class="text-muted" style="width: 35%;">Diproses oleh</td>
                           <td>{{ $data->approver->name ?? '-' }}</td>
                        </tr>
                        <tr>
                           <td class="text-muted">Waktu Proses</td>
                           <td>{{ $data->approved_at ? $data->approved_at->format('d F Y H:i') : '-' }}</td>
                        </tr>
                        @if ($data->catatan_admin)
                           <tr>
                              <td class="text-muted">Catatan Admin</td>
                              <td>{{ $data->catatan_admin }}</td>
                           </tr>
                        @endif
                     </table>
                  @elseif(auth()->user()->role &&
                          (auth()->user()->role->slug === 'super-admin' || auth()->user()->hasPermission('izin.admin', 'update')))
                     <hr>
                     <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success flex-grow-1 btn-approve"
                           data-id="{{ $data->id }}" data-name="{{ $data->pegawai->nama_lengkap }}">
                           <i class="ri-check-line me-1"></i>Setujui Izin
                        </button>
                        <button type="button" class="btn btn-outline-danger flex-grow-1 btn-reject"
                           data-id="{{ $data->id }}" data-name="{{ $data->pegawai->nama_lengkap }}">
                           <i class="ri-close-line me-1"></i>Tolak Izin
                        </button>
                     </div>
                  @endif
               </div>
            </div>
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
               window.AlertHandler.error('Alasan penolakan minimal 10 karakter');
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
