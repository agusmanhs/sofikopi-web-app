@extends('layouts/layoutMaster')

@section('title', 'Detail Delivery Order')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('page-style')
<style>
.camera-container {
    position: relative;
    width: 100%;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
    min-height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
}
#video-preview { width: 100%; height: auto; display: block; }
#canvas-capture { display: none; }
.captured-preview { width: 100%; border-radius: 12px; max-height: 240px; object-fit: cover; }
.camera-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .875rem;
}
.btn-capture-wrapper {
    width: 70px; height: 70px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; border: none; background: transparent; padding: 0;
}
.btn-capture-wrapper:active { transform: scale(0.95); }
.outer-circle {
    width: 70px; height: 70px; border-radius: 50%;
    background: var(--bs-primary, #7367f0);
    display: flex; align-items: center; justify-content: center;
}
.inner-circle {
    width: 56px; height: 56px; border-radius: 50%;
    background: var(--bs-primary, #7367f0);
    border: 3px solid rgba(255,255,255,.5);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: #fff;
}
@keyframes pulse-border {
    0%   { box-shadow: 0 0 0 0   rgba(115,103,240,.5); }
    70%  { box-shadow: 0 0 0 10px rgba(115,103,240,0); }
    100% { box-shadow: 0 0 0 0   rgba(115,103,240,0); }
}
.pulse-animation { animation: pulse-border 2s infinite; }
</style>
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js'
])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Penjualan / Delivery Order /</span> Detail
        </h4>
        <div class="d-flex gap-2">
            <a href="{{ route('delivery-order.print', $data->id) }}" target="_blank" class="btn btn-outline-danger">
                <i class="ri-file-pdf-line me-1"></i> Cetak PDF
            </a>
            <a href="{{ route('delivery-order.index') }}" class="btn btn-outline-secondary">
                <i class="ri-arrow-left-line me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <!-- DO Info & Tracking -->
        <div class="col-xl-8 col-lg-7">
            <div class="card mb-4">
                <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Informasi Surat Jalan</h5>
                    @php
                        $badgeClass = 'bg-label-secondary';
                        if($data->status == 'assigned') $badgeClass = 'bg-label-primary';
                        if($data->status == 'in_delivery') $badgeClass = 'bg-label-warning';
                        if($data->status == 'delivered') $badgeClass = 'bg-label-success';
                    @endphp
                    <span class="badge {{ $badgeClass }} px-3 py-2 fs-7">{{ ucfirst($data->status) }}</span>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Nomor Surat Jalan:</span>
                            <span class="fw-semibold text-heading">{{ $data->do_number }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Nomor Sales Order:</span>
                            <span class="fw-semibold text-heading">{{ $data->salesOrder->order_number ?? '-' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Customer:</span>
                            <span class="fw-semibold text-heading">{{ $data->salesOrder->customer_name ?? '-' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Tipe Pengiriman:</span>
                            <span class="fw-semibold text-heading">{{ $data->delivery_type == 'delivery' ? 'Diantar' : 'Ambil di Store' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Petugas Pengirim (Looper):</span>
                            <span class="fw-semibold text-heading text-primary">{{ $data->assignedTo->name ?? 'Belum Ditugaskan' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block small">Tanggal Kirim Selesai:</span>
                            <span class="fw-semibold text-heading">{{ $data->delivery_date ? $data->delivery_date->format('d/m/Y') : 'Belum selesai' }}</span>
                        </div>
                    </div>

                    @if($data->notes)
                    <div class="mb-4 bg-lighter p-3 rounded">
                        <span class="text-muted d-block mb-1 fw-bold fs-7">Catatan Pengiriman:</span>
                        <p class="mb-0 text-heading small">{{ $data->notes }}</p>
                    </div>
                    @endif

                    <div class="border-top pt-3 mt-4">
                        <h6 class="fw-bold mb-3">Item yang Dikirim</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="py-2">Nama Produk</th>
                                        <th class="py-2 text-center" width="120">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data->salesOrder->items ?? [] as $item)
                                        <tr>
                                            <td class="py-2 fw-medium">{{ $item->product->name ?? 'Produk Tidak Ditemukan' }}</td>
                                            <td class="py-2 text-center">{{ $item->quantity }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center py-3 text-muted">Tidak ada item dalam DO ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if($data->status == 'delivered')
            <div class="card mb-4">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-bold text-success"><i class="ri-checkbox-circle-line me-1"></i> Bukti Penerimaan</h5>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <span class="text-muted d-block small">Diterima Oleh:</span>
                                <span class="fw-semibold text-heading">{{ $data->received_by_name ?? '-' }}</span>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted d-block small">Waktu Penerimaan:</span>
                                <span class="fw-semibold text-heading">{{ $data->delivered_at ? $data->delivered_at->format('d M Y H:i') : '-' }}</span>
                            </div>
                            @if($data->proof_latitude && $data->proof_longitude)
                            <div class="mb-3">
                                <span class="text-muted d-block small">Koordinat GPS:</span>
                                <span class="fw-semibold text-heading">{{ $data->proof_latitude }}, {{ $data->proof_longitude }}</span>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ $data->proof_latitude }},{{ $data->proof_longitude }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2 d-block w-50">
                                    <i class="ri-map-pin-line me-1"></i> Lihat di Google Maps
                                </a>
                            </div>
                            @endif
                        </div>
                        <div class="col-md-6 text-center">
                            @if($data->proof_photo)
                            <span class="text-muted d-block small mb-2 text-start">Foto Bukti Penerimaan:</span>
                            <img src="{{ Storage::disk('public')->url($data->proof_photo) }}" alt="Foto Bukti" class="img-fluid rounded border shadow-sm" style="max-height: 200px;">
                            @else
                            <span class="text-muted">Tidak ada foto bukti</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- DO Actions Column -->
        <div class="col-xl-4 col-lg-5">
            @if($data->delivery_type == 'delivery')
            <!-- HRD Reassign Panel -->
            @if(in_array(auth()->user()->role->slug, ['hrd', 'super-admin']) && $data->status !== 'delivered')
            <div class="card mb-4 border-primary border">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-bold"><i class="ri-user-settings-line me-1 text-primary"></i> Penugasan Kurir (HRD)</h5>
                </div>
                <form action="{{ route('delivery-order.reassign', $data->id) }}" method="POST">
                    @csrf
                    <div class="card-body pt-3">
                        <div class="mb-3">
                            <label class="form-label">Pilih Kurir / Looper</label>
                            <select name="assigned_to" class="form-select select2" required>
                                <option value="">-- Pilih Kurir --</option>
                                @foreach($loopers as $looper)
                                    <option value="{{ $looper->id }}" {{ $data->assigned_to == $looper->id ? 'selected' : '' }}>
                                        {{ $looper->name }} ({{ $looper->role->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Tugas</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan opsional untuk kurir...">{{ $data->notes }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer border-top pt-3 text-end">
                        <button type="submit" class="btn btn-primary w-100">Simpan Penugasan</button>
                    </div>
                </form>
            </div>
            @endif

            <!-- Courier / Looper Panel -->
            @if(($data->assigned_to == auth()->id() || auth()->user()->role->slug == 'super-admin') && $data->status !== 'delivered')
            <div class="card mb-4 border-warning border">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-bold"><i class="ri-truck-line me-1 text-warning"></i> Menu Aksi Pengiriman</h5>
                </div>
                <div class="card-body pt-3">
                    @if(in_array($data->status, ['pending', 'assigned']))
                    <p class="small text-muted mb-3">Pesanan telah ditugaskan kepada Anda. Klik tombol di bawah untuk mulai mengirimkan pesanan.</p>
                    <form action="{{ route('delivery-order.start', $data->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100 py-2 fw-semibold">
                            <i class="ri-play-circle-line me-1"></i> Mulai Kirim Pesanan
                        </button>
                    </form>
                    @endif

                    @if($data->status == 'in_delivery')
                    <p class="small text-muted mb-3">Pesanan sedang dalam perjalanan. Saat tiba di lokasi, isikan detail penerima dan lampirkan bukti foto untuk menyelesaikan.</p>
                    
                    <form action="{{ route('delivery-order.upload-proof', $data->id) }}" method="POST" enctype="multipart/form-data" id="complete-delivery-form">
                        @csrf
                        <input type="hidden" name="proof_latitude" id="lat-input">
                        <input type="hidden" name="proof_longitude" id="lng-input">

                        <div class="mb-3">
                            <label class="form-label">Nama Penerima</label>
                            <input type="text" name="received_by_name" class="form-control" placeholder="Contoh: Budi Susanto" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Foto Bukti Serah Terima</label>

                            <!-- Camera widget (same pattern as absensi) -->
                            <div class="camera-container mb-2" id="camera-container">
                                <video id="video-preview" autoplay playsinline></video>
                                <canvas id="canvas-capture"></canvas>
                                <div class="camera-overlay" id="camera-overlay">
                                    <span><i class="ri-camera-off-line me-2"></i>Kamera belum aktif</span>
                                </div>
                            </div>

                            <!-- Photo preview (shown after capture) -->
                            <div class="text-center mb-2 d-none" id="preview-section">
                                <img id="captured-preview" class="captured-preview" src="" alt="Preview Foto">
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn-retake">
                                    <i class="ri-refresh-line me-1"></i>Ambil Ulang
                                </button>
                            </div>

                            <!-- Camera controls -->
                            <div class="text-center mb-2">
                                <button type="button" class="btn btn-outline-primary" id="btn-start-camera">
                                    <i class="ri-camera-line me-2"></i>Buka Kamera
                                </button>
                                <div class="d-none mt-2" id="capture-controls">
                                    <button type="button" class="btn-capture-wrapper pulse-animation mx-auto" id="btn-capture">
                                        <div class="outer-circle">
                                            <div class="inner-circle">
                                                <i class="ri-camera-fill ri-xl mb-1"></i>
                                                <small style="font-weight:800;line-height:1;">TEKAN</small>
                                            </div>
                                        </div>
                                    </button>
                                    <p class="text-muted small mt-2 mb-0">Tekan tombol untuk ambil foto</p>
                                </div>
                            </div>

                            <!-- Hidden primary input: receives File via DataTransfer after capture -->
                            <input type="file" name="proof_photo" id="proof-photo-input" accept="image/*" class="d-none" required>

                            <!-- Fallback: shown only if getUserMedia fails -->
                            <div id="camera-fallback" class="d-none">
                                <div class="alert alert-warning py-2 px-3 mb-2 small" id="fallback-message"></div>
                                <div class="d-flex gap-2">
                                    <label for="input-native-camera" class="btn btn-outline-primary btn-sm flex-fill mb-0">
                                        <i class="ri-camera-line me-1"></i>Buka Kamera
                                    </label>
                                    <input type="file" id="input-native-camera" accept="image/*" capture="environment" class="d-none">

                                    <label for="input-gallery" class="btn btn-outline-secondary btn-sm flex-fill mb-0">
                                        <i class="ri-image-line me-1"></i>Pilih dari Galeri
                                    </label>
                                    <input type="file" id="input-gallery" accept="image/*" class="d-none">
                                </div>
                            </div>

                            <small class="d-block mt-1" id="photo-status" style="color:inherit;">Belum ada foto diambil.</small>
                        </div>

                        <div class="mb-3 bg-light p-2 rounded border">
                            <span class="text-muted d-block small mb-1"><i class="ri-map-pin-line"></i> Lokasi GPS Kurir:</span>
                            <div id="gps-status" class="small fw-semibold text-warning">Mencari lokasi GPS...</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan penerimaan jika ada..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold" id="complete-btn" disabled>
                            <i class="ri-checkbox-circle-line me-1"></i> Selesaikan Pengiriman
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endif
            @else
            <!-- Self Pickup Panel -->
            <div class="card mb-4 border-info border">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-bold"><i class="ri-store-2-line me-1 text-info"></i> Ambil di Store</h5>
                </div>
                <div class="card-body pt-3">
                    @if($data->status !== 'delivered')
                        @if(in_array(auth()->user()->role->slug, ['hrd', 'super-admin']))
                        <p class="small text-muted mb-3">Pesanan ini diambil langsung di store oleh customer. Isikan nama penerima saat barang diserahkan untuk menyelesaikan.</p>
                        <form action="{{ route('delivery-order.complete-pickup', $data->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nama Penerima</label>
                                <input type="text" name="received_by_name" class="form-control" placeholder="Contoh: Budi Susanto" required>
                            </div>
                            <button type="submit" class="btn btn-info w-100 py-2 fw-semibold">
                                <i class="ri-checkbox-circle-line me-1"></i> Selesaikan Pengambilan
                            </button>
                        </form>
                        @else
                        <p class="small text-muted mb-0">Pesanan ini diambil langsung di store. Penyelesaian pengambilan dilakukan oleh petugas HRD.</p>
                        @endif
                    @else
                    <p class="small text-success mb-0"><i class="ri-checkbox-circle-line me-1"></i> Pengambilan telah diselesaikan.</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jq = window.$ || window.jQuery;
        if (jq) {
            jq('.select2').each(function() {
                var $this = jq(this);
                $this.wrap('<div class="position-relative"></div>').select2({
                    placeholder: '-- Pilih Kurir --',
                    dropdownParent: $this.parent(),
                    allowClear: true
                });
            });
        }

        // Get coordinates automatically if courier is finalizing DO
        if (document.getElementById('complete-delivery-form')) {
            const gpsStatus = document.getElementById('gps-status');
            const latInput = document.getElementById('lat-input');
            const lngInput = document.getElementById('lng-input');
            const completeBtn = document.getElementById('complete-btn');

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        latInput.value = position.coords.latitude;
                        lngInput.value = position.coords.longitude;
                        gpsStatus.textContent = 'Lokasi GPS berhasil direkam: ' + position.coords.latitude.toFixed(6) + ', ' + position.coords.longitude.toFixed(6);
                        gpsStatus.className = 'small fw-semibold text-success';
                        completeBtn.disabled = false;
                    },
                    function(error) {
                        gpsStatus.textContent = 'Gagal mengakses GPS: ' + error.message + '. Anda masih dapat menyelesaikan pengisian.';
                        gpsStatus.className = 'small fw-semibold text-danger';
                        completeBtn.disabled = false; // still allow complete
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                gpsStatus.textContent = 'Browser Anda tidak mendukung perekaman GPS.';
                gpsStatus.className = 'small fw-semibold text-danger';
                completeBtn.disabled = false;
            }

            // --- Camera: Proof Photo ---
            let doStream = null;

            const btnStartCamera   = document.getElementById('btn-start-camera');
            const btnCapture       = document.getElementById('btn-capture');
            const btnRetake        = document.getElementById('btn-retake');
            const videoPreview     = document.getElementById('video-preview');
            const canvasCapture    = document.getElementById('canvas-capture');
            const capturedPreview  = document.getElementById('captured-preview');
            const cameraContainer  = document.getElementById('camera-container');
            const cameraOverlay    = document.getElementById('camera-overlay');
            const captureControls  = document.getElementById('capture-controls');
            const previewSection   = document.getElementById('preview-section');
            const proofInput       = document.getElementById('proof-photo-input');
            const photoStatus      = document.getElementById('photo-status');
            const cameraFallback   = document.getElementById('camera-fallback');
            const fallbackMessage  = document.getElementById('fallback-message');
            const inputNativeCamera = document.getElementById('input-native-camera');
            const inputGallery     = document.getElementById('input-gallery');

            function stopStream() {
                if (doStream) {
                    doStream.getTracks().forEach(function(t) { t.stop(); });
                    doStream = null;
                }
            }

            function showFallback(msg) {
                fallbackMessage.textContent = msg;
                cameraFallback.classList.remove('d-none');
                btnStartCamera.classList.add('d-none');
                photoStatus.textContent = msg;
                photoStatus.style.color = 'var(--bs-warning)';
            }

            function syncFallbackFile(file) {
                if (!file) return;
                var dt = new DataTransfer();
                dt.items.add(file);
                proofInput.files = dt.files;
                capturedPreview.src = URL.createObjectURL(file);
                cameraContainer.classList.add('d-none');
                previewSection.classList.remove('d-none');
                photoStatus.textContent = 'Foto berhasil dipilih.';
                photoStatus.style.color = 'var(--bs-success)';
            }

            inputNativeCamera.addEventListener('change', function() {
                syncFallbackFile(this.files[0] || null);
            });

            inputGallery.addEventListener('change', function() {
                syncFallbackFile(this.files[0] || null);
            });

            btnStartCamera.addEventListener('click', function() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showFallback('Browser tidak mendukung akses kamera langsung. Gunakan pilihan di bawah:');
                    return;
                }
                navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: 640, height: 480 },
                    audio: false
                }).then(function(stream) {
                    doStream = stream;
                    videoPreview.srcObject = stream;
                    cameraOverlay.style.display = 'none';
                    btnStartCamera.classList.add('d-none');
                    captureControls.classList.remove('d-none');
                }).catch(function(err) {
                    var msg = err.name === 'NotAllowedError'
                        ? 'Izin kamera ditolak. Gunakan pilihan di bawah:'
                        : 'Kamera tidak dapat diakses. Gunakan pilihan di bawah:';
                    showFallback(msg);
                });
            });

            btnCapture.addEventListener('click', function() {
                canvasCapture.width  = videoPreview.videoWidth;
                canvasCapture.height = videoPreview.videoHeight;
                canvasCapture.getContext('2d').drawImage(videoPreview, 0, 0);

                canvasCapture.toBlob(function(blob) {
                    var file = new File([blob], 'proof_delivery.jpg', { type: 'image/jpeg' });
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    proofInput.files = dt.files;

                    capturedPreview.src = URL.createObjectURL(blob);
                    cameraContainer.classList.add('d-none');
                    captureControls.classList.add('d-none');
                    previewSection.classList.remove('d-none');
                    photoStatus.textContent = 'Foto berhasil diambil.';
                    photoStatus.style.color = 'var(--bs-success)';

                    stopStream();
                }, 'image/jpeg', 0.85);
            });

            btnRetake.addEventListener('click', function() {
                proofInput.value = '';
                inputNativeCamera.value = '';
                inputGallery.value = '';
                capturedPreview.src = '';
                previewSection.classList.add('d-none');
                cameraContainer.classList.remove('d-none');
                cameraFallback.classList.add('d-none');
                photoStatus.textContent = 'Belum ada foto diambil.';
                photoStatus.style.color = '';
                btnStartCamera.classList.remove('d-none');
                captureControls.classList.add('d-none');
            });

            window.addEventListener('beforeunload', stopStream);
        }
    });
</script>
@endsection
