@extends('layouts/layoutMaster')

@section('title', 'Admin - Semua Kunjungan')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-style')
   <style>
      .stat-card {
         border: none;
         border-radius: 12px;
         transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      .stat-card:hover {
         transform: translateY(-2px);
         box-shadow: 0 8px 25px rgba(0,0,0,0.08);
      }
      /* Fix DataTable search bar alignment on mobile */
      @media (max-width: 767.98px) {
         .card-header.flex-column .dt-action-buttons {
            text-align: start !important;
         }
         .card-header.flex-column .head-label {
            text-align: start !important;
         }
         .dataTables_filter {
            text-align: start !important;
         }
         .dataTables_filter label {
            justify-content: flex-start !important;
         }
      }
      .shadow-2xl {
         box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      }
   </style>
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
      <div class="row g-3 mb-4">
         <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
               <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                     <div>
                        <p class="text-muted mb-1 small">Total Kunjungan</p>
                        <h3 class="mb-0 fw-bold">{{ $data->count() }}</h3>
                     </div>
                     <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                           <i class="ri-clipboard-line ri-24px"></i>
                        </span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
               <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                     <div>
                        <p class="text-muted mb-1 small">Bulan Ini</p>
                        <h3 class="mb-0 fw-bold">{{ $data->where('tanggal_kunjungan', '>=', now()->startOfMonth())->count() }}</h3>
                     </div>
                     <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                           <i class="ri-calendar-check-line ri-24px"></i>
                        </span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
               <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                     <div>
                        <p class="text-muted mb-1 small">Outlet Dikunjungi</p>
                        <h3 class="mb-0 fw-bold">{{ $data->pluck('mitra_id')->unique()->count() }}</h3>
                     </div>
                     <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                           <i class="ri-store-2-line ri-24px"></i>
                        </span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
               <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                     <div>
                        <p class="text-muted mb-1 small">Petugas Aktif</p>
                        <h3 class="mb-0 fw-bold">{{ $data->pluck('user_id')->unique()->count() }}</h3>
                     </div>
                     <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                           <i class="ri-user-star-line ri-24px"></i>
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
            <form method="GET" action="{{ route('aktivitas.kunjungan.admin.index') }}" class="row g-3 align-items-end">
               <div class="col-md-3 col-6">
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
               <div class="col-md-3 col-6">
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
               <div class="col-md-2 col-6">
                  <label class="form-label">Dari Tanggal</label>
                  <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
               </div>
               <div class="col-md-2 col-6">
                  <label class="form-label">Sampai Tanggal</label>
                  <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
               </div>
               <div class="col-md-2">
                  <div class="d-flex gap-2 flex-wrap">
                     <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="ri-filter-line me-1"></i>Filter
                     </button>
                     <a href="{{ route('aktivitas.kunjungan.admin.export', request()->all()) }}" class="btn btn-success flex-grow-1">
                        <i class="ri-file-excel-line me-1"></i>Export
                     </a>
                     <a href="{{ route('aktivitas.kunjungan.admin.index') }}" class="btn btn-outline-secondary" title="Reset">
                        <i class="ri-refresh-line"></i>
                     </a>
                  </div>
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
                     <th></th>
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
                        <td></td>
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
                              <a href="javascript:void(0);" onclick="window.previewKunjunganFoto('{{ $kunjungan->foto_url }}', 'Foto Kunjungan - {{ $kunjungan->mitra->name ?? '-' }}')">
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

   <!-- Modal Preview Foto (Premium Style) -->
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
                     style="z-index: 10; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); color: white; font-weight: 600; letter-spacing: 0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                  </div>
                  <img src="" id="foto-preview" class="img-fluid w-100 shadow-lg"
                     style="max-height: 85vh; object-fit: contain; background: #000; border-radius: 12px;">
                  <div
                     class="position-absolute bottom-0 end-0 mb-3 me-3 px-2 py-1 bg-dark bg-opacity-50 text-white rounded small"
                     style="font-size: 10px;">
                     <i class="ri-shield-check-line me-1"></i>Verified QC Visit Photo
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
@endsection

@section('page-script')
   <script type="module">
      $(function() {
         window.previewKunjunganFoto = function(url, title) {
            const modal = new bootstrap.Modal(document.getElementById('modalPreviewFoto'));
            document.getElementById('foto-preview').src = url;
            document.getElementById('modal-photo-title').textContent = title;
            modal.show();
         }

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
               responsive: {
                  details: {
                     display: $.fn.dataTable.Responsive.display.modal({
                        header: function(row) {
                           return 'Detail Laporan Kunjungan';
                        }
                     }),
                     type: 'column',
                     renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                        tableClass: 'table'
                     })
                  }
               },
               columnDefs: [{
                  className: 'control',
                  orderable: false,
                  targets: 0
               }],
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
