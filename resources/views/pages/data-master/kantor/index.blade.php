@extends('layouts/layoutMaster')

@section('title', 'Manajemen Kantor')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Data Master /</span> Kantor
         </h4>
         <a href="{{ route('kantor.create') }}" class="btn btn-primary">
            <i class="ri-add-line me-1"></i>Tambah Kantor
         </a>
      </div>

      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="datatables-kantor table table-hover">
               <thead>
                  <tr>
                     <th>#</th>
                     <th>Kode</th>
                     <th>Nama Kantor</th>
                     <th>Alamat</th>
                     <th>Koordinat</th>
                     <th>Radius</th>
                     <th>Status</th>
                     <th class="text-center">Aksi</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($data as $index => $item)
                     <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><code>{{ $item->kode ?? '-' }}</code></td>
                        <td><strong>{{ $item->nama }}</strong></td>
                        <td>
                           <span class="d-inline-block text-truncate" style="max-width: 200px;">
                              {{ $item->alamat ?? '-' }}
                           </span>
                        </td>
                        <td>
                           <small class="text-muted">
                              {{ $item->titik_lokasi }}
                           </small>
                           <a href="https://maps.google.com/?q={{ $item->titik_lokasi }}" target="_blank" class="ms-1"
                              title="Lihat di Maps">
                              <i class="ri-map-pin-line text-primary"></i>
                           </a>
                        </td>
                        <td><span class="badge bg-label-info">{{ $item->radius_meter }}m</span></td>
                        <td>
                           @if ($item->is_aktif)
                              <span class="badge bg-success">Aktif</span>
                           @else
                              <span class="badge bg-secondary">Non-Aktif</span>
                           @endif
                        </td>
                        <td class="text-center">
                           <div class="d-flex justify-content-center gap-2">
                              <a href="{{ route('kantor.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">
                                 <i class="ri-pencil-line"></i>
                              </a>
                              <button type="button" class="btn btn-sm btn-outline-danger delete-record"
                                 data-id="{{ $item->id }}" data-name="{{ $item->nama }}">
                                 <i class="ri-delete-bin-line"></i>
                              </button>
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
         const dt_kantor = $('.datatables-kantor');

         if (dt_kantor.length) {
            dt_kantor.DataTable({
               responsive: true,
               displayLength: 10,
               lengthMenu: [10, 25, 50, 75, 100],
               language: {
                  paginate: {
                     next: '<i class="ri-arrow-right-s-line"></i>',
                     previous: '<i class="ri-arrow-left-s-line"></i>'
                  },
                  search: "",
                  searchPlaceholder: "Cari...",
                  lengthMenu: "_MENU_",
                  info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
               },
               dom: '<"card-header flex-column flex-md-row border-bottom"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"fB>><"row"<"col-sm-12 col-md-6"l>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
               buttons: []
            });
            $('div.head-label').html('<h5 class="card-title mb-0">Daftar Kantor</h5>');
         }

         document.querySelectorAll('.delete-record').forEach(btn => {
            btn.addEventListener('click', function() {
               const id = this.dataset.id;
               const name = this.dataset.name;

               window.AlertHandler.confirm(
                  'Hapus Kantor?',
                  `Apakah Anda yakin ingin menghapus kantor "${name}"?`,
                  'Ya, Hapus!',
                  function() {
                     fetch(`{{ url('kantor') }}/${id}`, {
                           method: 'DELETE',
                           headers: {
                              'X-CSRF-TOKEN': '{{ csrf_token() }}',
                              'Accept': 'application/json'
                           }
                        })
                        .then(response => response.json())
                        .then(data => {
                           window.AlertHandler.handle(data);
                           if (data.success) {
                              setTimeout(() => window.location.reload(), 1500);
                           }
                        });
                  }
               );
            });
         });
      });
   </script>
@endsection
