@extends('layouts/layoutMaster')

@section('title', 'Data Hari Libur')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
   @vite(['resources/assets/vendor/scss/pages/app-calendar.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/moment/moment.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Master Data /</span> Hari Libur
         </h4>
      </div>

      <div class="card mb-4">
         <div class="card-body">
            <div class="row g-3 align-items-end">
               <div class="col-md-3">
                  <form action="{{ route('hari-libur.index') }}" method="GET" id="filterForm">
                     <label class="form-label">Tahun</label>
                     <select name="year" class="form-select" onchange="this.form.submit()">
                        @for ($y = now()->year - 1; $y <= now()->year + 2; $y++)
                           <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}
                           </option>
                        @endfor
                     </select>
                  </form>
               </div>
               <div class="col-md-9 text-end">
                  <form action="{{ route('hari-libur.sync') }}" method="POST" class="d-inline">
                     @csrf
                     <input type="hidden" name="year" value="{{ $year }}">
                     <button type="submit" class="btn btn-primary me-2">
                        <i class="ri-refresh-line me-1"></i> Perbarui dari Data Pemerintah
                     </button>
                  </form>
                  <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
                     <i class="ri-add-line me-1"></i> Tambah Manual
                  </button>
               </div>
            </div>
         </div>
      </div>

      <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
         <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-table-tab" data-bs-toggle="pill" data-bs-target="#pills-table"
               type="button" role="tab">
               <i class="ri-table-line me-1"></i> Tabel
            </button>
         </li>
         <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-calendar-tab" data-bs-toggle="pill" data-bs-target="#pills-calendar"
               type="button" role="tab">
               <i class="ri-calendar-line me-1"></i> Kalender
            </button>
         </li>
      </ul>

      <div class="tab-content p-0" id="pills-tabContent">
         <div class="tab-pane fade show active" id="pills-table" role="tabpanel">
            <div class="card">
               <div class="table-responsive">
                  <table class="table table-hover">
                     <thead class="table-light">
                        <tr>
                           <th>Tanggal</th>
                           <th>Keterangan Libur</th>
                           <th class="text-center">Tipe</th>
                           <th class="text-center">Cuti Bersama</th>
                           <th class="text-center">Aksi</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($data as $item)
                           <tr>
                              <td style="width: 15%;">
                                 <span class="fw-bold">{{ $item->tanggal->format('d/m/Y') }}</span>
                                 <br>
                                 <small class="text-muted">{{ $item->tanggal->locale('id')->isoFormat('dddd') }}</small>
                              </td>
                              <td>
                                 <span class="fw-bold text-dark">{{ $item->nama }}</span>
                                 @if ($item->deskripsi)
                                    <br><small class="text-muted">{{ Str::limit($item->deskripsi, 50) }}</small>
                                 @endif
                              </td>
                              <td class="text-center">
                                 @if ($item->is_nasional)
                                    <span class="badge bg-label-danger">Nasional</span>
                                 @else
                                    <span class="badge bg-label-secondary">Lokal</span>
                                 @endif
                              </td>
                              <td class="text-center">
                                 @if ($item->is_cuti_bersama)
                                    <span class="badge bg-label-warning">Ya</span>
                                 @else
                                    <span class="badge bg-label-success">Tidak</span>
                                 @endif
                              </td>
                              <td class="text-center">
                                 <form action="{{ route('hari-libur.destroy', $item->id) }}" method="POST"
                                    class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <a href="{{ route('hari-libur.edit', $item->id) }}"
                                       class="btn btn-sm btn-icon btn-text-primary rounded-pill me-2">
                                       <i class="ri-pencil-line"></i>
                                    </a>
                                    <button type="submit"
                                       class="btn btn-sm btn-icon btn-text-danger rounded-pill btn-delete"
                                       data-name="{{ $item->nama }}">
                                       <i class="ri-delete-bin-line"></i>
                                    </button>
                                 </form>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="5" class="text-center py-5 text-muted">Belum ada data hari libur untuk tahun
                                 {{ $year }}.</td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
               <div class="card-footer d-flex justify-content-end">
                  {{ $data->appends(['year' => $year])->links() }}
               </div>
            </div>
         </div>
         <div class="tab-pane fade" id="pills-calendar" role="tabpanel">
            <div class="card p-4">
               <div id="calendar"></div>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal Add -->
   <div class="modal fade" id="modalAdd" tabindex="-1">
      <div class="modal-dialog">
         <form action="{{ route('hari-libur.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
               <h5 class="modal-title">Tambah Hari Libur</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
               <div class="mb-3">
                  <label class="form-label">Tanggal</label>
                  <input type="date" name="tanggal" class="form-control" required value="{{ date('Y-m-d') }}">
               </div>
               <div class="mb-3">
                  <label class="form-label">Nama Libur</label>
                  <input type="text" name="nama" class="form-control" required
                     placeholder="Contoh: Gathering Kantor">
               </div>
               <div class="row">
                  <div class="col-6 mb-3">
                     <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_nasional" value="1"
                           id="is_nasional">
                        <label class="form-check-label" for="is_nasional">Libur Nasional</label>
                     </div>
                  </div>
                  <div class="col-6 mb-3">
                     <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_cuti_bersama" value="1"
                           id="is_cuti_bersama">
                        <label class="form-check-label" for="is_cuti_bersama">Cuti Bersama</label>
                     </div>
                  </div>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
               <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
         </form>
      </div>
   </div>
@endsection

@section('page-script')
   <script>
      document.addEventListener('DOMContentLoaded', function() {
         const calendarEl = document.getElementById('calendar');
         const calendarTab = document.getElementById('pills-calendar-tab');

         let calendar;

         function initCalendar() {
            if (calendarEl && typeof window.Calendar !== 'undefined') {
               if (!calendar) {
                  try {
                     calendar = new window.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        locale: 'id',
                        plugins: [window.dayGridPlugin, window.interactionPlugin, window.listPlugin],
                        headerToolbar: {
                           start: 'prev,next title',
                           end: 'dayGridMonth,listMonth'
                        },
                        buttonText: {
                           today: 'Hari Ini',
                           month: 'Bulan',
                           list: 'List'
                        },
                        events: function(info, successCallback, failureCallback) {
                           fetch("{{ route('api.hari-libur.events') }}?start=" + info.startStr + "&end=" +
                                 info.endStr)
                              .then(response => response.json())
                              .then(data => successCallback(data))
                              .catch(error => {
                                 console.error('Error fetching events:', error);
                                 failureCallback(error);
                              });
                        },
                        eventClassNames: function({
                           event: calendarEvent
                        }) {
                           const colorName = calendarEvent.extendedProps.calendar || 'primary';
                           return ['fc-event-' + colorName];
                        },
                        eventDidMount: function(info) {
                           if (info.event.extendedProps.description) {
                              new bootstrap.Tooltip(info.el, {
                                 title: info.event.extendedProps.description,
                                 placement: 'top',
                                 trigger: 'hover',
                                 container: 'body'
                              });
                           }
                        },
                        eventClick: function(info) {
                           if (window.AlertHandler) {
                              window.AlertHandler.swal.fire({
                                 title: info.event.title,
                                 text: info.event.extendedProps.description ||
                                    'Tidak ada keterangan tambahan.',
                                 icon: 'info',
                                 confirmButtonText: 'Tutup',
                                 customClass: {
                                    confirmButton: 'btn btn-primary'
                                 },
                                 buttonsStyling: false
                              });
                           }
                        }
                     });
                     calendar.render();
                  } catch (error) {
                     console.error('Error initializing calendar:', error);
                  }
               } else {
                  calendar.render();
                  calendar.updateSize();
               }
            }
         }

         // Initialize calendar when tab is shown
         if (calendarTab) {
            calendarTab.addEventListener('shown.bs.tab', function() {
               setTimeout(() => {
                  initCalendar();
               }, 50);
            });
         }

         // Also if URL has #pills-calendar
         if (window.location.hash === '#pills-calendar') {
            const tab = new bootstrap.Tab(calendarTab);
            tab.show();
         }

         // Handle Delete Confirmation
         $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const name = $(this).find('.btn-delete').data('name') || 'data ini';

            if (window.AlertHandler) {
               window.AlertHandler.confirm(
                  'Hapus Hari Libur?',
                  `Apakah Anda yakin ingin menghapus "${name}"?`,
                  'Ya, Hapus!',
                  function() {
                     form.submit();
                  }
               );
            } else {
               if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                  form.submit();
               }
            }
         });
      });
   </script>
@endsection
