@extends('layouts/layoutMaster')

@section('title', 'Detail Pegawai')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="fw-bold mb-0">
            <span class="text-muted fw-light">Data Master / Pegawai /</span> Detail Profil
         </h4>
         <div class="d-flex gap-2">
            <a href="{{ route('pegawai.index') }}" class="btn btn-outline-secondary">
               <i class="ri-arrow-left-line me-1"></i>Kembali
            </a>
            <a href="{{ route('pegawai.edit', $data->id) }}" class="btn btn-primary">
               <i class="ri-pencil-line me-1"></i>Edit Data
            </a>
         </div>
      </div>

      <div class="row">
         <!-- Sisi Kiri: Profil & Kontak -->
         <div class="col-md-5 col-lg-4">
            <div class="card mb-4 mt-3">
               <div class="card-body">
                  <div class="text-center mb-4">
                     <div class="avatar avatar-xl mx-auto mb-3" style="width: 120px; height: 120px;">
                        <img src="{{ $data->foto_url }}" alt="{{ $data->nama_lengkap }}" class="rounded-circle shadow-sm"
                           style="object-fit: cover; border: 3px solid #eee;">
                     </div>
                     <h4 class="mb-1 fw-bold">{{ $data->nama_lengkap }}</h4>
                     <p class="text-muted small mb-0">{{ $data->jabatan ?? 'Pegawai' }}</p>
                     @if ($data->status_aktif)
                        <span class="badge bg-label-success mt-2">Aktif</span>
                     @else
                        <span class="badge bg-label-secondary mt-2">Non-Aktif</span>
                     @endif
                  </div>

                  <div class="pt-2 border-top">
                     <h6 class="text-uppercase small fw-bold text-muted mb-3">Informasi Kontak</h6>
                     <div class="mb-3">
                        <small class="text-muted d-block">NIP</small>
                        <span class="fw-semibold">{{ $data->nip ?? '-' }}</span>
                     </div>
                     <div class="mb-3">
                        <small class="text-muted d-block">Email</small>
                        <span class="fw-semibold">{{ $data->user->email ?? '-' }}</span>
                     </div>
                     <div class="mb-3">
                        <small class="text-muted d-block">No. Telepon</small>
                        <span class="fw-semibold">{{ $data->no_telp ?? '-' }}</span>
                     </div>
                     <div class="mb-0">
                        <small class="text-muted d-block">Alamat</small>
                        <span class="fw-semibold small">{{ $data->alamat ?? '-' }}</span>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <!-- Sisi Kanan: Penempatan & Statistik -->
         <div class="col-md-7 col-lg-8">
            <!-- Penempatan -->
            <div class="card mb-4 mt-3 border-0 shadow-sm">
               <div
                  class="card-header d-flex justify-content-between align-items-center py-3 bg-transparent border-bottom">
                  <h6 class="mb-0 fw-bold"><i class="ri-briefcase-line me-2 text-primary"></i>Penempatan & Jadwal</h6>
                  <span class="badge bg-label-info">
                     Terdaftar Sejak: {{ $data->tgl_masuk ? $data->tgl_masuk->format('d/m/Y') : '-' }}
                  </span>
               </div>
               <div class="card-body pt-4">
                  <div class="row g-3 mb-4">
                     <div class="col-sm-6 col-lg-4">
                        <div class="p-3 bg-label-primary rounded border border-primary border-opacity-10 h-100">
                           <small class="text-muted d-block mb-1 text-uppercase fw-bold" style="font-size: 10px;">Kantor
                              Utama</small>
                           <h6 class="mb-0 fw-bold text-primary">{{ $data->kantor->nama ?? '-' }}</h6>
                        </div>
                     </div>
                     <div class="col-sm-6 col-lg-4">
                        <div class="p-3 bg-label-success rounded border border-success border-opacity-10 h-100">
                           <small class="text-muted d-block mb-1 text-uppercase fw-bold"
                              style="font-size: 10px;">Divisi</small>
                           <h6 class="mb-0 fw-bold text-success">{{ $data->divisi->nama ?? '-' }}</h6>
                        </div>
                     </div>
                     <div class="col-sm-12 col-lg-4">
                        <div class="p-3 bg-label-info rounded border border-info border-opacity-10 h-100">
                           <small class="text-muted d-block mb-1 text-uppercase fw-bold" style="font-size: 10px;">Tipe
                              Shift</small>
                           <h6 class="mb-0 fw-bold text-info">Multi-Shift (Fleksibel)</h6>
                        </div>
                     </div>
                  </div>

                  <div class="mt-2">
                     <label class="form-label fw-bold mb-3 d-flex align-items-center">
                        <i class="ri-time-line me-2 text-warning"></i> Daftar Shift di Divisi {{ $data->divisi->nama }}
                     </label>
                     <div class="row g-3">
                        @forelse ($data->divisi->shifts->where('is_aktif', true) as $s)
                           <div class="col-md-6 col-xl-4">
                              <div class="card h-100 shadow-none border bg-light rounded-3">
                                 <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-1">
                                       <div class="badge bg-white text-dark shadow-sm border me-2" style="padding: 5px;">
                                          <i class="ri-sun-line text-warning"></i>
                                       </div>
                                       <div class="fw-bold small">{{ $s->nama }}</div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                       <span class="text-muted small">Waktu Kerja:</span>
                                       <span class="badge bg-dark rounded-pill">{{ $s->jam_masuk->format('H:i') }} -
                                          {{ $s->jam_pulang->format('H:i') }}</span>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        @empty
                           <div class="col-12">
                              <div class="alert alert-warning py-2 mb-0 small">Belum ada shift diatur untuk divisi ini
                              </div>
                           </div>
                        @endforelse
                     </div>
                  </div>
               </div>
            </div>

            <!-- Lokasi Absen -->
            <div class="card mb-4 border-0 shadow-sm">
               <div class="card-header py-3 bg-transparent border-bottom">
                  <h6 class="mb-0 fw-bold"><i class="ri-map-pin-line me-2 text-danger"></i>Akses Lokasi Absensi</h6>
               </div>
               <div class="table-responsive">
                  <table class="table table-hover mb-0">
                     <thead class="table-light">
                        <tr>
                           <th style="font-size: 11px;">NAMA AREA</th>
                           <th class="text-center" style="font-size: 11px;">KOORDINAT</th>
                           <th class="text-center" style="font-size: 11px;">RADIUS</th>
                        </tr>
                     </thead>
                     <tbody class="table-border-bottom-0">
                        @forelse($data->lokasiAbsen as $lokasi)
                           <tr>
                              <td>
                                 <div class="fw-bold text-dark">{{ $lokasi->nama }}</div>
                              </td>
                              <td class="text-center">
                                 <code class="small">{{ $lokasi->latitude }}, {{ $lokasi->longitude }}</code>
                              </td>
                              <td class="text-center">
                                 <span class="badge bg-label-danger rounded-pill">{{ $lokasi->radius_meter }}m</span>
                              </td>
                           </tr>
                        @empty
                           <tr>
                              <td colspan="3" class="text-center py-4 text-muted small">
                                 <i class="ri-information-line me-1"></i> Menggunakan lokasi default kantor utama
                              </td>
                           </tr>
                        @endforelse
                     </tbody>
                  </table>
               </div>
            </div>

            <!-- Statistik -->
            <div class="card border-0 shadow-sm overflow-hidden"
               style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
               <div class="card-body text-white">
                  @php
                     $absensi = $data
                         ->absensis()
                         ->whereMonth('tanggal', date('m'))
                         ->whereYear('tanggal', date('Y'))
                         ->get();
                     $hadir = $absensi
                         ->filter(function ($i) {
                             if ($i->status === 'Terlambat') {
                                 return false;
                             }
                             $isHadir = in_array($i->status, ['Tepat Waktu', 'Hadir', 'Dinas Luar Kota', 'Tugas']);
                             $hasClockIn = !is_null($i->jam_masuk);
                             return ($isHadir || $hasClockIn) && (!is_null($i->jam_pulang) || $i->tanggal->isToday());
                         })
                         ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                         ->count();
                     $terlambat = $absensi
                         ->filter(
                             fn($i) => $i->status === 'Terlambat' &&
                                 (!is_null($i->jam_pulang) || $i->tanggal->isToday()),
                         )
                         ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                         ->count();
                     $izin = $absensi
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
                  @endphp
                  <div class="d-flex align-items-center mb-3">
                     <i class="ri-line-chart-line me-2"></i>
                     <h6 class="fw-bold mb-0 text-white">Performa Kehadiran Bulan Ini</h6>
                  </div>
                  <div class="row g-3 mt-1">
                     <div class="col-4">
                        <div style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px);"
                           class="p-3 rounded text-center shadow-sm border border-white border-opacity-20">
                           <h4 class="fw-bold text-white mb-0">{{ $hadir }}</h4>
                           <small class="text-white text-uppercase"
                              style="font-size: 10px; opacity: 0.8; letter-spacing: 0.5px;">Hadir (Hari)</small>
                        </div>
                     </div>
                     <div class="col-4">
                        <div style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px);"
                           class="p-3 rounded text-center shadow-sm border border-white border-opacity-20">
                           <h4 class="fw-bold text-white mb-0">{{ $terlambat }}</h4>
                           <small class="text-white text-uppercase"
                              style="font-size: 10px; opacity: 0.8; letter-spacing: 0.5px;">Telat (Sesi)</small>
                        </div>
                     </div>
                     <div class="col-4">
                        <div style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px);"
                           class="p-3 rounded text-center shadow-sm border border-white border-opacity-20">
                           <h4 class="fw-bold text-white mb-0">{{ $izin }}</h4>
                           <small class="text-white text-uppercase"
                              style="font-size: 10px; opacity: 0.8; letter-spacing: 0.5px;">Izin / Cuti</small>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
@endsection
