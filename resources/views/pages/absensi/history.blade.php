@extends('layouts/layoutMaster')

@section('title', 'Riwayat Absensi')

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
            <span class="text-muted fw-light">Absensi /</span> Riwayat
         </h4>
         <a href="{{ isset($isAdminView) ? route('absensi.rekap') : route('absensi.index') }}"
            class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Kembali
         </a>
      </div>

      <!-- Filter -->
      <div class="card mb-4">
         <div class="card-body">
            <form method="GET"
               action="{{ isset($isAdminView) ? route('absensi.pegawai-history', $pegawai->id) : route('absensi.history') }}"
               class="row g-3">
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
               <div class="col-md-4 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary">
                     <i class="ri-filter-line me-1"></i>Filter
                  </button>
               </div>
            </form>
         </div>
      </div>

      <!-- Stats -->
      <div class="row mb-4 g-3">
         <!-- Baris 1: Hadir, Izin, Alpha -->
         <div class="col-md-4 col-6">
            <div class="card bg-label-success shadow-sm">
               <div class="card-body text-center">
                  <div
                     class="avatar avatar-md mx-auto mb-2 bg-success text-white rounded d-flex align-items-center justify-content-center">
                     <i class="ri-checkbox-circle-line ri-24px"></i>
                  </div>
                  <h4 class="mb-0 fw-bold">{{ $statistik['hadir'] }}</h4>
                  <small class="text-success fw-medium" data-bs-toggle="tooltip" data-bs-placement="top"
                     title="Total hari kerja (Tepat Waktu & Telat)">
                     Hadir <i class="ri-information-line"></i>
                  </small>
               </div>
            </div>
         </div>
         <div class="col-md-4 col-6">
            <div class="card bg-label-info shadow-sm">
               <div class="card-body text-center">
                  <div
                     class="avatar avatar-md mx-auto mb-2 bg-info text-white rounded d-flex align-items-center justify-content-center">
                     <i class="ri-file-list-3-line ri-24px"></i>
                  </div>
                  <h4 class="mb-0 fw-bold">{{ $statistik['izin'] }}</h4>
                  <small class="text-info fw-medium">Izin/Cuti/Sakit</small>
               </div>
            </div>
         </div>
         <div class="col-md-4 col-12">
            <div class="card bg-label-danger shadow-sm">
               <div class="card-body text-center">
                  <div
                     class="avatar avatar-md mx-auto mb-2 bg-danger text-white rounded d-flex align-items-center justify-content-center">
                     <i class="ri-close-circle-line ri-24px"></i>
                  </div>
                  <h4 class="mb-0 fw-bold">{{ $statistik['alfa'] }}</h4>
                  <small class="text-danger fw-medium" data-bs-toggle="tooltip" data-bs-placement="top"
                     title="Hari tanpa absen atau lupa absen pulang">
                     Alpha <i class="ri-information-line"></i>
                  </small>
               </div>
            </div>
         </div>

         <!-- Baris 2: Cepat Pulang, Terlambat -->
         <div class="col-md-6 col-6">
            <div class="card bg-label-secondary shadow-sm">
               <div class="card-body text-center">
                  <div
                     class="avatar avatar-md mx-auto mb-2 bg-secondary text-white rounded d-flex align-items-center justify-content-center">
                     <i class="ri-logout-box-r-line ri-24px"></i>
                  </div>
                  <h4 class="mb-0 fw-bold">{{ $statistik['cepat_pulang'] }}</h4>
                  <small class="text-secondary fw-medium">Cepat Pulang</small>
               </div>
            </div>
         </div>
         <div class="col-md-6 col-6">
            <div class="card bg-label-warning shadow-sm">
               <div class="card-body text-center">
                  <div
                     class="avatar avatar-md mx-auto mb-2 bg-warning text-white rounded d-flex align-items-center justify-content-center">
                     <i class="ri-timer-line ri-24px"></i>
                  </div>
                  <h4 class="mb-0 fw-bold">{{ $statistik['terlambat'] }}</h4>
                  <small class="text-warning fw-medium">Terlambat</small>
               </div>
            </div>
         </div>
      </div>

      <!-- Info Note -->
      <div class="alert alert-outline-primary d-flex align-items-center mb-4" role="alert">
         <span class="alert-icon me-2">
            <i class="ri-information-line ri-22px"></i>
         </span>
         <div class="d-flex flex-column ps-1">
            <h6 class="alert-heading mb-1 text-primary fw-bold">Informasi Perhitungan</h6>
            <span style="font-size: 0.85rem;">
               <strong>Alpha</strong> dihitung dari total hari wajib kerja yang tidak memiliki riwayat absen tuntas
               (termasuk hari bolos & lupa absen pulang). Target kerja bulan ini:
               <strong>{{ $statistik['total_hari_kerja'] }} hari</strong>.
            </span>
         </div>
      </div>

      <!-- Table -->
      <div class="card">
         <div class="card-datatable table-responsive d-none d-lg-block">
            <table class="datatables-history table table-hover">
               <thead>
                  <tr>
                     <th>Tanggal</th>
                     <th>Jam Masuk</th>
                     <th>Jam Pulang</th>
                     <th>Lokasi Masuk</th>
                     <th>Status</th>
                     <th>Keterangan</th>
                  </tr>
               </thead>
               <tbody>
                  @forelse($data as $absen)
                     <tr>
                        <td>
                           <strong>{{ $absen->tanggal->locale('id')->isoFormat('ddd, D MMM Y') }}</strong>
                        </td>
                        <td>
                           @if ($absen->jam_masuk)
                              {{ $absen->jam_masuk->format('H:i:s') }}
                              @if ($absen->foto_masuk)
                                 <a href="javascript:void(0);"
                                    onclick="previewFoto('{{ $absen->foto_masuk_url }}', 'Foto Masuk - {{ $absen->pegawai->nama_lengkap }}')"
                                    class="ms-1 d-inline-flex align-items-center">
                                    <i class="ri-image-line text-primary"></i>
                                 </a>
                              @endif
                           @else
                              <span class="text-muted">-</span>
                           @endif
                        </td>
                        <td>
                           @if ($absen->jam_pulang)
                              {{ $absen->jam_pulang->format('H:i:s') }}
                              @if ($absen->foto_pulang)
                                 <a href="javascript:void(0);"
                                    onclick="previewFoto('{{ $absen->foto_pulang_url }}', 'Foto Pulang - {{ $absen->pegawai->nama_lengkap }}')"
                                    class="ms-1 d-inline-flex align-items-center">
                                    <i class="ri-image-line text-primary"></i>
                                 </a>
                              @endif
                           @else
                              <span class="text-muted">-</span>
                           @endif
                        </td>
                        <td>{{ $absen->lokasi_masuk ?? '-' }}</td>
                        <td>
                           @php
                              $displayStatus = $absen->status;
                              $badgeColor = 'info';

                              if (in_array($absen->status, ['Tepat Waktu', 'Hadir'])) {
                                  $displayStatus = 'Hadir';
                                  $badgeColor = 'success';
                              } elseif ($absen->status === 'Terlambat') {
                                  $displayStatus = 'Telat';
                                  $badgeColor = 'warning';
                              } elseif (in_array($absen->status, ['Dinas Luar Kota', 'Tugas'])) {
                                  $displayStatus = $absen->status;
                                  $badgeColor = 'info';
                              }

                              // Jika tidak ada jam pulang dan bukan hari ini, anggap Alpha (kecuali Izin/Sakit/Cuti)
                              if (
                                  !in_array($absen->status, [
                                      'Izin',
                                      'Sakit',
                                      'Cuti',
                                      'Izin Pribadi',
                                      'Cuti Tahunan',
                                      'Dinas Luar Kota',
                                  ]) &&
                                  !$absen->jam_pulang &&
                                  !$absen->tanggal->isToday()
                              ) {
                                  $displayStatus = 'Alpha';
                                  $badgeColor = 'danger';
                              }
                           @endphp
                           <span class="badge bg-{{ $badgeColor }}">
                              {{ $displayStatus }}
                           </span>
                        </td>
                        <td>{{ $absen->keterangan ?? '-' }}</td>
                     </tr>
                  @empty
                     <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                           <i class="ri-calendar-line ri-3x mb-2"></i>
                           <p class="mb-0">Belum ada data absensi bulan ini</p>
                        </td>
                     </tr>
                  @endforelse
               </tbody>
            </table>
         </div>

         <!-- Card View (Mobile & Mini Tablets) -->
         <div class="d-block d-lg-none p-3">
            @forelse($data as $absen)
               <div class="card mb-3 shadow-sm">
                  <div class="card-body p-3">
                     <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                           <strong
                              class="text-primary d-block mb-1">{{ $absen->tanggal->locale('id')->isoFormat('ddd, D MMM Y') }}</strong>
                        </div>
                        @php
                           $displayStatus = $absen->status;
                           $badgeColor = 'info';

                           if (in_array($absen->status, ['Tepat Waktu', 'Hadir'])) {
                               $displayStatus = 'Hadir';
                               $badgeColor = 'success';
                           } elseif ($absen->status === 'Terlambat') {
                               $displayStatus = 'Telat';
                               $badgeColor = 'warning';
                           } elseif (in_array($absen->status, ['Dinas Luar Kota', 'Tugas'])) {
                               $displayStatus = $absen->status;
                               $badgeColor = 'info';
                           }

                           // Jika tidak ada jam pulang dan bukan hari ini, anggap Alpha (kecuali Izin/Sakit/Cuti)
                           if (
                               !in_array($absen->status, [
                                   'Izin',
                                   'Sakit',
                                   'Cuti',
                                   'Izin Pribadi',
                                   'Cuti Tahunan',
                                   'Dinas Luar Kota',
                               ]) &&
                               !$absen->jam_pulang &&
                               !$absen->tanggal->isToday()
                           ) {
                               $displayStatus = 'Alpha';
                               $badgeColor = 'danger';
                           }
                        @endphp
                        <span class="badge bg-{{ $badgeColor }}">
                           {{ $displayStatus }}
                        </span>
                     </div>

                     <div class="row g-3 mb-2">
                        <div class="col-6">
                           <small class="text-muted d-block mb-1"><i class="ri-login-circle-line me-1"></i>Jam
                              Masuk</small>
                           <div class="d-flex align-items-center">
                              @if ($absen->jam_masuk)
                                 <span class="me-2 fw-semibold">{{ $absen->jam_masuk->format('H:i:s') }}</span>
                                 @if ($absen->foto_masuk)
                                    <a href="javascript:void(0);"
                                       onclick="previewFoto('{{ $absen->foto_masuk_url }}', 'Foto Masuk')"
                                       class="text-primary">
                                       <i class="ri-image-line"></i>
                                    </a>
                                 @endif
                              @else
                                 <span class="text-muted">-</span>
                              @endif
                           </div>
                        </div>
                        <div class="col-6">
                           <small class="text-muted d-block mb-1"><i class="ri-logout-circle-line me-1"></i>Jam
                              Pulang</small>
                           <div class="d-flex align-items-center">
                              @if ($absen->jam_pulang)
                                 <span class="me-2 fw-semibold">{{ $absen->jam_pulang->format('H:i:s') }}</span>
                                 @if ($absen->foto_pulang)
                                    <a href="javascript:void(0);"
                                       onclick="previewFoto('{{ $absen->foto_pulang_url }}', 'Foto Pulang')"
                                       class="text-primary">
                                       <i class="ri-image-line"></i>
                                    </a>
                                 @endif
                              @else
                                 <span class="text-muted">-</span>
                              @endif
                           </div>
                        </div>
                     </div>

                     @if ($absen->lokasi_masuk)
                        <div class="mb-2 pb-2 border-bottom">
                           <small class="text-muted d-block mb-1"><i class="ri-map-pin-line me-1"></i>Lokasi</small>
                           <span class="d-block small text-truncate"
                              style="max-width: 100%;">{{ $absen->lokasi_masuk }}</span>
                        </div>
                     @endif

                     @if ($absen->keterangan)
                        <div class="mt-2">
                           <small class="text-muted d-block mb-1"><i
                                 class="ri-file-text-line me-1"></i>Keterangan</small>
                           <p class="mb-0 small">{{ $absen->keterangan }}</p>
                        </div>
                     @endif
                  </div>
               </div>
            @empty
               <div class="text-center py-5 text-muted">
                  <i class="ri-calendar-line ri-3x mb-3 d-block"></i>
                  <p class="mb-0">Belum ada data absensi bulan ini</p>
               </div>
            @endforelse
         </div>
      </div>
   </div>
   </div>

   <!-- Modal Preview Foto -->
   <div class="modal fade animate__animated animate__fadeIn" id="modalPreviewFoto" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
         <div class="modal-content bg-transparent shadow-none border-0">
            <div class="modal-header border-0 p-0 mb-3 justify-content-end">
               <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                  aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 text-center">
               <div class="position-relative">
                  <div id="modal-photo-title"
                     class="position-absolute top-0 start-50 translate-middle-x bg-dark bg-opacity-50 text-white px-3 py-1 rounded-bottom small"
                     style="z-index: 10;"></div>
                  <img src="" id="foto-preview"
                     class="img-fluid rounded-3 shadow-lg border border-3 border-white"
                     style="max-height: 85vh; border-radius: 15px !important;">
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal Preview Foto (Premium Style) -->
   <div class="modal fade animate__animated animate__fadeIn" id="modalPreviewFoto" tabindex="-1" aria-hidden="true">
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
                  <!-- Header Label (Glassmorphism) -->
                  <div id="modal-photo-title"
                     class="position-absolute top-0 start-50 translate-middle-x mt-3 px-4 py-2 rounded-pill shadow-lg"
                     style="z-index: 10; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); color: white; font-weight: 600; letter-spacing: 0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                  </div>

                  <img src="" id="foto-preview" class="img-fluid w-100 shadow-lg"
                     style="max-height: 85vh; object-fit: contain; background: #000; border-radius: 12px;">

                  <!-- Quality Badge -->
                  <div
                     class="position-absolute bottom-0 end-0 mb-3 me-3 px-2 py-1 bg-dark bg-opacity-50 text-white rounded small"
                     style="font-size: 10px;">
                     <i class="ri-shield-check-line me-1"></i>Verified Attendance Photo
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <style>
      .modal-backdrop.show {
         backdrop-filter: blur(8px);
         -webkit-backdrop-filter: blur(8px);
         background-color: rgba(0, 0, 0, 0.6);
      }

      .shadow-2xl {
         box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      }
   </style>
@endsection

@section('page-script')
   <script>
      window.addEventListener('load', function() {
         const dt_history = $('.datatables-history');

         if (dt_history.length) {
            dt_history.DataTable({
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
            $('div.head-label').html(
               '<h5 class="card-title mb-0">Riwayat {{ \Carbon\Carbon::create()->month((int) $bulan)->locale('id')->isoFormat('MMMM') }} {{ $tahun }}</h5>'
            );
         }
      });

      function previewFoto(url, title) {
         const modal = new bootstrap.Modal(document.getElementById('modalPreviewFoto'));
         document.getElementById('foto-preview').src = url;
         document.getElementById('modal-photo-title').textContent = title;
         modal.show();
      }
   </script>
@endsection
