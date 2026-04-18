@extends('layouts/layoutMaster')

@section('title', 'Riwayat Kunjungan')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Aktivitas /</span> Riwayat Kunjungan
         </h4>
         <a href="{{ route('aktivitas.kunjungan.create') }}" class="btn btn-primary">
            <i class="ri-add-line me-1"></i>Buat Kunjungan
         </a>
      </div>

      @if (session('success'))
         <div class="alert alert-success alert-dismissible mb-4">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
         </div>
      @endif

      @if (session('error'))
         <div class="alert alert-danger alert-dismissible mb-4">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
         </div>
      @endif

      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="datatables-kunjungan table table-hover">
               <thead>
                  <tr>
                     <th>#</th>
                     <th>Tanggal</th>
                     <th>Outlet</th>
                     <th>Espresso Calibration</th>
                     <th>Taste Notes</th>
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
                           <span class="fw-bold">{{ $kunjungan->mitra->name ?? '-' }}</span>
                           @if($kunjungan->mitra && $kunjungan->mitra->address)
                              <br><small class="text-muted">{{ Str::limit($kunjungan->mitra->address, 40) }}</small>
                           @endif
                        </td>
                        <td>
                           <span class="d-inline-block text-truncate" style="max-width: 200px;">
                              {{ $kunjungan->espresso_calibration }}
                           </span>
                        </td>
                        <td>
                           <span class="d-inline-block text-truncate" style="max-width: 200px;">
                              {{ $kunjungan->taste_notes }}
                           </span>
                        </td>
                        <td>
                           @if($kunjungan->foto_url)
                              <a href="{{ $kunjungan->foto_url }}" target="_blank">
                                 <img src="{{ $kunjungan->foto_url }}" alt="Foto" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                              </a>
                           @else
                              <span class="text-muted">-</span>
                           @endif
                        </td>
                        <td>
                           <a href="{{ route('aktivitas.kunjungan.show', $kunjungan->id) }}" class="btn btn-sm btn-outline-info" title="Detail">
                              <i class="ri-eye-line"></i>
                           </a>
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
         const dt = $('.datatables-kunjungan');
         if (dt.length) {
            dt.DataTable({
               responsive: true,
               displayLength: 10,
               lengthMenu: [10, 25, 50],
               order: [[1, 'desc']],
               language: {
                  paginate: {
                     next: '<i class="ri-arrow-right-s-line"></i>',
                     previous: '<i class="ri-arrow-left-s-line"></i>'
                  },
                  search: "",
                  searchPlaceholder: "Cari...",
                  lengthMenu: "_MENU_",
                  info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                  emptyTable: "Belum ada riwayat kunjungan",
               },
               dom: '<"card-header flex-column flex-md-row border-bottom"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"fB>><"row"<"col-sm-12 col-md-6"l>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
               buttons: []
            });
            $('div.head-label').html('<h5 class="card-title mb-0">Riwayat Kunjungan Saya</h5>');
         }
      });
   </script>
@endsection
