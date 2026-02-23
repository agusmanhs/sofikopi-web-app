@extends('layouts/layoutMaster')

@section('title', 'Rekap Absensi')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Absensi /</span> Rekap Bulanan
         </h4>
         <a href="{{ route('absensi.dashboard') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Kembali ke Dashboard
         </a>
      </div>

      <!-- Filter -->
      <div class="card mb-4">
         <div class="card-body">
            <form method="GET" action="{{ route('absensi.rekap') }}" class="row g-3">
               <div class="col-md-4">
                  <label class="form-label">Bulan</label>
                  <select name="bulan" class="form-select">
                     @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
                           {{ \Carbon\Carbon::create()->month((int) $i)->locale('id')->isoFormat('MMMM') }}
                        </option>
                     @endfor
                  </select>
               </div>
               <div class="col-md-4">
                  <label class="form-label">Tahun</label>
                  <select name="tahun" class="form-select">
                     @for ($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}
                        </option>
                     @endfor
                  </select>
               </div>
               <div class="col-md-4 d-flex align-items-end gap-2">
                  <button type="submit" class="btn btn-primary">
                     <i class="ri-filter-line me-1"></i>Filter
                  </button>
                  <button type="button" class="btn btn-success" onclick="exportExcel()">
                     <i class="ri-file-excel-line me-1"></i>Export Excel
                  </button>
               </div>
            </form>
         </div>
      </div>

      <!-- Info Hari Kerja -->
      <div class="row g-4 mb-4">
         <div class="col-md-6">
            <div class="card bg-label-primary border-0 shadow-sm">
               <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                     <h6 class="mb-0 fw-bold text-primary"><i class="ri-calendar-check-line me-1"></i> Shift Reguler (Ikut
                        Libur)</h6>
                     <span class="badge bg-primary">{{ $detailReguler['total'] }} Hari Kerja</span>
                  </div>
                  <ul class="list-unstyled mb-0 small text-muted">
                     <li><i class="ri-checkbox-blank-circle-fill ri-8px me-2 text-primary"></i> Target: 1 s/d
                        {{ $detailReguler['period_end']->format('d M Y') }}</li>
                     <li><i class="ri-checkbox-blank-circle-fill ri-8px me-2 text-danger"></i> Libur Minggu:
                        {{ count($detailReguler['sundays']) }} hari</li>
                     <li><i class="ri-checkbox-blank-circle-fill ri-8px me-2 text-warning"></i> Libur Nasional:
                        {{ $detailReguler['holidays']->count() }} hari</li>
                  </ul>
               </div>
            </div>
         </div>
         <div class="col-md-6">
            <div class="card bg-label-info border-0 shadow-sm">
               <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                     <h6 class="mb-0 fw-bold text-info"><i class="ri-calendar-event-line me-1"></i> Shift Full (Masuk
                        Minggu)</h6>
                     <span class="badge bg-info">{{ $detailFull['total'] }} Hari Kerja</span>
                  </div>
                  <ul class="list-unstyled mb-0 small text-muted">
                     <li><i class="ri-checkbox-blank-circle-fill ri-8px me-2 text-info"></i> Target: 1 s/d
                        {{ $detailFull['period_end']->format('d M Y') }}</li>
                     <li><i class="ri-checkbox-blank-circle-fill ri-8px me-2 text-success"></i> Libur Minggu: (Tetap Masuk)
                     </li>
                     <li><i class="ri-checkbox-blank-circle-fill ri-8px me-2 text-warning"></i> Libur Nasional:
                        {{ $detailFull['holidays']->count() }} hari</li>
                  </ul>
               </div>
            </div>
         </div>

         @if ($detailReguler['holidays']->count() > 0)
            <div class="col-12 mt-3">
               <div class="alert alert-warning border-0 shadow-sm mb-0">
                  <div class="d-flex align-items-center">
                     <i class="ri-notification-3-line me-2"></i>
                     <div class="small">
                        <strong>Daftar Libur Nasional:</strong>
                        @foreach ($detailReguler['holidays'] as $h)
                           {{ $h->tanggal->format('d M') }} ({{ $h->nama }}){{ !$loop->last ? ',' : '' }}
                        @endforeach
                     </div>
                  </div>
               </div>
            </div>
         @endif
      </div>

      <!-- Rekap Table -->
      <div class="card">
         <div class="card-datatable table-responsive">
            <table class="datatables-rekap table table-bordered table-hover" id="rekap-table">
               <thead class="table-light">
                  <tr>
                     <th rowspan="2" class="align-middle text-center">#</th>
                     <th rowspan="2" class="align-middle">Pegawai</th>
                     <th rowspan="2" class="align-middle">Divisi</th>
                     <th colspan="5" class="text-center">Rekap (Bulan Ini)</th>
                     <th rowspan="2" class="align-middle text-center">Total Jam</th>
                     <th rowspan="2" class="align-middle text-center">%</th>
                     <th rowspan="2" class="align-middle text-center">Aksi</th>
                  </tr>
                  <tr>
                     <th class="text-center bg-success text-white">Hadir</th>
                     <th class="text-center bg-warning text-dark">Telat</th>
                     <th class="text-center bg-info text-white">Izin</th>
                     <th class="text-center bg-danger text-white">Alpha</th>
                     <th class="text-center bg-secondary text-white">Sesi</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($data as $index => $pegawai)
                     @php
                        $isReguler = $pegawai->shift && $pegawai->shift->ikut_libur;
                        $hariBerjalan = $isReguler ? $detailReguler['total'] : $detailFull['total'];

                        $absensis = $pegawai->absensis;

                        // 1. Hadir (Tepat Waktu + Dinas + Lainnya yang ada Jam Masuk)
                        $hadirCount = $absensis
                            ->filter(function ($i) {
                                if ($i->status === 'Terlambat') {
                                    return false;
                                }
                                $isHadir = in_array($i->status, ['Tepat Waktu', 'Hadir', 'Dinas Luar Kota', 'Tugas']);
                                $hasClockIn = !is_null($i->jam_masuk);
                                return ($isHadir || $hasClockIn) &&
                                    (!is_null($i->jam_pulang) || $i->tanggal->isToday());
                            })
                            ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                            ->count();

                        // 2. Terlambat (Telat)
                        $terlambatCount = $absensis
                            ->filter(
                                fn($i) => $i->status === 'Terlambat' &&
                                    (!is_null($i->jam_pulang) || $i->tanggal->isToday()),
                            )
                            ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                            ->count();

                        // 3. Izin (Includ Sakit, Cuti, dll)
                        $izinCount = $absensis
                            ->whereIn('status', [
                                'Izin',
                                'Cuti',
                                'Sakit',
                                'Izin Pribadi',
                                'Cuti Tahunan',
                                'Dinas Luar Kota',
                            ])
                            ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                            ->count();

                        // 4. Days Active (untuk hitung Alpha & Persentase)
                        // Menggunakan logic: Izin/Sakit/Cuti ATAU (Sudah Pulang) ATAU (Masuk hari ini)
                        $daysActive = $absensis
                            ->filter(function ($item) {
                                if (
                                    in_array($item->status, [
                                        'Izin',
                                        'Sakit',
                                        'Cuti',
                                        'Izin Pribadi',
                                        'Cuti Tahunan',
                                        'Dinas Luar Kota',
                                    ])
                                ) {
                                    return true;
                                }
                                return !is_null($item->jam_pulang) || $item->tanggal->isToday();
                            })
                            ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                            ->count();

                        // 5. Alpha
                        $alphaCount = max(0, $hariBerjalan - $daysActive);

                        // Total work hours (based on actual duration)
                        $totalMenit = $absensis->sum('durasi_kerja_menit');
                        $totalJam = floor($totalMenit / 60);
                        $sisaMenit = $totalMenit % 60;

                        $persentase = $hariBerjalan > 0 ? round(($daysActive / $hariBerjalan) * 100, 1) : 0;

                        $progressClass = 'bg-danger';
                        if ($persentase >= 90) {
                            $progressClass = 'bg-success';
                        } elseif ($persentase >= 75) {
                            $progressClass = 'bg-primary';
                        } elseif ($persentase >= 50) {
                            $progressClass = 'bg-warning';
                        }
                     @endphp
                     <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                           <div class="d-flex align-items-center">
                              <div class="avatar avatar-sm me-2">
                                 <img src="{{ $pegawai->foto_url }}" alt="" class="rounded-circle">
                              </div>
                              <div>
                                 <h6 class="mb-0 text-truncate" style="max-width: 150px;">{{ $pegawai->nama_lengkap }}
                                 </h6>
                                 <small class="text-muted">{{ $pegawai->nip ?? '-' }}</small>
                              </div>
                           </div>
                        </td>
                        <td><small>{{ $pegawai->divisi->nama ?? '-' }}</small></td>
                        <td class="text-center text-success fw-bold">{{ $hadirCount }}</td>
                        <td class="text-center text-warning fw-bold">{{ $terlambatCount }}</td>
                        <td class="text-center text-info fw-bold">{{ $izinCount }}</td>
                        <td class="text-center text-danger fw-bold">{{ $alphaCount }}</td>
                        <td class="text-center"><span class="badge bg-label-secondary">{{ $absensis->count() }}</span>
                        </td>
                        <td class="text-center">
                           <span class="fw-bold">{{ $totalJam }} Jam</span>
                           @if ($sisaMenit > 0)
                              <small>{{ $sisaMenit }} Menit</small>
                           @endif
                        </td>
                        <td class="text-center">
                           <div class="d-flex align-items-center gap-2">
                              <div class="progress w-100" style="height: 8px;">
                                 <div class="progress-bar {{ $progressClass }}" style="width: {{ $persentase }}%">
                                 </div>
                              </div>
                              <small class="fw-bold">{{ $persentase }}%</small>
                           </div>
                        </td>
                        <td class="text-center">
                           <a href="{{ route('absensi.pegawai-history', $pegawai->id) }}"
                              class="btn btn-sm btn-icon btn-label-primary" title="Lihat Detail Riwayat">
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
         const dt_rekap = $('.datatables-rekap');

         if (dt_rekap.length) {
            dt_rekap.DataTable({
               responsive: true,
               displayLength: 50,
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
            $('div.head-label').html(
               '<h5 class="card-title mb-0">Rekap {{ \Carbon\Carbon::create()->month((int) $bulan)->locale('id')->isoFormat('MMMM') }} {{ $tahun }}</h5>'
            );
         }
      });

      function exportExcel() {
         const bulan = document.querySelector('select[name="bulan"]').value;
         const tahun = document.querySelector('select[name="tahun"]').value;

         window.location.href = `{{ route('absensi.rekap.export') }}?bulan=${bulan}&tahun=${tahun}`;
      }
   </script>
@endsection
