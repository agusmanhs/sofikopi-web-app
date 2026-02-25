<?php

namespace App\Services;

use App\Helpers\LocationHelper;
use App\Models\Absensi;
use App\Models\Pegawai;
use App\Repositories\AbsensiRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AbsensiService extends BaseService
{
    protected FileUploadService $fileUploadService;

    public function __construct(
        AbsensiRepository $repository,
        FileUploadService $fileUploadService
    ) {
        parent::__construct($repository);
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Get absensi by pegawai dan tanggal
     */
    public function getByPegawaiTanggal($pegawaiId, string $tanggal)
    {
        return $this->repository->getByPegawaiTanggal($pegawaiId, $tanggal);
    }

    /**
     * Get absensi by pegawai dan bulan
     */
    public function getByPegawaiBulan($pegawaiId, $bulan, $tahun)
    {
        return $this->repository->getByPegawaiBulan($pegawaiId, $bulan, $tahun);
    }

    /**
     * Get list pegawai yang belum absen hari ini
     */
    public function getBelumAbsenHariIni($tanggal = null)
    {
        return $this->repository->getBelumAbsenHariIni($tanggal);
    }

    /**
     * Get absensi hari ini
     */
    public function getAbsensiHariIni($tanggal = null)
    {
        return $this->repository->getAbsensiHariIni($tanggal);
    }

    /**
     * Proses absen masuk
     */
    /**
     * Proses absen masuk
     */
    /**
     * Proses absen masuk
     */
    public function absenMasuk(Pegawai $pegawai, array $data)
    {
        $shiftId = $data['shift_id'] ?? null;
        if (!$shiftId) {
            throw new \Exception('Shift tidak valid.');
        }

        $shift = \App\Models\Shift::find($shiftId);
        if (!$shift || !$shift->is_aktif) {
            throw new \Exception('Shift tidak ditemukan atau tidak aktif.');
        }

        // Check apakah ada sesi yang masih "MENGGANTUNG" (sudah masuk tapi belum pulang) secara global
        $activeSession = Absensi::where('pegawai_id', $pegawai->id)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_pulang')
            ->first();

        // Check apakah masih ada sesi aktif di shift mana pun
        // REVISI USER: Perbolehkan masuk shift baru meskipun shift sebelumnya belum pulang (irisan)
        /*
        if ($activeSession) {
             $shiftName = $activeSession->shift ? $activeSession->shift->nama : 'sebelumnya';
             throw new \Exception("Anda masih memiliki sesi aktif di {$shiftName}. Silakan absen pulang terlebih dahulu.");
        }
        */

        // Check apakah sudah absen masuk untuk shift ini (untuk mencegah double entry di shift yang sama)
        // REVISI: Hanya cek sesi hari ini. Sesi kemarin yang lupa checkout dianggap hangus/alpha.
        $existing = Absensi::where('pegawai_id', $pegawai->id)
            ->where('shift_id', $shiftId)
            ->whereDate('tanggal', today()) // Pastikan hanya cek tanggal hari ini
            ->first();

        if ($existing) {
             throw new \Exception('Anda sudah absen masuk pada shift ini hari ini.');
        }

        // CEK HARI LIBUR
        // REVISI: Cek apakah hari ini libur untuk divisi/shift ini
        $hariLibur = \App\Models\HariLibur::whereDate('tanggal', today())->first();
        if ($hariLibur) {
            $isTargetLibur = $hariLibur->is_all_divisi || 
                             (is_array($hariLibur->divisi_ids) && in_array($pegawai->divisi_id, $hariLibur->divisi_ids));
            
            if ($isTargetLibur && $shift->ikut_libur) {
                throw new \Exception("Hari ini libur: {$hariLibur->nama}. Shift '{$shift->nama}' tidak diizinkan absen.");
            }
        }

        // CEK HARI KERJA (Sesuai kolom hari_kerja)
        $namaHariIni = now()->format('l'); // Monday, Tuesday, etc.
        if ($shift->hari_kerja && !in_array($namaHariIni, $shift->hari_kerja)) {
            throw new \Exception("Hari ini ({$namaHariIni}) bukan jadwal hari kerja untuk shift '{$shift->nama}'.");
        }

        // VALIDASI WAKTU MASUK
        $now = now();
        $jamMasuk = Carbon::parse($shift->jam_masuk->format('H:i:s'));
        $jamPulang = Carbon::parse($shift->jam_pulang->format('H:i:s'));

        // Handle Cross-Day Shift (Misal: 20:00 - 04:00)
        $isCrossDay = $shift->is_cross_day;
        
        // 1. Tidak boleh absen jika sudah lewat jam pulang (Waktu Shift Habis)
        if ($now->gt($jamPulang) && !$isCrossDay) {
             throw new \Exception('Waktu shift ini sudah berakhir.');
        }

        // 2. Tidak boleh absen terlalu awal (Misal: 2 Jam sebelum shift mulai)
        $batasAwal = $jamMasuk->copy()->subHours(2);
        
        if ($now->lt($batasAwal)) {
             throw new \Exception('Absen masuk belum dibuka untuk shift ini (Dibuka: ' . $batasAwal->format('H:i') . ').');
        } 

        // Validasi lokasi
        $validasiLokasi = $this->validateLocation(
            $pegawai,
            $data['latitude'],
            $data['longitude']
        );

        if (!$validasiLokasi['valid']) {
            throw new \Exception($validasiLokasi['message']);
        }

        // Determine status (Hadir/Terlambat) based on selected shift
        $status = $this->determineStatusByShift($pegawai, $shift, now());

        // Upload foto
        $fotoPath = null;
        if (isset($data['foto'])) {
            $media = $this->fileUploadService->upload($data['foto'], 'absensi/masuk', 'public', [
                'width' => 640,
                'height' => 480,
                'quality' => 70,
            ]);
            $fotoPath = $media->path;
        }

        // Create absensi
        $absensiData = [
            'pegawai_id' => $pegawai->id,
            'shift_id' => $shiftId,
            'tanggal' => today(),
            'jam_masuk' => now()->format('H:i:s'),
            'foto_masuk' => $fotoPath,
            'latitude_masuk' => $data['latitude'],
            'longitude_masuk' => $data['longitude'],
            'lokasi_masuk' => $validasiLokasi['kantor_nama'] ?? null,
            'device_masuk' => $data['device'] ?? request()->header('User-Agent'),
            'status' => $status,
        ];

        return $this->create($absensiData);
    }

    /**
     * Proses absen pulang
     */
    public function absenPulang(Pegawai $pegawai, array $data)
    {
        // Check apakah sudah absen masuk hari ini
        // PERBAIKAN: Harus mencari absensi yang OPEN (masuk tapi belum pulang)
        
        $shiftId = $data['shift_id'] ?? null;
        
        $query = Absensi::where('pegawai_id', $pegawai->id)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_pulang');

        if ($shiftId) {
            $query->where('shift_id', $shiftId);
        }

        // Cari sesi terbaru yang belum pulang
        $existing = $query->orderBy('tanggal', 'desc')->orderBy('jam_masuk', 'desc')->first();

        if (!$existing) {
             throw new \Exception('Tidak ditemukan data absen masuk yang aktif (belum pulang)' . ($shiftId ? ' untuk shift ini.' : '.'));
        }

        // VALIDASI STRICT SAME DAY
        // Jika tanggal absensi TIDAK sama dengan hari ini, tolak.
        // KECUALI jika shift tersebut adalah CROSS DAY
        if (!$existing->tanggal->isToday()) {
            $shift = $existing->shift;
            if (!$shift || !$shift->is_cross_day) {
                throw new \Exception("Maaf, Anda tidak dapat melakukan absen pulang karena sudah berganti hari. Sesi tanggal " . $existing->tanggal->format('d-m-Y') . " dianggap tidak lengkap (Alpha).");
            }
            
            // Untuk Cross Day, maksimal pulang adalah jam yang ditentukan (misal jam 01:00 pagi besoknya)
            // Jadi jika ini hari Senin, dan dia masuk hari Minggu (Cross Day), ini valid.
        }

        // VALIDASI WAKTU PULANG
        $shift = $existing->shift;
        if ($shift) {
            $now = now();
            $jamPulang = Carbon::parse($shift->jam_pulang->format('H:i:s'));
            
            // VALIDASI BATAS MAKSIMAL: 2 jam setelah jam pulang shift
            $batasMaksimalPulang = $jamPulang->copy()->addHours(2);
            
            if ($now->gt($batasMaksimalPulang)) {
                throw new \Exception("Waktu absen pulang sudah melewati batas maksimal. Batas pulang: " . $batasMaksimalPulang->format('H:i'));
            }

            // Jika pulang lebih awal, keterangan wajib ada
            if ($now->lt($jamPulang)) {
                if (empty($data['keterangan'])) {
                    throw new \Exception("Anda pulang lebih awal (Jadwal: " . $jamPulang->format('H:i') . "). Harap berikan alasan pulang.");
                }
            }
        }

        // Validasi lokasi
        $validasiLokasi = $this->validateLocation(
            $pegawai,
            $data['latitude'],
            $data['longitude']
        );

        if (!$validasiLokasi['valid']) {
            throw new \Exception($validasiLokasi['message']);
        }

        // Upload foto
        $fotoPath = null;
        if (isset($data['foto'])) {
            $media = $this->fileUploadService->upload($data['foto'], 'absensi/pulang', 'public', [
                'width' => 640,
                'height' => 480,
                'quality' => 70,
            ]);
            $fotoPath = $media->path;
        }

        // Update absensi
        $existing->update([
            'jam_pulang' => now()->format('H:i:s'),
            'foto_pulang' => $fotoPath,
            'latitude_pulang' => $data['latitude'],
            'longitude_pulang' => $data['longitude'],
            'lokasi_pulang' => $validasiLokasi['kantor_nama'] ?? null,
            'device_pulang' => $data['device'] ?? request()->header('User-Agent'),
            'keterangan' => $data['keterangan'] ?? $existing->keterangan,
        ]);

        return $existing->fresh();
    }

    /**
     * Validasi lokasi pegawai
     */
    public function validateLocation(Pegawai $pegawai, $latitude, $longitude): array
    {
        // Get lokasi yang diizinkan untuk pegawai
        $lokasiDiizinkan = $pegawai->lokasiAbsenAktif()->get();

        // Jika tidak ada lokasi yang di-assign, gunakan kantor utama
        if ($lokasiDiizinkan->isEmpty() && $pegawai->kantor) {
            $lokasiDiizinkan = collect([$pegawai->kantor]);
        }

        if ($lokasiDiizinkan->isEmpty()) {
            return [
                'valid' => false,
                'message' => 'Tidak ada lokasi absensi yang ditentukan untuk Anda.',
            ];
        }

        // Check apakah dalam radius salah satu lokasi
        $locations = $lokasiDiizinkan->map(fn($k) => [
            'id' => $k->id,
            'nama' => $k->nama,
            'latitude' => $k->latitude,
            'longitude' => $k->longitude,
            'radius_meter' => $k->radius_meter,
        ])->toArray();

        $nearest = LocationHelper::getNearestLocation($latitude, $longitude, $locations);

        if (!$nearest) {
            return [
                'valid' => false,
                'message' => 'Gagal memvalidasi lokasi.',
            ];
        }

        if (!$nearest['is_within_radius']) {
            $distance = LocationHelper::formatDistance($nearest['distance']);
            return [
                'valid' => false,
                'message' => "Anda berada di luar radius absensi. Jarak ke {$nearest['location']['nama']}: {$distance} (Radius: {$nearest['location']['radius_meter']}m)",
                'distance' => $nearest['distance'],
                'kantor_nama' => $nearest['location']['nama'],
            ];
        }

        return [
            'valid' => true,
            'message' => 'Lokasi valid.',
            'distance' => $nearest['distance'],
            'kantor_id' => $nearest['location']['id'],
            'kantor_nama' => $nearest['location']['nama'],
        ];
    }

    /**
     * Determine status kehadiran (Hadir/Terlambat)
     */
    protected function determineStatus(Pegawai $pegawai, Carbon $waktuAbsen): string
    {
        $shift = $pegawai->shift;
        $divisi = $pegawai->divisi;

        // Jika tidak punya shift, atau shift tidak punya jam masuk, anggap Hadir
        if (!$shift || !$shift->jam_masuk) {
            return 'Tepat Waktu';
        }

        // Ambil jam masuk dari shift
        $jamMasuk = Carbon::parse($shift->jam_masuk->format('H:i:s'));
        
        // Toleransi tetap dari divisi
        $toleransi = $divisi->toleransi_terlambat ?? 0;
        $jamMasukDenganToleransi = $jamMasuk->copy()->addMinutes($toleransi);

        $waktuAbsenTime = Carbon::parse($waktuAbsen->format('H:i:s'));

        if ($waktuAbsenTime->gt($jamMasukDenganToleransi)) {
            return 'Terlambat';
        }

        return 'Tepat Waktu';
    }
    /**
     * Determine status kehadiran (Hadir/Terlambat) based on specific shift
     */
    protected function determineStatusByShift(Pegawai $pegawai, $shift, Carbon $waktuAbsen): string
    {
        // Jika shift tidak punya jam masuk, anggap Hadir
        if (!$shift || !$shift->jam_masuk) {
            return 'Tepat Waktu';
        }

        // Ambil jam masuk dari shift
        $jamMasuk = Carbon::parse($shift->jam_masuk->format('H:i:s'));
        
        // Toleransi tetap dari divisi pegawai (atau bisa di-override per shift nantinya)
        $divisi = $pegawai->divisi;
        $toleransi = $divisi->toleransi_terlambat ?? 0;
        
        $jamMasukDenganToleransi = $jamMasuk->copy()->addMinutes($toleransi);
        $waktuAbsenTime = Carbon::parse($waktuAbsen->format('H:i:s'));

        if ($waktuAbsenTime->gt($jamMasukDenganToleransi)) {
            return 'Terlambat';
        }

        return 'Tepat Waktu';
    }
    /**
     * Get rekap absensi per divisi
     */
    public function getRekapPerDivisi(string $startDate = null, string $endDate = null)
    {
        $startDate = $startDate ?? today()->toDateString();
        $endDate = $endDate ?? $startDate;

        $query = DB::table('absensis')
            ->join('pegawais', 'absensis.pegawai_id', '=', 'pegawais.id')
            ->join('divisis', 'pegawais.divisi_id', '=', 'divisis.id')
            ->leftJoin('shifts', 'absensis.shift_id', '=', 'shifts.id')
            ->whereBetween('absensis.tanggal', [$startDate, $endDate])
            ->select(
                'divisis.id',
                'divisis.nama as divisi',
                DB::raw('COUNT(absensis.id) as total_sesi'),
                DB::raw("COUNT(DISTINCT CASE WHEN absensis.status = 'Tepat Waktu' AND absensis.jam_pulang IS NOT NULL THEN absensis.pegawai_id END) as hadir"),
                DB::raw("COUNT(DISTINCT CASE WHEN absensis.status = 'Terlambat' AND absensis.jam_pulang IS NOT NULL THEN absensis.pegawai_id END) as terlambat"),
                DB::raw("COUNT(DISTINCT CASE WHEN absensis.status IN ('Izin', 'Cuti', 'Sakit') THEN absensis.pegawai_id END) as izin"),
                DB::raw("SUM(
                    CASE 
                        WHEN absensis.jam_masuk IS NOT NULL AND absensis.jam_pulang IS NOT NULL THEN
                            CASE 
                                WHEN absensis.jam_pulang < absensis.jam_masuk THEN
                                    TIMESTAMPDIFF(MINUTE, absensis.jam_masuk, DATE_ADD(absensis.jam_pulang, INTERVAL 1 DAY))
                                ELSE
                                    TIMESTAMPDIFF(MINUTE, absensis.jam_masuk, absensis.jam_pulang)
                            END
                        ELSE 0 
                    END
                ) as total_menit")
            )
            ->groupBy('divisis.id', 'divisis.nama');

        $data = $query->get();

        return $data->map(function($item) {
            $jam = floor($item->total_menit / 60);
            $menit = $item->total_menit % 60;
            $item->total_jam_format = "{$jam} Jam" . ($menit > 0 ? " {$menit} Menit" : "");
            return $item;
        });
    }

    /**
     * Get statistik absensi
     */
    public function getStatistik(string $tanggal = null)
    {
        $tanggal = $tanggal ?? today()->toDateString();
        $totalPegawai = Pegawai::aktif()->count();

        $absensis = Absensi::whereDate('tanggal', $tanggal)->get();

        return [
            'total_pegawai' => $totalPegawai,
            'sudah_absen' => $absensis->whereNotNull('jam_masuk')->unique('pegawai_id')->count(),
            'belum_absen' => $totalPegawai - $absensis->whereNotNull('jam_masuk')->unique('pegawai_id')->count(),
            'hadir' => $absensis->where('status', 'Tepat Waktu')->whereNotNull('jam_pulang')->unique('pegawai_id')->count(),
            'terlambat' => $absensis->where('status', 'Terlambat')->whereNotNull('jam_pulang')->unique('pegawai_id')->count(),
            'izin' => $absensis->whereIn('status', ['Izin', 'Cuti', 'Sakit'])->count(),
        ];
    }
    /**
     * Get statistik absensi untuk satu pegawai dalam satu bulan
     */
    public function getStatistikPegawai($pegawaiId, $bulan, $tahun)
    {
        $pegawai = Pegawai::with('shift')->find($pegawaiId);
        $shift = $pegawai ? $pegawai->shift : null;

        $absensis = $this->repository->getByPegawaiBulan($pegawaiId, $bulan, $tahun);
        
        // Hitung total hari kerja efektif (Termasuk Hari Ini)
        $detailHariKerja = $this->getDetailHariKerjaForEmployee($pegawai, $bulan, $tahun);
        $totalHariKerja = $detailHariKerja['total'];

        // Hari Aktif (Hadir/Telat/Izin yang Sah)
        $daysActive = $absensis->filter(function($item) {
            $allIzinTypes = ['Izin', 'Sakit', 'Cuti', 'Izin Pribadi', 'Cuti Tahunan', 'Cuti Melahirkan', 'Cuti Menikah', 'Cuti Duka', 'Dinas Luar Kota'];
            if (in_array($item->status, $allIzinTypes)) return true;
            
            // Hadir/Telat harus ada jam_pulang (Tuntas) ATAU masih dalam toleransi jam kerja (belum 2 jam dari batas pulang)
            if (!is_null($item->jam_pulang)) return true;
            
            if (!is_null($item->jam_masuk) && $item->shift) {
                // Tentukan batas kepulangan (2 jam setelah shift selesai)
                $batasPulang = \Carbon\Carbon::parse($item->tanggal->format('Y-m-d') . ' ' . $item->shift->jam_pulang->format('H:i:s'));
                if ($item->shift->is_cross_day) {
                    $batasPulang->addDay();
                }
                $batasPulang->addHours(2);
                
                // Jika SEKARANG belum melewati batas kepulangan, anggap sebagai Hadir sementara (Active)
                if (now()->lte($batasPulang)) return true;
                
                // Jika sudah melewati batas dan belum absen pulang, berarti tidak dihitung hadir (Alpha)
            }
            return false;
        })->unique(fn($i) => $i->tanggal->format('Y-m-d'))->count();

        // Hitung Alpha (Hari Kerja yang tidak ada di rekaman absensi MASUK dan PULANG yang lengkap)
        $datesWithPresence = $absensis->filter(function($item) {
            $allIzinTypes = ['Izin', 'Sakit', 'Cuti', 'Izin Pribadi', 'Cuti Tahunan', 'Cuti Melahirkan', 'Cuti Menikah', 'Cuti Duka', 'Dinas Luar Kota'];
            // 1. Jika statusnya izin/cuti resmi yang sah, dianggap hadir (bukan alpha)
            if (in_array($item->status, $allIzinTypes)) return true;
            
            // 2. Jika sudah ada absen pulang, aman.
            if (!is_null($item->jam_pulang)) return true;
            
            // 3. Jika baru absen masuk SAJA form hari sebelumnya atau hari ini, cek toleransi kepulangan
            if (!is_null($item->jam_masuk) && $item->shift) {
                $batasPulang = \Carbon\Carbon::parse($item->tanggal->format('Y-m-d') . ' ' . $item->shift->jam_pulang->format('H:i:s'));
                if ($item->shift->is_cross_day) {
                    $batasPulang->addDay();
                }
                $batasPulang->addHours(2); // Toleransi 2 jam
                
                // Jika sekarang belum melewati batas pulang, JANGAN hitung Alpha dulu (Dianggap Hadir/Pending)
                if (now()->lte($batasPulang)) return true;
            }
            
            // Jika sudah lewat toleransi pulang dan dia belum klik absen pulang, GUGUR (Alpha)
            return false;
        })->pluck('tanggal')->map(fn($d) => $d->format('Y-m-d'))->toArray();

        // Cari tanggal yang harusnya kerja tapi tidak ada di datesWithPresence
        $alphaDates = [];
        foreach ($detailHariKerja['working_dates'] as $workingDate) {
            if (!in_array($workingDate, $datesWithPresence)) {
                $alphaDates[] = $workingDate;
            }
        }
        $alphaCount = count($alphaDates);
        
        $tepatWaktu = $absensis->filter(function($item) {
            if (!in_array($item->status, ['Tepat Waktu', 'Hadir'])) return false;
            if (!is_null($item->jam_pulang)) return true; // Udah pulang ya dihitung hadir
            
            // Cek toleransi jika gantung
            if (!is_null($item->jam_masuk) && $item->shift) {
                $batasPulang = \Carbon\Carbon::parse($item->tanggal->format('Y-m-d') . ' ' . $item->shift->jam_pulang->format('H:i:s'));
                if ($item->shift->is_cross_day) $batasPulang->addDay();
                $batasPulang->addHours(2);
                if (now()->lte($batasPulang)) return true; // Masih aktif
            }
            return false;
        })->unique(fn($i) => $i->tanggal->format('Y-m-d'))->count();

        $terlambat = $absensis->filter(function($item) {
            if ($item->status !== 'Terlambat') return false;
            if (!is_null($item->jam_pulang)) return true;
            
            // Cek toleransi
            if (!is_null($item->jam_masuk) && $item->shift) {
                $batasPulang = \Carbon\Carbon::parse($item->tanggal->format('Y-m-d') . ' ' . $item->shift->jam_pulang->format('H:i:s'));
                if ($item->shift->is_cross_day) $batasPulang->addDay();
                $batasPulang->addHours(2);
                if (now()->lte($batasPulang)) return true;
            }
            return false;
        })->unique(fn($i) => $i->tanggal->format('Y-m-d'))->count();
        
        $dinasWork = $absensis->filter(fn($item) => in_array($item->status, ['Dinas Luar Kota', 'Tugas']))->unique(fn($i) => $i->tanggal->format('Y-m-d'))->count();
        
        // Sesuai Request: Record yang ada Jam Masuk (meskipun statusnya Izin/Info) harus masuk hitungan Hadir
        $othersWithJam = $absensis->filter(function($item) {
            $sudahDihitung = in_array($item->status, ['Tepat Waktu', 'Hadir', 'Terlambat', 'Dinas Luar Kota', 'Tugas']);
            if ($sudahDihitung || is_null($item->jam_masuk)) return false;
            
            // Cek syarat lengkap (ada pulang atau masih toleransi)
            if (!is_null($item->jam_pulang)) return true;
            if ($item->shift) {
                $batasPulang = \Carbon\Carbon::parse($item->tanggal->format('Y-m-d') . ' ' . $item->shift->jam_pulang->format('H:i:s'));
                if ($item->shift->is_cross_day) $batasPulang->addDay();
                $batasPulang->addHours(2);
                if (now()->lte($batasPulang)) return true;
            }
            return false;
        })->unique(fn($i) => $i->tanggal->format('Y-m-d'))->count();

        return [
            'hadir' => $tepatWaktu + $terlambat + $dinasWork + $othersWithJam, // Total Hadir Fisik / Berjam-jam
            'tepat_waktu' => $tepatWaktu,
            'terlambat' => $terlambat,
            'izin' => $absensis->whereIn('status', ['Izin', 'Sakit', 'Cuti', 'Izin Pribadi', 'Cuti Tahunan', 'Cuti Melahirkan', 'Cuti Menikah', 'Cuti Duka', 'Dinas Luar Kota'])->unique(fn($i) => $i->tanggal->format('Y-m-d'))->count(),
            'cepat_pulang' => $absensis->filter(function($item) {
                if (!$item->jam_pulang || !$item->shift) return false;
                $jam_pulang = \Carbon\Carbon::parse($item->jam_pulang->format('H:i:s'));
                $shift_pulang = \Carbon\Carbon::parse($item->shift->jam_pulang->format('H:i:s'));
                return $jam_pulang->lt($shift_pulang);
            })->count(),
            'alfa' => $alphaCount,
            'alpha_dates' => $alphaDates,
            'total_hari_kerja' => $totalHariKerja,
        ];
    }

    /**
     * Helper untuk hitung target hari kerja sampai kemarin saja
     */
    private function getHariKerjaEfektifSampaiKemarin($bulan, $tahun, $excludeSundays)
    {
        $start = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $end = \Carbon\Carbon::today()->subDay();

        // Jika kita sedang melihat bulan lalu, gunakan akhir bulan tersebut
        $bulanLalu = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        if ($end->gt($bulanLalu)) {
            $end = $bulanLalu;
        }

        // Kalau tanggal 1 bulan ini saja belum sampai kemarin (Awal bulan)
        if ($end->lt($start)) return 0;

        return $this->getHariKerjaEfektifDenganEnd($start, $end, $excludeSundays);
    }

    private function getHariKerjaEfektifDenganEnd($start, $end, $excludeSundays)
    {
        $holidays = \App\Models\HariLibur::whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])->pluck('tanggal')->map(fn($d) => $d->format('Y-m-d'))->toArray();
        $count = 0;
        $current = $start->copy();
        while ($current <= $end) {
            $isHoliday = in_array($current->format('Y-m-d'), $holidays);
            $isSunday = $current->isSunday();
            if (!$isHoliday && !($excludeSundays && $isSunday)) {
                $count++;
            }
            $current->addDay();
        }
        return $count;
    }

    /**
     * Hitung hari kerja efektif (tidak termasuk sabtu, minggu, dan hari libur)
     */
    public function getHariKerjaEfektif($bulan, $tahun, $excludeSundays = false)
    {
        return $this->getDetailHariKerja($bulan, $tahun, $excludeSundays)['total'];
    }

    public function getDetailHariKerja($bulan, $tahun, $excludeSundays = false)
    {
        $start = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $end = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        
        if ($tahun == now()->year && $bulan == now()->month) {
            $end = now();
        }

        $holidays = \App\Models\HariLibur::whereBetween('tanggal', [
                $start->format('Y-m-d'), 
                $end->format('Y-m-d')
            ])->get();

        $details = [
            'total' => 0,
            'holidays' => $holidays,
            'period_end' => $end,
            'working_dates' => [],
            'sundays' => []
        ];

        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            
            // Cek holiday (Global)
            $isHoliday = $holidays->where('tanggal', $current->startOfDay())->isNotEmpty();
            $isSunday = $current->isSunday();

            if ($isSunday) {
                $details['sundays'][] = $dateStr;
            }

            if (!$isHoliday && !($excludeSundays && $isSunday)) {
                $details['total']++;
                $details['working_dates'][] = $dateStr;
            }
            $current->addDay();
        }

        return $details;
    }

    /**
     * Hitung detail hari kerja spesifik untuk satu orang pegawai
     */
    public function getDetailHariKerjaForEmployee($pegawai, $bulan, $tahun)
    {
        $start = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $end = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        
        if ($tahun == now()->year && $bulan == now()->month) {
            $end = now();
        }

        $shift = $pegawai ? $pegawai->shift : null;
        $hariKerjaSesuaiShift = $shift ? $shift->hari_kerja : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $holidays = \App\Models\HariLibur::whereBetween('tanggal', [
                $start->format('Y-m-d'), 
                $end->format('Y-m-d')
            ])->get();

        $details = [
            'total' => 0,
            'working_dates' => []
        ];

        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            
            // 1. Cek apakah ini hari kerja di shift-nya
            $namaHari = $current->format('l');
            $isJadwalKerja = in_array($namaHari, $hariKerjaSesuaiShift);

            if ($isJadwalKerja) {
                // 2. Cek apakah ini hari libur untuk dia
                $holidayToday = $holidays->where('tanggal', $current->startOfDay())->first();
                $isHolidayForHim = false;

                if ($holidayToday && $shift && $shift->ikut_libur) {
                    $isHolidayForHim = $holidayToday->is_all_divisi || 
                                       (is_array($holidayToday->divisi_ids) && in_array($pegawai->divisi_id, $holidayToday->divisi_ids));
                }

                if (!$isHolidayForHim) {
                    $details['total']++;
                    $details['working_dates'][] = $dateStr;
                }
            }
            $current->addDay();
        }

        return $details;
    }
}
