@extends('layouts/layoutMaster')

@section('title', 'Absensi')

@section('page-style')
   <style>
      .camera-container {
         position: relative;
         width: 100%;
         max-width: 400px;
         margin: 0 auto;
         border-radius: 12px;
         overflow: hidden;
         background: #000;
      }

      #video-preview {
         width: 100%;
         height: auto;
         display: block;
      }

      #canvas-capture {
         display: none;
      }

      .captured-preview {
         width: 100%;
         height: auto;
         border-radius: 12px;
      }

      .location-status {
         display: flex;
         align-items: center;
         gap: 8px;
         padding: 12px 16px;
         border-radius: 8px;
         margin-bottom: 16px;
      }

      .location-status.valid {
         background: rgba(40, 199, 111, 0.1);
         border: 1px solid rgba(40, 199, 111, 0.3);
         color: #28c76f;
      }

      .location-status.invalid {
         background: rgba(234, 84, 85, 0.1);
         border: 1px solid rgba(234, 84, 85, 0.3);
         color: #ea5455;
      }

      .location-status.loading {
         background: rgba(255, 159, 67, 0.1);
         border: 1px solid rgba(255, 159, 67, 0.3);
         color: #ff9f43;
      }

      .absen-info-card {
         background: linear-gradient(135deg, #7367f0 0%, #9e95f5 100%);
         color: white;
         border-radius: 16px;
         padding: 24px;
         margin-bottom: 24px;
      }

      .absen-info-card .time {
         font-size: 3rem;
         font-weight: 700;
      }

      .absen-info-card .date {
         opacity: 0.9;
      }

      .status-badge {
         display: inline-flex;
         align-items: center;
         gap: 6px;
         padding: 8px 16px;
         border-radius: 50px;
         font-weight: 600;
      }

      .history-item {
         display: flex;
         justify-content: space-between;
         align-items: center;
         padding: 12px 0;
         border-bottom: 1px solid #eee;
      }

      .history-item:last-child {
         border-bottom: none;
      }

      .camera-overlay {
         position: absolute;
         top: 0;
         left: 0;
         right: 0;
         bottom: 0;
         display: flex;
         align-items: center;
         justify-content: center;
         background: rgba(0, 0, 0, 0.5);
         color: white;
         font-size: 1rem;
      }

      .pulse-animation {
         animation: pulse-border 2s infinite;
      }

      @keyframes pulse-border {
         0% {
            box-shadow: 0 0 0 0 rgba(115, 103, 240, 0.7), 0 4px 15px rgba(0, 0, 0, 0.2);
         }

         70% {
            box-shadow: 0 0 0 15px rgba(115, 103, 240, 0), 0 4px 15px rgba(0, 0, 0, 0.2);
         }

         100% {
            box-shadow: 0 0 0 0 rgba(115, 103, 240, 0), 0 4px 15px rgba(0, 0, 0, 0.2);
         }
      }

      .btn-capture-wrapper {
         display: flex;
         align-items: center;
         justify-content: center;
         transition: transform 0.2s;
         cursor: pointer;
         border: none;
         background: none;
         padding: 0;
         border-radius: 50%;
         outline: none !important;
         /* Responsive size */
         width: 80px;
         height: 80px;
      }

      @media (max-width: 576px) {
         .btn-capture-wrapper {
            width: 70px;
            height: 70px;
         }

         .btn-capture-wrapper .inner-circle i {
            font-size: 1.2rem !important;
         }

         .btn-capture-wrapper .inner-circle small {
            font-size: 0.5rem !important;
         }
      }

      .btn-capture-wrapper:hover {
         transform: scale(1.05);
      }

      .btn-capture-wrapper:active {
         transform: scale(0.95);
      }

      .outer-circle {
         background-color: white;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
         width: 100%;
         height: 100%;
         padding: 4px;
         border: 4px solid #7367f0;
      }

      .inner-circle {
         border-radius: 50%;
         width: 100%;
         height: 100%;
         display: flex;
         flex-direction: column;
         align-items: center;
         justify-content: center;
         color: #7367f0;
      }
   </style>
@endsection

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <!-- Header Info -->
      <div class="absen-info-card">
         <div class="row align-items-center">
            <div class="col-md-6">
               <h4 class="text-white mb-1">Selamat {{ now()->hour < 12 ? 'Pagi' : (now()->hour < 17 ? 'Siang' : 'Malam') }},
               </h4>
               <h3 class="text-white fw-bold mb-3">{{ $pegawai->nama_lengkap }}</h3>
               <p class="mb-1"><i class="ri-building-line me-2"></i>{{ $pegawai->divisi->nama ?? 'Divisi tidak diset' }}
               </p>
               <p class="mb-0"><i class="ri-map-pin-line me-2"></i>{{ $pegawai->kantor->nama ?? 'Kantor tidak diset' }}</p>
            </div>
            <div class="col-md-6 text-md-end">
               <div class="time" id="current-time">--:--:--</div>
               <div class="date">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</div>
            </div>
         </div>
      </div>

      <div class="row">
         <!-- Main Absensi Panel -->
         <div class="col-lg-8">
            <div class="card mb-4">
               <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">
                     <i class="ri-camera-line me-2"></i>Absensi Hari Ini
                  </h5>
                  @if ($absensiHariIni && $absensiHariIni->status)
                     @php
                        $displayStatus = $absensiHariIni->status;
                        $badgeColor = 'info';

                        if ($absensiHariIni->status === 'Tepat Waktu') {
                            $displayStatus = 'Hadir';
                            $badgeColor = 'success';
                        } elseif ($absensiHariIni->status === 'Terlambat') {
                            $badgeColor = 'warning';
                        }

                        // Khusus di halaman absen, jika belum pulang sampai ganti hari, status jadi Alpha
                        // (Meskipun biasanya di halaman ini hanya tampil data hari ini, tapi untuk jaga-jaga)
                        if (
                            !in_array($absensiHariIni->status, ['Izin', 'Sakit', 'Cuti']) &&
                            !$absensiHariIni->jam_pulang &&
                            !$absensiHariIni->tanggal->isToday()
                        ) {
                            $displayStatus = 'Alpha';
                            $badgeColor = 'danger';
                        }
                     @endphp
                     <span class="badge bg-{{ $badgeColor }}">
                        {{ $displayStatus }}
                     </span>
                  @endif
               </div>
               <div class="card-body">
                  <!-- Location Status -->
                  <div class="location-status loading" id="location-status">
                     <i class="ri-loader-4-line ri-spin"></i>
                     <span>Mengambil lokasi GPS...</span>
                  </div>

                  <!-- Camera Section -->
                  <div class="camera-container mb-4" id="camera-container">
                     <video id="video-preview" autoplay playsinline></video>
                     <canvas id="canvas-capture"></canvas>
                     <div class="camera-overlay" id="camera-overlay">
                        <span><i class="ri-camera-off-line me-2"></i>Kamera belum aktif</span>
                     </div>
                  </div>

                  <!-- Captured Preview -->
                  <div class="text-center mb-4 d-none" id="preview-section">
                     <img id="captured-preview" class="captured-preview" src="" alt="Preview">
                     <button type="button" class="btn btn-outline-secondary mt-2" id="btn-retake">
                        <i class="ri-refresh-line me-1"></i>Ambil Ulang
                     </button>
                  </div>

                  <!-- Camera Controls -->
                  <div class="text-center mb-4">
                     <button type="button" class="btn btn-lg btn-outline-primary" id="btn-start-camera">
                        <i class="ri-camera-line me-2"></i>Mulai Kamera
                     </button>

                     <div class="d-none" id="capture-controls">
                        <button type="button" class="btn-capture-wrapper pulse-animation mx-auto" id="btn-capture">
                           <div class="outer-circle">
                              <div class="inner-circle">
                                 <i class="ri-camera-fill ri-xl mb-1"></i>
                                 <small style="font-weight: 800; line-height: 1;">TEKAN</small>
                              </div>
                           </div>
                        </button>
                        <p class="text-muted mt-3 mb-0">Tekan tombol untuk ambil foto</p>
                     </div>
                  </div>

                  <!-- Absen Buttons -->
                  @php
                     $isEarly = false;
                     $targetJamPulang = null;
                     $batasMaksimalPulang = null;
                     $sudahLewatBatas = false;

                     if ($shift && $absensiHariIni && $absensiHariIni->jam_masuk && !$absensiHariIni->jam_pulang) {
                         $now = now();
                         $jamPulang = \Carbon\Carbon::parse($shift->jam_pulang->format('H:i:s'));
                         $jamMasuk = \Carbon\Carbon::parse($shift->jam_masuk->format('H:i:s'));

                         if ($jamMasuk->gt($jamPulang)) {
                             if ($now->format('H:i:s') > $jamMasuk->format('H:i:s')) {
                                 $jamPulang->addDay();
                             }
                         }
                         $isEarly = $now->lt($jamPulang);
                         $targetJamPulang = $jamPulang->format('Y-m-d\TH:i:s');

                         // Hitung batas maksimal (2 jam setelah jam pulang)
                         $batasMaksimalPulang = $jamPulang->copy()->addHours(2);
                         $sudahLewatBatas = $now->gt($batasMaksimalPulang);
                     }
                  @endphp
                  <div class="row g-3">
                     <div class="col-6">
                        <button type="button" class="btn btn-success btn-lg w-100" id="btn-absen-masuk"
                           @if ($absensiHariIni && $absensiHariIni->jam_masuk) disabled @endif>
                           <i class="ri-login-box-line me-2"></i>
                           @if ($absensiHariIni && $absensiHariIni->jam_masuk)
                              Masuk: {{ $absensiHariIni->jam_masuk->format('H:i') }}
                           @else
                              Absen Masuk
                           @endif
                        </button>
                     </div>
                     <div class="col-6">
                        <button type="button" class="btn btn-danger btn-lg w-100" id="btn-absen-pulang"
                           data-target-pulang="{{ $targetJamPulang }}" @if (!$absensiHariIni || !$absensiHariIni->jam_masuk || $absensiHariIni->jam_pulang || $sudahLewatBatas) disabled @endif>
                           <i class="ri-logout-box-line me-2"></i>
                           @if ($absensiHariIni && $absensiHariIni->jam_pulang)
                              Pulang: {{ $absensiHariIni->jam_pulang->format('H:i') }}
                           @elseif ($sudahLewatBatas)
                              Batas Waktu Terlewat
                           @else
                              Absen Pulang
                           @endif
                        </button>
                        @if ($sudahLewatBatas && $absensiHariIni && !$absensiHariIni->jam_pulang)
                           <small class="text-danger d-block mt-1">
                              <i class="ri-error-warning-line"></i> Batas absen pulang:
                              {{ $batasMaksimalPulang->format('H:i') }}
                           </small>
                        @endif
                     </div>
                  </div>

                  @if ($shift)
                     <div class="alert alert-primary mt-4 mb-0 shadow-sm border-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                           <span class="fw-bold"><i class="ri-time-line me-1"></i> Sesi Shift:
                              {{ $shift->nama }}</span>
                           <span class="badge bg-primary">{{ $shift->jam_masuk->format('H:i') }} -
                              {{ $shift->jam_pulang->format('H:i') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small opacity-75">
                           <span><i class="ri-building-line me-1"></i> {{ $pegawai->divisi->nama }}</span>
                           @if ($pegawai->divisi && $pegawai->divisi->toleransi_terlambat > 0)
                              <span><i class="ri-timer-line me-1"></i> Toleransi:
                                 {{ $pegawai->divisi->toleransi_terlambat }} mnt</span>
                           @endif
                        </div>
                     </div>
                  @endif
               </div>
            </div>
         </div>

         <!-- Sidebar -->
         <div class="col-lg-4">
            <!-- Today's Status -->
            @if ($absensiHariIni)
               <div class="card mb-4">
                  <div class="card-header">
                     <h6 class="mb-0"><i class="ri-calendar-check-line me-2"></i>Status Hari Ini</h6>
                  </div>
                  <div class="card-body">
                     <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Masuk</span>
                        <strong>{{ $absensiHariIni->jam_masuk ? $absensiHariIni->jam_masuk->format('H:i:s') : '-' }}</strong>
                     </div>
                     <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Pulang</span>
                        <strong>{{ $absensiHariIni->jam_pulang ? $absensiHariIni->jam_pulang->format('H:i:s') : '-' }}</strong>
                     </div>
                     <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Lokasi Masuk</span>
                        <strong>{{ $absensiHariIni->lokasi_masuk ?? '-' }}</strong>
                     </div>
                     <div class="d-flex justify-content-between">
                        <span class="text-muted">Status</span>
                        @php
                           $displayStatus = $absensiHariIni->status;
                           $badgeColor = 'info';

                           if ($absensiHariIni->status === 'Tepat Waktu') {
                               $displayStatus = 'Hadir';
                               $badgeColor = 'success';
                           } elseif ($absensiHariIni->status === 'Terlambat') {
                               $badgeColor = 'warning';
                           }

                           if (
                               !in_array($absensiHariIni->status, ['Izin', 'Sakit', 'Cuti']) &&
                               !$absensiHariIni->jam_pulang &&
                               !$absensiHariIni->tanggal->isToday()
                           ) {
                               $displayStatus = 'Alpha';
                               $badgeColor = 'danger';
                           }
                        @endphp
                        <span class="badge bg-{{ $badgeColor }}">
                           {{ $displayStatus }}
                        </span>
                     </div>
                  </div>
               </div>
            @endif

            <!-- Quick Actions -->
            <div class="card mb-4">
               <div class="card-header">
                  <h6 class="mb-0"><i class="ri-apps-line me-2"></i>Menu Cepat</h6>
               </div>
               <div class="card-body">
                  <div class="d-grid gap-2">
                     <a href="{{ route('absensi.history') }}" class="btn btn-outline-primary">
                        <i class="ri-history-line me-2"></i>Riwayat Absensi
                     </a>
                     <a href="{{ route('izin.create') }}" class="btn btn-outline-warning">
                        <i class="ri-file-list-3-line me-2"></i>Ajukan Izin
                     </a>
                     <a href="{{ route('izin.index') }}" class="btn btn-outline-info">
                        <i class="ri-folder-open-line me-2"></i>Status Izin Saya
                     </a>
                  </div>
               </div>
            </div>

            <!-- Recent History -->
            <div class="card">
               <div class="card-header d-flex justify-content-between align-items-center">
                  <h6 class="mb-0"><i class="ri-history-line me-2"></i>7 Hari Terakhir</h6>
                  <a href="{{ route('absensi.history') }}" class="btn btn-sm btn-link">Lihat Semua</a>
               </div>
               <div class="card-body">
                  @forelse($historyAbsensi->take(7) as $absen)
                     <div class="history-item">
                        <div>
                           <strong>{{ $absen->tanggal->locale('id')->isoFormat('ddd, D MMM') }}</strong>
                           <br>
                           <small class="text-muted">
                              {{ $absen->jam_masuk ? $absen->jam_masuk->format('H:i') : '-' }} -
                              {{ $absen->jam_pulang ? $absen->jam_pulang->format('H:i') : '-' }}
                           </small>
                        </div>
                        @php
                           $displayStatus = $absen->status;
                           $badgeColor = 'info';

                           if ($absen->status === 'Tepat Waktu') {
                               $displayStatus = 'Hadir';
                               $badgeColor = 'label-success';
                           } elseif ($absen->status === 'Terlambat') {
                               $displayStatus = 'Telat';
                               $badgeColor = 'label-warning';
                           } else {
                               $badgeColor = 'label-info';
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
                  @empty
                     <div class="text-center text-muted py-3">
                        <i class="ri-calendar-line ri-2x mb-2"></i>
                        <p class="mb-0">Belum ada riwayat absensi</p>
                     </div>
                  @endforelse
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Modal Alasan Pulang Cepat -->
   <div class="modal fade" id="modalAlasanPulang" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title">Alasan Pulang Lebih Awal</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               <div class="alert alert-warning">
                  <i class="ri-error-warning-line me-2"></i>
                  Anda melakukan absen pulang sebelum jam kerja berakhir. Silakan berikan alasan.
               </div>
               <div class="form-group">
                  <label for="alasan_pulang" class="form-label">Alasan / Keterangan</label>
                  <textarea class="form-control" id="alasan_pulang" rows="3"
                     placeholder="Contoh: Sakit, Ada urusan mendesak, dll"></textarea>
               </div>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
               <button type="button" class="btn btn-primary" id="btn-submit-alasan">Kirim & Absen Pulang</button>
            </div>
         </div>
      </div>
   </div>

   <!-- Hidden Form -->
   <form id="absensi-form" style="display: none;">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <input type="hidden" name="latitude" id="input-latitude">
      <input type="hidden" name="longitude" id="input-longitude">
      <input type="hidden" name="shift_id" value="{{ $shift->id ?? '' }}">
   </form>
@endsection

@section('page-script')
   <script>
      document.addEventListener('DOMContentLoaded', function() {
         // Elements
         const videoPreview = document.getElementById('video-preview');
         const canvasCapture = document.getElementById('canvas-capture');
         const capturedPreview = document.getElementById('captured-preview');
         const cameraOverlay = document.getElementById('camera-overlay');
         const cameraContainer = document.getElementById('camera-container');
         const previewSection = document.getElementById('preview-section');
         const captureControls = document.getElementById('capture-controls');
         const locationStatus = document.getElementById('location-status');

         const btnStartCamera = document.getElementById('btn-start-camera');
         const btnCapture = document.getElementById('btn-capture');
         const btnRetake = document.getElementById('btn-retake');
         const btnAbsenMasuk = document.getElementById('btn-absen-masuk');
         const btnAbsenPulang = document.getElementById('btn-absen-pulang');

         let stream = null;
         let capturedBlob = null;
         let currentLatitude = null;
         let currentLongitude = null;
         let locationValid = false;

         // Update clock
         function updateClock() {
            const now = new Date();
            document.getElementById('current-time').textContent =
               now.toLocaleTimeString('id-ID', {
                  hour: '2-digit',
                  minute: '2-digit',
                  second: '2-digit'
               });
         }
         updateClock();
         setInterval(updateClock, 1000);

         // Get location
         function getLocation() {
            if (!navigator.geolocation) {
               updateLocationStatus('error', 'GPS tidak didukung di browser ini');
               return;
            }

            navigator.geolocation.getCurrentPosition(
               (position) => {
                  currentLatitude = position.coords.latitude;
                  currentLongitude = position.coords.longitude;
                  document.getElementById('input-latitude').value = currentLatitude;
                  document.getElementById('input-longitude').value = currentLongitude;

                  // Validate location with server
                  validateLocation();
               },
               (error) => {
                  let message = 'Gagal mendapatkan lokasi';
                  if (error.code === error.PERMISSION_DENIED) {
                     message = 'Akses lokasi ditolak. Mohon izinkan akses GPS.';
                  }
                  updateLocationStatus('error', message);
               }, {
                  enableHighAccuracy: true,
                  timeout: 10000,
                  maximumAge: 60000
               }
            );
         }

         function validateLocation() {
            fetch('{{ route('absensi.validate-location') }}', {
                  method: 'POST',
                  headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                  },
                  body: JSON.stringify({
                     latitude: currentLatitude,
                     longitude: currentLongitude
                  })
               })
               .then(response => response.json())
               .then(data => {
                  if (data.success && data.data.valid) {
                     locationValid = true;
                     updateLocationStatus('valid',
                        `✓ Lokasi valid: ${data.data.kantor_nama} (${data.data.distance}m)`);
                  } else {
                     locationValid = false;
                     updateLocationStatus('error', data.data?.message || data.message || 'Lokasi di luar radius');
                  }
               })
               .catch(error => {
                  updateLocationStatus('error', 'Gagal validasi lokasi');
               });
         }

         function updateLocationStatus(status, message) {
            locationStatus.className = 'location-status';
            if (status === 'valid') {
               locationStatus.classList.add('valid');
               locationStatus.innerHTML = `<i class="ri-map-pin-line"></i><span>${message}</span>`;
            } else if (status === 'error') {
               locationStatus.classList.add('invalid');
               locationStatus.innerHTML = `<i class="ri-error-warning-line"></i><span>${message}</span>`;
            } else {
               locationStatus.classList.add('loading');
               locationStatus.innerHTML = `<i class="ri-loader-4-line ri-spin"></i><span>${message}</span>`;
            }
         }

         // Start camera
         btnStartCamera.addEventListener('click', async function() {
            try {
               stream = await navigator.mediaDevices.getUserMedia({
                  video: {
                     facingMode: 'user',
                     width: 640,
                     height: 480
                  }
               });
               videoPreview.srcObject = stream;
               cameraOverlay.style.display = 'none';
               btnStartCamera.classList.add('d-none');
               captureControls.classList.remove('d-none');
            } catch (error) {
               window.AlertHandler.showError(
                  'Gagal mengakses kamera. Pastikan Anda mengizinkan akses kamera.');
            }
         });

         // Capture photo
         btnCapture.addEventListener('click', function() {
            if (!stream) return;

            canvasCapture.width = videoPreview.videoWidth;
            canvasCapture.height = videoPreview.videoHeight;

            const ctx = canvasCapture.getContext('2d');
            ctx.drawImage(videoPreview, 0, 0);

            canvasCapture.toBlob(function(blob) {
               capturedBlob = blob;
               capturedPreview.src = URL.createObjectURL(blob);

               // Show preview, hide camera
               cameraContainer.classList.add('d-none');
               captureControls.classList.add('d-none');
               previewSection.classList.remove('d-none');
            }, 'image/jpeg', 0.8);
         });

         // Retake photo
         btnRetake.addEventListener('click', function() {
            capturedBlob = null;
            previewSection.classList.add('d-none');
            cameraContainer.classList.remove('d-none');
            captureControls.classList.remove('d-none');
         });

         // Submit absensi
         function submitAbsensi(type, keterangan = '') {
            if (!capturedBlob) {
               window.AlertHandler.showError('Silakan ambil foto terlebih dahulu!');
               return;
            }

            if (!locationValid) {
               window.AlertHandler.showError('Lokasi Anda tidak valid untuk absensi!');
               return;
            }

            const formData = new FormData();
            formData.append('foto', capturedBlob, 'foto.jpg');
            formData.append('latitude', currentLatitude);
            formData.append('longitude', currentLongitude);
            if (keterangan) {
               formData.append('keterangan', keterangan);
            }
            // Ambil shift_id dari hidden input
            const shiftId = document.querySelector('input[name="shift_id"]').value;
            formData.append('shift_id', shiftId);
            formData.append('_token', '{{ csrf_token() }}');

            const url = type === 'masuk' ? '{{ route('absensi.masuk') }}' : '{{ route('absensi.pulang') }}';
            const btn = type === 'masuk' ? btnAbsenMasuk : btnAbsenPulang;

            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line ri-spin me-2"></i>Memproses...';

            fetch(url, {
                  method: 'POST',
                  body: formData,
                  headers: {
                     'Accept': 'application/json'
                  }
               })
               .then(response => response.json())
               .then(data => {
                  window.AlertHandler.handle(data);
                  if (data.success) {
                     setTimeout(() => window.location.reload(), 1500);
                  } else {
                     btn.disabled = false;
                     btn.innerHTML = type === 'masuk' ? '<i class="ri-login-box-line me-2"></i>Absen Masuk' :
                        '<i class="ri-logout-box-line me-2"></i>Absen Pulang';
                  }
               })
               .catch(error => {
                  window.AlertHandler.showError('Terjadi kesalahan. Silakan coba lagi.');
                  btn.disabled = false;
               });
         }

         btnAbsenMasuk.addEventListener('click', () => submitAbsensi('masuk'));

         const modalAlasan = new bootstrap.Modal(document.getElementById('modalAlasanPulang'));
         const btnSubmitAlasan = document.getElementById('btn-submit-alasan');
         const inputAlasan = document.getElementById('alasan_pulang');

         btnAbsenPulang.addEventListener('click', function() {
            const targetPulangStr = this.getAttribute('data-target-pulang');
            if (targetPulangStr) {
               const targetPulang = new Date(targetPulangStr);
               const now = new Date();

               if (now < targetPulang) {
                  // Pulang lebih awal
                  modalAlasan.show();
               } else {
                  // Sudah jam pulang
                  submitAbsensi('pulang');
               }
            } else {
               submitAbsensi('pulang');
            }
         });

         btnSubmitAlasan.addEventListener('click', function() {
            const alasan = inputAlasan.value.trim();
            if (!alasan) {
               window.AlertHandler.showError('Harap masukkan alasan pulang lebih awal!');
               return;
            }
            modalAlasan.hide();
            submitAbsensi('pulang', alasan);
         });

         // Initialize
         getLocation();

         // Refresh location every 30 seconds
         setInterval(getLocation, 30000);
      });
   </script>
@endsection
