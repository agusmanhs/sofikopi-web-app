<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Izin;
use App\Services\AbsensiService;
use App\Services\InformasiService;
use App\Services\IzinService;
use App\Services\JadwalKerjaService;
use App\Services\PegawaiService;

class DashboardController extends Controller
{
    public function __construct(
        protected AbsensiService $absensiService,
        protected PegawaiService $pegawaiService,
        protected InformasiService $informasiService,
        protected JadwalKerjaService $jadwalKerjaService
    ) {}

    /**
     * Display the dashboard - different view based on role
     */
    public function index()
    {
        $user = auth()->user();

        // Mitra users land on their portal, not the internal dashboard.
        // Owner roles get the mitra dashboard; kasir roles (no dashboard
        // permission) go straight to the POS screen instead of a 403.
        if ($user->mitra_id !== null) {
            return $user->hasPermission('mitra-dashboard.index', 'read')
                ? redirect()->route('mitra-dashboard.index')
                : redirect()->route('pos.index');
        }

        $roleSlug = $user->role?->slug;

        // Admin dan Superadmin melihat dashboard admin
        if (in_array($roleSlug, ['super-admin', 'admin'])) {
            $tanggal = today()->toDateString();
            $filterRekap = request('filter_rekap', 'today');

            // Determine date range for rekap
            $startDate = $tanggal;
            $endDate = $tanggal;

            if ($filterRekap === 'yesterday') {
                $startDate = today()->subDay()->toDateString();
                $endDate = $startDate;
            } elseif ($filterRekap === 'week') {
                $startDate = today()->startOfWeek()->toDateString();
                $endDate = today()->endOfWeek()->toDateString();
            }

            $statistikAbsensi = $this->absensiService->getStatistik($tanggal);
            $rekapDivisi = $this->absensiService->getRekapPerDivisi($startDate, $endDate);

            $izinService = app(IzinService::class);
            $statistikIzin = $izinService->getStatistik();

            $recentAbsensi = Absensi::with(['pegawai', 'shift'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $recentIzin = Izin::with(['pegawai', 'jenisIzin'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            return view('pages.dashboard.dashboard', compact(
                'statistikAbsensi',
                'rekapDivisi',
                'statistikIzin',
                'recentAbsensi',
                'recentIzin',
                'tanggal',
                'filterRekap'
            ));
        }

        // User biasa (pegawai) melihat dashboard khusus user
        $pegawai = $this->pegawaiService->getByUserId($user->id);

        if (! $pegawai) {
            return view('pages.dashboard.unregistered');
        }

        $absensiHariIni = $pegawai->absensiHariIni();
        $bulan = now()->month;
        $tahun = now()->year;

        $historyAbsensi = $this->absensiService->getByPegawaiBulan($pegawai->id, $bulan, $tahun);
        $statistik = $this->absensiService->getStatistikPegawai($pegawai->id, $bulan, $tahun);
        $informasis = $this->informasiService->getLatest(5);

        // Jadwal Absen: resolusi jadwal (override/pola mingguan) pegawai untuk hari ini.
        // Jika pegawai belum punya jadwal sama sekali, dashboard tetap tampil daftar shift bebas (perilaku lama).
        $jadwalHariIni = $this->jadwalKerjaService->resolveShiftFor($pegawai, now());
        $hasSchedule = $jadwalHariIni['scheduled'];
        $scheduledShift = $jadwalHariIni['shift'];

        return view('pages.dashboard.user', compact(
            'pegawai',
            'absensiHariIni',
            'historyAbsensi',
            'statistik',
            'informasis',
            'hasSchedule',
            'scheduledShift'
        ));
    }
}
