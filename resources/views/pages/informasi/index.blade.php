@extends('layouts/layoutMaster')

@section('title', 'Manajemen Informasi')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">Manajemen Informasi</h4>
         <a href="{{ route('informasi.create') }}" class="btn btn-primary">
            <i class="ri-add-line me-1"></i> Tambah Informasi
         </a>
      </div>


      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="table border-top datatable">
               <thead>
                  <tr>
                     <th width="50">No</th>
                     <th>Gambar</th>
                     <th>Judul</th>
                     <th>Penulis</th>
                     <th>Tanggal</th>
                     <th width="150">Aksi</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($data as $informasi)
                     <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                           <img src="{{ $informasi->gambar_url }}" alt="Cover" class="rounded" width="60"
                              height="40" style="object-fit: cover;">
                        </td>
                        <td>{{ $informasi->judul }}</td>
                        <td>{{ $informasi->user->name ?? '-' }}</td>
                        <td>{{ $informasi->created_at->format('d M Y') }}</td>
                        <td>
                           <div class="d-flex gap-2">
                              <a href="{{ route('informasi.show', $informasi->id) }}"
                                 class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect">
                                 <i class="ri-eye-line"></i>
                              </a>
                              <a href="{{ route('informasi.edit', $informasi->id) }}"
                                 class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect">
                                 <i class="ri-edit-box-line"></i>
                              </a>
                              <form action="{{ route('informasi.destroy', $informasi->id) }}" method="POST"
                                 class="delete-form">
                                 @csrf
                                 @method('DELETE')
                                 <button type="button"
                                    class="btn btn-sm btn-icon btn-text-danger rounded-pill waves-effect btn-delete">
                                    <i class="ri-delete-bin-7-line"></i>
                                 </button>
                              </form>
                           </div>
                        </td>
                     </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
      </div>
   </div>
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
   <script>
      document.addEventListener('DOMContentLoaded', function() {
         // Gunakan jQuery dari window jika tersedia
         const jQuery = window.jQuery;
         if (typeof jQuery !== 'undefined') {
            jQuery('.datatable').DataTable({
               responsive: true,
               dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            });

            // SweetAlert Delete Confirmation
            jQuery('.btn-delete').on('click', function() {
               const form = jQuery(this).closest('form');
               if (window.AlertHandler) {
                  window.AlertHandler.confirm(
                     'Hapus Informasi?',
                     'Data yang dihapus tidak dapat dikembalikan!',
                     'Ya, Hapus!',
                     function() {
                        form.submit();
                     }
                  );
               } else {
                  if (confirm('Apakah Anda yakin ingin menghapus informasi ini?')) {
                     form.submit();
                  }
               }
            });
         }
      });
   </script>
@endsection
