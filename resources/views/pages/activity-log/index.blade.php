@extends('layouts/layoutMaster')

@section('title', 'Activity Log')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/select2/select2.js'])
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
                  <div class="col-md-4">
                     <label class="form-label">Cari</label>
                     <input type="text" name="search" class="form-control" placeholder="Nama user atau deskripsi"
                        value="{{ $filters['search'] ?? '' }}">
                  </div>
                  <div class="col-md-2">
                     <label class="form-label">Modul</label>
                     <select name="module" class="form-select">
                        <option value="">Semua Modul</option>
                        @foreach ($modules as $module)
                           <option value="{{ $module }}" {{ ($filters['module'] ?? '') == $module ? 'selected' : '' }}>
                              {{ ucwords(str_replace(['_', '-'], ' ', $module)) }}
                           </option>
                        @endforeach
                     </select>
                  </div>
                  <div class="col-md-3">
                     <label class="form-label">Aksi</label>
                     <select name="action" class="form-select">
                        <option value="">Semua Aksi</option>
                        @foreach ($actions as $action)
                           <option value="{{ $action }}"
                              {{ ($filters['action'] ?? '') == $action ? 'selected' : '' }}>
                              {{ ucwords(str_replace('_', ' ', $action)) }}
                           </option>
                        @endforeach
                     </select>
                  </div>
                  <div class="col-md-3">
                     <label class="form-label">User</label>
                     <select name="user_id" class="form-select select2">
                        <option value="">Semua User</option>
                        @foreach ($users as $user)
                           <option value="{{ $user->id }}"
                              {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                              {{ $user->name }}
                           </option>
                        @endforeach
                     </select>
                  </div>
                  <div class="col-md-3">
                     <label class="form-label">Dari Tanggal</label>
                     <input type="date" name="start_date" class="form-control"
                        value="{{ $filters['start_date'] ?? '' }}">
                  </div>
                  <div class="col-md-3">
                     <label class="form-label">Sampai Tanggal</label>
                     <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                  </div>
                  <div class="col-md-3 d-flex align-items-end gap-2">
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
         <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Riwayat Aktivitas</h5>
            <small class="text-muted">{{ $logs->total() }} aktivitas</small>
         </div>
         <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
               <thead class="table-light">
                  <tr>
                     <th width="130">Waktu</th>
                     <th width="170">User</th>
                     <th width="110">Modul</th>
                     <th width="130">Aksi</th>
                     <th>Deskripsi</th>
                     <th width="120">IP Address</th>
                     <th width="70">Detail</th>
                  </tr>
               </thead>
               <tbody>
                  @forelse($logs as $log)
                     @php
                        $detail = array_filter([
                            'properties' => $log->properties,
                            'old' => $log->old_values,
                            'new' => $log->new_values,
                        ]);
                        $namaUser = $log->user_name ?: $log->user?->name;
                     @endphp
                     <tr>
                        <td>
                           <small class="text-muted">
                              {{ $log->created_at->format('d M Y') }}<br>
                              <strong>{{ $log->created_at->format('H:i:s') }}</strong>
                           </small>
                        </td>
                        <td>
                           @if ($namaUser)
                              <div class="d-flex align-items-center">
                                 <div class="avatar avatar-sm me-2">
                                    <span class="avatar-initial rounded-circle bg-label-primary">
                                       {{ strtoupper(mb_substr($namaUser, 0, 2)) }}
                                    </span>
                                 </div>
                                 <span class="fw-medium">{{ $namaUser }}</span>
                              </div>
                           @else
                              <span class="text-muted">System</span>
                           @endif
                        </td>
                        <td><span class="badge bg-label-secondary">{{ $log->module_label }}</span></td>
                        <td>
                           <span class="badge bg-label-{{ $log->action_color }}">{{ $log->action_label }}</span>
                        </td>
                        <td>
                           {{ $log->description ?? '-' }}
                           @if ($log->subject_type)
                              <br><small class="text-muted">{{ class_basename($log->subject_type) }}
                                 #{{ $log->subject_id }}</small>
                           @endif
                        </td>
                        <td><small class="text-muted">{{ $log->ip_address ?? '-' }}</small></td>
                        <td>
                           @if ($detail)
                              <button type="button" class="btn btn-sm btn-icon btn-outline-primary btn-log-detail"
                                 data-title="{{ $log->action_label }} - {{ $namaUser ?? 'System' }}"
                                 data-detail="{{ json_encode($detail, JSON_UNESCAPED_UNICODE) }}">
                                 <i class="ri-eye-line"></i>
                              </button>
                           @else
                              <span class="text-muted">-</span>
                           @endif
                        </td>
                     </tr>
                  @empty
                     <tr>
                        <td colspan="7" class="text-center py-4">
                           <div class="text-muted">
                              <i class="ri-history-line ri-48px mb-2 d-block"></i>
                              <p class="mb-0">Tidak ada aktivitas yang cocok.</p>
                           </div>
                        </td>
                     </tr>
                  @endforelse
               </tbody>
            </table>
         </div>

         @if ($logs->hasPages())
            <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
               <small class="text-muted">
                  Menampilkan {{ $logs->firstItem() }} - {{ $logs->lastItem() }} dari {{ $logs->total() }}
               </small>
               {{ $logs->links('pagination::bootstrap-5') }}
            </div>
         @endif
      </div>
   </div>

   {{-- Detail Modal (satu untuk semua baris) --}}
   <div class="modal fade" id="logDetailModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="logDetailTitle">Detail</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailBody"></div>
         </div>
      </div>
   </div>
@endsection

@section('page-script')
   <script>
      window.addEventListener('load', function() {
         $('.select2').select2({ placeholder: '-- Pilih --', allowClear: true });

         const modalEl = document.getElementById('logDetailModal');
         const modal = new bootstrap.Modal(modalEl);
         const body = document.getElementById('logDetailBody');

         const label = (key) => key.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());

         const section = (title, data, colorClass) => {
            const wrap = document.createElement('div');
            wrap.className = 'mb-4';
            const h = document.createElement('h6');
            h.className = colorClass;
            h.textContent = title;
            wrap.appendChild(h);

            const table = document.createElement('table');
            table.className = 'table table-sm table-bordered mb-0';
            Object.entries(data).forEach(([key, value]) => {
               const tr = table.insertRow();
               const th = document.createElement('th');
               th.className = 'w-25 bg-light';
               th.textContent = label(key);
               const td = tr.insertCell();
               td.textContent = value === null || value === '' ? '-' :
                  (typeof value === 'object' ? JSON.stringify(value) : String(value));
               tr.insertBefore(th, td);
            });
            wrap.appendChild(table);
            return wrap;
         };

         document.querySelectorAll('.btn-log-detail').forEach((btn) => {
            btn.addEventListener('click', () => {
               const detail = JSON.parse(btn.dataset.detail);
               document.getElementById('logDetailTitle').textContent = btn.dataset.title;
               body.replaceChildren();
               if (detail.properties) body.appendChild(section('Rincian', detail.properties, 'text-primary'));
               if (detail.old) body.appendChild(section('Data Sebelum', detail.old, 'text-danger'));
               if (detail.new) body.appendChild(section('Data Sesudah', detail.new, 'text-success'));
               modal.show();
            });
         });
      });
   </script>
@endsection
