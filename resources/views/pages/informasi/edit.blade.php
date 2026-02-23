@extends('layouts/layoutMaster')

@section('title', 'Edit Informasi')

@section('vendor-style')
   <style>
      .ck-editor__editable {
         min-height: 400px;
      }
   </style>
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Informasi /</span> Edit Informasi
         </h4>
         <a href="{{ route('informasi.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i> Kembali
         </a>
      </div>

      <div class="card">
         <div class="card-body">
            <form action="{{ route('informasi.update', $informasi->id) }}" method="POST" enctype="multipart/form-data"
               id="formInformasi">
               @csrf
               @method('PUT')
               <div class="row">
                  <div class="col-md-8">
                     <div class="form-floating form-floating-outline mb-4">
                        <input type="text" class="form-control @error('judul') is-invalid @enderror" id="judul"
                           name="judul" value="{{ old('judul', $informasi->judul) }}"
                           placeholder="Masukkan judul informasi" required>
                        <label for="judul">Judul Informasi</label>
                        @error('judul')
                           <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                     </div>

                     <div class="mb-4">
                        <label class="form-label">Isi Informasi</label>
                        <textarea name="isi" id="editor" class="form-control @error('isi') is-invalid @enderror">{!! old('isi', $informasi->isi) !!}</textarea>
                        @error('isi')
                           <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                     </div>
                  </div>

                  <div class="col-md-4">
                     <div class="card bg-lighter shadow-none">
                        <div class="card-body">
                           <label class="form-label">Gambar Cover</label>
                           <div class="mb-3 text-center">
                              <div class="preview-container mb-3">
                                 <img src="{{ $informasi->gambar_url }}" alt="Preview" class="img-fluid rounded shadow-sm"
                                    id="preview-img" style="max-height: 200px; width: 100%; object-fit: cover;">
                              </div>
                              <input type="file" class="form-control @error('gambar') is-invalid @enderror"
                                 id="gambar" name="gambar" accept="image/*" onchange="previewImage(this)">
                              @error('gambar')
                                 <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
                           </div>
                           <div class="small text-muted mb-4">
                              Biarkan kosong jika tidak ingin mengubah gambar.
                           </div>
                           <hr>
                           <button type="submit" class="btn btn-primary w-100">
                              <i class="ri-save-line me-1"></i> Perbarui Informasi
                           </button>
                        </div>
                     </div>
                  </div>
               </div>
            </form>
         </div>
      </div>
   </div>
@endsection

@section('vendor-script')
   <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
@endsection

@section('page-script')
   <script>
      function previewImage(input) {
         if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
               $('#preview-img').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
         }
      }

      document.addEventListener('DOMContentLoaded', function() {
         if (typeof ClassicEditor !== 'undefined') {
            ClassicEditor
               .create(document.querySelector('#editor'), {
                  toolbar: [
                     'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote',
                     'undo', 'redo'
                  ]
               })
               .catch(error => {
                  console.error('CKEditor Error:', error);
               });
         } else {
            console.error('ClassicEditor is not defined. Check CDN script loading.');
         }
      });
   </script>
@endsection
