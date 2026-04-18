@extends('layouts/layoutMaster')

@section('title', 'Admin - Semua Kunjungan')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Admin / Aktivitas /</span> Riwayat Kunjungan
         </h4>
      </div>

      @if (session('success'))
         <div class="alert alert-success alert-dismissible mb-4">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
         </div>
      @endif

      {{-- Stats Cards --}}
      <div class="row mb-4">
         <div class="col-sm-6 col-xl-3">
            <div class="card">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between">
                     <div class="content-left">
                        <span class="text-heading">Total Kunjungan</span>
                        <div class="d-flex align-items-center my-1">
                           <h4 class="mb-0 me-2">{{ $data->count() }}</h4>
                        </div>
                        <small class="mb-0">Semua laporan</small>
                     </div>
                     <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                           <i class="ri-clipboard-line ri-26px"></i>
                        </span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between">
                     <div class="content-left">
                        <span class="text-heading">Bulan Ini</span>
                        <div class="d-flex align-items-center my-1">
                           <h4 class="mb-0 me-2">
                              {{ $data->where('tanggal_kunjungan', '>=', now()->startOfMonth())->count() }}</h4>
                        </div>
                        <small class="mb-0">{{ now()->format('F Y') }}</small>
                     </div>
                     <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                           <i class="ri-calendar-check-line ri-26px"></i>
                        </span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between">
                     <div class="content-left">
                        <span class="text-heading">Outlet Dikunjungi</span>
                        <div class="d-flex align-items-center my-1">
                           <h4 class="mb-0 me-2">{{ $data->pluck('mitra_id')->unique()->count() }}</h4>
                        </div>
                        <small class="mb-0">Outlet unik</small>
                     </div>
                     <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                           <i class="ri-store-2-line ri-26px"></i>
                        </span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between">
                     <div class="content-left">
                        <span class="text-heading">Petugas Aktif</span>
                        <div class="d-flex align-items-center my-1">
                           <h4 class="mb-0 me-2">{{ $data->pluck('user_id')->unique()->count() }}</h4>
                        </div>
                        <small class="mb-0">User yg melakukan kunjungan</small>
                     </div>
                     <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                           <i class="ri-user-star-line ri-26px"></i>
                        </span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      {{-- Filter --}}
      <div class="card mb-4">
         <div class="card-body">
            <form method="GET" action="{{ route('aktivitas.kunjungan.admin.index') }}" class="row g-3">
               <div class="col-md-3">
                  <label class="form-label">Petugas</label>
                  <select name="user_id" class="form-select select2-filter">
                     <option value="">Semua Petugas</option>
                     @foreach ($users as $user)
                        <option value="{{ $user->id }}"
                           {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                           {{ $user->pegawai->nama_lengkap ?? $user->name }}
                        </option>
                     @endforeach
                  </select>
               </div>
               <div class="col-md-3">
                  <label class="form-label">Outlet</label>
                  <select name="mitra_id" class="form-select select2-filter">
                     <option value="">Semua Outlet</option>
                     @foreach ($mitras as $mitra)
                        <option value="{{ $mitra->id }}"
                           {{ ($filters['mitra_id'] ?? '') == $mitra->id ? 'selected' : '' }}>
                           {{ $mitra->name }}
                        </option>
                     @endforeach
                  </select>
               </div>
               <div class="col-md-2">
                  <label class="form-label">Dari Tanggal</label>
                  <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
               </div>
               <div class="col-md-2">
                  <label class="form-label">Sampai Tanggal</label>
                  <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
               </div>
               <div class="col-md-2 d-flex align-items-end gap-2">
                  <button type="submit" class="btn btn-primary"><i class="ri-filter-line me-1"></i>Filter</button>
                  <a href="{{ route('aktivitas.kunjungan.admin.index') }}" class="btn btn-outline-secondary"><i
                        class="ri-refresh-line"></i></a>
               </div>
            </form>
         </div>
      </div>

      {{-- Data Table --}}
      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="datatables-admin-kunjungan table table-hover">
               <thead>
                  <tr>
                     <th>#</th>
                     <th>Tanggal</th>
                     <th>Petugas</th>
                     <th>Outlet</th>
                     <th>Espresso Calibration</th>
                     <th>Foto</th>
                     <th>Aksi</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($data as $index => $kunjungan)
                     <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                           <span class="fw-semibold">{{ $kunjungan->tanggal_kunjungan->format('d M Y') }}</span>
                        </td>
                        <td>
                           <div class="d-flex align-items-center">
                              <div class="avatar avatar-sm me-2">
                                 <span class="avatar-initial rounded-circle bg-label-primary">
                                    {{ strtoupper(substr($kunjungan->user->pegawai->nama_lengkap ?? ($kunjungan->user->name ?? '?'), 0, 1)) }}
                                 </span>
                              </div>
                              <span>{{ $kunjungan->user->pegawai->nama_lengkap ?? ($kunjungan->user->name ?? '-') }}</span>
                           </div>
                        </td>
                        <td>
                           <span class="fw-bold">{{ $kunjungan->mitra->name ?? '-' }}</span>
                        </td>
                        <td>
                           <span class="d-inline-block text-truncate" style="max-width: 180px;">
                              {{ $kunjungan->espresso_calibration }}
                           </span>
                        </td>
                        <td>
                           @if ($kunjungan->foto_url)
                              <a href="{{ $kunjungan->foto_url }}" target="_blank">
                                 <img src="{{ $kunjungan->foto_url }}" alt="Foto" class="rounded"
                                    style="width: 40px; height: 40px; object-fit: cover;">
                              </a>
                           @else
                              <span class="text-muted">-</span>
                           @endif
                        </td>
                        <td>
                           <div class="d-flex gap-1">
                              <a href="{{ route('aktivitas.kunjungan.admin.show', $kunjungan->id) }}"
                                 class="btn btn-sm btn-outline-info" title="Detail">
                                 <i class="ri-eye-line"></i>
                              </a>
                              @if (auth()->user()->role->slug === 'super-admin')
                                 <button type="button" class="btn btn-sm btn-outline-danger btn-delete-kunjungan"
                                    data-id="{{ $kunjungan->id }}" data-outlet="{{ $kunjungan->mitra->name ?? '-' }}"
                                    data-date="{{ $kunjungan->tanggal_kunjungan->format('d M Y') }}" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
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
@endsection

@section('page-script')
   <script type="module">
      $(function() {
         // Init Select2 for filters
         $('.select2-filter').each(function() {
            var $this = $(this);
            $this.wrap('<div class="position-relative"></div>').select2({
               placeholder: $this.find('option:first').text(),
               allowClear: true,
               dropdownParent: $this.parent()
            });
         });

         // Init DataTable
         const dt = $('.datatables-admin-kunjungan');
         if (dt.length) {
            dt.DataTable({
               responsive: true,
               displayLength: 25,
               lengthMenu: [10, 25, 50, 100],
               order: [
                  [1, 'desc']
               ],
               language: {
                  paginate: {
                     next: '<i class="ri-arrow-right-s-line"></i>',
                     previous: '<i class="ri-arrow-left-s-line"></i>'
                  },
                  search: "",
                  searchPlaceholder: "Cari...",
                  lengthMenu: "_MENU_",
                  info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                  emptyTable: "Tidak ada data kunjungan",
               },
               dom: '<"card-header flex-column flex-md-row border-bottom"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"fB>><"row"<"col-sm-12 col-md-6"l>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
               buttons: []
            });
            $('div.head-label').html('<h5 class="card-title mb-0">Semua Laporan Kunjungan</h5>');
         }

         // Delete handler (admin only)
         $(document).on('click', '.btn-delete-kunjungan', function() {
            const id = $(this).data('id');
            const outlet = $(this).data('outlet');
            const date = $(this).data('date');

            window.AlertHandler.confirm(
               'Hapus Kunjungan?',
               `Hapus laporan kunjungan "${outlet}" tanggal ${date}?`,
               'Ya, Hapus!',
               function() {
                  fetch(`{{ url('aktivitas/kunjungan/admin') }}/${id}`, {
                        method: 'DELETE',
                        headers: {
                           'X-CSRF-TOKEN': '{{ csrf_token() }}',
                           'Accept': 'application/json'
                        }
                     })
                     .then(r => r.json())
                     .then(data => {
                        window.AlertHandler.handle(data);
                        if (data.success) {
                           setTimeout(() => location.reload(), 1500);
                        }
                     })
                     .catch(err => {
                        console.error(err);
                        window.AlertHandler.showError('Terjadi kesalahan sistem');
                     });
               }
            );
         });
      });
   </script>
@endsection
