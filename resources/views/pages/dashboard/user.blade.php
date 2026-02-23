@extends('layouts/layoutMaster')

@section('title', 'Dashboard Pegawai')

@section('page-style')
   <style>
      .user-welcome-card {
         background: linear-gradient(135deg, #1d1b31 0%, #4c4196 100%);
         color: white;
         border-radius: 16px;
         position: relative;
         overflow: hidden;
         border: none;
         box-shadow: 0 10px 30px rgba(115, 103, 240, 0.2);
      }

      .user-welcome-card::after {
         content: '';
         position: absolute;
         top: -50px;
         right: -50px;
         width: 150px;
         height: 150px;
         background: rgba(255, 255, 255, 0.05);
         border-radius: 50%;
      }

      .stat-card {
         transition: all 0.3s ease;
         border: 1px solid rgba(0, 0, 0, 0.05);
         border-radius: 12px;
         background: #fff;
      }

      .stat-card:hover {
         transform: translateY(-5px);
         box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
      }

      .icon-box {
         width: 54px;
         height: 54px;
         border-radius: 12px;
         display: flex;
         align-items: center;
         justify-content: center;
         margin-bottom: 15px;
         font-size: 24px;
      }

      .history-item {
         display: flex;
         justify-content: space-between;
         align-items: center;
         padding: 15px 0;
         border-bottom: 1px solid #f0f2f4;
      }

      .history-item:last-child {
         border-bottom: none;
      }

      .quick-action-btn {
         display: flex;
         flex-direction: column;
         align-items: center;
         justify-content: center;
         padding: 20px;
         border-radius: 12px;
         background: #fff;
         border: 1px solid #f0f2f4;
         transition: all 0.2s;
         text-decoration: none;
         color: #566a7f;
         box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
      }

      .quick-action-btn:hover {
         background: #7367f0;
         color: #fff !important;
         border-color: #7367f0;
         transform: translateY(-3px);
         box-shadow: 0 4px 12px rgba(115, 103, 240, 0.3);
      }

      .quick-action-btn i {
         font-size: 28px;
         margin-bottom: 8px;
         color: #7367f0;
         transition: all 0.2s;
      }

      .quick-action-btn:hover i {
         color: #fff;
      }

      .time-display {
         color: white;
         font-size: 3rem;
         font-weight: 800;
         line-height: 1;
         letter-spacing: -1px;
         text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      }

      .glass-badge {
         background: rgba(255, 255, 255, 0.15);
         backdrop-filter: blur(5px);
         border: 1px solid rgba(255, 255, 255, 0.2);
         color: white !important;
         padding: 8px 16px;
         border-radius: 8px;
         font-weight: 500;
      }

      .card-title-premium {
         color: #32475c;
         font-weight: 700;
         letter-spacing: 0.5px;
      }
   </style>
   @vite(['resources/assets/vendor/libs/swiper/swiper.scss'])
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <!-- Welcome Section -->
      <div class="card user-welcome-card mb-4">
         <div class="card-body p-4">
            <div class="row align-items-center">
               <div class="col-md-7">
                  <div class="d-flex align-items-center mb-3">
                     <div class="avatar avatar-xl me-3 shadow">
                        <img src="{{ $pegawai->foto_url }}" alt="User Avatar"
                           class="rounded-circle border border-3 border-white">
                     </div>
                     <div>
                        <h3 class="text-white fw-bold mb-1">Halo, {{ $pegawai->nama_lengkap }}!</h3>
                        <p class="mb-0 text-white opacity-100 fw-medium">{{ $pegawai->jabatan }} —
                           {{ $pegawai->divisi->nama }}</p>
                     </div>
                  </div>
                  <div class="d-flex flex-wrap gap-3 mt-4">
                     <div class="glass-badge">
                        <i class="ri-building-line me-1"></i> {{ $pegawai->kantor->nama }}
                     </div>
                     <div class="glass-badge shadow-sm">
                        <i class="ri-time-line me-1"></i>
                        <span class="fw-bold">{{ $pegawai->divisi->shifts->where('is_aktif', true)->count() }} Shift</span>
                        <small class="opacity-75 ms-1">(Fleksibel)</small>
                     </div>
                     @php
                        $hariLibur = \App\Models\HariLibur::whereDate('tanggal', today())->first();
                        $shifts = \App\Models\Shift::where('divisi_id', $pegawai->divisi_id)
                            ->where('is_aktif', true)
                            ->get();
                        // Shift yang tetap masuk meskipun libur
                        $workingShifts = $shifts->filter(fn($s) => $s->ikut_libur == false);
                        $anyShiftWorkingToday = $workingShifts->isNotEmpty();
                     @endphp
                     @if ($hariLibur)
                        @if (!$anyShiftWorkingToday)
                           <div class="glass-badge bg-danger shadow-sm border-0 text-white">
                              <i class="ri-calendar-event-fill me-1"></i> Libur: {{ $hariLibur->nama }}
                           </div>
                        @else
                           <div class="glass-badge bg-info shadow-sm border-0 text-white">
                              <i class="ri-calendar-event-fill me-1"></i> Hari Ini: {{ $hariLibur->nama }} (Tetap Bertugas)
                           </div>
                        @endif
                     @endif
                  </div>
               </div>
               <div class="col-md-5 text-md-end mt-4 mt-md-0">
                  <div class="time-display" id="clock">00:00:00</div>
                  <div class="text-white opacity-100 fw-medium mt-2">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                  </div>
               </div>
            </div>
         </div>
      </div>

      <!-- Informasi Kantor (Moved & Dismissible) -->
      <div class="alert alert-primary alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
         <div class="d-flex align-items-center">
            <i class="ri-notification-3-line ri-24px me-3 text-primary"></i>
            <div>
               <h6 class="alert-heading mb-1 fw-bold text-primary">Informasi Kantor</h6>
               <p class="mb-0 small">
                  Pastikan untuk selalu melakukan absen tepat waktu dan di dalam radius kantor
                  ({{ $pegawai->kantor->radius_meter }}m).
               </p>
            </div>
         </div>
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>

      <!-- Monthly Summary Stats -->
      <div class="row g-4 mb-4">
         <div class="col-sm-6 col-xl-3">
            <div class="card stat-card shadow-sm">
               <div class="card-body">
                  <div class="icon-box bg-label-success shadow-sm">
                     <i class="ri-checkbox-circle-fill"></i>
                  </div>
                  <h6 class="card-title-premium mb-1">HADIR</h6>
                  <div class="d-flex align-items-baseline">
                     <h3 class="mb-0 me-2 fw-bold text-success">{{ $statistik['hadir'] }}</h3>
                     <small class="text-muted fw-medium">hari</small>
                  </div>
                  <div class="mt-2 pt-1">
                     <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success"
                           style="width: {{ $statistik['total_hari_kerja'] > 0 ? ($statistik['hadir'] / $statistik['total_hari_kerja']) * 100 : 0 }}%">
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card stat-card shadow-sm">
               <div class="card-body">
                  <div class="icon-box bg-label-warning shadow-sm">
                     <i class="ri-timer-fill"></i>
                  </div>
                  <h6 class="card-title-premium mb-1">TERLAMBAT</h6>
                  <div class="d-flex align-items-baseline">
                     <h3 class="mb-0 me-2 fw-bold text-warning">{{ $statistik['terlambat'] }}</h3>
                     <small class="text-muted fw-medium">kali</small>
                  </div>
                  <div class="mt-2 pt-1">
                     <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning"
                           style="width: {{ $statistik['total_hari_kerja'] > 0 ? ($statistik['terlambat'] / $statistik['total_hari_kerja']) * 100 : 0 }}%">
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card stat-card shadow-sm">
               <div class="card-body">
                  <div class="icon-box bg-label-info shadow-sm">
                     <i class="ri-calendar-todo-fill"></i>
                  </div>
                  <h6 class="card-title-premium mb-1">IZIN / SAKIT</h6>
                  <div class="d-flex align-items-baseline">
                     <h3 class="mb-0 me-2 fw-bold text-info">{{ $statistik['izin'] }}</h3>
                     <small class="text-muted fw-medium">hari</small>
                  </div>
                  <div class="mt-2 pt-1">
                     <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info"
                           style="width: {{ $statistik['total_hari_kerja'] > 0 ? ($statistik['izin'] / $statistik['total_hari_kerja']) * 100 : 0 }}%">
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-sm-6 col-xl-3">
            <div class="card stat-card shadow-sm">
               <div class="card-body">
                  <div class="icon-box bg-label-danger shadow-sm">
                     <i class="ri-close-circle-fill"></i>
                  </div>
                  <h6 class="card-title-premium mb-1">ALPA</h6>
                  <div class="d-flex align-items-baseline">
                     <h3 class="mb-0 me-2 fw-bold text-danger">{{ $statistik['alfa'] }}</h3>
                     <small class="text-muted fw-medium">hari</small>
                  </div>
                  <div class="mt-2 pt-1">
                     <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-danger"
                           style="width: {{ $statistik['total_hari_kerja'] > 0 ? ($statistik['alfa'] / $statistik['total_hari_kerja']) * 100 : 0 }}%">
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <!-- Media Informasi (Carousel) -->
      @if ($informasis->count() > 0)
         <div class="row mb-4">
            <div class="col-12">
               <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0 fw-bold"><i class="ri-information-line me-2"></i>Informasi Terbaru</h5>
                  <div class="d-flex gap-2">
                     <div class="swiper-button-prev-custom cursor-pointer text-primary"><i
                           class="ri-arrow-left-s-line ri-24px"></i></div>
                     <div class="swiper-button-next-custom cursor-pointer text-primary"><i
                           class="ri-arrow-right-s-line ri-24px"></i></div>
                  </div>
               </div>

               <div class="swiper swiper-informasi pb-5">
                  <div class="swiper-wrapper">
                     @foreach ($informasis as $info)
                        <div class="swiper-slide h-auto">
                           <a href="{{ route('informasi.show', $info->id) }}"
                              class="text-decoration-none h-100 d-block">
                              <div class="card h-100 border-0 shadow-sm overflow-hidden stat-card">
                                 <img src="{{ $info->gambar_url }}" class="card-img-top" alt="{{ $info->judul }}"
                                    style="height: 160px; object-fit: cover;">
                                 <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-2">
                                       <span
                                          class="badge bg-label-primary fs-xsmall">{{ $info->created_at->format('d M Y') }}</span>
                                    </div>
                                    <h6 class="card-title fw-bold text-heading mb-2 line-clamp-2"
                                       style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 3em;">
                                       {{ $info->judul }}</h6>
                                    <p class="card-text small text-muted mb-0 line-clamp-3"
                                       style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                       {{ Str::limit(strip_tags($info->isi), 80) }}
                                    </p>
                                 </div>
                              </div>
                           </a>
                        </div>
                     @endforeach
                  </div>
                  <div class="swiper-pagination"></div>
               </div>
            </div>
         </div>
      @endif

      <div class="row">
         <!-- Left Column: Today's Status & Quick Actions -->
         <div class="col-lg-8">
            <!-- Shift Selection & Today's Attendance -->
            <div class="card mb-4 border-0 shadow-sm">
               <div class="card-header bg-transparent">
                  <h5 class="mb-0"><i class="ri-user-follow-line me-2"></i>Pilih Jadwal Absen</h5>
                  <p class="text-muted small mb-0 mt-1">Silakan pilih shift yang ingin Anda ambil hari ini.</p>
               </div>
               <div class="card-body">
                  <div class="row g-3">
                     @php
                        // Ambil semua shift aktif di divisi pegawai
                        $shifts = \App\Models\Shift::where('divisi_id', $pegawai->divisi_id)
                            ->where('is_aktif', true)
                            ->get();
                        $now = now();

                        // CARI SESI AKTIF GLOBAL (User sedang masuk di shift mana pun)
                        $globalActiveSession = \App\Models\Absensi::where('pegawai_id', $pegawai->id)
                            ->whereNotNull('jam_masuk')
                            ->whereNull('jam_pulang')
                            ->first();
                     @endphp

                     @forelse($shifts as $shift)
                        @php
                           // Cek status shift
                           $jamMasuk = \Carbon\Carbon::parse($shift->jam_masuk);
                           $jamPulang = \Carbon\Carbon::parse($shift->jam_pulang);

                           // Cek apakah data absen spesifik untuk shift ini HANYA UNTUK HARI INI
                           // Sesi kemarin yang lupa checkout diabaikan (dianggap Alpha) agar hari ini bisa absen lagi.
                           $absenShift = \App\Models\Absensi::where('pegawai_id', $pegawai->id)
                               ->where('shift_id', $shift->id)
                               ->whereDate('tanggal', today())
                               ->orderBy('tanggal', 'desc')
                               ->first();

                           // Logika tombol default
                           $isMasuk = false;
                           $isPulang = false;
                           $isSelesai = false;
                           $isDisabled = false;
                           $isOtherShiftActive = false; // REVISI USER: Perbolehkan irisan shift (selalu false)

                           $btnText = 'Ambil Sesi Ini';
                           $btnClass = 'btn-primary';
                           $statusText = 'Tersedia';
                           $statusClass = 'text-primary';

                           if ($absenShift) {
                               if ($absenShift->jam_pulang) {
                                   $isSelesai = true;
                                   $isDisabled = true;
                                   $btnText = 'Selesai';
                                   $btnClass = 'btn-success';
                                   $statusText = 'Sudah Selesai';
                                   $statusClass = 'text-success';
                               } elseif ($absenShift->jam_masuk) {
                                   // Cek apakah sudah boleh pulang
                                   $jamMasukShift = $jamMasuk->copy();
                                   $jamPulangShift = $jamPulang->copy();
                                   $isCrossDay = $jamPulang->lt($jamMasuk); // Recalculate for this context

                                   if ($isCrossDay) {
                                       if ($now->format('H:i:s') > $jamMasukShift->format('H:i:s')) {
                                           $jamPulangShift->addDay();
                                       }
                                   }

                                   // Batas maksimal pulang: 2 jam setelah jam pulang shift
                                   $batasMaksimalPulang = $jamPulangShift->copy()->addHours(2);

                                   if ($now->gt($batasMaksimalPulang)) {
                                       // Sudah lewat batas maksimal (2 jam), tidak bisa absen pulang lagi
                                       $isDisabled = true;
                                       $btnText = 'Sesi Berakhir';
                                       $btnClass = 'btn-secondary';
                                       $statusText = 'Alpha (Waktu Habis)';
                                       $statusClass = 'text-danger';
                                   } elseif ($now->lt($jamPulangShift)) {
                                       $isPulang = true; // State is 'Working'
                                       // $isDisabled = true; // DISABLE DULU BIAR BISA PULANG CEPAT
                                       $btnText = 'Absen Pulang';
                                       $btnClass = 'btn-warning';
                                       $statusText = 'Sedang Berjalan';
                                       $statusClass = 'text-warning';
                                   } else {
                                       // Sudah lewat jam pulang, tapi masih dalam batas 2 jam
                                       $isPulang = true;
                                       $btnText = 'Absen Pulang';
                                       $btnClass = 'btn-danger';
                                       $statusText = 'Waktunya Pulang';
                                       $statusClass = 'text-danger';
                                   }
                               }
                           } else {
                               // Belum absen, cek apakah hari ini libur dan shift mengikuti libur
                               if ($hariLibur && $shift->ikut_libur) {
                                   $isDisabled = true;
                                   $btnText = 'Libur';
                                   $btnClass = 'btn-danger';
                                   $statusText = 'Libur Nasional';
                                   $statusClass = 'text-danger';
                               }

                               // Belum absen, cek waktu
                               $batasAwal = $jamMasuk->copy()->subHours(2); // Bisa absen 2 jam sebelum
                               $isCrossDay = $jamPulang->lt($jamMasuk); // Recalculate for this context

                               $valid = false;

                               if ($isCrossDay) {
                                   // Logic Cross Day: Valid if Now > BatasAwal OR Now < JamPulang
                                   // Invalid if Now is between JamPulang and BatasAwal
                                   if ($now->gt($jamPulang) && $now->lt($batasAwal)) {
                                       $isDisabled = true;
                                       $btnClass = 'btn-secondary';
                                       // Determine label based on proximity
                                       if (
                                           $now->diffInHours($batasAwal, false) > 0 &&
                                           $now->diffInHours($batasAwal, false) < 6
                                       ) {
                                           $btnText = 'Belum Dibuka';
                                           $statusText = 'Dibuka ' . $batasAwal->format('H:i');
                                           $statusClass = 'text-muted';
                                       } else {
                                           $btnText = 'Sesi Berakhir';
                                           $statusText = 'Shift Berakhir';
                                           $statusClass = 'text-danger';
                                       }
                                   } else {
                                       $valid = true;
                                   }
                               } else {
                                   // Logic Normal
                                   if ($now->lt($batasAwal)) {
                                       $isDisabled = true;
                                       $btnClass = 'btn-secondary';
                                       $btnText = 'Belum Dibuka';
                                       $statusText = 'Dibuka ' . $batasAwal->format('H:i');
                                       $statusClass = 'text-muted';
                                   } elseif ($now->gt($jamPulang)) {
                                       $isDisabled = true;
                                       $btnClass = 'btn-secondary';
                                       $btnText = 'Sesi Berakhir';
                                       $statusText = 'Shift Berakhir';
                                       $statusClass = 'text-danger';
                                   } else {
                                       $valid = true;
                                   }
                               }

                               if ($valid) {
                                   $isMasuk = true;
                               }

                               // OVERRIDE LOGIKA JIKA ADA SESI LAIN YANG AKTIF
                               if ($isOtherShiftActive) {
                                   $isDisabled = true;
                                   $btnText = 'Sedang Bekerja...';
                                   $btnClass = 'btn-secondary';
                                   $statusText = 'Sesi Lain Aktif';
                                   $statusClass = 'text-warning';
                               }
                           }
                        @endphp

                        <div class="col-md-6">
                           <div
                              class="card border {{ $absenShift && $absenShift->jam_masuk && !$absenShift->jam_pulang ? 'border-primary border-2 bg-label-primary' : '' }} {{ $isSelesai ? 'bg-label-success' : ($isPulang ? 'bg-label-warning' : '') }}">
                              <div class="card-body p-3">
                                 <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 fw-bold">{{ $shift->nama }}</h6>
                                    <span
                                       class="badge bg-white {{ $statusClass }} shadow-sm">{{ $statusText }}</span>
                                 </div>
                                 <div class="mb-3">
                                    <div class="d-flex align-items-center text-muted small mb-1">
                                       <i class="ri-time-line me-1"></i>
                                       {{ $jamMasuk->format('H:i') }} - {{ $jamPulang->format('H:i') }}
                                    </div>
                                    @if ($absenShift && $absenShift->jam_masuk)
                                       <div class="d-flex align-items-center text-success small">
                                          <i class="ri-login-box-line me-1"></i>
                                          Masuk: {{ $absenShift->jam_masuk->format('H:i') }}
                                       </div>
                                    @endif
                                 </div>

                                 @if ($isDisabled)
                                    <button class="btn {{ $btnClass }} w-100" disabled>
                                       {{ $btnText }}
                                    </button>
                                 @else
                                    <a href="{{ route('absensi.index', ['shift_id' => $shift->id]) }}"
                                       class="btn {{ $btnClass }} w-100">
                                       {{ $btnText }}
                                    </a>
                                 @endif
                              </div>
                           </div>
                        </div>
                     @empty
                        <div class="col-12 text-center py-4">
                           <p class="text-muted mb-0">Tidak ada jadwal shift aktif untuk divisi Anda.</p>
                        </div>
                     @endforelse
                  </div>
               </div>
            </div>

            <!-- Quick Actions Grid -->
            <h5 class="mb-3">Akses Cepat</h5>
            <div class="row g-3 mb-4 text-center">
               <div class="col-6 col-md">
                  <a href="{{ route('absensi.index') }}" class="quick-action-btn shadow-sm">
                     <i class="ri-camera-lens-line"></i>
                     <span>Absensi</span>
                  </a>
               </div>
               <div class="col-6 col-md">
                  <a href="{{ route('izin.create') }}" class="quick-action-btn shadow-sm">
                     <i class="ri-file-add-line"></i>
                     <span>Ajukan Izin</span>
                  </a>
               </div>
               <div class="col-6 col-md">
                  <a href="{{ route('absensi.calendar') }}" class="quick-action-btn shadow-sm">
                     <i class="ri-calendar-2-line"></i>
                     <span>Kalender</span>
                  </a>
               </div>
               <div class="col-6 col-md">
                  <a href="{{ route('absensi.history') }}" class="quick-action-btn shadow-sm">
                     <i class="ri-history-line"></i>
                     <span>Riwayat</span>
                  </a>
               </div>
               <div class="col-6 col-md">
                  <a href="{{ route('profile.edit') }}" class="quick-action-btn shadow-sm">
                     <i class="ri-user-settings-line"></i>
                     <span>Profil Saya</span>
                  </a>
               </div>
            </div>
         </div>

         <!-- Right Column: Recent Activity & Info -->
         <div class="col-lg-4">
            <!-- Recent Activity -->
            <div class="card mb-4 border-0 shadow-sm">
               <div class="card-header d-flex justify-content-between align-items-center bg-transparent">
                  <h5 class="mb-0">Aktivitas Terakhir</h5>
                  <a href="{{ route('absensi.history') }}" class="btn btn-sm btn-link p-0">Lihat Semua</a>
               </div>
               <div class="card-body pt-1">
                  @forelse($historyAbsensi->take(5) as $absen)
                     <div class="history-item">
                        <div>
                           <div class="fw-medium">{{ $absen->tanggal->locale('id')->isoFormat('dddd, D MMM') }}</div>
                           <small class="text-muted">
                              {{ $absen->jam_masuk ? $absen->jam_masuk->format('H:i') : '--' }} -
                              {{ $absen->jam_pulang ? $absen->jam_pulang->format('H:i') : '--' }}
                           </small>
                        </div>
                        <div>
                           @php
                              $displayStatus = $absen->status;
                              $badgeColor = 'label-info';

                              if ($absen->status === 'Tepat Waktu') {
                                  $displayStatus = 'Hadir';
                                  $badgeColor = 'label-success';
                              } elseif ($absen->status === 'Terlambat') {
                                  $displayStatus = 'Telat';
                                  $badgeColor = 'label-warning';
                              }

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
                                  $badgeColor = 'label-danger';
                              }
                           @endphp
                           <span class="badge bg-{{ $badgeColor }}">
                              {{ $displayStatus }}
                           </span>
                        </div>
                     </div>
                  @empty
                     <div class="text-center py-4">
                        <i class="ri-inbox-line ri-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Belum ada aktivitas</p>
                     </div>
                  @endforelse
               </div>
            </div>

         </div>
      </div>
   </div>
