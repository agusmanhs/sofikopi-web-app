@extends('layouts/layoutMaster')

@section('title', $informasi->judul)

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Informasi /</span> Detail
         </h4>
         <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i> Kembali
         </a>
      </div>

      <div class="row justify-content-center">
         <div class="col-lg-8">
            <div class="card overflow-hidden">
               <img class="img-fluid cursor-pointer" src="{{ $informasi->gambar_url }}" alt="Thumbnail"
                  style="width: 100%; max-height: 400px; object-fit: cover;" data-bs-toggle="modal"
                  data-bs-target="#imagePreviewModal">
               <div class="card-body p-sm-7 p-4">
                  <div class="d-flex justify-content-between mb-4">
                     <div class="d-flex align-items-center">
                        <i class="ri-user-line me-1 ri-20px"></i>
                        <span class="fw-medium">{{ $informasi->user->name ?? 'System' }}</span>
                     </div>
                     <div class="d-flex align-items-center">
                        <i class="ri-calendar-line me-1 ri-20px"></i>
                        <span class="text-muted">{{ $informasi->created_at->format('d M Y, H:i') }}</span>
                     </div>
                  </div>
                  <h2 class="mb-4">{{ $informasi->judul }}</h2>
                  <div class="text-body mt-4 informasi-content" style="line-height: 1.8;">
                     {!! $informasi->isi !!}
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal Preview Image -->
   <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-full" role="document">
         <div class="modal-content bg-transparent border-0 shadow-none">
            <div class="modal-body p-0 text-center">
               <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2"
                  data-bs-dismiss="modal" aria-label="Close" style="z-index: 100; font-size: 1.5rem;"></button>
               <img src="{{ $informasi->gambar_url }}"
                  class="img-fluid rounded shadow-lg animate__animated animate__zoomIn" alt="Full Preview"
                  style="max-height: 95vh; width: auto;">
            </div>
         </div>
      </div>
   </div>

   <style>
      .modal-full {
         max-width: 95vw;
         margin: 10px auto;
      }

      .cursor-pointer {
         cursor: pointer;
         transition: transform 0.3s ease, opacity 0.3s ease;
      }

      .cursor-pointer:hover {
         opacity: 0.9;
         transform: scale(1.01);
      }

      .informasi-content img {
         max-width: 100%;
         height: auto;
         border-radius: 8px;
      }
   </style>
@endsection
