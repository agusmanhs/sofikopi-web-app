@extends('layouts/layoutMaster')

@section('title', 'My Profile')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <h4 class="fw-bold py-3 mb-4">
         <span class="text-muted fw-light">Account Settings /</span> My Profile
      </h4>

      @if (session('success'))
         <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
      @endif

      <div class="row">
         <div class="col-md-12">
            <div class="card mb-4">
               <h5 class="card-header">Profile Details</h5>
               <form id="formAccountSettings" method="POST" action="{{ route('profile.update') }}"
                  enctype="multipart/form-data">
                  @csrf
                  @method('PUT')

                  <!-- Account -->
                  <div class="card-body">
                     <div class="d-flex align-items-start align-items-sm-center gap-4">
                        <img src="{{ $user->profile_photo_url }}" alt="user-avatar"
                           class="d-block w-px-100 h-px-100 rounded" id="uploadedAvatar" style="object-fit: cover;" />
                        <div class="button-wrapper">
                           <label for="upload" class="btn btn-primary me-2 mb-3" tabindex="0">
                              <span class="d-none d-sm-block">Upload new photo</span>
                              <i class="ri-upload-2-line d-block d-sm-none"></i>
                              <input type="file" id="upload" name="foto" class="account-file-input" hidden
                                 accept="image/png, image/jpeg" />
                           </label>
                           {{-- <button type="button" class="btn btn-outline-secondary account-image-reset mb-3">
                              <i class="ri-refresh-line d-block d-sm-none"></i>
                              <span class="d-none d-sm-block">Reset</span>
                           </button> --}}
                           <div class="text-muted small">Allowed JPG, GIF or PNG. Max size of 2048K</div>
                           @error('foto')
                              <div class="text-danger small mt-1">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                  </div>
                  <hr class="my-0">
                  <div class="card-body">
                     <div class="row">
                        <div class="col-md-6 mb-4">
                           <div class="form-floating form-floating-outline">
                              <input class="form-control @error('name') is-invalid @enderror" type="text" id="name"
                                 name="name" value="{{ old('name', $user->name) }}" autofocus required />
                              <label for="name">Username</label>
                           </div>
                           @error('name')
                              <div class="text-danger small mt-1">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-6 mb-4">
                           <div class="form-floating form-floating-outline">
                              <input class="form-control @error('email') is-invalid @enderror" type="email"
                                 id="email" name="email" value="{{ old('email', $user->email) }}" required />
                              <label for="email">E-mail</label>
                           </div>
                           @error('email')
                              <div class="text-danger small mt-1">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-6 mb-4">
                           <div class="form-floating form-floating-outline">
                              <input class="form-control @error('nama_lengkap') is-invalid @enderror" type="text"
                                 id="nama_lengkap" name="nama_lengkap"
                                 value="{{ old('nama_lengkap', $pegawai->nama_lengkap ?? $user->name) }}" required />
                              <label for="nama_lengkap">Nama Lengkap</label>
                           </div>
                           @error('nama_lengkap')
                              <div class="text-danger small mt-1">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-6 mb-4">
                           <div class="form-floating form-floating-outline">
                              <input class="form-control @error('no_telp') is-invalid @enderror" type="text"
                                 id="no_telp" name="no_telp" value="{{ old('no_telp', $pegawai->no_telp ?? '') }}" />
                              <label for="no_telp">No. Telepon</label>
                           </div>
                           @error('no_telp')
                              <div class="text-danger small mt-1">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="col-md-12 mb-4">
                           <div class="form-floating form-floating-outline">
                              <textarea class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" rows="3"
                                 style="height: 100px;">{{ old('alamat', $pegawai->alamat ?? '') }}</textarea>
                              <label for="alamat">Alamat</label>
                           </div>
                           @error('alamat')
                              <div class="text-danger small mt-1">{{ $message }}</div>
                           @enderror
                        </div>

                        @if ($pegawai)
                           <div class="col-md-6 mb-4">
                              <div class="form-floating form-floating-outline">
                                 <input class="form-control" type="text" value="{{ $pegawai->nip }}" readonly
                                    disabled />
                                 <label>NIP</label>
                              </div>
                           </div>
                           <div class="col-md-6 mb-4">
                              <div class="form-floating form-floating-outline">
                                 <input class="form-control" type="text" value="{{ $pegawai->divisi->nama ?? '-' }}"
                                    readonly disabled />
                                 <label>Divisi</label>
                              </div>
                           </div>
                           <div class="col-md-6 mb-4">
                              <div class="form-floating form-floating-outline">
                                 <input class="form-control" type="text" value="{{ $pegawai->jabatan ?? '-' }}"
                                    readonly disabled />
                                 <label>Jabatan</label>
                              </div>
                           </div>
                        @endif
                     </div>
                     <div class="mt-2">
                        <button type="submit" class="btn btn-primary me-2">Save changes</button>
                        {{-- <button type="reset" class="btn btn-outline-secondary">Discard</button> --}}
                     </div>
                  </div>
               </form>
            </div>
         </div>
      </div>
   </div>

   <script>
      document.addEventListener('DOMContentLoaded', function(e) {
         (function() {
            const uploadedAvatar = document.getElementById('uploadedAvatar');
            const fileInput = document.querySelector('.account-file-input');
            const resetFileInput = document.querySelector('.account-image-reset');

            if (uploadedAvatar) {
               const resetImage = uploadedAvatar.src;
               fileInput.onchange = () => {
                  if (fileInput.files[0]) {
                     uploadedAvatar.src = window.URL.createObjectURL(fileInput.files[0]);
                  }
               };
               resetFileInput.onclick = () => {
                  fileInput.value = '';
                  uploadedAvatar.src = resetImage;
               };
            }
         })();
      });
   </script>
@endsection
