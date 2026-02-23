@php
   $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Admin Dashboard')

@section('vendor-style')
   @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('page-style')
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

      .card-gradient-danger {
         background: linear-gradient(135deg, #fb3a50 0%, #f44336 100%);
         color: white;
      }

      .card-gradient-warning {
         background: linear-gradient(135deg, #FF9A8B 0%, #FF6A88 55%, #FF99AC 100%);
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
         padding: 0.5rem 1rem;
      }

      .stat-icon {
         width: 48px;
         height: 48px;
         display: flex;
         align-items: center;
         justify-content: center;
         border-radius: 12px;
         font-size: 24px;
      }

      .quick-link {
         display: flex;
         align-items: center;
         padding: 1rem;
         border-radius: 15px;
         background: #f8f9fa;
         transition: all 0.2s;
         text-decoration: none !important;
         color: #32475c;
         border: 1px solid transparent;
      }

      .quick-link:hover {
         background: #fff;
         border-color: #7367f0;
         color: #7367f0;
         box-shadow: 0 4px 12px rgba(115, 103, 240, 0.1);
      }

      .table-premium thead th {
         background-color: #f8f9fa;
         text-transform: uppercase;
         font-size: 0.75rem;
         letter-spacing: 1px;
         font-weight: 700;
         border: none;
      }

      .avatar-status-overlay {
         position: absolute;
         bottom: 0;
         right: 0;
         width: 12px;
         height: 12px;
         border-radius: 50%;
         border: 2px solid #fff;
      }
   </style>
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <!-- Header Section -->
      <div class="row align-items-center mb-6">
         <div class="col-md-8">
            <h3 class="fw-bold mb-1">Pusat Kendali Utama</h3>
            <p class="text-muted mb-0">Pantau kehadiran dan manajemen pegawai secara real-time hari ini,
               {{ \Carbon\Carbon::parse($tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
         </div>
         <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="d-flex justify-content-md-end gap-2">
               <a href="{{ route('absensi.rekap') }}" class="btn btn-primary shadow-sm">
                  <i class="ri-file-chart-line me-1"></i> Rekap Bulanan
               </a>
               <a href="{{ route('absensi.dashboard') }}" class="btn btn-outline-secondary">
                  <i class="ri-settings-4-line"></i>
               </a>
            </div>
         </div>
      </div>

      <!-- Key Statistics -->
      <div class="row g-4 mb-6">
         <div class="col-sm-6 col-xl-3">
            <div class="card dash-card card-gradient-primary shadow-lg h-100 stat-card-custom">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between mb-4">
                     <div>
                        <div class="glass-card mb-2 d-inline-block">
                           <small class="text-white fw-medium">STATISTIK PEGAWAI</small>
                        </div>
                        <h2 class="mb-0 text-white fw-bold display-5">{{ $statistikAbsensi['total_pegawai'] }}</h2>
                        <p class="mb-0 text-white opacity-75 fw-medium">Total Pegawai</p>
                     </div>
                     <div class="stat-icon bg-white shadow-lg rounded-pill" style="width: 60px; height: 60px;">
                        <i class="ri-team-fill text-primary ri-32px"></i>
                     </div>
                  </div>
                  <div class="mt-4 pt-1 border-top border-white border-opacity-10 pt-3">
                     <div class="d-flex justify-content-between align-items-center">
                        <small class="text-white opacity-75">Status Aktif</small>
                        <span class="badge bg-white text-primary rounded-pill px-3">100%</span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card dash-card card-gradient-success shadow-lg h-100 stat-card-custom">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between mb-4">
                     <div>
                        <div class="glass-card mb-2 d-inline-block">
                           <small class="text-white fw-medium">KEHADIRAN HARI INI</small>
                        </div>
                        <h2 class="mb-0 text-white fw-bold display-5">{{ $statistikAbsensi['hadir'] }}</h2>
                        <p class="mb-0 text-white opacity-75 fw-medium">Hadir Tepat Waktu</p>
                     </div>
                     <div class="stat-icon bg-white shadow-lg rounded-pill" style="width: 60px; height: 60px;">
                        <i class="ri-user-follow-fill text-success ri-32px"></i>
                     </div>
                  </div>
                  <div class="mt-2">
                     @php
                        $percentHadir =
                            $statistikAbsensi['total_pegawai'] > 0
                                ? round(($statistikAbsensi['hadir'] / $statistikAbsensi['total_pegawai']) * 100)
                                : 0;
                     @endphp
                     <div class="d-flex justify-content-between mb-1">
                        <small class="text-white opacity-75">Tingkat Kehadiran</small>
                        <small class="text-white fw-bold">{{ $percentHadir }}%</small>
                     </div>
                     <div class="progress bg-white bg-opacity-25 shadow-sm" style="height: 10px; border-radius: 5px;">
                        <div class="progress-bar bg-white progress-bar-striped progress-bar-animated"
                           style="width: {{ $percentHadir }}%; border-radius: 5px;"></div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card dash-card shadow-lg h-100 bg-white border-0 stat-card-custom"
               style="border-bottom: 4px solid #f44336 !important;">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between mb-4">
                     <div>
                        <div class="badge bg-label-danger mb-2">BELUM ABSEN</div>
                        <h2 class="mb-0 text-danger fw-bold display-5">{{ $statistikAbsensi['belum_absen'] }}</h2>
                        <p class="mb-0 text-muted small">Anggota Tim</p>
                     </div>
                     <div class="stat-icon bg-danger shadow-danger shadow-sm rounded-pill"
                        style="width: 60px; height: 60px;">
                        <i class="ri-error-warning-fill text-white ri-32px"></i>
                     </div>
                  </div>
                  <div class="mt-4">
                     <a href="{{ route('absensi.dashboard') }}#belumAbsetTab"
                        class="btn btn-sm btn-outline-danger w-100 rounded-pill">Lihat Daftar</a>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card dash-card shadow-lg h-100 bg-white border-0 stat-card-custom"
               style="border-bottom: 4px solid #ff9800 !important;">
               <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between mb-4">
                     <div>
                        <div class="badge bg-label-warning mb-2">PERMINTAAN IZIN</div>
                        <h2 class="mb-0 text-warning fw-bold display-5">{{ $statistikIzin['pending'] }}</h2>
                        <p class="mb-0 text-muted small">Menunggu Review</p>
                     </div>
                     <div class="stat-icon bg-warning shadow-warning shadow-sm rounded-pill"
                        style="width: 60px; height: 60px;">
                        <i class="ri-mail-send-fill text-white ri-32px"></i>
                     </div>
                  </div>
                  <div class="mt-4">
                     <a href="{{ route('izin.admin.pending') }}"
                        class="btn btn-sm btn-outline-warning w-100 rounded-pill">Proses Sekarang</a>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <div class="row g-6">
         <!-- Division Performance -->
         <div class="col-lg-8">
            <div class="card dash-card shadow-sm border-0 h-100">
               <div
                  class="card-header d-flex justify-content-between align-items-center border-bottom bg-transparent py-4">
                  <h5 class="mb-0 fw-bold"><i class="ri-bar-chart-grouped-line me-2 text-primary"></i>Produktivitas &
                     Kehadiran Divisi</h5>
                  <div class="dropdown">
                     <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">
                        @if ($filterRekap === 'today')
                           Hari Ini
                        @elseif($filterRekap === 'yesterday')
                           Kemarin
                        @else
                           Minggu Ini
                        @endif
                     </button>
                     <ul class="dropdown-menu">
                        <li><a class="dropdown-item @if ($filterRekap === 'today') active @endif"
                              href="{{ route('dashboard', ['filter_rekap' => 'today']) }}">Hari Ini</a></li>
                        <li><a class="dropdown-item @if ($filterRekap === 'yesterday') active @endif"
                              href="{{ route('dashboard', ['filter_rekap' => 'yesterday']) }}">Kemarin</a></li>
                        <li><a class="dropdown-item @if ($filterRekap === 'week') active @endif"
                              href="{{ route('dashboard', ['filter_rekap' => 'week']) }}">Minggu Ini</a></li>
                     </ul>
                  </div>
               </div>
               <div class="table-responsive">
                  <table class="table table-premium table-hover align-middle mb-0">
                     <thead>
                        <tr>
                           <th class="ps-4">Divisi</th>
                           <th class="text-center">Hadir</th>
                           <th class="text-center">Telat</th>
                           <th class="text-center">Izin</th>
                           <th class="text-center">Total Jam</th>
                           <th class="pe-4 text-end">Aksi</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($rekapDivisi as $rekap)
                           <tr>
                              <td class="ps-4">
                                 <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-label-primary rounded-pill me-3"
                                       style="width: 40px; height: 40px;">
                                       <i class="ri-community-line"></i>
                                    </div>
                                    <span class="fw-bold">{{ $rekap->divisi }}</span>
                                 </div>
                              </td>
                              <td class="text-center"><span class="badge bg-label-success">{{ $rekap->hadir }}</span>
                              </td>
                              <td class="text-center"><span
                                    class="badge bg-label-warning">{{ $rekap->terlambat }}</span>
                              </td>
                              <td class="text-center"><span class="badge bg-label-info">{{ $rekap->izin }}</span></td>
                              <td class="text-center fw-medium text-dark">{{ $rekap->total_jam_format }}</td>
                              <td class="pe-4 text-end">
                                 <button class="btn btn-icon btn-text-secondary rounded-pill"><i
                                       class="ri-arrow-right-s-line"></i></button>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="6" class="text-center py-5">
                                 <p class="text-muted mb-0">Belum ada data rekapan hari ini</p>
                              </td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
               <div class="card-footer bg-transparent border-top py-4 text-center">
                  <a href="{{ route('absensi.dashboard') }}" class="btn btn-sm btn-link">Lihat Detail Semua Divisi <i
                        class="ri-arrow-right-line ms-1"></i></a>
               </div>
            </div>
         </div>

         <!-- Quick Access & Recent Requests -->
         <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card dash-card shadow-sm border-0 mb-6">
               <div class="card-header py-4 bg-transparent">
                  <h5 class="mb-0 fw-bold">Akses Cepat</h5>
               </div>
               <div class="card-body">
                  <div class="row g-3">
                     <div class="col-6">
                        <a href="{{ route('pegawai.index') }}" class="quick-link">
                           <div class="bg-label-primary rounded p-2 me-2">
                              <i class="ri-user-star-line"></i>
                           </div>
                           <small class="fw-bold">Pegawai</small>
                        </a>
                     </div>
                     <div class="col-6">
                        <a href="{{ route('shift.index') }}" class="quick-link">
                           <div class="bg-label-info rounded p-2 me-2">
                              <i class="ri-calendar-todo-line"></i>
                           </div>
                           <small class="fw-bold">Jadwal</small>
                        </a>
                     </div>
                     <div class="col-6">
                        <a href="{{ route('izin.admin.pending') }}" class="quick-link">
                           <div class="bg-label-warning rounded p-2 me-2">
                              <i class="ri-shield-user-line"></i>
                           </div>
                           <small class="fw-bold">Persetujuan</small>
                        </a>
                     </div>
                     <div class="col-6">
                        <a href="{{ route('hari-libur.index') }}" class="quick-link">
                           <div class="bg-label-danger rounded p-2 me-2">
                              <i class="ri-umbrella-line"></i>
                           </div>
                           <small class="fw-bold">Libur</small>
                        </a>
                     </div>
                  </div>
               </div>
            </div>

            <!-- Recent Requests -->
            <div class="card dash-card shadow-sm border-0">
               <div
                  class="card-header d-flex justify-content-between align-items-center py-4 bg-transparent border-bottom">
                  <h5 class="mb-0 fw-bold">Permohonan Terbaru</h5>
                  <span class="badge bg-label-primary">{{ $recentIzin->count() }}</span>
               </div>
               <div class="card-body p-0">
                  <div class="list-group list-group-flush">
                     @forelse($recentIzin as $izin)
                        <div class="list-group-item p-4 border-0 border-bottom">
                           <div class="d-flex mb-2">
                              <div class="avatar avatar-sm me-3">
                                 <img src="{{ $izin->pegawai->foto_url }}" alt="AV" class="rounded-circle">
                              </div>
                              <div class="flex-grow-1">
                                 <h6 class="mb-0 small fw-bold">{{ $izin->pegawai->nama_lengkap }}</h6>
                                 <p class="mb-0 text-muted small">{{ $izin->jenisIzin->nama }} &bull;
                                    {{ $izin->jumlah_hari }} Hari</p>
                              </div>
                              @php
                                 $badgeClass = 'bg-label-warning';
                                 if ($izin->status_approval == 'Approved') {
                                     $badgeClass = 'bg-label-success';
                                 }
                                 if ($izin->status_approval == 'Rejected') {
                                     $badgeClass = 'bg-label-danger';
                                 }
                              @endphp
                              <span
                                 class="badge {{ $badgeClass }} rounded-pill h-px-20 px-2 py-0 d-flex align-items-center"
                                 style="font-size: 0.65rem;">
                                 {{ $izin->status_approval }}
                              </span>
                           </div>
                           <div class="p-2 bg-light rounded small mt-2">
                              <span class="text-truncate d-block">"{{ $izin->alasan }}"</span>
                           </div>
                           <div class="mt-2 d-flex justify-content-between align-items-center">
                              <small class="text-muted"><i
                                    class="ri-time-line me-1"></i>{{ $izin->created_at->diffForHumans() }}</small>
                              @if ($izin->status_approval == 'Pending')
                                 <a href="{{ route('izin.admin.pending') }}"
                                    class="btn btn-xs btn-primary">Persetujuan</a>
                              @endif
                           </div>
                        </div>
                     @empty
                        <div class="p-5 text-center">
                           <p class="text-muted small mb-0">Tidak ada permohonan baru</p>
                        </div>
                     @endforelse
                  </div>
               </div>
               <div class="card-footer bg-transparent py-3 text-center">
                  <a href="{{ route('izin.admin.index') }}" class="btn btn-sm btn-link text-muted">Lihat Semua
                     History</a>
               </div>
            </div>
         </div>
      </div>

      <!-- Recent Attendance Activity -->
      <div class="row mt-6">
         <div class="col-12">
            <div class="card dash-card shadow-sm border-0">
               <div
                  class="card-header d-flex justify-content-between align-items-center bg-transparent py-4 px-6 border-bottom">
                  <h5 class="mb-0 fw-bold"><i class="ri-history-line me-2 text-info"></i>Aktivitas Absensi Terbaru</h5>
                  <a href="{{ route('absensi.dashboard') }}" class="btn btn-sm btn-outline-info">Monitoring Live</a>
               </div>
               <div class="table-responsive">
                  <table class="table table-premium align-middle">
                     <thead>
                        <tr>
                           <th class="ps-6">Pegawai</th>
                           <th>Shift</th>
                           <th class="text-center">Jam Masuk</th>
                           <th class="text-center">Jam Pulang</th>
                           <th class="text-center">Status</th>
                           <th class="pe-6">Lokasi</th>
                        </tr>
                     </thead>
                     <tbody>
                        @forelse($recentAbsensi as $abs)
                           <tr>
                              <td class="ps-6">
                                 <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-3 position-relative">
                                       <img src="{{ $abs->pegawai->foto_url }}" alt="" class="rounded-circle">
                                       <div class="avatar-status-overlay bg-success"></div>
                                    </div>
                                    <div>
                                       <h6 class="mb-0 small fw-bold">{{ $abs->pegawai->nama_lengkap }}</h6>
                                       <small class="text-muted">{{ $abs->pegawai->divisi->nama }}</small>
                                    </div>
                                 </div>
                              </td>
                              <td><span class="badge bg-label-secondary">{{ $abs->shift->nama ?? '-' }}</span></td>
                              <td class="text-center fw-bold">
                                 {{ $abs->jam_masuk ? $abs->jam_masuk->format('H:i') : '-' }}</td>
                              <td class="text-center fw-bold">
                                 {{ $abs->jam_pulang ? $abs->jam_pulang->format('H:i') : '-' }}</td>
                              <td class="text-center">
                                 <span
                                    class="badge bg-{{ $abs->status == 'Hadir' ? 'success' : ($abs->status == 'Terlambat' ? 'warning' : 'danger') }}">
                                    {{ $abs->status }}
                                 </span>
                              </td>
                              <td class="pe-6 small">
                                 <i
                                    class="ri-map-pin-line me-1 text-muted"></i>{{ Str::limit($abs->lokasi_masuk ?? '-', 30) }}
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="6" class="text-center py-5 text-muted">Belum ada aktivitas hari ini</td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
      </div>
   </div>
@endsection

@section('page-script')
   <script>
      // Placeholder for future charts/logic
   </script>
@endsection