@endsection

@section('vendor-script')
   @vite(['resources/assets/vendor/libs/swiper/swiper.js'])
@endsection

@section('page-script')
   <script>
      document.addEventListener('DOMContentLoaded', function() {
         // Initialize Clock
         function updateClock() {
            const now = new Date();
            const options = {
               hour: '2-digit',
               minute: '2-digit',
               second: '2-digit',
               hour12: false
            };
            const clockEl = document.getElementById('clock');
            if (clockEl) clockEl.textContent = now.toLocaleTimeString('id-ID', options);
         }
         setInterval(updateClock, 1000);
         updateClock();

         // Initialize Swiper
         if (typeof Swiper !== 'undefined') {
            new Swiper('.swiper-informasi', {
               slidesPerView: 1,
               spaceBetween: 20,
               pagination: {
                  el: '.swiper-pagination',
                  clickable: true,
               },
               navigation: {
                  nextEl: '.swiper-button-next-custom',
                  prevEl: '.swiper-button-prev-custom',
               },
               breakpoints: {
                  640: {
                     slidesPerView: 2,
                     spaceBetween: 20,
                  },
                  1024: {
                     slidesPerView: 3,
                     spaceBetween: 30,
                  },
               },
               autoplay: {
                  delay: 5000,
                  disableOnInteraction: false,
               },
            });
         }
      });
   </script>
@endsection
