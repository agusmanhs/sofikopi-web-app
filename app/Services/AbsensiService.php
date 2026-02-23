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
        // REVISI: Hanya tolak absen jika shift tersebut dikonfigurasi mengikuti hari libur
        $hariLibur = \App\Models\HariLibur::whereDate('tanggal', today())->first();
        if ($hariLibur && $shift->ikut_libur) {
             throw new \Exception("Hari ini libur nasional/cuti bersama: {$hariLibur->nama}. Shift '{$shift->nama}' tidak diizinkan absen.");
        }

        // VALIDASI WAKTU MASUK
        $now = now();
        $jamMasuk = Carbon::parse($shift->jam_masuk->format('H:i:s'));
        $jamPulang = Carbon::parse($shift->jam_pulang->format('H:i:s'));

        // Handle Cross-Day Shift (Misal: 20:00 - 04:00)
        // REVISI: Karena aturan "Strict Same Day", kita abaikan cross-day logic untuk validasi tanggal.
        // Tapi kita tetap butuh tahu range valid jam masuk.
        
        $isCrossDay = $jamPulang->lt($jamMasuk);
        // if ($isCrossDay) {
        //      $jamPulang->addDay();
        // }
        // DISABLE CROSS DAY LOGIC sesuai permintaan user (hanya bisa absen di hari itu)

        // 1. Tidak boleh absen jika sudah lewat jam pulang (Waktu Shift Habis)
        // Kita bandingkan jika sekarang sudah jauh melewati jam pulang
        if ($now->gt($jamPulang)) {
             throw new \Exception('Waktu shift ini sudah berakhir.');
        }

        // 2. Tidak boleh absen terlalu awal (Misal: 2 Jam sebelum shift mulai)
        // Jika cross-day (Masuk 20:00), maka batas awal 18:00.
        // Jika sekarang jam 07:00 pagi, jelas belum bisa.
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
        if (!$existing->tanggal->isToday()) {
             throw new \Exception("Maaf, Anda tidak dapat melakukan absen pulang karena sudah berganti hari. Sesi tanggal " . $existing->tanggal->format('d-m-Y') . " dianggap tidak lengkap (Alpha).");
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
        $excludeSundays = $pegawai && $pegawai->shift && $pegawai->shift->ikut_libur;

        $absensis = $this->repository->getByPegawaiBulan($pegawaiId, $bulan, $tahun);
        
        // Hitung total hari kerja efektif (Termasuk Hari Ini)
        $totalHariKerja = $this->getHariKerjaEfektif($bulan, $tahun, $excludeSundays);

        // Hari Aktif (Hadir/Telat/Izin yang Sah)
        $daysActive = $absensis->filter(function($item) {
            // Semua jenis izin/cuti/sakit yang ada di database
            $allIzinTypes = ['Izin', 'Sakit', 'Cuti', 'Izin Pribadi', 'Cuti Tahunan', 'Cuti Melahirkan', 'Cuti Menikah', 'Cuti Duka', 'Dinas Luar Kota'];
            if (in_array($item->status, $allIzinTypes)) return true;
            
            // Hadir/Telat harus ada jam_pulang (Tuntas)
            // KECUALI kalau hari ini, Masuk saja sudah cukup untuk tidak Alpha (sedang berjalan)
            return !is_null($item->jam_pulang) || $item->tanggal->isToday();
        })->unique(fn($i) => $i->tanggal->format('Y-m-d'))->count();

        $alphaCount = max(0, $totalHariKerja - $daysActive);
        
        $tepatWaktu = $absensis->filter(fn($item) => in_array($item->status, ['Tepat Waktu', 'Hadir']) && (!is_null($item->jam_pulang) || $item->tanggal->isToday()))->unique(fn($i) => $i->tanggal->format('Y-m-d'))->count();
        $terlambat = $absensis->filter(fn($item) => $item->status === 'Terlambat' && (!is_null($item->jam_pulang) || $item->tanggal->isToday()))->unique(fn($i) => $i->tanggal->format('Y-m-d'))->count();
        $dinasWork = $absensis->filter(fn($item) => in_array($item->status, ['Dinas Luar Kota', 'Tugas']))->unique(fn($i) => $i->tanggal->format('Y-m-d'))->count();
        
        // Sesuai Request: Record yang ada Jam Masuk (meskipun statusnya Izin/Info) harus masuk hitungan Hadir
        $othersWithJam = $absensis->filter(function($item) {
            $sudahDihitung = in_array($item->status, ['Tepat Waktu', 'Hadir', 'Terlambat', 'Dinas Luar Kota', 'Tugas']);
            return !$sudahDihitung && !is_null($item->jam_masuk);
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

        $holidayDates = $holidays->pluck('tanggal')->map(fn($d) => $d->format('Y-m-d'))->toArray();

        $details = [
            'total' => 0,
            'holidays' => $holidays,
            'sundays' => [],
            'period_end' => $end
        ];

        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $isHoliday = in_array($dateStr, $holidayDates);
            $isSunday = $current->isSunday();

            if ($isSunday) {
                $details['sundays'][] = $current->copy();
            }

            if (!$isHoliday && !($excludeSundays && $isSunday)) {
                $details['total']++;
            }
            $current->addDay();
        }

        return $details;
    }
}
