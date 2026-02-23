@extends('layouts/layoutMaster')

@section('title', 'Daftar User')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      {{-- Alerts --}}
      @if (session('success'))
         <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
      @endif

      @if (session('error'))
         <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
      @endif

      {{-- Header --}}
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Dashboard /</span> Daftar User
         </h4>
         <a href="{{ route('user.create') }}" class="btn btn-primary">
            <i class="ri-add-line me-1"></i> Tambah User
         </a>
      </div>

      {{-- Stats Cards --}}
      <div class="row g-4 mb-4">
         <div class="col-sm-6 col-xl-3">
            <div class="card">
               <div class="card-body">
                  <div class="d-flex justify-content-between">
                     <div class="me-1">
                        <p class="text-heading mb-1">Total Users</p>
                        <div class="d-flex align-items-center">
                           <h4 class="mb-0 me-2">{{ $users->count() }}</h4>
                        </div>
                     </div>
                     <div class="avatar">
                        <div class="avatar-initial bg-label-primary rounded-3">
                           <i class="ri-group-line ri-26px"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      {{-- Users Table --}}
      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="datatables-users table table-hover">
               <thead class="table-light">
                  <tr>
                     <th>#</th>
                     <th>Nama</th>
                     <th>Email</th>
                     <th>Role</th>
                     <th>Dibuat</th>
                     <th>Aksi</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($users as $index => $user)
                     <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                           <div class="d-flex align-items-center">
                              <div class="avatar avatar-sm me-2">
                                 <span class="avatar-initial rounded-circle bg-label-primary">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                 </span>
                              </div>
                              <span class="fw-medium">{{ $user->name }}</span>
                           </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                           @if ($user->role)
                              <span class="badge bg-label-primary">{{ $user->role->name }}</span>
                           @else
                              <span class="badge bg-label-secondary">Tidak ada role</span>
                           @endif
                        </td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td>
                           <div class="dropdown">
                              <button type="button"
                                 class="btn btn-sm btn-icon btn-text-secondary dropdown-toggle hide-arrow"
                                 data-bs-toggle="dropdown">
                                 <i class="ri-more-2-line"></i>
                              </button>
                              <div class="dropdown-menu">
                                 <a class="dropdown-item" href="{{ route('user.show', $user->id) }}">
                                    <i class="ri-eye-line me-1"></i> Lihat
                                 </a>
                                 <a class="dropdown-item" href="{{ route('user.edit', $user->id) }}">
                                    <i class="ri-pencil-line me-1"></i> Edit
                                 </a>
                                 <form action="{{ route('user.destroy', $user->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                       <i class="ri-delete-bin-line me-1"></i> Hapus
                                    </button>
                                 </form>
                              </div>
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
@section('page-script')
   <script>
      window.addEventListener('load', function() {
         const dt_user = $('.datatables-users');

         if (dt_user.length) {
            dt_user.DataTable({
               responsive: true,
               displayLength: 10,
               lengthMenu: [10, 25, 50, 75, 100],
               language: {
                  paginate: {
                     next: '<i class="ri-arrow-right-s-line"></i>',
                     previous: '<i class="ri-arrow-left-s-line"></i>'
                  },
                  search: "",
                  searchPlaceholder: "Cari User...",
                  lengthMenu: "_MENU_",
                  info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
               },
               dom: '<"card-header flex-column flex-md-row border-bottom"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"fB>><"row"<"col-sm-12 col-md-6"l>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
               buttons: []
            });
            $('div.head-label').html('<h5 class="card-title mb-0">Daftar User</h5>');
         }
      });
   </script>
@endsection
