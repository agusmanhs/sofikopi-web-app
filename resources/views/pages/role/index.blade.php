@extends('layouts/layoutMaster')

@section('title', 'Manajemen Role')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      @if (session('success'))
         <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
      @endif

      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Manajemen /</span> Role
         </h4>
         <a href="{{ route('role.create') }}" class="btn btn-primary">
            <i class="ri-add-line me-1"></i> Tambah Role
         </a>
      </div>

      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="datatables-roles table table-hover">
               <thead>
                  <tr>
                     <th>#</th>
                     <th>Nama Role</th>
                     <th>Slug</th>
                     <th>Aksi</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($roles as $index => $role)
                     <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $role->name }}</strong></td>
                        <td><code>{{ $role->slug }}</code></td>
                        <td>
                           <a href="{{ route('role.edit', $role->id) }}" class="btn btn-sm btn-outline-primary">
                              <i class="ri-pencil-line"></i>
                           </a>
                           <button type="button" class="btn btn-sm btn-outline-danger delete-record"
                              data-action="{{ route('role.destroy', $role->id) }}">
                              <i class="ri-delete-bin-line"></i>
                           </button>
                        </td>
                     </tr>
                  @endforeach
               </tbody>
            </table>
         </div>

      </div>
   </div>
@endsection

@section('page-script')
   <script>
      window.addEventListener('load', function() {
         const dt_role = $('.datatables-roles');

         if (dt_role.length) {
            dt_role.DataTable({
               responsive: true,
               displayLength: 10,
               lengthMenu: [10, 25, 50, 75, 100],
               language: {
                  paginate: {
                     next: '<i class="ri-arrow-right-s-line"></i>',
                     previous: '<i class="ri-arrow-left-s-line"></i>'
                  },
                  search: "",
                  searchPlaceholder: "Cari Role...",
                  lengthMenu: "_MENU_",
                  info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
               },
               dom: '<"card-header flex-column flex-md-row border-bottom"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"fB>><"row"<"col-sm-12 col-md-6"l>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
               buttons: []
            });
            $('div.head-label').html('<h5 class="card-title mb-0">Daftar Role</h5>');
         }
      });
   </script>
   @vite(['resources/assets/js/app-role-index.js'])
@endsection
