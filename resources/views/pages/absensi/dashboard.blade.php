@extends('layouts/layoutMaster')

@section('title', 'Dashboard Absensi')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <div class="d-flex justify-content-between align-items-center mb-6">
         <div>
            <h4 class="fw-bold mb-1">Monitoring Kehadiran</h4>
            <p class="text-muted mb-0">Pemantauan log absensi pegawai secara real-time</p>
         </div>
         <div class="d-flex flex-column align-items-end">
            <label class="form-label small text-muted mb-1">Filter Tanggal</label>
            <input type="date" class="form-control shadow-sm border-0 bg-white" id="filter-tanggal"
               value="{{ $tanggal }}" style="width: 200px; border-radius: 10px;">
         </div>
      </div>

      <!-- Statistics Cards -->
      <div class="row g-4 mb-6">
         <div class="col-sm-6 col-xl-3">
            <div class="card dash-card card-gradient-primary shadow-lg h-100 stat-card-custom">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between mb-3">
                     <div>
                        <div class="glass-card mb-2 d-inline-block p-1 px-2">
                           <small class="text-white fw-medium" style="font-size: 0.65rem;">TOTAL PEGAWAI</small>
                        </div>
                        <h2 class="mb-0 text-white fw-bold display-6">{{ $statistik['total_pegawai'] }}</h2>
                     </div>
                     <div class="stat-icon bg-white shadow-lg rounded-pill" style="width: 50px; height: 50px;">
                        <i class="ri-group-fill text-primary ri-24px"></i>
                     </div>
                  </div>
                  <div class="mt-4 pt-1 border-top border-white border-opacity-10">
                     <small class="text-white opacity-75">Seluruh Anggota Tim</small>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card dash-card card-gradient-success shadow-lg h-100 stat-card-custom">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between mb-3">
                     <div>
                        <div class="glass-card mb-2 d-inline-block p-1 px-2">
                           <small class="text-white fw-medium" style="font-size: 0.65rem;">SUDAH ABSEN</small>
                        </div>
                        <h2 class="mb-0 text-white fw-bold display-6">{{ $statistik['sudah_absen'] }}</h2>
                     </div>
                     <div class="stat-icon bg-white shadow-lg rounded-pill" style="width: 50px; height: 50px;">
                        <i class="ri-checkbox-circle-fill text-success ri-24px"></i>
                     </div>
                  </div>
                  @php
                     $percentMasuk =
                         $statistik['total_pegawai'] > 0
                             ? round(($statistik['sudah_absen'] / $statistik['total_pegawai']) * 100)
                             : 0;
                  @endphp
                  <div class="mt-3">
                     <div class="progress bg-white bg-opacity-25 shadow-sm" style="height: 6px; border-radius: 3px;">
                        <div class="progress-bar bg-white" style="width: {{ $percentMasuk }}%;"></div>
                     </div>
                     <small class="text-white opacity-75 mt-1 d-block">{{ $percentMasuk }}% partisipasi</small>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card dash-card shadow-lg h-100 bg-white border-0 stat-card-custom"
               style="border-bottom: 4px solid #ff9800 !important;">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between mb-3">
                     <div>
                        <div class="badge bg-label-warning mb-2" style="font-size: 0.65rem;">BELUM ABSEN</div>
                        <h2 class="mb-0 text-warning fw-bold display-6">{{ $statistik['belum_absen'] }}</h2>
                     </div>
                     <div class="stat-icon bg-warning shadow-warning shadow-sm rounded-pill"
                        style="width: 50px; height: 50px;">
                        <i class="ri-time-fill text-white ri-24px"></i>
                     </div>
                  </div>
                  <div class="mt-4 pt-1 border-top border-light">
                     <small class="text-muted">Menunggu Kehadiran</small>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card dash-card shadow-lg h-100 bg-white border-0 stat-card-custom"
               style="border-bottom: 4px solid #f44336 !important;">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between mb-3">
                     <div>
                        <div class="badge bg-label-danger mb-2" style="font-size: 0.65rem;">TERLAMBAT</div>
                        <h2 class="mb-0 text-danger fw-bold display-6">{{ $statistik['terlambat'] }}</h2>
                     </div>
                     <div class="stat-icon bg-danger shadow-danger shadow-sm rounded-pill"
                        style="width: 50px; height: 50px;">
                        <i class="ri-alarm-warning-fill text-white ri-24px"></i>
                     </div>
                  </div>
                  <div class="mt-4 pt-1 border-top border-light">
                     <small class="text-muted">Butuh Perhatian</small>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <div class="row">
         <!-- Rekap Per Divisi -->
         <div class="col-lg-6 mb-4">
            <div class="card h-100">
               <div class="card-header">
                  <h5 class="mb-0"><i class="ri-pie-chart-line me-2"></i>Rekap Per Divisi</h5>
               </div>
               <div class="table-responsive">
                  <table class="table">
                     <thead>
                        <tr>
                           <th>Divisi</th>
                           <th class="text-center">Tepat Waktu</th>
                           <th class="text-center">Telat</th>
                           <th class="text-center">Izin</th>
                           <th class="text-center">Jam Kerja</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($rekapDivisi as $rekap)
                           <tr>
                              <td class="ps-4">
                                 <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-label-primary rounded-pill me-3"
                                       style="width: 35px; height: 35px;">
                                       <i class="ri-community-line" style="font-size: 1rem;"></i>
                                    </div>
                                    <span class="fw-bold">{{ $rekap->divisi }}</span>
                                 </div>
                              </td>
                              <td class="text-center"><span class="badge bg-label-success">{{ $rekap->hadir }}</span></td>
                              <td class="text-center"><span class="badge bg-label-warning">{{ $rekap->terlambat }}</span>
                              </td>
                              <td class="text-center"><span class="badge bg-label-info">{{ $rekap->izin }}</span></td>
                              <td class="text-center fw-bold text-dark">{{ $rekap->total_jam_format }}</td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="4" class="text-center text-muted">Belum ada data</td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
            </div>
         </div>

         <!-- Belum Absen -->
         <div class="col-lg-6 mb-4">
            <div class="card h-100">
               <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0"><i class="ri-user-unfollow-line me-2 text-warning"></i>Belum Absen Hari Ini</h5>
                  <span class="badge bg-warning">{{ $belumAbsen->count() }}</span>
               </div>
               <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                  @forelse($belumAbsen as $pegawai)
                     <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-sm me-3">
                           <img src="{{ $pegawai->foto_url }}" alt="{{ $pegawai->nama_lengkap }}"
                              class="rounded-circle">
                        </div>
                        <div class="flex-grow-1">
                           <h6 class="mb-0">{{ $pegawai->nama_lengkap }}</h6>
                           <small class="text-muted">{{ $pegawai->divisi->nama ?? '-' }}</small>
                        </div>
                        <div class="d-flex align-items-center">
                           <span class="badge bg-label-warning me-2">Belum Absen</span>
                           <a href="{{ route('absensi.pegawai-history', $pegawai->id) }}"
                              class="btn btn-sm btn-icon btn-label-primary shadow-sm" title="Lihat Detail Riwayat">
                              <i class="ri-eye-line"></i>
                           </a>
                        </div>
                     </div>
                  @empty
                     <div class="text-center text-muted py-4">
                        <i class="ri-check-double-line ri-3x text-success mb-2"></i>
                        <p class="mb-0">Semua pegawai sudah absen!</p>
                     </div>
                  @endforelse
               </div>
            </div>
         </div>
      </div>

      <!-- Data Absensi Hari Ini -->
      <div class="card">
         <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ri-list-check-2 me-2"></i>Data Absensi Hari Ini</h5>
            <a href="{{ route('absensi.rekap') }}" class="btn btn-sm btn-outline-primary">
               <i class="ri-file-excel-line me-1"></i>Rekap Bulanan
            </a>
         </div>
         <div class="table-responsive">
            <table class="table table-hover">
               <thead>
                  <tr>
                     <th>#</th>
                     <th>Pegawai</th>
                     <th>Shift</th>
                     <th class="text-center">Jam Masuk</th>
                     <th class="text-center">Jam Pulang</th>
                     <th class="text-center">Durasi</th>
                     <th>Lokasi</th>
                     <th class="text-center">Status</th>
                     <th>Keterangan</th>
                     <th class="text-center">Aksi</th>
                  </tr>
               </thead>
               <tbody>
                  @forelse($sudahAbsen as $index => $absen)
                     <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                           <div class="d-flex align-items-center">
                              <div class="avatar avatar-sm me-2">
                                 <img src="{{ $absen->pegawai->foto_url }}" alt="" class="rounded-circle">
                              </div>
                              <div>
                                 <strong>{{ $absen->pegawai->nama_lengkap }}</strong>
                                 <br><small class="text-muted">{{ $absen->pegawai->nip ?? '-' }}</small>
                              </div>
                           </div>
                        </td>
                        <td>
                           @if ($absen->shift)
                              <span class="badge bg-label-secondary">{{ $absen->shift->nama }}</span>
                           @else
                              -
                           @endif
                        </td>
                        <td class="text-center">
                           @if ($absen->jam_masuk)
                              <span class="fw-medium">{{ $absen->jam_masuk->format('H:i') }}</span>
                              @if ($absen->foto_masuk)
                                 <a href="javascript:void(0);"
                                    onclick="previewFoto('{{ $absen->foto_masuk_url }}', 'Foto Masuk - {{ $absen->pegawai->nama_lengkap }}')"
                                    class="ms-1">
                                    <i class="ri-image-line text-primary"></i>
                                 </a>
                              @endif
                           @else
                              <span class="text-muted">-</span>
                           @endif
                        </td>
                        <td class="text-center">
                           @if ($absen->jam_pulang)
                              <span class="fw-medium">{{ $absen->jam_pulang->format('H:i') }}</span>
                              @if ($absen->foto_pulang)
                                 <a href="javascript:void(0);"
                                    onclick="previewFoto('{{ $absen->foto_pulang_url }}', 'Foto Pulang - {{ $absen->pegawai->nama_lengkap }}')"
                                    class="ms-1">
                                    <i class="ri-image-line text-primary"></i>
                                 </a>
                              @endif
                           @else
                              <span class="text-muted">-</span>
                           @endif
                        </td>
                        <td class="text-center">
                           <strong>{{ $absen->durasi_kerja }}</strong>
                        </td>
                        <td>{{ $absen->lokasi_masuk ?? '-' }}</td>
                        <td class="text-center">
                           @php
                              $displayStatus = $absen->status;
                              $badgeColor = 'info';

                              if ($absen->status === 'Tepat Waktu') {
                                  $displayStatus = 'Hadir';
                                  $badgeColor = 'success';
                              } elseif ($absen->status === 'Terlambat') {
                                  $displayStatus = 'Telat';
                                  $badgeColor = 'warning';
                              }

                              // Jika tidak ada jam pulang dan bukan hari ini, anggap Alpha
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
                        <td>
                           <small class="text-muted">{{ $absen->keterangan ?? '-' }}</small>
                        </td>
                        <td class="text-center">
                           <a href="{{ route('absensi.pegawai-history', $absen->pegawai_id) }}"
                              class="btn btn-sm btn-icon btn-label-primary shadow-sm" title="Lihat Detail Riwayat">
                              <i class="ri-eye-line"></i>
                           </a>
                        </td>
                     </tr>
                  @empty
                     <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                           <i class="ri-inbox-line ri-3x mb-2"></i>
                           <p class="mb-0">Belum ada data absensi hari ini</p>
                        </td>
                     </tr>
                  @endforelse
               </tbody>
            </table>
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
      .dash-card {
         border: none;
         border-radius: 20px;
         transition: all 0.3s ease;
         overflow: hidden;
      }

      .dash-card:hover {
         transform: translateY(-5px);
         box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
      }

      .card-gradient-primary {
         background: linear-gradient(135deg, #C51E2E 0%, #E53935 46%, #FF5252 100%);
         color: white;
      }

      .card-gradient-success {
         background: linear-gradient(135deg, #0093E9 0%, #80D0C7 100%);
         color: white;
      }

      .stat-card-custom {
         position: relative;
         z-index: 1;
      }

      .stat-card-custom::before {
         content: "";
         position: absolute;
         top: -15px;
         right: -15px;
         width: 100px;
         height: 100px;
         background: rgba(255, 255, 255, 0.1);
         border-radius: 50%;
         z-index: -1;
      }

      .glass-card {
         background: rgba(255, 255, 255, 0.15);
         backdrop-filter: blur(5px);
         border: 1px solid rgba(255, 255, 255, 0.2);
         border-radius: 12px;
      }

      .stat-icon {
         display: flex;
         align-items: center;
         justify-content: center;
         transition: all 0.3s ease;
      }

      .stat-icon i {
         line-height: 1;
      }

      .modal-backdrop.show {
         backdrop-filter: blur(8px);
         -webkit-backdrop-filter: blur(8px);
         background-color: rgba(0, 0, 0, 0.6);
      }

      .shadow-2xl {
         box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      }

      .table-premium thead th {
         background-color: #f8f9fa;
         text-transform: uppercase;
         font-size: 0.75rem;
         letter-spacing: 1px;
         font-weight: 700;
         border: none;
      }
   </style>
@endsection

@section('page-script')
   <script>
      window.addEventListener('load', function() {
         document.getElementById('filter-tanggal').addEventListener('change', function() {
            window.location.href = '{{ route('absensi.dashboard') }}?tanggal=' + this.value;
         });
      });

      function previewFoto(url, title) {
         const modal = new bootstrap.Modal(document.getElementById('modalPreviewFoto'));
         document.getElementById('foto-preview').src = url;
         document.getElementById('modal-photo-title').textContent = title;
         modal.show();
      }
   </script>
@endsection
