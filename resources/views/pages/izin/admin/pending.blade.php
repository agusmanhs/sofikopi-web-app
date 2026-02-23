@extends('layouts/layoutMaster')

@section('title', 'Izin Pending')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Admin / Izin /</span> Menunggu Persetujuan
         </h4>
         <a href="{{ route('izin.admin.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Kembali
         </a>
      </div>
      @if ($data->isEmpty())
         <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
               <div class="avatar avatar-xl bg-label-success mx-auto mb-4" style="width: 100px; height: 100px;">
                  <span class="avatar-initial rounded-circle"><i class="ri-check-double-line ri-4x"></i></span>
               </div>
               <h4 class="fw-bold text-dark">Tidak Ada Antrian Persetujuan</h4>
               <p class="text-muted mx-auto" style="max-width: 400px;">
                  Semua pengajuan izin telah diproses. Saat ini belum ada pengajuan baru yang menunggu persetujuan Anda.
               </p>
               <a href="{{ route('izin.admin.index') }}" class="btn btn-primary mt-2">
                  <i class="ri-history-line me-1"></i>Lihat Riwayat Izin
               </a>
            </div>
         </div>
      @else
         <div class="row g-4">
            @foreach ($data as $izin)
               <div class="col-md-6 col-lg-4">
                  <div class="card h-100 border-0 shadow-sm overflow-hidden ripple-effect">
                     <div class="card-header bg-label-secondary border-bottom p-3">
                        <div class="d-flex align-items-center justify-content-between">
                           <div class="d-flex align-items-center">
                              <div class="avatar avatar-sm me-2">
                                 <img src="{{ $izin->pegawai->foto_url }}" alt="avatar"
                                    class="rounded-circle border border-2 border-white shadow-sm">
                              </div>
                              <div class="user-info">
                                 <h6 class="mb-0 text-dark fw-bold">{{ $izin->pegawai->nama_lengkap }}</h6>
                                 <small class="text-muted d-block"
                                    style="font-size: 0.75rem;">{{ $izin->pegawai->divisi->nama ?? '-' }}</small>
                              </div>
                           </div>
                           <span class="badge bg-label-warning px-2 rounded-pill">Pending</span>
                        </div>
                     </div>
                     <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                           <div>
                              <p class="text-muted small mb-1">Jenis Pengajuan</p>
                              <h5 class="mb-0 fw-bold text-primary">{{ $izin->jenisIzin->nama }}</h5>
                           </div>
                           <div class="text-end">
                              <p class="text-muted small mb-1">Durasi</p>
                              <span class="badge bg-light text-dark border fw-bold">{{ $izin->jumlah_hari }} Hari</span>
                           </div>
                        </div>

                        <div class="bg-light rounded p-3 mb-3 border-start border-4 border-info">
                           <div class="d-flex align-items-center mb-2">
                              <i class="ri-calendar-event-line text-info me-2"></i>
                              <span class="fw-medium small text-dark">Rentang Tanggal</span>
                           </div>
                           <p class="mb-0 text-dark fw-bold small">
                              {{ $izin->tgl_mulai->translatedFormat('d M Y') }}
                              @if ($izin->tgl_mulai != $izin->tgl_selesai)
                                 <span class="text-muted fw-normal px-1">s/d</span>
                                 {{ $izin->tgl_selesai->translatedFormat('d M Y') }}
                              @endif
                           </p>
                        </div>

                        <div class="mb-3">
                           <label class="text-muted small mb-1">Alasan:</label>
                           <p class="text-dark mb-0 bg-white p-2 border rounded shadow-none"
                              style="font-size: 0.85rem; border-style: dashed !important;">
                              {{ $izin->alasan }}
                           </p>
                        </div>

                        @if ($izin->file_surat)
                           <a href="{{ $izin->file_surat_url }}" target="_blank"
                              class="btn btn-sm btn-outline-info w-100 mb-3 border-dashed">
                              <i class="ri-attachment-line me-1"></i>Lihat Bukti Pendukung
                           </a>
                        @endif

                        <div class="d-flex align-items-center mt-3 pt-3 border-top">
                           <small class="text-muted">
                              <i class="ri-history-line me-1"></i>Diajukan {{ $izin->created_at->diffForHumans() }}
                           </small>
                        </div>
                     </div>
                     <div class="card-footer bg-white border-top p-3">
                        <div class="row g-2">
                           <div class="col-6">
                              <button type="button" class="btn btn-success w-100 btn-approve shadow-sm"
                                 data-id="{{ $izin->id }}" data-name="{{ $izin->pegawai->nama_lengkap }}">
                                 <i class="ri-check-line me-1"></i>Terima
                              </button>
                           </div>
                           <div class="col-6">
                              <button type="button" class="btn btn-outline-danger w-100 btn-reject"
                                 data-id="{{ $izin->id }}" data-name="{{ $izin->pegawai->nama_lengkap }}">
                                 <i class="ri-close-line me-1"></i>Tolak
                              </button>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            @endforeach
         </div>
      @endif
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
