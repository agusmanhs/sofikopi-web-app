@extends('layouts/layoutMaster')

@section('title', 'Activity Log')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      {{-- Header --}}
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Dashboard /</span> Activity Log
         </h4>
      </div>

      {{-- Filter Card --}}
      <div class="card mb-4">
         <div class="card-header">
            <h5 class="card-title mb-0">
               <i class="ri-filter-2-line me-1"></i> Filter
            </h5>
         </div>
         <div class="card-body">
            <form action="{{ route('activity-log.index') }}" method="GET">
               <div class="row g-3">
                  <div class="col-md-3">
                     <label class="form-label">Aksi</label>
                     <select name="action" class="form-select">
                        <option value="">Semua Aksi</option>
                        @foreach ($actions as $action)
                           <option value="{{ $action }}"
                              {{ ($filters['action'] ?? '') == $action ? 'selected' : '' }}>
                              {{ ucfirst($action) }}
                           </option>
                        @endforeach
                     </select>
                  </div>
                  <div class="col-md-3">
                     <label class="form-label">User</label>
                     <select name="user_id" class="form-select">
                        <option value="">Semua User</option>
                        @foreach ($users as $user)
                           <option value="{{ $user->id }}"
                              {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                              {{ $user->name }}
                           </option>
                        @endforeach
                     </select>
                  </div>
                  <div class="col-md-2">
                     <label class="form-label">Dari Tanggal</label>
                     <input type="date" name="start_date" class="form-control"
                        value="{{ $filters['start_date'] ?? '' }}">
                  </div>
                  <div class="col-md-2">
                     <label class="form-label">Sampai Tanggal</label>
                     <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                  </div>
                  <div class="col-md-2 d-flex align-items-end gap-2">
                     <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line me-1"></i> Filter
                     </button>
                     <a href="{{ route('activity-log.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-refresh-line"></i>
                     </a>
                  </div>
               </div>
            </form>
         </div>
      </div>

      {{-- Activity Log Table --}}
      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="datatables-logs table table-hover">
               <thead class="table-light">
                  <tr>
                     <th width="180">Waktu</th>
                     <th width="120">User</th>
                     <th width="100">Aksi</th>
                     <th>Deskripsi</th>
                     <th width="120">IP Address</th>
                     <th width="80">Detail</th>
                  </tr>
               </thead>
               <tbody>
                  @forelse($logs as $log)
                     <tr>
                        <td data-order="{{ $log->created_at->timestamp }}">
                           <small class="text-muted">
                              {{ $log->created_at->format('d M Y') }}<br>
                              <strong>{{ $log->created_at->format('H:i:s') }}</strong>
                           </small>
                        </td>
                        <td>
                           @if ($log->user)
                              <div class="d-flex align-items-center">
                                 <div class="avatar avatar-sm me-2">
                                    <span class="avatar-initial rounded-circle bg-label-primary">
                                       {{ strtoupper(substr($log->user->name, 0, 2)) }}
                                    </span>
                                 </div>
                                 <span class="fw-medium">{{ $log->user->name }}</span>
                              </div>
                           @else
                              <span class="text-muted">System</span>
                           @endif
                        </td>
                        <td>
                           <span class="badge bg-label-{{ $log->action_color }}">
                              {{ $log->action_label }}
                           </span>
                        </td>
                        <td>
                           {{ $log->description ?? '-' }}
                           @if ($log->subject_type)
                              <br><small class="text-muted">{{ class_basename($log->subject_type) }}
                                 #{{ $log->subject_id }}</small>
                           @endif
                        </td>
                        <td>
                           <small class="text-muted">{{ $log->ip_address ?? '-' }}</small>
                        </td>
                        <td>
                           @if ($log->old_values || $log->new_values)
                              <button type="button" class="btn btn-sm btn-icon btn-outline-primary" data-bs-toggle="modal"
                                 data-bs-target="#detailModal{{ $log->id }}">
                                 <i class="ri-eye-line"></i>
                              </button>

                              {{-- Detail Modal --}}
                              <div class="modal fade" id="detailModal{{ $log->id }}" tabindex="-1">
                                 <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                       <div class="modal-header">
                                          <h5 class="modal-title">Detail Perubahan</h5>
                                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                       </div>
                                       <div class="modal-body">
                                          <div class="row">
                                             @if ($log->old_values)
                                                <div class="col-md-6">
                                                   <h6 class="text-danger">Data Sebelum:</h6>
                                                   <pre class="bg-light p-3 rounded"><code>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                </div>
                                             @endif
                                             @if ($log->new_values)
                                                <div class="col-md-6">
                                                   <h6 class="text-success">Data Sesudah:</h6>
                                                   <pre class="bg-light p-3 rounded"><code>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                </div>
                                             @endif
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           @else
                              <span class="text-muted">-</span>
                           @endif
                        </td>
                     </tr>
                  @empty
                     <tr>
                        <td colspan="6" class="text-center py-4">
                           <div class="text-muted">
                              <i class="ri-history-line ri-48px mb-2 d-block"></i>
                              <p>Belum ada aktivitas tercatat.</p>
                           </div>
                        </td>
                     </tr>
                  @endforelse
               </tbody>
            </table>
         </div>

         @if (count($logs) >= 500)
            <div class="alert alert-info mt-3 mx-3">
               <i class="ri-information-line me-1"></i> Menampilkan 500 aktivitas terbaru. Gunakan filter untuk mencari
               data yang lebih spesifik.
            </div>
         @endif
      </div>
   </div>
@endsection

@section('page-script')
   <script>
      window.addEventListener('load', function() {
         const dt_logs = $('.datatables-logs');

         if (dt_logs.length) {
            dt_logs.DataTable({
               responsive: true,
               displayLength: 25,
               lengthMenu: [10, 25, 50, 75, 100],
               order: [
                  [0, 'desc']
               ], // Sort by time desc by default
               language: {
                  paginate: {
                     next: '<i class="ri-arrow-right-s-line"></i>',
                     previous: '<i class="ri-arrow-left-s-line"></i>'
                  },
                  search: "",
                  searchPlaceholder: "Cari di tabel ini...",
                  lengthMenu: "_MENU_",
                  info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
               },
               dom: '<"card-header flex-column flex-md-row border-bottom"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"fB>><"row"<"col-sm-12 col-md-6"l>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
               buttons: []
            });
            $('div.head-label').html('<h5 class="card-title mb-0">Riwayat Aktivitas</h5>');
         }
      });
   </script>
@endsection
