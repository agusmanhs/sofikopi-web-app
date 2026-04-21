@extends('layouts/layoutMaster')

@section('title', 'Detail Kunjungan')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Aktivitas / Kunjungan /</span> Detail
         </h4>
      </div>

      <div class="row">
         <div class="col-lg-8 mx-auto">
            <div class="card mb-4">
               <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0"><i class="ri-clipboard-line me-2"></i>Laporan Quality Control</h5>
                  <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                     <i class="ri-arrow-left-line me-1"></i>Kembali
                  </a>
               </div>
               <div class="card-body">
                  {{-- Header Info --}}
                  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start mb-4 gap-2">
                     <div>
                        <h6 class="text-primary mb-1">
                           <i class="ri-store-2-line me-1"></i>{{ $data->mitra->name ?? '-' }}
                        </h6>
                        @if($data->mitra && $data->mitra->address)
                           <small class="text-muted">
                              <i class="ri-map-pin-line me-1"></i>{{ $data->mitra->address }}
                           </small>
                        @endif
                     </div>
                     <div class="text-sm-end">
                        <span class="badge bg-label-primary fs-6">
                           {{ $data->tanggal_kunjungan->format('d M Y') }}
                        </span>
                        <br>
                        <small class="text-muted">
                           oleh {{ $data->user->pegawai->nama_lengkap ?? $data->user->name ?? '-' }}
                        </small>
                     </div>
                  </div>

                  {{-- Visit Type Badge --}}
                  @if($data->visit_type)
                  <div class="mb-4">
                     <span class="badge bg-label-{{ $data->visit_type == 'routine' ? 'success' : 'warning' }} px-3 py-2">
                        <i class="ri-{{ $data->visit_type == 'routine' ? 'repeat-line' : 'phone-line' }} me-1"></i>
                        {{ $data->visit_type == 'routine' ? 'Kunjungan Rutin' : 'By Request' }}
                     </span>
                  </div>
                  @endif

                  <hr>

                  {{-- Espresso Calibration --}}
                  <div class="mb-4">
                     <h6 class="text-uppercase text-muted mb-2">
                        <i class="ri-settings-4-line me-1"></i> Espresso Calibration
                     </h6>
                     <div class="bg-light rounded p-3">
                        {!! nl2br(e($data->espresso_calibration)) !!}
                     </div>
                  </div>

                  {{-- Taste Notes --}}
                  <div class="mb-4">
                     <h6 class="text-uppercase text-muted mb-2">
                        <i class="ri-goblet-line me-1"></i> Taste Notes
                     </h6>
                     <div class="bg-light rounded p-3">
                        {!! nl2br(e($data->taste_notes)) !!}
                     </div>
                  </div>

                  {{-- Flow of Customers --}}
                  @if($data->flow_of_customers)
                  <div class="mb-4">
                     <h6 class="text-uppercase text-muted mb-2">
                        <i class="ri-group-line me-1"></i> Flow of Customers
                     </h6>
                     <div class="bg-light rounded p-3">
                        {!! nl2br(e($data->flow_of_customers)) !!}
                     </div>
                  </div>
                  @endif

                  {{-- Feedback --}}
                  @if($data->feedback)
                  <div class="mb-4">
                     <h6 class="text-uppercase text-muted mb-2">
                        <i class="ri-feedback-line me-1"></i> Feedback
                     </h6>
                     <div class="bg-light rounded p-3">
                        {!! nl2br(e($data->feedback)) !!}
                     </div>
                  </div>
                  @endif

                  {{-- Problem --}}
                  @if($data->problem)
                  <div class="mb-4">
                     <h6 class="text-uppercase text-muted mb-2">
                        <i class="ri-error-warning-line me-1 text-danger"></i> Problem
                     </h6>
                     <div class="bg-danger bg-opacity-10 rounded p-3 border border-danger border-opacity-25">
                        {!! nl2br(e($data->problem)) !!}
                     </div>
                  </div>
                  @endif

                  {{-- Note --}}
                  @if($data->note)
                  <div class="mb-4">
                     <h6 class="text-uppercase text-muted mb-2">
                        <i class="ri-sticky-note-line me-1"></i> Note
                     </h6>
                     <div class="bg-light rounded p-3">
                        {!! nl2br(e($data->note)) !!}
                     </div>
                  </div>
                  @endif

                  {{-- Foto Kunjungan --}}
                  @if($data->foto_url)
                  <div class="mb-3">
                     <h6 class="text-uppercase text-muted mb-2">
                        <i class="ri-camera-line me-1"></i> Foto Kunjungan
                     </h6>
                     <a href="javascript:void(0);" onclick="window.previewFoto('{{ $data->foto_url }}', 'Foto Kunjungan - {{ $data->mitra->name ?? '-' }}')">
                        <img src="{{ $data->foto_url }}" alt="Foto Kunjungan"
                           class="rounded border img-fluid" style="max-height: 400px; object-fit: cover; cursor: pointer;">
                     </a>
                  </div>
                  @endif
               </div>
               <div class="card-footer text-muted">
                  <small>
                     <i class="ri-time-line me-1"></i>Dibuat pada {{ $data->created_at->format('d M Y, H:i') }}
                  </small>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal Preview Foto -->
   <div class="modal fade" id="modalPreviewFoto" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
         <div class="modal-content bg-transparent shadow-none border-0">
            <div class="modal-header border-0 p-0 mb-3 justify-content-end">
               <button type="button" class="btn btn-icon btn-light rounded-circle shadow-lg" data-bs-dismiss="modal"
                  aria-label="Close" style="width: 40px; height: 40px;">
                  <i class="ri-close-line ri-xl text-dark"></i>
               </button>
            </div>
            <div class="modal-body p-0 text-center">
               <div class="position-relative overflow-hidden rounded-4 shadow-2xl">
                  <div id="modal-photo-title"
                     class="position-absolute top-0 start-50 translate-middle-x mt-3 px-4 py-2 rounded-pill shadow-lg"
                     style="z-index: 10; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); color: white; font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                  </div>
                  <img src="" id="foto-preview" class="img-fluid w-100 shadow-lg"
                     style="max-height: 85vh; object-fit: contain; background: #000; border-radius: 12px;">
               </div>
            </div>
         </div>
      </div>
   </div>

   <style>
      .shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
   </style>
@endsection

@section('page-script')
   <script>
      window.previewFoto = function(url, title) {
         const modal = new bootstrap.Modal(document.getElementById('modalPreviewFoto'));
         document.getElementById('foto-preview').src = url;
         document.getElementById('modal-photo-title').textContent = title;
         modal.show();
      }
   </script>
@endsection
